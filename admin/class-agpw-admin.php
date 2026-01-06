<?php
/**
 * Admin Panel Configuration
 * Handles all WordPress admin settings and pages
 */

if (!defined('ABSPATH')) exit;

class AGPW_Admin {
    
    /**
     * Register all settings
     */
    public static function register_settings() {
        // API Settings
        register_setting('agpw_options_group', 'agpw_api_key');
        register_setting('agpw_options_group', 'agpw_exchangerate_api_key');
        register_setting('agpw_options_group', 'agpw_default_currency');
        
        // Manual Override Options
        register_setting('agpw_options_group', 'agpw_use_manual_spot'); // NEW: Toggle for manual spot price
        register_setting('agpw_options_group', 'agpw_manual_spot_usd');
        register_setting('agpw_options_group', 'agpw_use_manual_exchange'); // NEW: Toggle for manual exchange rate
        register_setting('agpw_options_group', 'agpw_manual_exchange_rate'); // NEW: Manual exchange rate value
        
        // Formula Settings
        register_setting('agpw_options_group', 'agpw_oz_divisor');      
        register_setting('agpw_options_group', 'agpw_weight_multiplier'); 
        register_setting('agpw_options_group', 'agpw_buy_deduction');
        register_setting('agpw_options_group', 'agpw_deduction_currency'); // NEW: Currency for deduction
        
        // System Settings
        register_setting('agpw_options_group', 'agpw_last_updated');  
        register_setting('agpw_options_group', 'agpw_volatility_range');
        register_setting('agpw_options_group', 'agpw_volatility_interval');
        register_setting('agpw_options_group', 'agpw_timezone');
        register_setting('agpw_options_group', 'agpw_cron_interval'); // NEW: CRON update interval
        
        // Backward compatibility (deprecated but kept for migration)
        register_setting('agpw_options_group', 'agpw_usd_lkr_rate');
        
        // NEW: API Data Display Section (shown first)
        add_settings_section('agpw_api_display_section', 'API Data Display', array(__CLASS__, 'api_display_section_callback'), 'agpw-settings');
        
        add_settings_section('agpw_main_section', 'General Configuration', null, 'agpw-settings');
        add_settings_section('agpw_formula_section', 'Calculation Formula', null, 'agpw-settings');
        add_settings_section('agpw_volatility_section', 'Volatility Animation', null, 'agpw-settings');
        add_settings_section('agpw_general_display_section', 'Display Settings', null, 'agpw-settings');
        add_settings_section('agpw_system_section', 'System Settings', null, 'agpw-settings'); // NEW
    
        // -- General --
        add_settings_field('agpw_api_key', 'GoldAPI.io API Key', array(__CLASS__, 'api_key_callback'), 'agpw-settings', 'agpw_main_section');
        add_settings_field('agpw_use_manual_spot', 'Gold Price Source', array(__CLASS__, 'use_manual_spot_callback'), 'agpw-settings', 'agpw_main_section');
        add_settings_field('agpw_manual_spot_usd', 'Manual Spot Price (USD)', array(__CLASS__, 'manual_spot_callback'), 'agpw-settings', 'agpw_main_section');
        
        add_settings_field('agpw_exchangerate_api_key', 'ExchangeRate-API Key', array(__CLASS__, 'exchangerate_api_key_callback'), 'agpw-settings', 'agpw_main_section');
        add_settings_field('agpw_use_manual_exchange', 'Exchange Rate Source', array(__CLASS__, 'use_manual_exchange_callback'), 'agpw-settings', 'agpw_main_section');
        add_settings_field('agpw_manual_exchange_rate', 'Manual Exchange Rate', array(__CLASS__, 'manual_exchange_rate_callback'), 'agpw-settings', 'agpw_main_section');
        
        add_settings_field('agpw_default_currency', 'Default Currency', array(__CLASS__, 'default_currency_callback'), 'agpw-settings', 'agpw_main_section');
    
        // -- Formula --
        add_settings_field('agpw_oz_divisor', 'Ounce to Gram Divisor', array(__CLASS__, 'divisor_callback'), 'agpw-settings', 'agpw_formula_section');
        add_settings_field('agpw_weight_multiplier', 'Weight Multiplier (grams)', array(__CLASS__, 'multiplier_callback'), 'agpw-settings', 'agpw_formula_section');
        add_settings_field('agpw_buy_deduction', 'Buying Price Deduction', array(__CLASS__, 'deduction_callback'), 'agpw-settings', 'agpw_formula_section');
        add_settings_field('agpw_deduction_currency', 'Deduction Type', array(__CLASS__, 'deduction_currency_callback'), 'agpw-settings', 'agpw_formula_section');
        
        // -- Volatility --
        add_settings_field('agpw_volatility_range', 'Fluctuation Range (+/-)', array(__CLASS__, 'volatility_range_callback'), 'agpw-settings', 'agpw_volatility_section');
        add_settings_field('agpw_volatility_interval', 'Update Interval (ms)', array(__CLASS__, 'volatility_interval_callback'), 'agpw-settings', 'agpw_volatility_section');
        
        // -- Display --
        add_settings_field('agpw_timezone', 'Timezone', array(__CLASS__, 'timezone_callback'), 'agpw-settings', 'agpw_general_display_section');
        
        // -- System --
        add_settings_field('agpw_cron_interval', 'Auto-Update Interval', array(__CLASS__, 'cron_interval_callback'), 'agpw-settings', 'agpw_system_section');
    }
    
