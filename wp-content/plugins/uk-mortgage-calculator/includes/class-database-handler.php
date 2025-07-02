<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Handler Class
 */
class UK_Mortgage_Calculator_Database {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'uk_mortgage_calculations';
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            calculator_type varchar(50) NOT NULL,
            user_email varchar(100) DEFAULT NULL,
            user_name varchar(100) DEFAULT NULL,
            input_data longtext NOT NULL,
            result_data longtext NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY calculator_type (calculator_type),
            KEY created_at (created_at),
            KEY user_email (user_email)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Add database version option
        add_option('uk_mortgage_calc_db_version', '1.0');
    }
    
    /**
     * Save calculation data
     */
    public function save_calculation($calculator_type, $input_data, $result_data, $user_email = null, $user_name = null) {
        global $wpdb;
        
        $settings = get_option('uk_mortgage_settings', []);
        
        // Check if data collection is enabled
        if (empty($settings['collect_user_data'])) {
            return false;
        }
        
        $insert_data = [
            'calculator_type' => sanitize_text_field($calculator_type),
            'user_email' => $user_email ? sanitize_email($user_email) : null,
            'user_name' => $user_name ? sanitize_text_field($user_name) : null,
            'input_data' => wp_json_encode($input_data),
            'result_data' => wp_json_encode($result_data),
            'ip_address' => $this->get_user_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'created_at' => current_time('mysql')
        ];
        
        $result = $wpdb->insert($this->table_name, $insert_data);
        
        if ($result === false) {
            error_log('UK Mortgage Calculator: Failed to save calculation data - ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get calculations by criteria
     */
    public function get_calculations($args = []) {
        global $wpdb;
        
        $defaults = [
            'limit' => 20,
            'offset' => 0,
            'calculator_type' => null,
            'date_from' => null,
            'date_to' => null,
            'user_email' => null
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where_conditions = ['1=1'];
        $where_values = [];
        
        if ($args['calculator_type']) {
            $where_conditions[] = 'calculator_type = %s';
            $where_values[] = $args['calculator_type'];
        }
        
        if ($args['date_from']) {
            $where_conditions[] = 'created_at >= %s';
            $where_values[] = $args['date_from'];
        }
        
        if ($args['date_to']) {
            $where_conditions[] = 'created_at <= %s';
            $where_values[] = $args['date_to'];
        }
        
        if ($args['user_email']) {
            $where_conditions[] = 'user_email = %s';
            $where_values[] = $args['user_email'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT * FROM $this->table_name WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];
        
        return $wpdb->get_results($wpdb->prepare($sql, $where_values));
    }
    
    /**
     * Get calculation statistics
     */
    public function get_statistics() {
        global $wpdb;
        
        $stats = [];
        
        // Total calculations
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM $this->table_name");
        
        // Today's calculations
        $stats['today'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name WHERE DATE(created_at) = %s",
            current_time('Y-m-d')
        ));
        
        // This week's calculations
        $stats['week'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name WHERE created_at >= %s",
            date('Y-m-d', strtotime('-7 days'))
        ));
        
        // By calculator type
        $stats['by_type'] = $wpdb->get_results(
            "SELECT calculator_type, COUNT(*) as count FROM $this->table_name GROUP BY calculator_type"
        );
        
        return $stats;
    }
    
    /**
     * Delete calculation
     */
    public function delete_calculation($id) {
        global $wpdb;
        
        return $wpdb->delete($this->table_name, ['id' => intval($id)], ['%d']);
    }
    
    /**
     * Export calculations to CSV
     */
    public function export_to_csv($args = []) {
        $calculations = $this->get_calculations(array_merge($args, ['limit' => 10000]));
        
        if (empty($calculations)) {
            return false;
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
        foreach ($calculations as $calc) {
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
        
        return $upload_dir['url'] . '/' . $filename;
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP']);
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
            return sanitize_text_field($_SERVER['HTTP_X_REAL_IP']);
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            return sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        return '';
    }
}

/**
 * Email Handler Class
 */
class UK_Mortgage_Calculator_Email {
    
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('uk_mortgage_settings', []);
    }
    
    /**
     * Send calculation results to user
     */
    public function send_results_email($user_email, $user_name, $calculator_type, $input_data, $result_data) {
        if (empty($this->settings['enable_email_notifications'])) {
            return false;
        }
        
        if (!is_email($user_email)) {
            return false;
        }
        
        $subject = $this->get_email_subject($calculator_type);
        $message = $this->get_email_message($user_name, $calculator_type, $input_data, $result_data);
        $headers = $this->get_email_headers();
        
        // Send to user
        $sent = wp_mail($user_email, $subject, $message, $headers);
        
        // Send copy to admin if configured
        if (!empty($this->settings['admin_email']) && $this->settings['admin_email'] !== $user_email) {
            $admin_subject = '[Copy] ' . $subject;
            wp_mail($this->settings['admin_email'], $admin_subject, $message, $headers);
        }
        
        return $sent;
    }
    
    /**
     * Generate PDF report
     */
    public function generate_pdf_report($calculator_type, $input_data, $result_data) {
        // This would integrate with a PDF library like TCPDF or FPDF
        // For now, return a placeholder
        return [
            'success' => false,
            'message' => __('PDF generation feature coming soon.', 'uk-mortgage-calc')
        ];
    }
    
    /**
     * Get email subject with placeholders replaced
     */
    private function get_email_subject($calculator_type) {
        $subject = $this->settings['email_template_subject'] ?? 'Your Mortgage Calculation Results';
        
        $replacements = [
            '{calculator_type}' => ucfirst(str_replace('-', ' ', $calculator_type))
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $subject);
    }
    
    /**
     * Get email message with placeholders replaced
     */
    private function get_email_message($user_name, $calculator_type, $input_data, $result_data) {
        $template = $this->settings['email_template_content'] ?? $this->get_default_template();
        
        $replacements = [
            '{user_name}' => $user_name ?: 'Valued Customer',
            '{calculator_type}' => ucfirst(str_replace('-', ' ', $calculator_type)),
            '{results}' => $this->format_results_for_email($calculator_type, $result_data)
        ];
        
        $message = str_replace(array_keys($replacements), array_values($replacements), $template);
        
        return $message;
    }
    
    /**
     * Get email headers
     */
    private function get_email_headers() {
        $headers = [];
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>';
        
        return $headers;
    }
    
    /**
     * Format results for email display
     */
    private function format_results_for_email($calculator_type, $result_data) {
        $currency = '£';
        $html = '<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">';
        
        switch ($calculator_type) {
            case 'affordability':
                $html .= '<h3>Your Affordability Results</h3>';
                $html .= '<p><strong>Maximum Borrowing:</strong> ' . $currency . number_format($result_data['max_borrowing'] ?? 0) . '</p>';
                $html .= '<p><strong>Maximum Property Value:</strong> ' . $currency . number_format($result_data['max_property_value'] ?? 0) . '</p>';
                $html .= '<p><strong>Monthly Budget:</strong> ' . $currency . number_format($result_data['monthly_budget'] ?? 0) . '</p>';
                break;
                
            case 'repayment':
                $html .= '<h3>Your Monthly Repayment</h3>';
                $html .= '<p><strong>Monthly Payment:</strong> ' . $currency . number_format($result_data['monthly_payment'] ?? 0) . '</p>';
                $html .= '<p><strong>Total Interest:</strong> ' . $currency . number_format($result_data['total_interest'] ?? 0) . '</p>';
                break;
                
            case 'remortgage':
                $html .= '<h3>Your Remortgage Analysis</h3>';
                $html .= '<p><strong>Monthly Saving:</strong> ' . $currency . number_format($result_data['monthly_saving'] ?? 0) . '</p>';
                $html .= '<p><strong>Annual Saving:</strong> ' . $currency . number_format($result_data['annual_saving'] ?? 0) . '</p>';
                break;
                
            case 'valuation':
                $html .= '<h3>Your Property Valuation</h3>';
                $html .= '<p><strong>Estimated Value:</strong> ' . $currency . number_format($result_data['estimated_value'] ?? 0) . '</p>';
                break;
        }
        
        $html .= '<p style="font-size: 12px; color: #666; margin-top: 20px;">This is an automated estimate. Please consult with a qualified mortgage advisor for personalized advice.</p>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get default email template
     */
    private function get_default_template() {
        return '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <h2>Hello {user_name},</h2>
            
            <p>Thank you for using our {calculator_type} calculator. Please find your calculation results below:</p>
            
            {results}
            
            <p>If you have any questions about these results or would like to discuss your mortgage options further, please don\'t hesitate to contact us.</p>
            
            <p>Best regards,<br>
            The Mortgage Team</p>
            
            <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
            <p style="font-size: 12px; color: #666;">
                This email was sent from ' . get_bloginfo('name') . '. 
                These calculations are estimates only and should not be considered as financial advice.
            </p>
        </body>
        </html>';
    }
}

/**
 * AJAX Handlers for Admin
 */
class UK_Mortgage_Calculator_Ajax {
    
    public function __construct() {
        add_action('wp_ajax_uk_mortgage_delete_entry', [$this, 'delete_entry']);
        add_action('wp_ajax_uk_mortgage_export_data', [$this, 'export_data']);
        add_action('wp_ajax_uk_mortgage_get_entry_details', [$this, 'get_entry_details']);
        add_action('wp_ajax_uk_mortgage_clear_all_data', [$this, 'clear_all_data']);
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
        
        $db = new UK_Mortgage_Calculator_Database();
        $result = $db->delete_calculation($entry_id);
        
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
        
        $db = new UK_Mortgage_Calculator_Database();
        $file_url = $db->export_to_csv();
        
        if ($file_url) {
            wp_send_json_success(['download_url' => $file_url]);
        } else {
            wp_send_json_error(__('Failed to export data.', 'uk-mortgage-calc'));
        }
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
}