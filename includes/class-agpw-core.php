<?php
/**
 * Core Plugin Logic
 * Handles API fetching, calculations, and data storage
 */

if (!defined('ABSPATH')) exit;

class AGPW_Core {
    
    /**
     * Fetch gold price and exchange rates from APIs
     */
    public static function fetch_and_store_prices() {
        $spot_price_usd = 0.0;
        
        // 1. Get Spot Price - Check manual/API selection
        $use_manual_spot = get_option('agpw_use_manual_spot', 'api');
        
        if ($use_manual_spot === 'manual') {
            // Use manual spot price
            $manual_spot = get_option('agpw_manual_spot_usd', '');
            if (!empty($manual_spot)) {
                $spot_price_usd = floatval($manual_spot);
            }
        } else {
            // Use API
            $api_key = get_option('agpw_api_key');
            if (!empty($api_key)) {
                $response = wp_remote_get('https://www.goldapi.io/api/XAU/USD', array(
                    'headers' => array('x-access-token' => $api_key, 'Content-Type' => 'application/json'),
                    'timeout' => 20
                ));
                
                if (!is_wp_error($response)) {
                    $body = wp_remote_retrieve_body($response);
                    $json = json_decode($body, true);
                    
                    if (isset($json['price'])) {
                        $spot_price_usd = floatval($json['price']);
                    } elseif (isset($json['price_gram_24k'])) {
                        $spot_price_usd = floatval($json['price_gram_24k']) * 31.1034768;
                    }
                }
            }
        }
    
        // 2. Fetch Exchange Rates - Check manual/API selection
        $use_manual_exchange = get_option('agpw_use_manual_exchange', 'api');
        
        if ($use_manual_exchange === 'manual') {
            // Use manual exchange rate
            $manual_rate = get_option('agpw_manual_exchange_rate', '');
            $default_currency = get_option('agpw_default_currency', 'LKR');
            
            if (!empty($manual_rate)) {
                $stored_rates = array();
                $stored_rates[$default_currency] = floatval($manual_rate);
                $stored_rates['USD'] = 1.0; // USD is always 1
                
                update_option('agpw_currency_rates', $stored_rates);
                update_option('agpw_currency_updated', current_time('mysql'));
            }
        } else {
            // Use API
            $ex_api_key = get_option('agpw_exchangerate_api_key');
            
            if (!empty($ex_api_key)) {
                $ex_url = "https://v6.exchangerate-api.com/v6/{$ex_api_key}/latest/USD";
                $ex_response = wp_remote_get($ex_url);
                if (!is_wp_error($ex_response)) {
                    $ex_body = wp_remote_retrieve_body($ex_response);
                    $ex_json = json_decode($ex_body, true);
                    if (isset($ex_json['result']) && $ex_json['result'] === 'success') {
                        $stored_rates = $ex_json['conversion_rates'];
                        update_option('agpw_currency_rates', $stored_rates);
                        update_option('agpw_currency_updated', current_time('mysql'));
                    }
                }
            }
        }
    
        // Store Base USD Spot for live calculation
        update_option('agpw_spot_price_usd', $spot_price_usd);
        
        // Calculate Default Currency for backward compatibility cache
        $def_currency = get_option('agpw_default_currency', 'LKR');
        $agpw_data = self::calculate_data($spot_price_usd, $def_currency);
        
        update_option('agpw_gold_prices', $agpw_data);
    }
    
    /**
     * Get exchange rate for a currency
     */
    public static function get_exchange_rate($target_currency) {
        $target_currency = strtoupper($target_currency);
        
        // USD is always 1
        if ($target_currency === 'USD') return 1.0;
        
        // Check if using manual exchange rate
        $use_manual_exchange = get_option('agpw_use_manual_exchange', 'api');
        
        if ($use_manual_exchange === 'manual') {
            $manual_rate = get_option('agpw_manual_exchange_rate', '');
            $default_currency = get_option('agpw_default_currency', 'LKR');
            
            // Return manual rate if currency matches default currency
            if ($target_currency === strtoupper($default_currency) && !empty($manual_rate)) {
                return floatval($manual_rate);
            }
        }
        
        // Try to get from stored API rates
        $rates = get_option('agpw_currency_rates', []);
        if (isset($rates[$target_currency])) {
            return floatval($rates[$target_currency]);
        }
        
        // No rate found
        return 0.0;
    }
    
    /**
     * Calculate gold prices for all karats
     * Formula: (USD Spot Price × Currency Rate × Weight) ÷ Divisor
     */
    public static function calculate_data($spot_usd, $currency_code) {
        if ($spot_usd <= 0) return [];
        
        $rate = self::get_exchange_rate($currency_code);
        if ($rate <= 0) return [];
        
        // Get formula constants from admin settings
        $divisor = floatval(get_option('agpw_oz_divisor', 31));
        if ($divisor <= 0) $divisor = 31;
        
        $multiplier = floatval(get_option('agpw_weight_multiplier', 8));
        
        $deduction = floatval(get_option('agpw_buy_deduction', 2));
        $deduction_currency = get_option('agpw_deduction_currency', 'amount');
        
        // CORRECT FORMULA: (USD Price × Currency Rate × Weight) ÷ Divisor
        // This gives price per weight in selected currency
        $base_24k_sell = ($spot_usd * $rate * $multiplier) / $divisor;
        
        $karats = ['24' => 24.0, '22' => 22.0, '21' => 21.0, '20' => 20.0, '18' => 18.0];
        $data = [];
        
        foreach ($karats as $key => $k_val) {
            // Calculate selling price based on karat purity
            $sell_price = $base_24k_sell * ($k_val / 24.0);
            
            // Calculate buying price based on deduction type
            if ($deduction_currency === 'percentage') {
                // Deduction is a percentage
                $buy_price = $sell_price - ($sell_price * ($deduction / 100));
            } else {
                // Deduction is a fixed amount in the selected currency
                $buy_price = $sell_price - $deduction;
            }
            
            if ($buy_price < 0) $buy_price = 0;
            
            $data[$key] = array(
                'karat_label' => $key . 'K',
                'purity' => round(($k_val/24)*100, 1) . '%',
                'buy' => round($buy_price, 2),
                'sell' => round($sell_price, 2)
            );
        }
        
        $data['meta'] = array(
            'updated' => get_option('agpw_currency_updated', current_time('mysql')),
            'unit_label' => ($multiplier == 8) ? '1 Pavan (8g)' : $multiplier . 'g',
            'currency' => $currency_code,
            'formula' => "({$spot_usd} USD × {$rate} rate × {$multiplier}g) ÷ {$divisor} = " . round($base_24k_sell, 2) . " {$currency_code}"
        );
        
        return $data;
    }
}
