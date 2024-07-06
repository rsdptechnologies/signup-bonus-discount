<?php
/*
Plugin Name: RSDP Signup Bonus Discount
Description: Give a signup bonus discount for new users.
Version: 1.0
Author: Deepak Vishwakarma

*/

define('YOUR_SPECIAL_SECRET_KEY', '667e4468bdaea7.62198941');
define('YOUR_LICENSE_SERVER_URL', 'https://gangasewasadanhospital.com/vip');
define('YOUR_ITEM_REFERENCE', 'My First Plugin');

// File integrity check
function check_file_integrity() {
    $plugin_dir = __DIR__;
    $hashes = include $plugin_dir . '/hashes.php';

    foreach ($hashes as $relativePath => $expectedHash) {
        $fullPath = $plugin_dir . $relativePath;
        if (!file_exists($fullPath) || hash_file('sha256', $fullPath) !== $expectedHash) {
            wp_die('Error: Plugin files have been modified. Please reinstall the plugin.');
        }
    }
}
add_action('plugins_loaded', 'check_file_integrity');

// Add admin menu for license management
add_action('admin_menu', 'slm_sample_license_menu');

function slm_sample_license_menu() {
    add_options_page('Sample License Activation Menu', 'Signup Bonus Discount License Key', 'manage_options', 'sample-license-activation', 'license_management_page');
}

function license_management_page() {
    echo '<div class="wrap">';
    echo '<h2>Signup Bonus Discount License Management</h2>';

    if (isset($_REQUEST['activate_license'])) {
        $license_key = sanitize_text_field($_REQUEST['sample_license_key']);

        $api_params = array(
            'slm_action' => 'slm_activate',
            'secret_key' => YOUR_SPECIAL_SECRET_KEY,
            'license_key' => $license_key,
            'registered_domain' => $_SERVER['SERVER_NAME'],
            'item_reference' => urlencode(YOUR_ITEM_REFERENCE),
        );

        $query = esc_url_raw(add_query_arg($api_params, YOUR_LICENSE_SERVER_URL));
        $response = wp_remote_get($query, array('timeout' => 20, 'sslverify' => false));

        if (is_wp_error($response)) {
            echo "Unexpected Error! The query returned with an error.";
        }

        $license_data = json_decode(wp_remote_retrieve_body($response));

        if ($license_data->result == 'success') {
            echo '<br />' . esc_html__('The following message was returned from the server: ', 'your-text-domain') . esc_html($license_data->message);
            update_option('sample_license_key', $license_key);
        } else {
            echo '<br />' . esc_html__('The following message was returned from the server: ', 'your-text-domain') . esc_html($license_data->message);
        }
    }

    if (isset($_REQUEST['deactivate_license'])) {
        $license_key = sanitize_text_field($_REQUEST['sample_license_key']);

        $api_params = array(
            'slm_action' => 'slm_deactivate',
            'secret_key' => YOUR_SPECIAL_SECRET_KEY,
            'license_key' => $license_key,
            'registered_domain' => $_SERVER['SERVER_NAME'],
            'item_reference' => urlencode(YOUR_ITEM_REFERENCE),
        );

        $query = esc_url_raw(add_query_arg($api_params, YOUR_LICENSE_SERVER_URL));
        $response = wp_remote_get($query, array('timeout' => 20, 'sslverify' => false));

        if (is_wp_error($response)) {
            echo "Unexpected Error! The query returned with an error.";
        }

        $license_data = json_decode(wp_remote_retrieve_body($response));

        if ($license_data->result == 'success') {
            echo '<br />' . esc_html__('The following message was returned from the server: ', 'your-text-domain') . esc_html($license_data->message);
            update_option('sample_license_key', '');
        } else {
            echo '<br />' . esc_html__('The following message was returned from the server: ', 'your-text-domain') . esc_html($license_data->message);
        }
    }
    ?>
    <p><?php esc_html_e('Please enter the license key for this product to activate it:', 'your-text-domain'); ?></p>
    <form action="" method="post">
        <table class="form-table">
            <tr>
                <th style="width:100px;"><label for="sample_license_key"><?php esc_html_e('License Key', 'your-text-domain'); ?></label></th>
                <td><input class="regular-text" type="text" id="sample_license_key" name="sample_license_key" value="<?php echo esc_attr(get_option('sample_license_key')); ?>" /></td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="activate_license" value="<?php esc_attr_e('Activate', 'your-text-domain'); ?>" class="button-primary" />
            <input type="submit" name="deactivate_license" value="<?php esc_attr_e('Deactivate', 'your-text-domain'); ?>" class="button" />
        </p>
    </form>
    <?php
    echo '</div>';
}

// Conditionally add Signup Bonus Discount feature after license activation
$license_key = get_option('sample_license_key');
if (!empty($license_key)) {
    add_action('woocommerce_cart_calculate_fees', 'apply_signup_bonus_discount');
    add_action('admin_menu', 'signup_bonus_discount_menu');
}

// Signup Bonus Discount
function apply_signup_bonus_discount() {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        
        $args = array(
            'customer_id' => $user_id,
            'post_status' => array('wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending'),
        );
        $orders = wc_get_orders($args);
        
        $discount = get_option('signup_bonus_discount_amount', 50);
        
        if (count($orders) == 0) {
            WC()->cart->add_fee(__('Signup Bonus Discount', 'your-text-domain'), -$discount, true, '');
        }
    }
}

// Admin Menu for Signup Bonus Discount Settings
function signup_bonus_discount_menu() {
    add_menu_page(
        __('Signup Bonus Discount Settings', 'your-text-domain'),
        __('Signup Discount', 'your-text-domain'),
        'manage_options',
        'signup-bonus-discount-settings',
        'signup_bonus_discount_settings_page',
        'dashicons-admin-generic',
        90
    );
}

// Settings Page for Signup Bonus Discount
function signup_bonus_discount_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Signup Bonus Discount Settings', 'your-text-domain'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('signup_bonus_discount_settings');
            do_settings_sections('signup-bonus-discount');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register Settings for Signup Bonus Discount
add_action('admin_init', 'signup_bonus_discount_settings_init');

function signup_bonus_discount_settings_init() {
    register_setting('signup_bonus_discount_settings', 'signup_bonus_discount_amount');

    add_settings_section(
        'signup_bonus_discount_section',
        __('General Settings', 'your-text-domain'),
        '__return_null',
        'signup-bonus-discount'
    );

    add_settings_field(
        'signup_bonus_discount_amount',
        __('Discount Amount', 'your-text-domain'),
        'signup_bonus_discount_amount_callback',
        'signup-bonus-discount',
        'signup_bonus_discount_section'
    );
}

function signup_bonus_discount_amount_callback() {
    $amount = get_option('signup_bonus_discount_amount', 50);
    echo '<input type="number" name="signup_bonus_discount_amount" value="' . esc_attr($amount) . '" />';
}

// Send email notification on plugin activation
function send_activation_notification_email() {
    $to = 'computersiked@gmail.com';
    $subject = 'Signup Bonus Discount Plugin Activated';
    $message = 'The Signup Bonus Discount plugin has been activated on your website: ' . get_site_url();

    wp_mail($to, $subject, $message);
}
register_activation_hook(__FILE__, 'send_activation_notification_email');

// Redirect to license activation page on activation
function plugin_redirect_on_activation() {
    if (is_admin() && isset($_GET['activate']) && $_GET['activate'] == 'true') {
        wp_redirect(admin_url('options-general.php?page=sample-license-activation'));
        exit;
    }
}
add_action('admin_init', 'plugin_redirect_on_activation');
?>