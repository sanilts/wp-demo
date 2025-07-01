<?php
/**
 * Plugin Name: UK Mortgage Calculator Pro (Minimal Test)
 * Description: Minimal test version to verify basic functionality
 * Version: 1.0.3
 * Author: Your Name
 * Text Domain: uk-mortgage-calc
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('UK_MORTGAGE_CALC_VERSION', '1.0.3');
define('UK_MORTGAGE_CALC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UK_MORTGAGE_CALC_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Main Plugin Class
 */
class UK_Mortgage_Calculator {
    
    private static $_instance = null;
    
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
    }
    
    public function init() {
        // Check if Elementor is available
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', [$this, 'admin_notice_missing_elementor']);
            return;
        }
        
        // Include files
        $this->include_files();
        
        // Setup hooks
        add_action('elementor/elements/categories_registered', [$this, 'register_widget_categories']);
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // AJAX handlers
        add_action('wp_ajax_calculate_mortgage', [$this, 'ajax_calculate_mortgage']);
        add_action('wp_ajax_nopriv_calculate_mortgage', [$this, 'ajax_calculate_mortgage']);
    }
    
    private function include_files() {
        $base_widget_file = UK_MORTGAGE_CALC_PLUGIN_PATH . 'includes/class-base-widget.php';
        $widget_classes_file = UK_MORTGAGE_CALC_PLUGIN_PATH . 'includes/widget-classes.php';
        
        if (file_exists($base_widget_file)) {
            require_once $base_widget_file;
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Missing file: includes/class-base-widget.php</p></div>';
            });
            return;
        }
        
        if (file_exists($widget_classes_file)) {
            require_once $widget_classes_file;
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Missing file: includes/widget-classes.php</p></div>';
            });
            return;
        }
    }
    
    public function register_widget_categories($elements_manager) {
        $elements_manager->add_category(
            'uk-mortgage',
            [
                'title' => esc_html__('UK Mortgage Calculators', 'uk-mortgage-calc'),
                'icon' => 'fa fa-calculator',
            ]
        );
    }
    
    public function register_widgets($widgets_manager) {
        // Register each widget if the class exists
        $widget_classes = [
            'UK_Mortgage_Affordability_Widget',
            'UK_Mortgage_Repayment_Widget', 
            'UK_Mortgage_Remortgage_Widget',
            'UK_Mortgage_Valuation_Widget'
        ];
        
        foreach ($widget_classes as $widget_class) {
            if (class_exists($widget_class)) {
                $widgets_manager->register(new $widget_class());
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
            'nonce' => wp_create_nonce('uk_mortgage_nonce')
        ]);
    }
    
    public function ajax_calculate_mortgage() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'uk_mortgage_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $calculator_type = sanitize_text_field($_POST['calculator_type'] ?? '');
        $data = $_POST['data'] ?? [];
        
        // Sanitize data
        $sanitized_data = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized_data[$key] = array_map('sanitize_text_field', $value);
            } else {
                $sanitized_data[$key] = sanitize_text_field($value);
            }
        }
        
        try {
            $calculator = new UK_Mortgage_Calculator_Engine();
            $result = $calculator->calculate($calculator_type, $sanitized_data);
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error('Calculation failed: ' . $e->getMessage());
        }
    }
    
    public function admin_notice_missing_elementor() {
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'uk-mortgage-calc'),
            '<strong>' . esc_html__('UK Mortgage Calculator', 'uk-mortgage-calc') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'uk-mortgage-calc') . '</strong>'
        );
        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
}

/**
 * Simple Calculation Engine
 */
class UK_Mortgage_Calculator_Engine {
    
    public function calculate($type, $data) {
        switch ($type) {
            case 'affordability':
                return $this->calculate_affordability($data);
            case 'repayment':
                return $this->calculate_repayment($data);
            case 'remortgage':
                return $this->calculate_remortgage($data);
            case 'valuation':
                return $this->calculate_valuation($data);
            default:
                throw new Exception('Invalid calculator type');
        }
    }
    
    private function calculate_affordability($data) {
        $annual_income = floatval($data['annual_income'] ?? 0);
        $partner_income = floatval($data['partner_income'] ?? 0);
        $monthly_outgoings = floatval($data['monthly_outgoings'] ?? 0);
        $deposit = floatval($data['deposit'] ?? 0);
        
        if ($annual_income <= 0) {
            throw new Exception('Annual income must be greater than 0');
        }
        
        $total_income = $annual_income + $partner_income;
        $monthly_income = $total_income / 12;
        $available_monthly = $monthly_income - $monthly_outgoings;
        
        if ($available_monthly <= 0) {
            throw new Exception('Monthly outgoings exceed income');
        }
        
        // Simple calculation: 4.5x annual income
        $max_lending = $total_income * 4.5;
        $max_property_value = $max_lending + $deposit;
        
        return [
            'max_borrowing' => round($max_lending, 2),
            'max_property_value' => round($max_property_value, 2),
            'monthly_budget' => round($available_monthly * 0.35, 2),
            'debt_to_income_ratio' => round(($max_lending / $total_income) * 100, 1),
            'loan_to_value' => $max_property_value > 0 ? round(($max_lending / $max_property_value) * 100, 1) : 0,
        ];
    }
    
