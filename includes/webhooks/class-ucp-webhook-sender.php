<?php
/**
 * Webhook Sender
 *
 * @package WC_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_UCP_Webhook_Sender
{

    /**
     * Send webhook
     */
    public function send($webhook, $event, $payload)
    {
        $body = wp_json_encode(array(
            'event' => $event,
            'timestamp' => current_time('c'),
            'data' => $payload,
        ));

        $signature = $this->generate_signature($body, $webhook->secret);

        $headers = array(
            'Content-Type' => 'application/json',
            'X-UCP-Event' => $event,
            'X-UCP-Signature' => $signature,
            'X-UCP-Timestamp' => time(),
            'User-Agent' => 'WooCommerce-UCP-Agent/' . WC_UCP_VERSION,
        );

        $args = array(
            'body' => $body,
            'headers' => $headers,
            'timeout' => 30,
            'redirection' => 0,
            'httpversion' => '1.1',
            'sslverify' => apply_filters('wc_ucp_webhook_ssl_verify', true),
        );

        // Use wp_safe_remote_post for security
        $response = wp_safe_remote_post($webhook->url, $args);

        // Log the attempt
        $this->log_delivery($webhook, $event, $response);

        if (is_wp_error($response)) {
            $this->handle_failure($webhook, $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code < 200 || $status_code >= 300) {
            $this->handle_failure($webhook, "HTTP $status_code");
            return false;
        }

        return true;
    }

    /**
     * Generate HMAC signature
     */
    private function generate_signature($body, $secret)
    {
        return 'sha256=' . hash_hmac('sha256', $body, $secret);
    }

    /**
     * Log webhook delivery
     */
    private function log_delivery($webhook, $event, $response)
    {
        if (get_option('wc_ucp_log_enabled', 'no') !== 'yes') {
            return;
        }

        $log_entry = array(
            'webhook_id' => $webhook->id,
            'event' => $event,
            'url' => $webhook->url,
            'timestamp' => current_time('mysql'),
            'success' => !is_wp_error($response),
        );

        if (is_wp_error($response)) {
            $log_entry['error'] = $response->get_error_message();
        } else {
            $log_entry['status_code'] = wp_remote_retrieve_response_code($response);
        }

        // Store in options (simple logging, could be enhanced)
        $logs = get_option('wc_ucp_webhook_logs', array());
        $logs[] = $log_entry;

        // Keep only last 100 entries
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }

        update_option('wc_ucp_webhook_logs', $logs);
    }

    /**
     * Handle delivery failure
     */
    private function handle_failure($webhook, $error)
    {
        // Could implement retry logic or notification here
        do_action('wc_ucp_webhook_delivery_failed', $webhook, $error);
    }

    /**
     * Verify incoming webhook signature
     */
    public static function verify_signature($payload, $signature, $secret)
    {
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }
}
