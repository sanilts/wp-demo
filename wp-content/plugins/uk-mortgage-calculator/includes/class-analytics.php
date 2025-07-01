<?php

// =============================================================================
// FILE: includes/class-analytics.php
// =============================================================================

/**
 * Analytics and tracking functionality
 */
class UK_Mortgage_Analytics {
    
    public function track_calculation($calculator_type, $inputs, $results, $user_data = []) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'uk_mortgage_calculations';
        
        $data = [
            'calculator_type' => $calculator_type,
            'inputs' => json_encode($inputs),
            'results' => json_encode($results),
            'user_ip' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => current_time('mysql'),
            'page_url' => $_SERVER['HTTP_REFERER'] ?? '',
        ];
        
        $wpdb->insert($table_name, $data);
        
        // Also send to Google Analytics if configured
        $this->send_to_google_analytics($calculator_type, $inputs, $results);
    }
    
    public function get_calculation_stats($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'uk_mortgage_calculations';
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                calculator_type,
                COUNT(*) as count,
                AVG(JSON_EXTRACT(results, '$.max_borrowing')) as avg_borrowing,
                AVG(JSON_EXTRACT(results, '$.monthly_payment')) as avg_payment
            FROM {$table_name} 
            WHERE timestamp >= %s 
            GROUP BY calculator_type
        ", $date_from));
        
        return $stats;
    }
    
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '';
        }
    }
    
    private function send_to_google_analytics($calculator_type, $inputs, $results) {
        $ga_tracking_id = get_option('uk_mortgage_ga_tracking_id');
        
        if (empty($ga_tracking_id)) {
            return;
        }
        
        // Send event to Google Analytics Measurement Protocol
        $data = [
            'v' => '1',
            'tid' => $ga_tracking_id,
            'cid' => $this->get_client_id(),
            't' => 'event',
            'ec' => 'Mortgage Calculator',
            'ea' => ucfirst($calculator_type) . ' Calculation',
            'el' => json_encode($inputs),
            'ev' => intval($results['max_borrowing'] ?? $results['monthly_payment'] ?? 0)
        ];
        
        wp_remote_post('https://www.google-analytics.com/collect', [
            'body' => http_build_query($data),
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded']
        ]);
    }
    
    private function get_client_id() {
        return wp_generate_uuid4();
    }
    
    public function create_analytics_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'uk_mortgage_calculations';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            calculator_type varchar(50) NOT NULL,
            inputs longtext NOT NULL,
            results longtext NOT NULL,
            user_ip varchar(45),
            user_agent text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            page_url text,
            PRIMARY KEY (id),
            KEY calculator_type (calculator_type),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
