<?php
/**
 * Plugin Name: UK Mortgage Calculator Pro
 * Plugin URI: https://yourwebsite.com
 * Description: Professional UK mortgage calculators for Elementor with comprehensive calculations, user data collection, and email functionality
 * Version: 2.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: uk-mortgage-calc
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Elementor tested up to: 3.18
 * Elementor Pro tested up to: 3.18
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('UK_MORTGAGE_CALC_VERSION', '2.0.0');
define('UK_MORTGAGE_CALC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UK_MORTGAGE_CALC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('UK_MORTGAGE_CALC_PLUGIN_FILE', __FILE__);

/**
 * Main Plugin Class
 */
final class UK_Mortgage_Calculator {
    
    private static $_instance = null;
    private $database_handler = null;
    private $email_handler = null;
    private $admin_handler = null;
    
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('plugins_loaded', [$this, 'init'], 0);
        
        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Register uninstall hook
        register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall']);
    }
    
    public function init() {
        // Load text domain
        load_plugin_textdomain('uk-mortgage-calc', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Always include the core files
        $this->include_core_files();
        
        // Initialize core handlers
        $this->init_handlers();
        
        // Setup basic functionality (AJAX, scripts)
        $this->setup_basic_functionality();
        
        // Initialize admin functionality
        if (is_admin()) {
            $this->init_admin();
        }
        
        // Check if Elementor is installed and activated
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', [$this, 'admin_notice_missing_main_plugin']);
            return;
        }
        
        // Check minimum Elementor version
        if (!version_compare(ELEMENTOR_VERSION, '3.0.0', '>=')) {
            add_action('admin_notices', [$this, 'admin_notice_minimum_elementor_version']);
            return;
        }
        
        // Check for minimum PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', [$this, 'admin_notice_minimum_php_version']);
            return;
        }
        
        // Initialize Elementor integration
        add_action('elementor/init', [$this, 'elementor_init']);
    }
    
    private function include_core_files() {
        // Core calculation engine
        require_once UK_MORTGAGE_CALC_PLUGIN_PATH . 'includes/class-calculator-engine.php';
        
        // API handler
        require_once UK_MORTGAGE_CALC_PLUGIN_PATH . 'includes/class-api-handler.php';
        
        // Admin functionality
        if (is_admin()) {
            require_once UK_MORTGAGE_CALC_PLUGIN_PATH . 'includes/class-admin-settings.php';
            require_once UK_MORTGAGE_CALC_PLUGIN_PATH . 'includes/class-admin-ajax.php';
        }
    }
    
    private function init_handlers() {
        try {
            // Initialize API handler
            if (class_exists('UK_Mortgage_API_Handler')) {
                $this->api_handler = new UK_Mortgage_API_Handler();
            }
            
            // Initialize admin handlers
            if (is_admin()) {
                if (class_exists('UK_Mortgage_Calculator_Admin')) {
                    $this->admin_handler = new UK_Mortgage_Calculator_Admin();
                }
                if (class_exists('UK_Mortgage_Calculator_Ajax')) {
                    new UK_Mortgage_Calculator_Ajax();
                }
            }
        } catch (Exception $e) {
            error_log('UK Mortgage Calculator: Failed to initialize handlers - ' . $e->getMessage());
        }
    }
    
    private function setup_basic_functionality() {
        // Register scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('elementor/frontend/after_enqueue_styles', [$this, 'enqueue_elementor_styles']);
        
        // AJAX handlers for frontend
        add_action('wp_ajax_calculate_mortgage', [$this, 'ajax_calculate_mortgage']);
        add_action('wp_ajax_nopriv_calculate_mortgage', [$this, 'ajax_calculate_mortgage']);
        add_action('wp_ajax_generate_pdf_report', [$this, 'ajax_generate_pdf_report']);
        add_action('wp_ajax_nopriv_generate_pdf_report', [$this, 'ajax_generate_pdf_report']);
        add_action('wp_ajax_send_email_report', [$this, 'ajax_send_email_report']);
        add_action('wp_ajax_nopriv_send_email_report', [$this, 'ajax_send_email_report']);
        
        // Add shortcode support
        add_shortcode('uk_mortgage_calculator', [$this, 'shortcode_handler']);
    }
    
    private function init_admin() {
        // Admin-specific initialization
        add_action('admin_init', [$this, 'check_database_version']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_plugin_action_links']);
    }
    
    public function elementor_init() {
        // Include Elementor-dependent files
        $this->include_elementor_files();
        
        // Add element category
        add_action('elementor/elements/categories_registered', [$this, 'add_elementor_widget_categories']);
        
        // Register widgets
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
    }
    
    private function include_elementor_files() {
        // Include enhanced base widget class first
        require_once UK_MORTGAGE_CALC_PLUGIN_PATH . 'includes/class-base-widget.php';
        
        // Then include individual widget files
        $widget_files = [
            'widgets/affordability-widget.php',
            'widgets/repayment-widget.php',
            'widgets/remortgage-widget.php',
            'widgets/valuation-widget.php'
        ];
        
        foreach ($widget_files as $file) {
            $file_path = UK_MORTGAGE_CALC_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log("UK Mortgage Calculator: Missing widget file: $file");
            }
        }
    }
    
    public function add_elementor_widget_categories($elements_manager) {
        $elements_manager->add_category(
            'uk-mortgage-calculators',
            [
                'title' => esc_html__('UK Mortgage Calculators', 'uk-mortgage-calc'),
                'icon' => 'eicon-calculator',
            ]
        );
    }
    
    public function register_widgets($widgets_manager) {
        $widgets = [
            'UK_Mortgage_Affordability_Widget',
            'UK_Mortgage_Repayment_Widget',
            'UK_Mortgage_Remortgage_Widget',
            'UK_Mortgage_Valuation_Widget'
        ];
        
        foreach ($widgets as $widget_class) {
            if (class_exists($widget_class)) {
                try {
                    $widgets_manager->register(new $widget_class());
                } catch (Exception $e) {
                    error_log("UK Mortgage Calculator: Failed to register $widget_class - " . $e->getMessage());
                }
            }
        }
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style(
            'uk-mortgage-calc-css',
            UK_MORTGAGE_CALC_PLUGIN_URL . 'assets/css/mortgage-calculator.css',
            [],
            UK_MORTGAGE_CALC_VERSION
        );
        
        wp_enqueue_script(
            'uk-mortgage-calc-js',
            UK_MORTGAGE_CALC_PLUGIN_URL . 'assets/js/mortgage-calculator.js',
            ['jquery'],
            UK_MORTGAGE_CALC_VERSION,
            true
        );
        
        $localization_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('uk_mortgage_nonce'),
            'currency_symbol' => '£',
            'messages' => [
                'calculating' => __('Calculating...', 'uk-mortgage-calc'),
                'error' => __('An error occurred. Please try again.', 'uk-mortgage-calc'),
                'invalid_input' => __('Please check your inputs and try again.', 'uk-mortgage-calc'),
                'email_sent' => __('Results sent to your email!', 'uk-mortgage-calc'),
                'email_required' => __('Please enter your email address.', 'uk-mortgage-calc'),
                'consent_required' => __('Please agree to receive emails.', 'uk-mortgage-calc')
            ]
        ];
        
        wp_localize_script('uk-mortgage-calc-js', 'ukMortgageAjax', $localization_data);
    }
    
    public function enqueue_elementor_styles() {
        wp_enqueue_style(
            'uk-mortgage-calc-elementor',
            UK_MORTGAGE_CALC_PLUGIN_URL . 'assets/css/elementor-overrides.css',
            [],
            UK_MORTGAGE_CALC_VERSION
        );
    }
    
    public function ajax_calculate_mortgage() {
        check_ajax_referer('uk_mortgage_nonce', 'nonce');
        
        $calculator_type = sanitize_text_field($_POST['calculator_type'] ?? '');
        $data = $_POST['data'] ?? [];
        
        if (empty($calculator_type)) {
            wp_send_json_error(__('Calculator type is required.', 'uk-mortgage-calc'));
        }
        
        try {
            $calculator = new UK_Mortgage_Calculator_Engine();
            $result = $calculator->calculate($calculator_type, $data);
            
            // Save calculation data if user provided email
            $user_email = sanitize_email($data['user_email'] ?? '');
            if ($user_email) {
                $this->save_calculation_data($calculator_type, $data, $result, $user_email);
            }
            
            wp_send_json_success($result);
        } catch (Exception $e) {
            error_log('UK Mortgage Calculator Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }
    
    public function ajax_generate_pdf_report() {
        check_ajax_referer('uk_mortgage_nonce', 'nonce');
        wp_send_json_error(__('PDF generation feature coming soon.', 'uk-mortgage-calc'));
    }
    
    public function ajax_send_email_report() {
        check_ajax_referer('uk_mortgage_nonce', 'nonce');
        
        $user_email = sanitize_email($_POST['user_email'] ?? '');
        $user_name = sanitize_text_field($_POST['user_name'] ?? '');
        $calculator_type = sanitize_text_field($_POST['calculator_type'] ?? '');
        $input_data = $_POST['input_data'] ?? [];
        $result_data = $_POST['result_data'] ?? [];
        $consent = isset($_POST['email_consent']) && $_POST['email_consent'] === '1';
        
        // Validate required fields
        if (empty($user_email)) {
            wp_send_json_error(__('Email address is required.', 'uk-mortgage-calc'));
        }
        
        $settings = get_option('uk_mortgage_settings', []);
        if (!empty($settings['require_email_consent']) && !$consent) {
            wp_send_json_error(__('Please agree to receive emails.', 'uk-mortgage-calc'));
        }
        
        try {
            $sent = $this->send_results_email($user_email, $user_name, $calculator_type, $input_data, $result_data);
            
            if ($sent) {
                wp_send_json_success(__('Results sent to your email!', 'uk-mortgage-calc'));
            } else {
                wp_send_json_error(__('Failed to send email. Please try again.', 'uk-mortgage-calc'));
            }
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    private function save_calculation_data($calculator_type, $input_data, $result_data, $user_email) {
        global $wpdb;
        
        $settings = get_option('uk_mortgage_settings', []);
        
        // Check if data collection is enabled
        if (empty($settings['collect_user_data'])) {
            return false;
        }
        
        $table_name = $wpdb->prefix . 'uk_mortgage_calculations';
        
        $insert_data = [
            'calculator_type' => sanitize_text_field($calculator_type),
            'user_email' => $user_email ? sanitize_email($user_email) : null,
            'user_name' => sanitize_text_field($input_data['user_name'] ?? ''),
            'input_data' => wp_json_encode($input_data),
            'result_data' => wp_json_encode($result_data),
            'ip_address' => $this->get_user_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'created_at' => current_time('mysql')
        ];
        
        return $wpdb->insert($table_name, $insert_data);
    }
    
    private function send_results_email($user_email, $user_name, $calculator_type, $input_data, $result_data) {
        $settings = get_option('uk_mortgage_settings', []);
        
        if (empty($settings['enable_email_notifications'])) {
            return false;
        }
        
        if (!is_email($user_email)) {
            return false;
        }
        
        $subject = $this->get_email_subject($calculator_type);
        $message = $this->get_email_message($user_name, $calculator_type, $input_data, $result_data);
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];
        
        // Send to user
        $sent = wp_mail($user_email, $subject, $message, $headers);
        
        // Send copy to admin if configured
        if (!empty($settings['admin_email']) && $settings['admin_email'] !== $user_email) {
            $admin_subject = '[Copy] ' . $subject;
            wp_mail($settings['admin_email'], $admin_subject, $message, $headers);
        }
        
        return $sent;
    }
    
    private function get_email_subject($calculator_type) {
        $settings = get_option('uk_mortgage_settings', []);
        $subject = $settings['email_template_subject'] ?? 'Your Mortgage Calculation Results';
        
        $replacements = [
            '{calculator_type}' => ucfirst(str_replace('-', ' ', $calculator_type))
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $subject);
    }
    
    private function get_email_message($user_name, $calculator_type, $input_data, $result_data) {
        $settings = get_option('uk_mortgage_settings', []);
        $template = $settings['email_template_content'] ?? $this->get_default_email_template();
        
        $replacements = [
            '{user_name}' => $user_name ?: 'Valued Customer',
            '{calculator_type}' => ucfirst(str_replace('-', ' ', $calculator_type)),
            '{results}' => $this->format_results_for_email($calculator_type, $result_data)
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
    
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
    
    private function get_default_email_template() {
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
    
    public function shortcode_handler($atts) {
        $atts = shortcode_atts([
            'type' => 'affordability',
            'title' => '',
            'description' => '',
            'auto_calculate' => 'yes'
        ], $atts);
        
        // This would render a basic calculator without Elementor
        // For now, return a message directing users to use Elementor
        return '<div class="uk-mortgage-shortcode-notice">' . 
               __('Please use Elementor widgets for the full calculator experience.', 'uk-mortgage-calc') . 
               '</div>';
    }
    
    public function check_database_version() {
        $current_version = get_option('uk_mortgage_calc_db_version', '0');
        
        if (version_compare($current_version, '1.0', '<')) {
            $this->create_database_tables();
            update_option('uk_mortgage_calc_db_version', '1.0');
        }
    }
    
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=uk-mortgage-calculator') . '">' . 
                        __('Settings', 'uk-mortgage-calc') . '</a>';
        array_unshift($links, $settings_link);
        
        return $links;
    }
    
    public function activate() {
        // Create database tables
        $this->create_database_tables();
        
        // Set default options
        $default_settings = [
            'collect_user_data' => true,
            'enable_email_notifications' => true,
            'require_email_consent' => true,
            'admin_email' => get_option('admin_email'),
            'email_template_subject' => 'Your Mortgage Calculation Results',
            'gdpr_compliance' => 'I consent to receiving my calculation results via email and understand my data will be processed according to the privacy policy.'
        ];
        
        add_option('uk_mortgage_settings', $default_settings);
        update_option('uk_mortgage_calc_version', UK_MORTGAGE_CALC_VERSION);
        update_option('uk_mortgage_calc_db_version', '1.0');
        
        // Clear any cached data
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Clear Elementor cache if available
        if (class_exists('\Elementor\Plugin')) {
            \Elementor\Plugin::$instance->files_manager->clear_cache();
        }
    }
    
    private function create_database_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'uk_mortgage_calculations';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
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
    }
    
    public function deactivate() {
        // Clear cached data
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Clear scheduled events if any
        wp_clear_scheduled_hook('uk_mortgage_cleanup_old_data');
    }
    
    public static function uninstall() {
        // Remove options
        delete_option('uk_mortgage_settings');
        delete_option('uk_mortgage_calc_version');
        delete_option('uk_mortgage_calc_db_version');
        
        // Remove database tables
        global $wpdb;
        $table_name = $wpdb->prefix . 'uk_mortgage_calculations';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        // Clear any cached data
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
    
    public function admin_notice_missing_main_plugin() {
        if (isset($_GET['activate'])) unset($_GET['activate']);
        
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" to be installed and activated for full functionality.', 'uk-mortgage-calc'),
            '<strong>' . esc_html__('UK Mortgage Calculator Pro', 'uk-mortgage-calc') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'uk-mortgage-calc') . '</strong>'
        );
        
        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
    
    public function admin_notice_minimum_elementor_version() {
        if (isset($_GET['activate'])) unset($_GET['activate']);
        
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'uk-mortgage-calc'),
            '<strong>' . esc_html__('UK Mortgage Calculator Pro', 'uk-mortgage-calc') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'uk-mortgage-calc') . '</strong>',
            '3.0.0'
        );
        
        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
    
    public function admin_notice_minimum_php_version() {
        if (isset($_GET['activate'])) unset($_GET['activate']);
        
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'uk-mortgage-calc'),
            '<strong>' . esc_html__('UK Mortgage Calculator Pro', 'uk-mortgage-calc') . '</strong>',
            '<strong>' . esc_html__('PHP', 'uk-mortgage-calc') . '</strong>',
            '7.4'
        );
        
        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
}

// Initialize the plugin
UK_Mortgage_Calculator::instance();