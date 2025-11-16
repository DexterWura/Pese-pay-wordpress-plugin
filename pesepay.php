<?php
/**
 * Plugin Name: PesePay Payment Gateway
 * Plugin URI: https://pesepay.com
 * Description: Accept payments on your WordPress site using PesePay payment gateway. Secure, easy to use, and production-ready.
 * Version: 1.0.0
 * Author: Dexterity Wurayayi
 * Author URI: https://pesepay.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pesepay
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PESEPAY_VERSION', '1.0.0');
define('PESEPAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PESEPAY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PESEPAY_PLUGIN_FILE', __FILE__);

/**
 * Main PesePay Plugin Class
 */
class PesePay_Plugin {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'init'), 0);
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Load payment gateway
        require_once PESEPAY_PLUGIN_DIR . 'includes/class-pesepay-gateway.php';
        
        // Register gateway with WooCommerce
        add_filter('woocommerce_payment_gateways', array($this, 'add_gateway'));
    }
    
    /**
     * Add gateway to WooCommerce
     */
    public function add_gateway($gateways) {
        $gateways[] = 'WC_PesePay_Gateway';
        return $gateways;
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('PesePay Settings', 'pesepay'),
            __('PesePay', 'pesepay'),
            'manage_options',
            'pesepay-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Verify nonce
        if (isset($_POST['pesepay_settings_nonce']) && !wp_verify_nonce($_POST['pesepay_settings_nonce'], 'pesepay_settings')) {
            return;
        }
        
        register_setting('pesepay_settings', 'pesepay_integration_key', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_api_key'),
            'default' => ''
        ));
        
        register_setting('pesepay_settings', 'pesepay_encryption_key', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_api_key'),
            'default' => ''
        ));
        
        register_setting('pesepay_settings', 'pesepay_test_mode', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ));
    }
    
    /**
     * Sanitize API key
     */
    public function sanitize_api_key($value) {
        return sanitize_text_field(trim($value));
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if settings were saved
        if (isset($_GET['settings-updated'])) {
            add_settings_error('pesepay_messages', 'pesepay_message', __('Settings saved successfully.', 'pesepay'), 'updated');
        }
        
        settings_errors('pesepay_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                wp_nonce_field('pesepay_settings', 'pesepay_settings_nonce');
                settings_fields('pesepay_settings');
                do_settings_sections('pesepay_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="pesepay_integration_key"><?php _e('Integration Key', 'pesepay'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="pesepay_integration_key" 
                                   name="pesepay_integration_key" 
                                   value="<?php echo esc_attr(get_option('pesepay_integration_key')); ?>" 
                                   class="regular-text" 
                                   required />
                            <p class="description">
                                <?php _e('Enter your PesePay Integration Key. You can find this in your PesePay dashboard.', 'pesepay'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pesepay_encryption_key"><?php _e('Encryption Key', 'pesepay'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="pesepay_encryption_key" 
                                   name="pesepay_encryption_key" 
                                   value="<?php echo esc_attr(get_option('pesepay_encryption_key')); ?>" 
                                   class="regular-text" 
                                   required />
                            <p class="description">
                                <?php _e('Enter your PesePay Encryption Key. Keep this secure and never share it publicly.', 'pesepay'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pesepay_test_mode"><?php _e('Test Mode', 'pesepay'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   id="pesepay_test_mode" 
                                   name="pesepay_test_mode" 
                                   value="1" 
                                   <?php checked(get_option('pesepay_test_mode'), 1); ?> />
                            <label for="pesepay_test_mode">
                                <?php _e('Enable test mode for testing payments', 'pesepay'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Settings', 'pesepay')); ?>
            </form>
            <div class="pesepay-info">
                <h2><?php _e('Need Help?', 'pesepay'); ?></h2>
                <p>
                    <?php _e('For any issues with the payment gateway, please contact PesePay support directly.', 'pesepay'); ?>
                </p>
                <p>
                    <a href="https://developers.pesepay.com/overview" target="_blank" rel="noopener noreferrer">
                        <?php _e('View PesePay API Documentation', 'pesepay'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Add plugin action links
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=pesepay-settings') . '">' . __('Settings', 'pesepay') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <?php 
                printf(
                    __('PesePay Payment Gateway requires WooCommerce to be installed and active. %s', 'pesepay'),
                    '<a href="' . admin_url('plugin-install.php?s=woocommerce&tab=search&type=term') . '">' . __('Install WooCommerce', 'pesepay') . '</a>'
                );
                ?>
            </p>
        </div>
        <?php
    }
}

// Initialize plugin
PesePay_Plugin::get_instance();

