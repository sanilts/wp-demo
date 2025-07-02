<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX Handlers for Admin Functions
 */
class UK_Mortgage_Calculator_Ajax {
    
    public function __construct() {
        add_action('wp_ajax_uk_mortgage_delete_entry', [$this, 'delete_entry']);
        add_action('wp_ajax_uk_mortgage_export_data', [$this, 'export_data']);
        add_action('wp_ajax_uk_mortgage_get_entry_details', [$this, 'get_entry_details']);
        add_action('wp_ajax_uk_mortgage_clear_all_data', [$this, 'clear_all_data']);
        add_action('wp_ajax_uk_mortgage_test_api', [$this, 'test_api_connection']);
    }
    
    public function delete_entry() {
        check_ajax_referer('uk_mortgage_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'uk-mortgage-calc'));
        }
        
        $entry_id = intval($_POST['entry_id'] ?? 0);
        if (!$entry_id) {
            wp_send_json_error(__('Invalid entry ID.', 'uk-mortgage-calc'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'uk_mortgage_calculations';
        
        $result = $wpdb->delete($table_name, ['id' => $entry_id], ['%d']);
        
        if ($result) {
            wp_send_json_success(__('Entry deleted successfully.', 'uk-mortgage-calc'));
        } else {
            wp_send_json_error(__('Failed to delete entry.', 'uk-mortgage-calc'));
        }
    }
    
    public function export_data() {
        check_ajax_referer('uk_mortgage_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'uk-mortgage-calc'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'uk_mortgage_calculations';
        
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 10000");
        
        if (empty($results)) {
            wp_send_json_error(__('No data to export.', 'uk-mortgage-calc'));
        }
        
        $filename = 'mortgage-calculations-' . date('Y-m-d') . '.csv';
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        $file = fopen($file_path, 'w');
        
        // CSV headers
        fputcsv($file, [
            'ID',
            'Type',
            'User Email',
            'User Name',
            'Input Data',
            'Result Data',
            'IP Address',
            'Date'
        ]);
        
        // CSV data
        foreach ($results as $calc) {
            fputcsv($file, [
                $calc->id,
                $calc->calculator_type,
                $calc->user_email,
                $calc->user_name,
                $calc->input_data,
                $calc->result_data,
                $calc->ip_address,
                $calc->created_at
            ]);
        }
        
        fclose($file);
        
        $file_url = $upload_dir['url'] . '/' . $filename;
        wp_send_json_success(['download_url' => $file_url]);
    }
    
    public function get_entry_details() {
        check_ajax_referer('uk_mortgage_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'uk-mortgage-calc'));
        }
        
        $entry_id = intval($_POST['entry_id'] ?? 0);
        if (!$entry_id) {
            wp_send_json_error(__('Invalid entry ID.', 'uk-mortgage-calc'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'uk_mortgage_calculations';
        
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $entry_id
        ));
        
        if ($entry) {
            $entry->input_data = json_decode($entry->input_data, true);
            $entry->result_data = json_decode($entry->result_data, true);
            wp_send_json_success($entry);
        } else {
            wp_send_json_error(__('Entry not found.', 'uk-mortgage-calc'));
        }
    }
    
    public function clear_all_data() {
        check_ajax_referer('uk_mortgage_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'uk-mortgage-calc'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'uk_mortgage_calculations';
        
        $result = $wpdb->query("TRUNCATE TABLE $table_name");
        
        if ($result !== false) {
            wp_send_json_success(__('All data cleared successfully.', 'uk-mortgage-calc'));
        } else {
            wp_send_json_error(__('Failed to clear data.', 'uk-mortgage-calc'));
        }
    }
    
    public function test_api_connection() {
        check_ajax_referer('uk_mortgage_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'uk-mortgage-calc'));
        }
        
        $api_type = sanitize_text_field($_POST['api_type'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (!$api_type || !$api_key) {
            wp_send_json_error(__('Missing API type or key.', 'uk-mortgage-calc'));
        }
        
        // Test API connection based on type
        $result = $this->perform_api_test($api_type, $api_key);
        
        if ($result) {
            wp_send_json_success(__('API connection successful.', 'uk-mortgage-calc'));
        } else {
            wp_send_json_error(__('API connection failed.', 'uk-mortgage-calc'));
        }
    }
    
    private function perform_api_test($api_type, $api_key) {
        switch ($api_type) {
            case 'zoopla':
                return $this->test_zoopla_api($api_key);
            case 'rightmove':
                return $this->test_rightmove_api($api_key);
            case 'postcode':
                return $this->test_postcode_api($api_key);
            default:
                return false;
        }
    }
    
    private function test_zoopla_api($api_key) {
        $api_url = 'https://api.zoopla.co.uk/api/v1/area_stats';
        $params = [
            'api_key' => $api_key,
            'postcode' => 'SW1A 1AA'
        ];
        
        $response = wp_remote_get(add_query_arg($params, $api_url), [
            'timeout' => 10
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code === 200;
    }
    
    private function test_rightmove_api($api_key) {
        // Rightmove doesn't have a public API
        // This is a placeholder for commercial API testing
        return false;
    }
    
    private function test_postcode_api($api_key) {
        // Test with postcodes.io (free API)
        $response = wp_remote_get('https://api.postcodes.io/postcodes/SW1A1AA/validate', [
            'timeout' => 5
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code === 200;
    }
}