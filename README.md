# PesePay WordPress Plugin

A secure, production-ready WordPress plugin that enables merchants to accept payments through the PesePay payment gateway on their WooCommerce store.

**Author:** Dexterity Wurayayi  
**Version:** 1.0.0  
**Requires:** WordPress 5.0+, WooCommerce 3.0+, PHP 7.2+

## Description

The PesePay WordPress plugin seamlessly integrates PesePay payment gateway with your WooCommerce store, allowing customers to make secure payments using various payment methods supported by PesePay. The plugin is built with security best practices and is ready for production use.

## Features

- ✅ **Easy Integration:** Simple setup with just your Integration Key and Encryption Key
- ✅ **WooCommerce Compatible:** Fully integrated with WooCommerce payment system
- ✅ **Secure:** Built with WordPress security best practices
- ✅ **Test Mode:** Test payments before going live
- ✅ **Production Ready:** Clean, secure code suitable for live environments
- ✅ **Automatic Order Management:** Orders are automatically updated based on payment status
- ✅ **Callback Handling:** Secure payment verification and status updates

## Installation

### Automatic Installation

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins** → **Add New**
3. Search for "PesePay"
4. Click **Install Now** and then **Activate**

### Manual Installation

1. Download the plugin ZIP file
2. Extract the files to `/wp-content/plugins/pesepay/` directory
3. Log in to your WordPress admin dashboard
4. Navigate to **Plugins** → **Installed Plugins**
5. Find "PesePay Payment Gateway" and click **Activate**

## Configuration

### Step 1: Get Your PesePay Credentials

1. Sign up for a PesePay account at [https://pesepay.com](https://pesepay.com)
2. Log in to your PesePay dashboard
3. Navigate to **Settings** → **API Keys**
4. Copy your **Integration Key** and **Encryption Key**

### Step 2: Configure the Plugin

1. In WordPress admin, go to **Settings** → **PesePay**
2. Enter your **Integration Key**
3. Enter your **Encryption Key**
4. Enable **Test Mode** if you want to test payments (disable for production)
5. Click **Save Settings**

### Step 3: Enable Payment Gateway in WooCommerce

1. Go to **WooCommerce** → **Settings** → **Payments**
2. Find **PesePay** in the payment methods list
3. Click **Manage** or toggle it to **Enabled**
4. Configure the gateway title and description (optional)
5. Click **Save changes**

## Usage

Once configured, customers will see PesePay as a payment option during checkout. When selected:

1. Customer completes order details
2. Customer clicks "Place Order"
3. Customer is redirected to PesePay payment page
4. Customer completes payment on PesePay
5. Customer is redirected back to your store
6. Order status is automatically updated based on payment result

## API Documentation

For detailed API documentation and integration guides, visit:
**https://developers.pesepay.com/overview**

## Support

### Plugin Issues

If you encounter any issues with the plugin functionality, please:
1. Check that WooCommerce is installed and activated
2. Verify your API credentials are correct
3. Ensure your server meets the minimum requirements
4. Check WordPress error logs for any error messages

### Payment Gateway Issues

**For any issues related to the PesePay payment gateway itself, please contact PesePay support directly.**

- **Website:** [https://pesepay.com](https://pesepay.com)
- **Support:** Contact through your PesePay dashboard
- **Documentation:** [https://developers.pesepay.com/overview](https://developers.pesepay.com/overview)

## Requirements

- **WordPress:** 5.0 or higher
- **WooCommerce:** 3.0 or higher
- **PHP:** 7.2 or higher
- **SSL Certificate:** Required for secure payment processing

## Security

This plugin follows WordPress security best practices:

- ✅ All user inputs are sanitized and validated
- ✅ Nonces are used for form submissions
- ✅ API credentials are stored securely
- ✅ Payment callbacks are verified
- ✅ SQL injection and XSS protection
- ✅ Secure API communication

## Changelog

### 1.0.0
- Initial release
- WooCommerce integration
- Payment processing
- Callback handling
- Admin settings page
- Test mode support

## License

This plugin is licensed under the GPL v2 or later.

## Credits

**Author:** Dexterity Wurayayi  
**Payment Gateway:** PesePay  
**API Documentation:** [https://developers.pesepay.com/overview](https://developers.pesepay.com/overview)

---

**Note:** This plugin requires an active PesePay merchant account. Sign up at [https://pesepay.com](https://pesepay.com) to get started.
