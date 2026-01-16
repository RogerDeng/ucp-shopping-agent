<?php
/**
 * Webhook Sender
 *
 * Sends webhooks with retry logic and failed webhook storage.
 *
 * @package WC_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_UCP_Webhook_Sender
{

    /**
     * Maximum retry attempts
     *
     * @var int
     */
    const MAX_RETRIES = 3;

    /**
     * Base retry delay in seconds
     *
     * @var int
     */
    const RETRY_DELAY = 5;

    /**
     * Request timeout in seconds
     *
     * @var int
     */
    const TIMEOUT = 30;

    /**
     * Send webhook with retry support
     *
     * @param object $webhook Webhook object with url and secret properties.
     * @param string $event   Event name.
     * @param array  $payload Event payload data.
     * @return bool True on success, false on failure.
     */
    public function send($webhook, $event, $payload)
    {
        $body = wp_json_encode(array(
            'id' => wp_generate_uuid4(),
            'event' => $event,
            'timestamp' => current_time('c'),
            'api_version' => '2026-01-11',
            'source' => array(
                'platform' => 'WooCommerce',
                'plugin' => 'ucp-shopping-agent',
                'version' => WC_UCP_VERSION,
                'site_url' => home_url(),
            ),
            'data' => $payload,
        ));

        $signature = $this->generate_signature($body, $webhook->secret);

        return $this->send_with_retry($webhook, $event, $body, $signature);
    }

    /**
     * Send webhook with retry logic
     *
     * @param object $webhook   Webhook object.
     * @param string $event     Event name.
     * @param string $body      JSON body.
     * @param string $signature HMAC signature.
     * @return bool True on success, false on failure.
     */
    private function send_with_retry($webhook, $event, $body, $signature)
    {
        $attempt = 0;
        $last_error = null;

        while ($attempt < self::MAX_RETRIES) {
            $attempt++;

            $result = $this->send_request($webhook->url, $body, $signature, $event);

            if ($result === true) {
                $this->log_delivery($webhook, $event, true, $attempt);
                return true;
            }

            $last_error = $result;

            // Log retry attempt
            $this->log_retry($webhook, $event, $attempt, $result);

            // Wait before retry with exponential backoff (except on last attempt)
            if ($attempt < self::MAX_RETRIES) {
                $delay = self::RETRY_DELAY * pow(2, $attempt - 1);
                sleep($delay);
            }
        }

        // All retries failed - store for later processing
        $this->store_failed_webhook($webhook, $event, $body, $signature, $last_error);
        $this->log_delivery($webhook, $event, false, $attempt, $last_error);
        $this->handle_failure($webhook, $last_error);

        return false;
    }

    /**
     * Send HTTP request
     *
     * @param string $url       Webhook URL.
     * @param string $body      JSON body.
     * @param string $signature HMAC signature.
     * @param string $event     Event name.
     * @return bool|string True on success, error message on failure.
     */
    private function send_request($url, $body, $signature, $event)
    {
        $headers = array(
            'Content-Type' => 'application/json',
            'X-UCP-Event' => $event,
            'X-UCP-Signature' => $signature,
            'X-UCP-Timestamp' => time(),
            'X-UCP-Delivery-ID' => wp_generate_uuid4(),
            'User-Agent' => 'WooCommerce-UCP-Agent/' . WC_UCP_VERSION,
        );

        $args = array(
            'body' => $body,
            'headers' => $headers,
            'timeout' => self::TIMEOUT,
            'redirection' => 0,
            'httpversion' => '1.1',
            'sslverify' => apply_filters('wc_ucp_webhook_ssl_verify', true),
        );

        $response = wp_safe_remote_post($url, $args);

        if (is_wp_error($response)) {
            return $response->get_error_message();
        }

        $status_code = wp_remote_retrieve_response_code($response);

        // Success: 2xx status codes
        if ($status_code >= 200 && $status_code < 300) {
            return true;
        }

        // Client error: 4xx - don't retry
        if ($status_code >= 400 && $status_code < 500) {
            return "HTTP $status_code (client error - will not retry)";
        }

        // Server error: 5xx - will retry
        return "HTTP $status_code";
    }

    /**
     * Generate HMAC signature with timestamp
     *
     * @param string $body   Request body.
     * @param string $secret Webhook secret.
     * @return string Signature in format t=timestamp,v1=hash.
     */
    private function generate_signature($body, $secret)
    {
        $timestamp = time();
        $message = $timestamp . '.' . $body;
        $hash = hash_hmac('sha256', $message, $secret);
        return sprintf('t=%d,v1=%s', $timestamp, $hash);
    }

    /**
     * Store failed webhook for later retry
     *
     * @param object $webhook   Webhook object.
     * @param string $event     Event name.
     * @param string $body      JSON body.
     * @param string $signature HMAC signature.
     * @param string $error     Error message.
     */
    private function store_failed_webhook($webhook, $event, $body, $signature, $error)
    {
        $failed_webhooks = get_option('wc_ucp_failed_webhooks', array());

        // Limit stored failed webhooks to 100
        if (count($failed_webhooks) >= 100) {
            array_shift($failed_webhooks);
        }

        $failed_webhooks[] = array(
            'webhook_id' => $webhook->id,
            'url' => $webhook->url,
            'secret' => $webhook->secret,
            'event' => $event,
            'body' => $body,
            'signature' => $signature,
            'error' => $error,
            'attempts' => self::MAX_RETRIES,
            'failed_at' => current_time('mysql', true),
        );

        update_option('wc_ucp_failed_webhooks', $failed_webhooks);
    }

    /**
     * Retry failed webhooks (called by WP-Cron)
     *
     * @return array Results of retry attempts.
     */
    public function retry_failed_webhooks()
    {
        $failed_webhooks = get_option('wc_ucp_failed_webhooks', array());

        if (empty($failed_webhooks)) {
            return array();
        }

        $results = array();
        $remaining = array();

        foreach ($failed_webhooks as $webhook_data) {
            // Skip if older than 24 hours
            $failed_at = strtotime($webhook_data['failed_at']);
            if (time() - $failed_at > 86400) {
                continue;
            }

            $result = $this->send_request(
                $webhook_data['url'],
                $webhook_data['body'],
                $webhook_data['signature'],
                $webhook_data['event']
            );

            if ($result === true) {
                $results[] = array(
                    'success' => true,
                    'event' => $webhook_data['event'],
                    'url' => $webhook_data['url'],
                );
            } else {
                // Increment attempts and keep for future retry
                $webhook_data['attempts']++;
                $webhook_data['last_retry'] = current_time('mysql', true);
                $webhook_data['last_error'] = $result;

                // Only keep if under 10 total attempts
                if ($webhook_data['attempts'] < 10) {
                    $remaining[] = $webhook_data;
                }

                $results[] = array(
                    'success' => false,
                    'event' => $webhook_data['event'],
                    'url' => $webhook_data['url'],
                    'error' => $result,
                    'attempts' => $webhook_data['attempts'],
                );
            }
        }

        update_option('wc_ucp_failed_webhooks', $remaining);

        return $results;
    }

    /**
     * Get count of failed webhooks
     *
     * @return int Number of failed webhooks pending retry.
     */
    public function get_failed_count()
    {
        $failed_webhooks = get_option('wc_ucp_failed_webhooks', array());
        return count($failed_webhooks);
    }

    /**
     * Clear all failed webhooks
     *
     * @return bool True on success.
     */
    public function clear_failed_webhooks()
    {
        return delete_option('wc_ucp_failed_webhooks');
    }

    /**
     * Log webhook delivery
     *
     * @param object      $webhook   Webhook object.
     * @param string      $event     Event name.
     * @param bool        $success   Whether delivery succeeded.
     * @param int         $attempts  Number of attempts made.
     * @param string|null $error     Error message if failed.
     */
    private function log_delivery($webhook, $event, $success, $attempts = 1, $error = null)
    {
        if (get_option('wc_ucp_log_enabled', 'no') !== 'yes') {
            return;
        }

        $log_entry = array(
            'webhook_id' => $webhook->id,
            'event' => $event,
            'url' => $webhook->url,
            'timestamp' => current_time('mysql'),
            'success' => $success,
            'attempts' => $attempts,
        );

        if (!$success && $error) {
            $log_entry['error'] = $error;
        }

        $logs = get_option('wc_ucp_webhook_logs', array());
        $logs[] = $log_entry;

        // Keep only last 100 entries
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }

        update_option('wc_ucp_webhook_logs', $logs);
    }

    /**
     * Log retry attempt
     *
     * @param object $webhook Webhook object.
     * @param string $event   Event name.
     * @param int    $attempt Current attempt number.
     * @param string $error   Error message.
     */
    private function log_retry($webhook, $event, $attempt, $error)
    {
        if (get_option('wc_ucp_log_enabled', 'no') !== 'yes') {
            return;
        }

        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $message = sprintf(
                '[UCP Webhook] Retry attempt %d/%d for %s to %s: %s',
                $attempt,
                self::MAX_RETRIES,
                $event,
                $webhook->url,
                $error
            );
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log($message);
        }
    }

    /**
     * Handle delivery failure
     *
     * @param object $webhook Webhook object.
     * @param string $error   Error message.
     */
    private function handle_failure($webhook, $error)
    {
        do_action('wc_ucp_webhook_delivery_failed', $webhook, $error);
    }

    /**
     * Verify incoming webhook signature
     *
     * @param string $payload   Raw request body.
     * @param string $signature Signature header value.
     * @param string $secret    Webhook secret.
     * @return bool True if signature is valid.
     */
    public static function verify_signature($payload, $signature, $secret)
    {
        // Parse signature: t=timestamp,v1=hash
        if (!preg_match('/t=(\d+),v1=([a-f0-9]+)/', $signature, $matches)) {
            // Fallback to simple format for backward compatibility
            if (strpos($signature, 'sha256=') === 0) {
                $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
                return hash_equals($expected, $signature);
            }
            return false;
        }

        $timestamp = (int) $matches[1];
        $received_hash = $matches[2];

        // Check timestamp tolerance (5 minutes)
        if (abs(time() - $timestamp) > 300) {
            return false;
        }

        // Reconstruct message and verify
        $message = $timestamp . '.' . $payload;
        $expected_hash = hash_hmac('sha256', $message, $secret);

        return hash_equals($expected_hash, $received_hash);
    }
}
