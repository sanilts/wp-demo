<?php
/**
 * Plugin Name: UK Mortgage Calculator Pro
 * Description: Comprehensive UK mortgage calculator suite for Elementor with affordability, repayment, remortgage, and valuation tools.
 * Version: 1.0.1
 * Author: Your Name
 * Text Domain: uk-mortgage-calc
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 8.2
 * Elementor tested up to: 3.16
 * Elementor Pro tested up to: 3.16
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('UK_MORTGAGE_CALC_VERSION', '1.0.1');
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
        add_action('init', [$this, 'load_textdomain']);
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('uk-mortgage-calc', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    public function init() {
        // Check if Elementor is installed and activated
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', [$this, 'admin_notice_missing_main_plugin']);
            return;
        }
        
        // Check for minimum Elementor version
        if (!version_compare(ELEMENTOR_VERSION, '3.0.0', '>=')) {
            add_action('admin_notices', [$this, 'admin_notice_minimum_elementor_version']);
            return;
        }
        
        // Check for minimum PHP version
        if (version_compare(PHP_VERSION, '8.2', '<')) {
            add_action('admin_notices', [$this, 'admin_notice_minimum_php_version']);
            return;
        }
        
        // Include required files
        $this->include_files();
        
        // Add plugin actions
        add_action('elementor/widgets/register', [$this, 'init_widgets']);
        add_action('elementor/elements/categories_registered', [$this, 'register_widget_categories']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_calculate_mortgage', [$this, 'ajax_calculate_mortgage']);
        add_action('wp_ajax_nopriv_calculate_mortgage', [$this, 'ajax_calculate_mortgage']);
        add_action('wp_ajax_generate_pdf_report', [$this, 'ajax_generate_pdf_report']);
        add_action('wp_ajax_nopriv_generate_pdf_report', [$this, 'ajax_generate_pdf_report']);
    }
    
    private function include_files() {
        // Include class files
        $files = [
            'includes/class-analytics.php',
            'includes/class-api-integrations.php',
            'includes/class-email-handler.php',
            'includes/class-pdf-generator.php'
        ];
        
        foreach ($files as $file) {
            $file_path = UK_MORTGAGE_CALC_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
        
        // Include base widget class
        require_once UK_MORTGAGE_CALC_PLUGIN_PATH . 'includes/class-base-widget.php';
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
    
    public function init_widgets($widgets_manager) {
        // Register widget classes
        $widgets_manager->register(new UK_Mortgage_Affordability_Widget());
        $widgets_manager->register(new UK_Mortgage_Repayment_Widget());
        $widgets_manager->register(new UK_Mortgage_Remortgage_Widget());
        $widgets_manager->register(new UK_Mortgage_Valuation_Widget());
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script(
            'uk-mortgage-calc-js',
            UK_MORTGAGE_CALC_PLUGIN_URL . 'assets/js/mortgage-calculator.js',
            ['jquery'],
            UK_MORTGAGE_CALC_VERSION,
            true
        );
        
        wp_enqueue_style(
            'uk-mortgage-calc-css',
            UK_MORTGAGE_CALC_PLUGIN_URL . 'assets/css/mortgage-calculator.css',
            [],
            UK_MORTGAGE_CALC_VERSION
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
            
            // Track calculation if analytics class exists
            if (class_exists('UK_Mortgage_Analytics')) {
                $analytics = new UK_Mortgage_Analytics();
                $analytics->track_calculation($calculator_type, $sanitized_data, $result);
            }
            
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error('Calculation failed: ' . $e->getMessage());
        }
    }
    
    public function ajax_generate_pdf_report() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'uk_mortgage_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        if (!class_exists('UK_Mortgage_PDF_Generator')) {
            wp_send_json_error('PDF generation not available');
            return;
        }
        
        $calculator_type = sanitize_text_field($_POST['calculator_type'] ?? '');
        $data = $_POST['data'] ?? [];
        
        // Sanitize data
        $sanitized_data = [];
        foreach ($data as $key => $value) {
            $sanitized_data[$key] = sanitize_text_field($value);
        }
        
        try {
            $pdf_generator = new UK_Mortgage_PDF_Generator();
            $pdf_url = $pdf_generator->generate($calculator_type, $sanitized_data, []);
            wp_send_json_success(['pdf_url' => $pdf_url]);
        } catch (Exception $e) {
            wp_send_json_error('PDF generation failed: ' . $e->getMessage());
        }
    }
    
    // Admin notices
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
            '8.2'
        );
        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
}

/**
 * Calculation Engine
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
        $term_years = intval($data['term_years'] ?? 25);
        
        if ($annual_income <= 0) {
            throw new Exception('Annual income must be greater than 0');
        }
        
        $total_income = $annual_income + $partner_income;
        $monthly_income = $total_income / 12;
        $available_monthly = $monthly_income - $monthly_outgoings;
        
        if ($available_monthly <= 0) {
            throw new Exception('Monthly outgoings exceed income');
        }
        
        // UK typical lending criteria: 4.5x annual income
        $max_lending = $total_income * 4.5;
        $max_property_value = $max_lending + $deposit;
        
        // Calculate affordability based on monthly payments (stress test at 7%)
        $stress_rate = 0.07 / 12;
        $num_payments = $term_years * 12;
        
        if ($stress_rate > 0) {
            $max_monthly_payment = $available_monthly * 0.35; // 35% of available income
            $max_loan_by_payment = $max_monthly_payment * ((1 - pow(1 + $stress_rate, -$num_payments)) / $stress_rate);
        } else {
            $max_loan_by_payment = $max_lending;
        }
        
        $actual_max_lending = min($max_lending, $max_loan_by_payment);
        $actual_max_property = $actual_max_lending + $deposit;
        
        return [
            'max_borrowing' => round($actual_max_lending, 2),
            'max_property_value' => round($actual_max_property, 2),
            'monthly_budget' => round($available_monthly * 0.35, 2),
            'debt_to_income_ratio' => round(($actual_max_lending / $total_income) * 100, 1),
            'loan_to_value' => $actual_max_property > 0 ? round(($actual_max_lending / $actual_max_property) * 100, 1) : 0,
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
        
        if ($interest_rate < 0) {
            throw new Exception('Interest rate cannot be negative');
        }
        
        $monthly_rate = $interest_rate / 12;
        $num_payments = $term_years * 12;
        
        if ($monthly_rate > 0) {
            $monthly_payment = $loan_amount * ($monthly_rate * pow(1 + $monthly_rate, $num_payments)) / 
                              (pow(1 + $monthly_rate, $num_payments) - 1);
        } else {
            $monthly_payment = $loan_amount / $num_payments;
        }
        
        $total_monthly = $monthly_payment + $overpayment;
        $total_paid = $monthly_payment * $num_payments;
        $total_interest = $total_paid - $loan_amount;
        
        // Calculate with overpayments
        $overpayment_savings = 0;
        $overpayment_time_saved = 0;
        
        if ($overpayment > 0) {
            $remaining_balance = $loan_amount;
            $months_with_overpayment = 0;
            
            while ($remaining_balance > 0 && $months_with_overpayment < $num_payments) {
                $interest_payment = $remaining_balance * $monthly_rate;
                $principal_payment = $monthly_payment - $interest_payment + $overpayment;
                $remaining_balance -= $principal_payment;
                $months_with_overpayment++;
                
                if ($remaining_balance <= 0) break;
            }
            
            $overpayment_time_saved = $num_payments - $months_with_overpayment;
            $overpayment_savings = $overpayment_time_saved * $monthly_payment;
        }
        
        return [
            'monthly_payment' => round($monthly_payment, 2),
            'total_monthly_with_overpayment' => round($total_monthly, 2),
            'total_paid' => round($total_paid, 2),
            'total_interest' => round($total_interest, 2),
            'overpayment_savings' => round($overpayment_savings, 2),
            'time_saved_months' => $overpayment_time_saved,
        ];
    }
    
    private function calculate_remortgage($data) {
        $current_balance = floatval($data['current_balance'] ?? 0);
        $current_rate = floatval($data['current_rate'] ?? 0) / 100;
        $new_rate = floatval($data['new_rate'] ?? 0) / 100;
        $remaining_term = intval($data['remaining_term'] ?? 25);
        
        // Calculate fees
        $arrangement_fee = floatval($data['arrangement_fee'] ?? 0);
        $valuation_fee = floatval($data['valuation_fee'] ?? 0);
        $legal_fees = floatval($data['legal_fees'] ?? 0);
        $exit_fee = floatval($data['exit_fee'] ?? 0);
        $fees = $arrangement_fee + $valuation_fee + $legal_fees + $exit_fee;
        
        if ($current_balance <= 0) {
            throw new Exception('Current balance must be greater than 0');
        }
        
        // Calculate current monthly payment
        $current_monthly = $this->calculate_monthly_payment($current_balance, $current_rate, $remaining_term);
        
        // Calculate new monthly payment
        $new_monthly = $this->calculate_monthly_payment($current_balance + $fees, $new_rate, $remaining_term);
        
        $monthly_saving = $current_monthly - $new_monthly;
        $annual_saving = $monthly_saving * 12;
        $break_even_months = $monthly_saving > 0 ? ceil($fees / $monthly_saving) : 0;
        
        return [
            'current_monthly_payment' => round($current_monthly, 2),
            'new_monthly_payment' => round($new_monthly, 2),
            'monthly_saving' => round($monthly_saving, 2),
            'annual_saving' => round($annual_saving, 2),
            'total_fees' => round($fees, 2),
            'break_even_months' => $break_even_months,
            'worthwhile' => $monthly_saving > 0 && $break_even_months <= 24,
        ];
    }
    
    private function calculate_valuation($data) {
        $property_type = sanitize_text_field($data['property_type'] ?? '');
        $bedrooms = intval($data['bedrooms'] ?? 0);
        $postcode = sanitize_text_field($data['postcode'] ?? '');
        $floor_area = floatval($data['floor_area'] ?? 0);
        
        if (empty($property_type) || $bedrooms <= 0) {
            throw new Exception('Property type and bedrooms are required');
        }
        
        // Base values by property type
        $base_values = [
            'flat' => 250000,
            'terraced' => 300000,
            'semi-detached' => 350000,
            'detached' => 450000,
            'bungalow' => 320000,
        ];
        
        $base_value = $base_values[$property_type] ?? 300000;
        $bedroom_multiplier = 1 + (($bedrooms - 2) * 0.15);
        
        if ($floor_area > 0) {
            $area_value = $floor_area * 300; // £300 per sq ft average
            $estimated_value = ($base_value * $bedroom_multiplier + $area_value) / 2;
        } else {
            $estimated_value = $base_value * $bedroom_multiplier;
        }
        
        // Add regional adjustments
        $regional_multiplier = $this->get_regional_multiplier($postcode);
        $final_value = $estimated_value * $regional_multiplier;
        
        return [
            'estimated_value' => round($final_value, -3), // Round to nearest £1000
            'value_range_low' => round($final_value * 0.9, -3),
            'value_range_high' => round($final_value * 1.1, -3),
            'confidence_level' => 75,
            'comparable_sales' => 12,
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
    
    private function get_regional_multiplier($postcode) {
        // Simplified regional multipliers - would use API data in production
        $postcode_area = strtoupper(substr($postcode, 0, 2));
        
        $multipliers = [
            'SW' => 1.8, 'W1' => 2.2, 'WC' => 2.0, 'EC' => 1.9, 'E1' => 1.6,
            'N1' => 1.4, 'NW' => 1.5, 'SE' => 1.3, 'CR' => 1.2, 'BR' => 1.1,
            'RH' => 1.3, 'GU' => 1.4, 'SL' => 1.3, 'HP' => 1.2, 'AL' => 1.2,
            'M1' => 0.7, 'M2' => 0.7, 'M3' => 0.7, // Manchester
            'B1' => 0.6, 'B2' => 0.6, // Birmingham
            'L1' => 0.5, 'L2' => 0.5, // Liverpool
            'LS' => 0.6, // Leeds
            'S1' => 0.5, // Sheffield
            'NE' => 0.5, // Newcastle
            'EH' => 0.8, // Edinburgh
            'G1' => 0.6, // Glasgow
            'CF' => 0.6, // Cardiff
            'OX' => 1.4, // Oxford
            'CB' => 1.3, // Cambridge
        ];
        
        return $multipliers[$postcode_area] ?? 1.0;
    }
}

// Include widget classes at the end
require_once UK_MORTGAGE_CALC_PLUGIN_PATH . 'includes/widget-classes.php';

// Initialize the plugin
UK_Mortgage_Calculator::instance();

// Activation hook
register_activation_hook(__FILE__, function() {
    // Create analytics table if class exists
    if (class_exists('UK_Mortgage_Analytics')) {
        $analytics = new UK_Mortgage_Analytics();
        $analytics->create_analytics_table();
    }
    
    // Set default options
    add_option('uk_mortgage_version', UK_MORTGAGE_CALC_VERSION);
    add_option('uk_mortgage_install_date', current_time('mysql'));
    
    // Schedule daily rate updates
    if (!wp_next_scheduled('uk_mortgage_update_rates')) {
        wp_schedule_event(time(), 'daily', 'uk_mortgage_update_rates');
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Clear scheduled events
    wp_clear_scheduled_hook('uk_mortgage_update_rates');
});

// Schedule rate updates
add_action('uk_mortgage_update_rates', function() {
    if (class_exists('UK_Mortgage_API_Integrations')) {
        $api = new UK_Mortgage_API_Integrations();
        $api->get_current_rates(); // This will cache the latest rates
    }
});

// Add admin menu for settings
add_action('admin_menu', function() {
    add_options_page(
        esc_html__('UK Mortgage Calculator Settings', 'uk-mortgage-calc'),
        esc_html__('Mortgage Calculator', 'uk-mortgage-calc'),
        'manage_options',
        'uk-mortgage-settings',
        'uk_mortgage_settings_page'
    );
});

function uk_mortgage_settings_page() {
    if (isset($_POST['submit'])) {
        update_option('uk_mortgage_zoopla_api_key', sanitize_text_field($_POST['uk_mortgage_zoopla_api_key'] ?? ''));
        update_option('uk_mortgage_ga_tracking_id', sanitize_text_field($_POST['uk_mortgage_ga_tracking_id'] ?? ''));
        echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved.', 'uk-mortgage-calc') . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('UK Mortgage Calculator Settings', 'uk-mortgage-calc'); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field('uk_mortgage_settings_save', 'uk_mortgage_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Zoopla API Key', 'uk-mortgage-calc'); ?></th>
                    <td><input type="text" name="uk_mortgage_zoopla_api_key" value="<?php echo esc_attr(get_option('uk_mortgage_zoopla_api_key')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Google Analytics Tracking ID', 'uk-mortgage-calc'); ?></th>
                    <td><input type="text" name="uk_mortgage_ga_tracking_id" value="<?php echo esc_attr(get_option('uk_mortgage_ga_tracking_id')); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}