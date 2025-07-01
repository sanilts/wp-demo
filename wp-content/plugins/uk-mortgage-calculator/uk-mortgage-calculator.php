<?php
/**
 * Plugin Name: UK Mortgage Calculator Pro
 * Plugin URI: https://yourwebsite.com
 * Description: Professional UK mortgage calculators for Elementor with comprehensive calculations
 * Version: 1.0.7
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
define('UK_MORTGAGE_CALC_VERSION', '1.0.6');
define('UK_MORTGAGE_CALC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UK_MORTGAGE_CALC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('UK_MORTGAGE_CALC_PLUGIN_FILE', __FILE__);

/**
 * Main Plugin Class
 */
final class UK_Mortgage_Calculator {
    
    private static $_instance = null;
    
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
        
        // Register activation hook
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }
    
    public function init() {
        // Load text domain
        load_plugin_textdomain('uk-mortgage-calc', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Always include the calculator engine (works without Elementor)
        $this->include_core_files();
        
        // Setup basic functionality (AJAX, scripts)
        $this->setup_basic_functionality();
        
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
        // Always include the calculator engine
        require_once UK_MORTGAGE_CALC_PLUGIN_PATH . 'includes/class-calculator-engine.php';
    }
    
    private function setup_basic_functionality() {
        // Register scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // AJAX handlers
        add_action('wp_ajax_calculate_mortgage', [$this, 'ajax_calculate_mortgage']);
        add_action('wp_ajax_nopriv_calculate_mortgage', [$this, 'ajax_calculate_mortgage']);
        add_action('wp_ajax_generate_pdf_report', [$this, 'ajax_generate_pdf_report']);
        add_action('wp_ajax_nopriv_generate_pdf_report', [$this, 'ajax_generate_pdf_report']);
    }
    
    public function elementor_init() {
        // Now we can safely include Elementor-dependent files
        $this->include_elementor_files();
        
        // Add element category
        add_action('elementor/elements/categories_registered', [$this, 'add_elementor_widget_categories']);
        
        // Register widgets
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
    }
    
    private function include_elementor_files() {
        // Include base widget class first
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
        // Register widgets only if their classes exist
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
                    error_log("UK Mortgage Calculator: Successfully registered $widget_class");
                } catch (Exception $e) {
                    error_log("UK Mortgage Calculator: Failed to register $widget_class - " . $e->getMessage());
                }
            } else {
                error_log("UK Mortgage Calculator: Widget class $widget_class not found");
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
        
        wp_localize_script('uk-mortgage-calc-js', 'ukMortgageAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('uk_mortgage_nonce'),
            'currency_symbol' => '£',
            'messages' => [
                'calculating' => __('Calculating...', 'uk-mortgage-calc'),
                'error' => __('An error occurred. Please try again.', 'uk-mortgage-calc'),
                'invalid_input' => __('Please check your inputs and try again.', 'uk-mortgage-calc')
            ]
        ]);
    }
    
    public function ajax_calculate_mortgage() {
        check_ajax_referer('uk_mortgage_nonce', 'nonce');
        
        $calculator_type = sanitize_text_field($_POST['calculator_type'] ?? '');
        $data = $_POST['data'] ?? [];
        
        if (empty($calculator_type)) {
            wp_send_json_error(__('Calculator type is required.', 'uk-mortgage-calc'));
        }
        
        try {
            if (!class_exists('UK_Mortgage_Calculator_Engine')) {
                throw new Exception('Calculator engine not found');
            }
            
            $calculator = new UK_Mortgage_Calculator_Engine();
            $result = $calculator->calculate($calculator_type, $data);
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    public function ajax_generate_pdf_report() {
        check_ajax_referer('uk_mortgage_nonce', 'nonce');
        wp_send_json_error(__('PDF generation feature coming soon.', 'uk-mortgage-calc'));
    }
    
    public function activate() {
        update_option('uk_mortgage_calc_version', UK_MORTGAGE_CALC_VERSION);
        
        // Clear any cached data
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Clear Elementor cache if available
        if (class_exists('\Elementor\Plugin')) {
            \Elementor\Plugin::$instance->files_manager->clear_cache();
        }
    }
    
    public function deactivate() {
        // Clear cached data
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
    
    public function admin_notice_missing_main_plugin() {
        if (isset($_GET['activate'])) unset($_GET['activate']);
        
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'uk-mortgage-calc'),
            '<strong>' . esc_html__('UK Mortgage Calculator', 'uk-mortgage-calc') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'uk-mortgage-calc') . '</strong>'
        );
        
        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
    
    public function admin_notice_minimum_elementor_version() {
        if (isset($_GET['activate'])) unset($_GET['activate']);
        
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'uk-mortgage-calc'),
            '<strong>' . esc_html__('UK Mortgage Calculator', 'uk-mortgage-calc') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'uk-mortgage-calc') . '</strong>',
            '3.0.0'
        );
        
        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
    
    public function admin_notice_minimum_php_version() {
        if (isset($_GET['activate'])) unset($_GET['activate']);
        
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'uk-mortgage-calc'),
            '<strong>' . esc_html__('UK Mortgage Calculator', 'uk-mortgage-calc') . '</strong>',
            '<strong>' . esc_html__('PHP', 'uk-mortgage-calc') . '</strong>',
            '7.4'
        );
        
        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
}

// Initialize the plugin
UK_Mortgage_Calculator::instance();