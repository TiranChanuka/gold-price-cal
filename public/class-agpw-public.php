<?php
/**
 * Frontend Display Handler
 * Handles shortcodes and public-facing output
 */

if (!defined('ABSPATH')) exit;

class AGPW_Public {
    
    /**
     * Shortcode handler
     */
    public static function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'type' => 'table', // table, grid, ticker, single
            'karat' => 'all',  // 24, 22, 21, 18, or comma list
            'theme' => 'default', // default, dark, glass, luxury
            'date' => 'no', 
            'title' => '', 
            'date_pos' => 'bottom',
            'currency' => '' // Optional currency override
        ), $atts);
    
        // Determine Currency
        $default_currency = get_option('agpw_default_currency', 'LKR');
        $currency = !empty($atts['currency']) ? strtoupper($atts['currency']) : $default_currency;
        
        // Fetch Data
        $spot_usd = get_option('agpw_spot_price_usd', 0);
        if ($spot_usd <= 0) {
            $data = get_option('agpw_gold_prices', array());
        } else {
            $data = AGPW_Core::calculate_data($spot_usd, $currency);
        }
        
        // Auto-recovery attempt
        if (empty($data) || !isset($data['24'])) {
            AGPW_Core::fetch_and_store_prices();
            $spot_usd = get_option('agpw_spot_price_usd', 0);
            $data = AGPW_Core::calculate_data($spot_usd, $currency);
        }
    
        if (empty($data) || !isset($data['24'])) {
             return '<div class="agpw-error" style="color:red;">Data error. Check API Key.</div>';
        }
    
        $unit = isset($data['meta']['unit_label']) ? $data['meta']['unit_label'] : '';
        $last_update = isset($data['meta']['updated']) ? $data['meta']['updated'] : '';
    
        // Filter Karats
        $karat_list = ['24','22','21','20','18'];
        if ($atts['karat'] !== 'all') {
            $requested = explode(',', $atts['karat']);
            $karat_list = array_intersect($requested, $karat_list);
        }
    
        wp_enqueue_style('agpw-style');
        wp_enqueue_script('agpw-script');
    
        ob_start();
        ?>
        <div class="agpw-wrapper agpw-mode-<?php echo esc_attr($atts['type']); ?> agpw-theme-<?php echo esc_attr($atts['theme']); ?>">
            
            <?php if (!empty($atts['title'])): ?>
                <h2 class="agpw-main-title"><?php echo esc_html($atts['title']); ?></h2>
            <?php endif; ?>
    
            <?php 
            // Date Display Component (Top)
            if ($atts['date'] === 'yes' && $atts['date_pos'] === 'top'): ?>
                 <div class="agpw-clock-container agpw-pos-top">
                    <div class="agpw-time" id="agpw-time-display">--:--</div>
                    <div class="agpw-date" id="agpw-date-display">--/--/----</div>
                 </div>
            <?php endif; ?>
    
            
            <?php if ($atts['type'] === 'single'): 
                // Display vivid single card for the first requested karat
                $k = reset($karat_list);
                if(isset($data[$k])): ?>
                <div class="agpw-single-card">
                    <div class="agpw-single-header">
                        <span class="agpw-k-badge"><?php echo esc_html($data[$k]['karat_label']); ?></span>
                        <span class="agpw-unit"><?php echo esc_html($unit); ?></span>
                    </div>
                    <div class="agpw-single-body">
                        <div class="agpw-price-box">
                            <span class="label">Buying</span>
                            <span class="value agpw-price-buy"><?php echo number_format($data[$k]['buy'], 2); ?></span>
                        </div>
                        <div class="agpw-divider"></div>
                        <div class="agpw-price-box">
                            <span class="label">Selling</span>
                            <span class="value agpw-price-sell"><?php echo number_format($data[$k]['sell'], 2); ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
    
            <?php elseif ($atts['type'] === 'table'): ?>
                <table class="agpw-table">
                    <thead>
                        <tr>
                            <th>Karat</th>
                            <th>Buying (<?php echo esc_html($currency); ?>)</th>
                            <th>Selling (<?php echo esc_html($currency); ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($karat_list as $k): 
                            if(!isset($data[$k])) continue; ?>
                            <tr>
                                <td>
                                    <div class="agpw-k-badge"><?php echo esc_html($data[$k]['karat_label']); ?></div>
                                    <small style="color:#777;"><?php echo esc_html($data[$k]['purity']); ?></small>
                                </td>
                                <td class="agpw-price-buy"><?php echo number_format($data[$k]['buy'], 2); ?></td>
                                <td class="agpw-price-sell"><?php echo number_format($data[$k]['sell'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
    
            <?php elseif ($atts['type'] === 'grid'): ?>
                <div class="agpw-grid">
                    <?php foreach ($karat_list as $k): 
                            if(!isset($data[$k])) continue; ?>
                        <div class="agpw-card">
                            <div class="agpw-card-header" style="display: flex; justify-content: space-between;"><span class="agpw-k-badge"><?php echo esc_html($data[$k]['karat_label']); ?></span><small class="agpw-purity-grid"><?php echo esc_html($data[$k]['purity']); ?></small></div>
                            <div class="agpw-card-body">
                                <div class="agpw-row">
                                    <span>Buying</span>
                                    <strong class="agpw-price-buy"><?php echo number_format($data[$k]['buy'], 2); ?></strong>
                                </div>
                                <div class="agpw-row">
                                    <span>Selling</span>
                                    <strong class="agpw-price-sell"><?php echo number_format($data[$k]['sell'], 2); ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
    
            <?php elseif ($atts['type'] === 'ticker'): ?>
                <div class="agpw-ticker-container">
                    <div class="agpw-ticker-track">
                        <?php 
                        // Render twice for seamless loop
                        for ($i = 0; $i < 2; $i++): 
                            foreach ($karat_list as $k): 
                                if(!isset($data[$k])) continue; ?>
                                <div class="agpw-ticker-item">
                                    <span class="k-label"><?php echo esc_html($data[$k]['karat_label']); ?></span>
                                    <span class="price agpw-price-buy">Buy: <?php echo number_format($data[$k]['buy'], 0); ?></span>
                                    <span class="price agpw-price-sell">Sell: <?php echo number_format($data[$k]['sell'], 0); ?></span>
                                </div>
                            <?php endforeach; 
                        endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
    
    
            <?php 
            // Date Display Component (Bottom)
            if ($atts['date'] === 'yes' && $atts['date_pos'] === 'bottom'): ?>
                 <div class="agpw-clock-container agpw-pos-bottom">
                    <div class="agpw-time" id="agpw-time-display">-- : --</div>
                    <div class="agpw-date" id="agpw-date-display">-- / -- / ----</div>
                 </div>
            <?php endif; ?>
    
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Enqueue assets
     */
    public static function enqueue_assets() {
        wp_register_style('agpw-style', plugins_url('../assets/css/agpw-style.css', __FILE__), array(), '2.3');
        wp_register_script('agpw-script', plugins_url('../assets/js/agpw-script.js', __FILE__), array('jquery'), '2.3', true);
        
        // Inject Settings to JS
        $vol_range = get_option('agpw_volatility_range', '2000');
        $vol_interval = get_option('agpw_volatility_interval', '2000');
        
        wp_localize_script('agpw-script', 'agpw_config', array(
            'volatility_range' => floatval($vol_range),
            'volatility_interval' => floatval($vol_interval),
            'timezone' => get_option('agpw_timezone', 'Asia/Colombo')
        ));
        
        wp_enqueue_script('agpw-script');
    }
}
