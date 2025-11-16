<?php
/**
 * PesePay Payment Gateway for WooCommerce
 *
 * @package PesePay
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_PesePay_Gateway Class
 */
class WC_PesePay_Gateway extends WC_Payment_Gateway {
    
    /**
     * API endpoint
     */
    private $api_url;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'pesepay';
        $this->icon = '';
        $this->has_fields = false;
        $this->method_title = __('PesePay', 'pesepay');
        $this->method_description = __('Accept payments via PesePay payment gateway.', 'pesepay');
        
        // Load settings
        $this->init_form_fields();
        $this->init_settings();
        
        // Define user set variables
        $this->title = $this->get_option('title', __('PesePay', 'pesepay'));
        $this->description = $this->get_option('description', __('Pay securely via PesePay.', 'pesepay'));
        $this->enabled = $this->get_option('enabled', 'no');
        
        // Set API URL based on test mode
        $test_mode = get_option('pesepay_test_mode', false);
        $this->api_url = $test_mode 
            ? 'https://api.pesepay.com/sandbox' 
            : 'https://api.pesepay.com';
        
        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_wc_pesepay_gateway', array($this, 'handle_callback'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
    }
    
    /**
     * Initialize form fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'pesepay'),
                'type' => 'checkbox',
                'label' => __('Enable PesePay Payment Gateway', 'pesepay'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'pesepay'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'pesepay'),
                'default' => __('PesePay', 'pesepay'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'pesepay'),
                'type' => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'pesepay'),
                'default' => __('Pay securely via PesePay payment gateway.', 'pesepay'),
                'desc_tip' => true,
            ),
        );
    }
    
    /**
     * Process the payment and return the result
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return array(
                'result' => 'fail',
                'redirect' => ''
            );
        }
        
        // Get API credentials
        $integration_key = get_option('pesepay_integration_key');
        $encryption_key = get_option('pesepay_encryption_key');
        
        if (empty($integration_key) || empty($encryption_key)) {
            wc_add_notice(__('Payment gateway is not configured properly. Please contact the store administrator.', 'pesepay'), 'error');
            return array(
                'result' => 'fail',
                'redirect' => ''
            );
        }
        
        // Prepare payment data
        $payment_data = array(
            'amount' => $order->get_total(),
            'currency' => $order->get_currency(),
            'referenceNumber' => $order->get_order_number(),
            'reason' => sprintf(__('Order #%s', 'pesepay'), $order->get_order_number()),
            'returnUrl' => $this->get_return_url($order),
            'resultUrl' => add_query_arg('wc-api', 'WC_PesePay_Gateway', home_url('/')),
            'email' => $order->get_billing_email(),
            'firstName' => $order->get_billing_first_name(),
            'lastName' => $order->get_billing_last_name(),
        );
        
        // Create payment request
        $response = $this->create_payment_request($payment_data, $integration_key, $encryption_key);
        
        if (is_wp_error($response)) {
            wc_add_notice(__('Payment error: ', 'pesepay') . $response->get_error_message(), 'error');
            return array(
                'result' => 'fail',
                'redirect' => ''
            );
        }
        
        // Store payment reference in order meta
        if (isset($response['referenceNumber'])) {
            $order->update_meta_data('_pesepay_reference', $response['referenceNumber']);
            $order->save();
        }
        
        // Redirect to PesePay payment page
        if (isset($response['redirectUrl'])) {
            return array(
                'result' => 'success',
                'redirect' => $response['redirectUrl']
            );
        }
        
        wc_add_notice(__('Unable to initiate payment. Please try again.', 'pesepay'), 'error');
        return array(
            'result' => 'fail',
            'redirect' => ''
        );
    }
    
    /**
     * Create payment request with PesePay API
     */
    private function create_payment_request($data, $integration_key, $encryption_key) {
        $url = $this->api_url . '/api/payments/initiate';
        
        // Validate and prepare request body
        $amount = floatval($data['amount']);
        if ($amount <= 0) {
            return new WP_Error('invalid_amount', __('Invalid payment amount.', 'pesepay'));
        }
        
        $body = array(
            'amount' => $amount,
            'currencyCode' => strtoupper(sanitize_text_field($data['currency'])),
            'referenceNumber' => sanitize_text_field($data['referenceNumber']),
            'reason' => sanitize_text_field($data['reason']),
            'returnUrl' => esc_url_raw($data['returnUrl']),
            'resultUrl' => esc_url_raw($data['resultUrl']),
            'email' => sanitize_email($data['email']),
            'firstName' => sanitize_text_field($data['firstName']),
            'lastName' => sanitize_text_field($data['lastName']),
        );
        
        // Add additional order data if available
        if (isset($data['phone'])) {
            $body['phone'] = sanitize_text_field($data['phone']);
        }
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . sanitize_text_field($integration_key),
            ),
            'body' => wp_json_encode($body),
            'timeout' => 30,
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        
        if ($response_code !== 200 && $response_code !== 201) {
            $error_message = isset($response_data['message']) 
                ? $response_data['message'] 
                : __('Payment request failed.', 'pesepay');
            return new WP_Error('pesepay_error', $error_message);
        }
        
        return $response_data;
    }
    
    /**
     * Handle payment callback from PesePay
     */
    public function handle_callback() {
        // Verify nonce for security
        if (!isset($_GET['referenceNumber']) || !isset($_GET['status'])) {
            wp_die(__('Invalid callback parameters.', 'pesepay'), __('Payment Error', 'pesepay'), array('response' => 400));
        }
        
        $reference_number = sanitize_text_field($_GET['referenceNumber']);
        $status = sanitize_text_field($_GET['status']);
        
        // Find order by reference number
        $orders = wc_get_orders(array(
            'meta_key' => '_pesepay_reference',
            'meta_value' => $reference_number,
            'limit' => 1,
        ));
        
        if (empty($orders)) {
            // Try to find by order number
            $order_id = intval($reference_number);
            $order = wc_get_order($order_id);
        } else {
            $order = $orders[0];
        }
        
        if (!$order) {
            wp_die(__('Order not found.', 'pesepay'), __('Payment Error', 'pesepay'), array('response' => 404));
        }
        
        // Verify payment status with PesePay API
        $payment_status = $this->verify_payment_status($reference_number);
        
        if (is_wp_error($payment_status)) {
            // Log error but don't fail the order
            if (function_exists('wc_get_logger')) {
                $logger = wc_get_logger();
                $logger->error('PesePay verification error: ' . $payment_status->get_error_message(), array('source' => 'pesepay'));
            } else {
                error_log('PesePay verification error: ' . $payment_status->get_error_message());
            }
        } else {
            $status = $payment_status;
        }
        
        // Update order status based on payment status
        if ($status === 'SUCCESS' || $status === 'success') {
            if ($order->get_status() !== 'processing' && $order->get_status() !== 'completed') {
                $order->payment_complete();
                $order->add_order_note(__('Payment completed via PesePay.', 'pesepay'));
                wc_reduce_stock_levels($order->get_id());
            }
            
            // Redirect to thank you page
            wp_safe_redirect($this->get_return_url($order));
            exit;
        } else {
            // Payment failed
            $order->update_status('failed', __('Payment failed via PesePay.', 'pesepay'));
            wc_add_notice(__('Payment failed. Please try again.', 'pesepay'), 'error');
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        }
    }
    
    /**
     * Verify payment status with PesePay API
     */
    private function verify_payment_status($reference_number) {
        $integration_key = get_option('pesepay_integration_key');
        
        if (empty($integration_key)) {
            return new WP_Error('no_credentials', __('API credentials not configured.', 'pesepay'));
        }
        
        $url = $this->api_url . '/api/payments/' . urlencode($reference_number);
        
        $args = array(
            'method' => 'GET',
            'headers' => array(
                'Authorization' => 'Bearer ' . sanitize_text_field($integration_key),
            ),
            'timeout' => 30,
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        
        if ($response_code !== 200) {
            return new WP_Error('verification_failed', __('Could not verify payment status.', 'pesepay'));
        }
        
        return isset($response_data['status']) ? $response_data['status'] : 'UNKNOWN';
    }
    
    /**
     * Output for the order received page
     */
    public function thankyou_page($order_id) {
        $order = wc_get_order($order_id);
        
        if ($order && $order->get_payment_method() === $this->id) {
            echo '<p>' . esc_html__('Thank you for your payment. Your order is being processed.', 'pesepay') . '</p>';
        }
    }
}

