<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple API Handler for External Integrations
 */
class UK_Mortgage_API_Handler {
    
    private $settings;
    private $cache_duration = 3600; // 1 hour
    
    public function __construct() {
        $this->settings = get_option('uk_mortgage_settings', []);
    }
    
    /**
     * Get current interest rates
     */
    public function get_current_interest_rates() {
        $cache_key = 'uk_mortgage_interest_rates';
        $cached_rates = get_transient($cache_key);
        
        if ($cached_rates !== false) {
            return $cached_rates;
        }
        
        // Default rates - in production, these would come from APIs
        $rates = [
            'typical_mortgage_rate' => 4.5,
            'base_rate' => 5.25,
            'fixed_2_year' => 4.2,
            'fixed_5_year' => 4.8,
            'variable_rate' => 5.1,
            'tracker_rate' => 4.9,
            'first_time_buyer' => 4.3,
            'buy_to_let' => 5.2
        ];
        
        // Cache for 1 hour
        set_transient($cache_key, $rates, $this->cache_duration);
        
        return $rates;
    }
    
    /**
     * Get property valuation from APIs
     */
    public function get_property_valuation($postcode, $property_type, $bedrooms) {
        // For now, return null to use manual calculation
        // In production, this would call actual APIs
        return null;
    }
    
    /**
     * Get regional data and market trends
     */
    public function get_regional_data($postcode) {
        // Basic regional data
        $postcode_area = strtoupper(substr(str_replace(' ', '', $postcode), 0, 2));
        
        // Mock regional data based on postcode area
        $london_areas = ['SW', 'W1', 'WC', 'EC', 'E1', 'N1', 'NW', 'SE'];
        
        if (in_array($postcode_area, $london_areas)) {
            return [
                'price_multiplier' => 1.6,
                'trends' => [
                    'price_change_12m' => 5.2,
                    'sales_volume_change' => -8.1,
                    'time_to_sell_change' => 12
                ],
                'market_activity' => 'high',
                'average_time_to_sell' => 32
            ];
        }
        
        return [
            'price_multiplier' => 1.0,
            'trends' => [
                'price_change_12m' => 2.1,
                'sales_volume_change' => -3.2,
                'time_to_sell_change' => 8
            ],
            'market_activity' => 'moderate',
            'average_time_to_sell' => 45
        ];
    }
    
    /**
     * Test API connection
     */
    public function test_api_connection($api_type) {
        // Basic API testing
        switch ($api_type) {
            case 'postcode':
                return $this->test_postcode_connection();
            default:
                return false;
        }
    }
    
    private function test_postcode_connection() {
        $response = wp_remote_get('https://api.postcodes.io/postcodes/SW1A1AA/validate', [
            'timeout' => 5
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code === 200;
    }
    
    /**
     * Clear all cached API data
     */
    public function clear_cache() {
        global $wpdb;
        
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_uk_mortgage_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_uk_mortgage_%'");
        
        return true;
    }
}