    private function calculate_repayment($data) {
        $loan_amount = floatval($data['loan_amount'] ?? 0);
        $interest_rate = floatval($data['interest_rate'] ?? 0) / 100;
        $term_years = intval($data['term_years'] ?? 25);
        $overpayment = floatval($data['overpayment'] ?? 0);
        
        if ($loan_amount <= 0) {
            throw new Exception('Loan amount must be greater than 0');
        }
        
        $monthly_rate = $interest_rate / 12;
        $num_payments = $term_years * 12;
        
        if ($monthly_rate > 0) {
            $monthly_payment = $loan_amount * ($monthly_rate * pow(1 + $monthly_rate, $num_payments)) / 
                              (pow(1 + $monthly_rate, $num_payments) - 1);
        } else {
            $monthly_payment = $loan_amount / $num_payments;
        }
        
        $total_paid = $monthly_payment * $num_payments;
        $total_interest = $total_paid - $loan_amount;
        
        return [
            'monthly_payment' => round($monthly_payment, 2),
            'total_monthly_with_overpayment' => round($monthly_payment + $overpayment, 2),
            'total_paid' => round($total_paid, 2),
            'total_interest' => round($total_interest, 2),
            'overpayment_savings' => 0,
            'time_saved_months' => 0,
        ];
    }
    
    private function calculate_remortgage($data) {
        $current_balance = floatval($data['current_balance'] ?? 0);
        $current_rate = floatval($data['current_rate'] ?? 0) / 100;
        $new_rate = floatval($data['new_rate'] ?? 0) / 100;
        $remaining_term = intval($data['remaining_term'] ?? 25);
        
        if ($current_balance <= 0) {
            throw new Exception('Current balance must be greater than 0');
        }
        
        // Simple monthly payment calculation
        $current_monthly = $this->calculate_monthly_payment($current_balance, $current_rate, $remaining_term);
        $new_monthly = $this->calculate_monthly_payment($current_balance, $new_rate, $remaining_term);
        
        $monthly_saving = $current_monthly - $new_monthly;
        $annual_saving = $monthly_saving * 12;
        
        return [
            'current_monthly_payment' => round($current_monthly, 2),
            'new_monthly_payment' => round($new_monthly, 2),
            'monthly_saving' => round($monthly_saving, 2),
            'annual_saving' => round($annual_saving, 2),
            'total_fees' => 0,
            'break_even_months' => 0,
            'worthwhile' => $monthly_saving > 0,
        ];
    }
    
    private function calculate_valuation($data) {
        $property_type = sanitize_text_field($data['property_type'] ?? '');
        $bedrooms = intval($data['bedrooms'] ?? 0);
        
        if (empty($property_type) || $bedrooms <= 0) {
            throw new Exception('Property type and bedrooms are required');
        }
        
        // Simple estimation
        $base_values = [
            'flat' => 250000,
            'terraced' => 300000,
            'semi-detached' => 350000,
            'detached' => 450000,
            'bungalow' => 320000,
        ];
        
        $base_value = $base_values[$property_type] ?? 300000;
        $estimated_value = $base_value * (1 + (($bedrooms - 2) * 0.15));
        
        return [
            'estimated_value' => round($estimated_value, -3),
            'value_range_low' => round($estimated_value * 0.9, -3),
            'value_range_high' => round($estimated_value * 1.1, -3),
            'confidence_level' => 75,
            'comparable_sales' => 10,
        ];
    }
    
    private function calculate_monthly_payment($loan_amount, $annual_rate, $term_years) {
        $monthly_rate = $annual_rate / 12;
        $num_payments = $term_years * 12;
        
        if ($monthly_rate > 0) {
            return $loan_amount * ($monthly_rate * pow(1 + $monthly_rate, $num_payments)) / 
                   (pow(1 + $monthly_rate, $num_payments) - 1);
        }
        
        return $loan_amount / $num_payments;
    }
}

// Initialize the plugin
UK_Mortgage_Calculator::instance();

// Activation hook
register_activation_hook(__FILE__, function() {
    add_option('uk_mortgage_version', UK_MORTGAGE_CALC_VERSION);
    flush_rewrite_rules();
});