    /**
     * API Data Display Section - Shows current API values
     */
    public static function api_display_section_callback() {
        $spot_usd = get_option('agpw_spot_price_usd', 0);
        $default_currency = get_option('agpw_default_currency', 'LKR');
        $exchange_rate = AGPW_Core::get_exchange_rate($default_currency);
        $last_updated = get_option('agpw_currency_updated', 'Never');
        $rates = get_option('agpw_currency_rates', []);
        
        // Check sources
        $use_manual_spot = get_option('agpw_use_manual_spot', 'api');
        $use_manual_exchange = get_option('agpw_use_manual_exchange', 'api');
        
        $spot_source = ($use_manual_spot === 'manual') ? '🔧 Manual' : '🌐 API';
        $exchange_source = ($use_manual_exchange === 'manual') ? '🔧 Manual' : '🌐 API';
        
        ?>
        <div class="agpw-api-display-box" style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #0073aa;">
            <h3 style="margin-top: 0; color: #0073aa;">📊 Current Data</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div style="background: white; padding: 15px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <strong style="color: #666; font-size: 12px; text-transform: uppercase;">Gold Spot Price (USD/oz)</strong>
                    <div style="font-size: 24px; font-weight: bold; color: #d4af37; margin-top: 5px;">
                        $<?php echo $spot_usd > 0 ? number_format($spot_usd, 2) : 'N/A'; ?>
                    </div>
                    <small style="color: #999; font-size: 11px;">Source: <?php echo $spot_source; ?></small>
                </div>
                
                <div style="background: white; padding: 15px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <strong style="color: #666; font-size: 12px; text-transform: uppercase;">USD to <?php echo esc_html($default_currency); ?> Rate</strong>
                    <div style="font-size: 24px; font-weight: bold; color: #2271b1; margin-top: 5px;">
                        <?php echo $exchange_rate > 0 ? number_format($exchange_rate, 4) : 'N/A'; ?>
                    </div>
                    <small style="color: #999; font-size: 11px;">Source: <?php echo $exchange_source; ?></small>
                </div>
                
                <div style="background: white; padding: 15px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <strong style="color: #666; font-size: 12px; text-transform: uppercase;">Last Updated</strong>
                    <div style="font-size: 14px; font-weight: bold; color: #50575e; margin-top: 5px;">
                        <?php echo esc_html($last_updated); ?>
                    </div>
                </div>
            </div>
            
            <!-- Currency Selector -->
            <div style="background: white; padding: 15px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #1d2327;">
                    🌍 Change Plugin Currency (affects entire plugin)
                </label>
                <select id="agpw_currency_selector" style="width: 100%; max-width: 300px; padding: 8px; font-size: 14px;">
                    <?php
                    if (!empty($rates)) {
                        foreach ($rates as $code => $rate) {
                            $selected = ($code === $default_currency) ? 'selected' : '';
                            echo "<option value='" . esc_attr($code) . "' $selected>" . esc_html($code) . " - " . number_format($rate, 4) . "</option>";
                        }
                    } else {
                        echo "<option value='" . esc_attr($default_currency) . "'>" . esc_html($default_currency) . " - No rates loaded</option>";
                    }
                    ?>
                </select>
                <p class="description" style="margin-top: 10px;">
                    This will update the default currency for all widgets and calculations. Make sure to save settings after changing.
                </p>
            </div>
            
            <p style="margin-top: 15px; color: #666; font-size: 13px;">
                ℹ️ <strong>Note:</strong> Data source (API/Manual) is configured in the settings below. Use "Force Update" button to refresh API data.
            </p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#agpw_currency_selector').on('change', function() {
                var selectedCurrency = $(this).val();
                $('input[name="agpw_default_currency"]').val(selectedCurrency);
                
                // Visual feedback
                $(this).css('border-color', '#00a32a');
                setTimeout(function() {
                    $('#agpw_currency_selector').css('border-color', '');
                }, 1000);
            });
        });
        </script>
        <?php
    }
    
    // --- Field Callbacks ---
    public static function api_key_callback() {
        $value = get_option('agpw_api_key', '');
        $use_manual = get_option('agpw_use_manual_spot', 'api');
        $readonly = ($use_manual === 'manual') ? 'readonly' : '';
        $style = ($use_manual === 'manual') ? 'opacity: 0.5;' : '';
        echo '<input type="text" id="agpw_api_key" name="agpw_api_key" value="' . esc_attr($value) . '" class="regular-text" ' . $readonly . ' style="' . $style . '" />';
        echo '<p class="description">Get your free API key from <a href="https://www.goldapi.io/" target="_blank">goldapi.io</a></p>';
    }
    
    public static function use_manual_spot_callback() {
        $value = get_option('agpw_use_manual_spot', 'api');
        ?>
        <label>
            <input type="radio" name="agpw_use_manual_spot" value="api" class="agpw-spot-source" <?php checked($value, 'api'); ?> />
            Use API (GoldAPI.io)
        </label>
        <br>
        <label>
            <input type="radio" name="agpw_use_manual_spot" value="manual" class="agpw-spot-source" <?php checked($value, 'manual'); ?> />
            Use Manual Price (below)
        </label>
        <p class="description">Choose whether to fetch gold price from API or use manual entry.</p>
        <?php
    }
    
    public static function manual_spot_callback() {
        $value = get_option('agpw_manual_spot_usd', '');
        $use_manual = get_option('agpw_use_manual_spot', 'api');
        $readonly = ($use_manual === 'api') ? 'readonly' : '';
        $style = ($use_manual === 'api') ? 'opacity: 0.5;' : '';
        echo '<input type="number" step="0.01" id="agpw_manual_spot_usd" name="agpw_manual_spot_usd" value="' . esc_attr($value) . '" class="regular-text" placeholder="e.g. 2650.00" ' . $readonly . ' style="' . $style . '" />';
        echo '<p class="description">Enter manual gold spot price in USD per ounce. Only used if "Manual" is selected above.</p>';
    }
    
    public static function exchangerate_api_key_callback() {
        $value = get_option('agpw_exchangerate_api_key', '');
        $use_manual = get_option('agpw_use_manual_exchange', 'api');
        $readonly = ($use_manual === 'manual') ? 'readonly' : '';
        $style = ($use_manual === 'manual') ? 'opacity: 0.5;' : '';
        echo '<input type="text" id="agpw_exchangerate_api_key" name="agpw_exchangerate_api_key" value="' . esc_attr($value) . '" class="regular-text" ' . $readonly . ' style="' . $style . '" />';
        echo '<p class="description">Get your free key from <a href="https://www.exchangerate-api.com/" target="_blank">exchangerate-api.com</a></p>';
    }
    
    public static function use_manual_exchange_callback() {
        $value = get_option('agpw_use_manual_exchange', 'api');
        ?>
        <label>
            <input type="radio" name="agpw_use_manual_exchange" value="api" class="agpw-exchange-source" <?php checked($value, 'api'); ?> />
            Use API (ExchangeRate-API)
        </label>
        <br>
        <label>
            <input type="radio" name="agpw_use_manual_exchange" value="manual" class="agpw-exchange-source" <?php checked($value, 'manual'); ?> />
            Use Manual Rate (below)
        </label>
        <p class="description">Choose whether to fetch exchange rates from API or use manual entry.</p>
        <?php
    }
    
    public static function manual_exchange_rate_callback() {
        $value = get_option('agpw_manual_exchange_rate', '');
        $currency = get_option('agpw_default_currency', 'LKR');
        $use_manual = get_option('agpw_use_manual_exchange', 'api');
        $readonly = ($use_manual === 'api') ? 'readonly' : '';
        $style = ($use_manual === 'api') ? 'opacity: 0.5;' : '';
        echo '<input type="number" step="0.0001" id="agpw_manual_exchange_rate" name="agpw_manual_exchange_rate" value="' . esc_attr($value) . '" class="regular-text" placeholder="e.g. 305.50" ' . $readonly . ' style="' . $style . '" />';
        echo '<p class="description">Enter manual exchange rate (USD to ' . esc_html($currency) . '). Only used if "Manual" is selected above.</p>';
    }
    
    public static function default_currency_callback() {
        $value = get_option('agpw_default_currency', 'LKR');
        echo '<input type="text" name="agpw_default_currency" value="' . esc_attr($value) . '" class="small-text" placeholder="LKR" />';
        echo '<p class="description">e.g. LKR, USD, AUD, GBP. Used if shortcode currency is not specified.</p>';
    }
    
    public static function divisor_callback() {
        $value = get_option('agpw_oz_divisor', '31');
        echo '<input type="number" step="0.0001" name="agpw_oz_divisor" value="' . esc_attr($value) . '" class="small-text" />';
        echo '<p class="description">Standard Troy Ounce is ~31.1035. Adjust as needed for your formula.</p>';
    }
    
    public static function multiplier_callback() {
        $value = get_option('agpw_weight_multiplier', '8');
        echo '<input type="number" step="0.01" name="agpw_weight_multiplier" value="' . esc_attr($value) . '" class="small-text" />';
        echo '<p class="description">Weight in grams (e.g., 8 for 1 Pavan, 1 for 1 Gram).</p>';
    }
    
    public static function deduction_callback() {
        $value = get_option('agpw_buy_deduction', '2');
        $default_currency = get_option('agpw_default_currency', 'LKR');
        // Retrieve saved type to display initial unit correctly
        $type = get_option('agpw_deduction_currency', 'amount');
        $unit = ($type === 'percentage') ? '%' : $default_currency;
        
        echo '<input type="number" step="0.01" name="agpw_buy_deduction" value="' . esc_attr($value) . '" class="regular-text" /> <span id="agpw_deduction_unit">' . esc_html($unit) . '</span>';
        echo '<p class="description">Value to subtract from Selling Price to get Buying Price.</p>';
    }
    
    public static function deduction_currency_callback() {
        $value = get_option('agpw_deduction_currency', 'amount');
        $default_currency = get_option('agpw_default_currency', 'LKR');
        ?>
        <label>
            <input type="radio" name="agpw_deduction_currency" value="amount" class="agpw-deduction-type" <?php checked($value, 'amount'); ?> />
            Fixed Amount (e.g. 5000 <?php echo esc_html($default_currency); ?>)
        </label>
        <br>
        <label>
            <input type="radio" name="agpw_deduction_currency" value="percentage" class="agpw-deduction-type" <?php checked($value, 'percentage'); ?> />
            Percentage (e.g. 2%)
        </label>
        <p class="description">Select how the deduction value above is applied.</p>
        <?php
    }
    
    public static function volatility_range_callback() {
        $value = get_option('agpw_volatility_range', '2');
        $deduction_currency = get_option('agpw_deduction_currency', 'amount');
        $currency = get_option('agpw_default_currency', 'LKR');
        $unit = ($deduction_currency === 'percentage') ? '%' : $currency;
        echo '<input type="number" step="0.01" name="agpw_volatility_range" value="' . esc_attr($value) . '" class="small-text" /> ' . esc_html($unit);
        echo '<p class="description">Max random change (+/-) for price animation. Default: 2.</p>';
    }
    
    public static function volatility_interval_callback() {
        $value = get_option('agpw_volatility_interval', '2000');
        echo '<input type="number" step="100" name="agpw_volatility_interval" value="' . esc_attr($value) . '" class="small-text" /> ms';
        echo '<p class="description">Update speed in milliseconds (1000 = 1 sec). Default: 2000.</p>';
    }
    
    public static function timezone_callback() {
        $value = get_option('agpw_timezone', 'Asia/Colombo');
        $zones = timezone_identifiers_list();
        echo '<select name="agpw_timezone" class="regular-text">';
        foreach ($zones as $zone) {
            $selected = ($zone == $value) ? 'selected' : '';
            echo "<option value='".esc_attr($zone)."' $selected>".esc_html($zone)."</option>";
        }
        echo '</select>';
        echo '<p class="description">Select the timezone for the real-time clock.</p>';
    }
    
    public static function cron_interval_callback() {
        $value = get_option('agpw_cron_interval', 'twicedaily');
        ?>
        <select name="agpw_cron_interval">
            <option value="hourly" <?php selected($value, 'hourly'); ?>>Every Hour</option>
            <option value="twicedaily" <?php selected($value, 'twicedaily'); ?>>Twice Daily (Default)</option>
            <option value="daily" <?php selected($value, 'daily'); ?>>Once Daily</option>
            <option value="weekly" <?php selected($value, 'weekly'); ?>>Once Weekly</option>
        </select>
        <p class="description">How often to automatically fetch new prices from APIs. Change requires plugin reactivation.</p>
        <?php
    }
    
    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_options_page('Gold Price Settings', 'Gold Price Settings', 'manage_options', 'agpw-settings', array(__CLASS__, 'settings_page_html'));
    }
    
    /**
     * Settings page HTML
     */
    public static function settings_page_html() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap">
            <h1>⚙️ Gold Price Widget Settings</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('agpw_options_group');
                do_settings_sections('agpw-settings');
                submit_button('Save Settings');
                ?>
            </form>
            <hr>
            <h3>Actions</h3>
            <form method="post" action="">
                <input type="hidden" name="agpw_force_update" value="1">
                <?php wp_nonce_field('agpw_force_update_action', 'agpw_nonce'); ?>
                <p><input type="submit" class="button button-secondary" value="Force Update / Recalculate Now"></p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Function to toggle Gold Price fields
            function toggleSpotFields() {
                var useManual = $('input[name="agpw_use_manual_spot"]:checked').val();
                
                if (useManual === 'manual') {
                    // Disable API key, enable manual input
                    $('#agpw_api_key').prop('readonly', true).css('opacity', '0.5');
                    $('#agpw_manual_spot_usd').prop('readonly', false).css('opacity', '1');
                } else {
                    // Enable API key, disable manual input
                    $('#agpw_api_key').prop('readonly', false).css('opacity', '1');
                    $('#agpw_manual_spot_usd').prop('readonly', true).css('opacity', '0.5');
                }
            }
            
            // Function to toggle Exchange Rate fields
            function toggleExchangeFields() {
                var useManual = $('input[name="agpw_use_manual_exchange"]:checked').val();
                
                if (useManual === 'manual') {
                    // Disable API key, enable manual input
                    $('#agpw_exchangerate_api_key').prop('readonly', true).css('opacity', '0.5');
                    $('#agpw_manual_exchange_rate').prop('readonly', false).css('opacity', '1');
                } else {
                    // Enable API key, disable manual input
                    $('#agpw_exchangerate_api_key').prop('readonly', false).css('opacity', '1');
                    $('#agpw_manual_exchange_rate').prop('readonly', true).css('opacity', '0.5');
                }
            }
            
            // Initialize on page load
            toggleSpotFields();
            toggleExchangeFields();
            toggleDeductionUnit();
            
            // Listen for changes
            $('.agpw-spot-source').on('change', toggleSpotFields);
            $('.agpw-exchange-source').on('change', toggleExchangeFields);
            
            // Function to toggle Deduction Unit
            function toggleDeductionUnit() {
                var type = $('input[name="agpw_deduction_currency"]:checked').val();
                var currency = $('input[name="agpw_default_currency"]').val() || 'LKR';
                
                if (type === 'percentage') {
                    $('#agpw_deduction_unit').text('%');
                } else {
                    $('#agpw_deduction_unit').text(currency);
                }
            }
            
            // Listen for deduction type changes
            $('.agpw-deduction-type').on('change', toggleDeductionUnit);
            
            // Update currency unit when default currency changes
            $('input[name="agpw_default_currency"]').on('input', function() {
                toggleDeductionUnit();
            });
        });
        </script>
        
        <div class="agpw-help-section" style="margin-top: 30px; background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 5px;">
            <h2 style="margin-top: 0;">📌 Shortcode Guide & Instructions</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div>
                    <h3>📝 Available Shortcodes</h3>
                    <p>Use these codes in any page or widget:</p>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li><code>[gold_price_table]</code> - Standard Table</li>
                        <li><code>[gold_price type="ticker"]</code> - Scrolling Ticker</li>
                        <li><code>[gold_price type="grid"]</code> - Grid/Card View</li>
                        <li><code>[gold_price type="plain"]</code> - Simple Text</li>
                    </ul>
                </div>
                <div>
                    <h3>⚙️ Common Parameters</h3>
                    <p>Customize your shortcodes:</p>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li><code>title="My Title"</code> - Set custom title</li>
                        <li><code>rows="24,22"</code> - Show specific karats only</li>
                        <li><code>currency="USD"</code> - Override currency</li>
                    </ul>
                    <p><strong>Example:</strong><br><code>[gold_price type="table" title="Today Gold Rates"]</code></p>
                </div>
            </div>
        </div>

        <?php
        if (isset($_POST['agpw_force_update']) && check_admin_referer('agpw_force_update_action', 'agpw_nonce')) {
            AGPW_Core::fetch_and_store_prices();
            echo '<div class="updated"><p>Prices recalculated!</p></div>';
        }
    }
}
