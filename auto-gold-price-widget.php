<?php
/*
Plugin Name: Auto Gold Price Widget
Description: Advanced gold price calculator with custom formulas, manual/API selection, configurable CRON updates, and multiple layouts. Full currency support with no hardcoded values.
Version: 2.2
Author: Tiran Chanuka
*/

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('AGPW_VERSION', '2.2');
define('AGPW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AGPW_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load core classes
require_once AGPW_PLUGIN_DIR . 'includes/class-agpw-core.php';
require_once AGPW_PLUGIN_DIR . 'admin/class-agpw-admin.php';
require_once AGPW_PLUGIN_DIR . 'public/class-agpw-public.php';

// ====== INITIALIZATION ======

/**
 * Register settings
 */
function agpw_register_settings() {
    AGPW_Admin::register_settings();
}
add_action('admin_init', 'agpw_register_settings');

/**
 * Add admin menu
 */
function agpw_add_admin_menu() {
    AGPW_Admin::add_admin_menu();
}
add_action('admin_menu', 'agpw_add_admin_menu');

/**
 * Scheduled price update
 */
function agpw_fetch_and_store_prices() {
    AGPW_Core::fetch_and_store_prices();
}
add_action('agpw_update_gold_prices', 'agpw_fetch_and_store_prices');

/**
 * Activation hook
 */
function agpw_activate() {
    // Get configured CRON interval (default: twicedaily)
    $cron_interval = get_option('agpw_cron_interval', 'twicedaily');
    
    // Clear any existing scheduled events
    wp_clear_scheduled_hook('agpw_update_gold_prices');
    
    // Schedule new event with configured interval
    if (!wp_next_scheduled('agpw_update_gold_prices')) {
        wp_schedule_event(time(), $cron_interval, 'agpw_update_gold_prices');
    }
    
    // Fetch prices immediately on activation
    AGPW_Core::fetch_and_store_prices();
}
register_activation_hook(__FILE__, 'agpw_activate');

/**
 * Deactivation hook
 */
function agpw_deactivate() {
    wp_clear_scheduled_hook('agpw_update_gold_prices');
}
register_deactivation_hook(__FILE__, 'agpw_deactivate');

/**
 * Register shortcodes
 */
function agpw_shortcode_handler($atts) {
    return AGPW_Public::shortcode_handler($atts);
}
add_shortcode('gold_price', 'agpw_shortcode_handler'); 
add_shortcode('gold_price_table', 'agpw_shortcode_handler');

/**
 * Enqueue frontend assets
 */
function agpw_enqueue_assets() {
    AGPW_Public::enqueue_assets();
}
add_action('wp_enqueue_scripts', 'agpw_enqueue_assets');

// ====== BACKWARD COMPATIBILITY FUNCTIONS ======
// These functions maintain compatibility with any external code that might call them

function agpw_get_exchange_rate($target_currency) {
    return AGPW_Core::get_exchange_rate($target_currency);
}

function agpw_calculate_data($spot_usd, $currency_code) {
    return AGPW_Core::calculate_data($spot_usd, $currency_code);
}
