<?php
/**
 * Webhook Manager
 *
 * @package Shopping_Agent_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class Shopping_Agent_UCP_Webhook_Manager
{

    /**
     * Webhooks table name
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'shopping_agent_ucp_webhooks';
    }

    /**
     * Handle order status changed
     */
    public function on_order_status_changed($order_id, $old_status, $new_status, $order)
    {
        $this->dispatch_event('order.status_changed', array(
            'order_id' => $order_id,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'order' => $this->format_order_payload($order),
        ));

        // Also fire order.created for new orders
        if ($old_status === 'pending' && in_array($new_status, array('processing', 'on-hold'))) {
            $this->dispatch_event('order.created', array(
                'order_id' => $order_id,
                'order' => $this->format_order_payload($order),
            ));
        }
    }

    /**
     * Handle payment complete
     */
    public function on_payment_complete($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $this->dispatch_event('order.paid', array(
            'order_id' => $order_id,
            'order' => $this->format_order_payload($order),
        ));
    }

    /**
     * Handle order refunded
     */
    public function on_order_refunded($order_id, $refund_id)
    {
        $order = wc_get_order($order_id);
        $refund = wc_get_order($refund_id);

        if (!$order || !$refund) {
            return;
        }

        $this->dispatch_event('order.refunded', array(
            'order_id' => $order_id,
            'refund_id' => $refund_id,
            'amount' => $refund->get_amount(),
            'reason' => $refund->get_reason(),
            'order' => $this->format_order_payload($order),
        ));
    }

    /**
     * Dispatch event to all registered webhooks
     */
    public function dispatch_event($event, $payload)
    {
        $webhooks = $this->get_active_webhooks_for_event($event);

        if (empty($webhooks)) {
            return;
        }

        $sender = new Shopping_Agent_UCP_Webhook_Sender();

        foreach ($webhooks as $webhook) {
            $sender->send($webhook, $event, $payload);
        }
    }

    /**
     * Get active webhooks for an event
     */
    private function get_active_webhooks_for_event($event)
    {
        global $wpdb;

        $webhooks = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE status = %s",
                'active'
            )
        );

        $matching_webhooks = array();

        foreach ($webhooks as $webhook) {
            $events = json_decode($webhook->events, true);
            if (is_array($events) && in_array($event, $events)) {
                $matching_webhooks[] = $webhook;
            }
        }

        return $matching_webhooks;
    }

    /**
     * Create webhook
     */
    public function create($api_key_id, $url, $events, $secret = null)
    {
        global $wpdb;

        if (!$secret) {
            $secret = wp_generate_password(32, false);
        }

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'api_key_id' => $api_key_id,
                'url' => $url,
                'events' => wp_json_encode($events),
                'secret' => $secret,
                'status' => 'active',
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error(
                'webhook_creation_failed',
                __('Failed to create webhook.', 'shopping-agent-with-ucp'),
                array('status' => 500)
            );
        }

        return array(
            'id' => $wpdb->insert_id,
            'secret' => $secret,
        );
    }

    /**
     * Delete webhook
     */
    public function delete($webhook_id)
    {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            array('id' => $webhook_id),
            array('%d')
        );
    }

    /**
     * Get webhooks for API key
     */
    public function get_by_api_key($api_key_id)
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE api_key_id = %d ORDER BY created_at DESC",
                $api_key_id
            )
        );
    }

    /**
     * Format order payload for webhook
     */
    private function format_order_payload($order)
    {
        return array(
            'id' => $order->get_id(),
            'number' => $order->get_order_number(),
            'status' => $order->get_status(),
            'currency' => $order->get_currency(),
            'total' => $order->get_total(),
            'customer' => array(
                'id' => $order->get_customer_id(),
                'email' => $order->get_billing_email(),
            ),
            'date_created' => $order->get_date_created() ? $order->get_date_created()->format('c') : null,
            'date_modified' => $order->get_date_modified() ? $order->get_date_modified()->format('c') : null,
        );
    }
}
