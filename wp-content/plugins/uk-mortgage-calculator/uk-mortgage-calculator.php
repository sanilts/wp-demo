<?php
/**
 * Plugin Name: UK Mortgage Calculator Pro
 * Description: Comprehensive UK mortgage calculator suite for Elementor with affordability, repayment, remortgage, and valuation tools.
 * Version: 1.0.0
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

define('UK_MORTGAGE_CALC_VERSION', '1.0.0');
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
        
        // Add plugin actions
        add_action('elementor/widgets/widgets_registered', [$this, 'init_widgets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_calculate_mortgage', [$this, 'ajax_calculate_mortgage']);
        add_action('wp_ajax_nopriv_calculate_mortgage', [$this, 'ajax_calculate_mortgage']);
        add_action('wp_ajax_generate_pdf_report', [$this, 'ajax_generate_pdf_report']);
        add_action('wp_ajax_nopriv_generate_pdf_report', [$this, 'ajax_generate_pdf_report']);
    }
    
    public function init_widgets() {
        require_once(__DIR__ . '/widgets/affordability-calculator.php');
        require_once(__DIR__ . '/widgets/repayment-calculator.php');
        require_once(__DIR__ . '/widgets/remortgage-calculator.php');
        require_once(__DIR__ . '/widgets/valuation-calculator.php');
        
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \UK_Mortgage_Affordability_Widget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \UK_Mortgage_Repayment_Widget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \UK_Mortgage_Remortgage_Widget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \UK_Mortgage_Valuation_Widget());
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
        check_ajax_referer('uk_mortgage_nonce', 'nonce');
        
        $calculator_type = sanitize_text_field($_POST['calculator_type']);
        $data = array_map('sanitize_text_field', $_POST['data']);
        
        $calculator = new UK_Mortgage_Calculator_Engine();
        $result = $calculator->calculate($calculator_type, $data);
        
        wp_send_json_success($result);
    }
    
    public function ajax_generate_pdf_report() {
        check_ajax_referer('uk_mortgage_nonce', 'nonce');
        
        $data = array_map('sanitize_text_field', $_POST['data']);
        $pdf_generator = new UK_Mortgage_PDF_Generator();
        $pdf_url = $pdf_generator->generate($data);
        
        wp_send_json_success(['pdf_url' => $pdf_url]);
    }
    
    // Admin notices
    public function admin_notice_missing_main_plugin() {
        echo '<div class="notice notice-warning is-dismissible"><p>' . 
             esc_html__('UK Mortgage Calculator requires Elementor to be installed and activated.', 'uk-mortgage-calc') . 
             '</p></div>';
    }
    
    public function admin_notice_minimum_elementor_version() {
        echo '<div class="notice notice-warning is-dismissible"><p>' . 
             esc_html__('UK Mortgage Calculator requires Elementor version 3.0.0 or greater.', 'uk-mortgage-calc') . 
             '</p></div>';
    }
    
    public function admin_notice_minimum_php_version() {
        echo '<div class="notice notice-warning is-dismissible"><p>' . 
             esc_html__('UK Mortgage Calculator requires PHP version 8.2 or greater.', 'uk-mortgage-calc') . 
             '</p></div>';
    }
}

/**
 * Base Widget Class
 */
abstract class UK_Mortgage_Base_Widget extends \Elementor\Widget_Base {
    
    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Calculator Settings', 'uk-mortgage-calc'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'title',
            [
                'label' => esc_html__('Calculator Title', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => $this->get_default_title(),
                'placeholder' => esc_html__('Enter calculator title', 'uk-mortgage-calc'),
            ]
        );
        
        $this->add_control(
            'description',
            [
                'label' => esc_html__('Description', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => $this->get_default_description(),
                'placeholder' => esc_html__('Enter description', 'uk-mortgage-calc'),
            ]
        );
        
        $this->add_control(
            'show_pdf_download',
            [
                'label' => esc_html__('Show PDF Download', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'uk-mortgage-calc'),
                'label_off' => esc_html__('Hide', 'uk-mortgage-calc'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'enable_email_reports',
            [
                'label' => esc_html__('Enable Email Reports', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'uk-mortgage-calc'),
                'label_off' => esc_html__('No', 'uk-mortgage-calc'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->register_calculator_specific_controls();
        
        $this->end_controls_section();
        
        // Style Controls
        $this->register_style_controls();
    }
    
    protected function register_style_controls() {
        $this->start_controls_section(
            'style_section',
            [
                'label' => esc_html__('Calculator Style', 'uk-mortgage-calc'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'primary_color',
            [
                'label' => esc_html__('Primary Color', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#007cba',
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc .btn-primary' => 'background-color: {{VALUE}}',
                    '{{WRAPPER}} .uk-mortgage-calc .form-control:focus' => 'border-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'secondary_color',
            [
                'label' => esc_html__('Secondary Color', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f8f9fa',
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc .calculator-result' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => esc_html__('Title Typography', 'uk-mortgage-calc'),
                'selector' => '{{WRAPPER}} .uk-mortgage-calc h3',
            ]
        );
        
        $this->end_controls_section();
    }
    
    abstract protected function register_calculator_specific_controls();
    abstract protected function get_default_title();
    abstract protected function get_default_description();
    abstract protected function render_calculator_form();
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        ?>
        <div class="uk-mortgage-calc" data-calculator-type="<?php echo esc_attr($this->get_calculator_type()); ?>">
            <div class="calculator-header">
                <?php if (!empty($settings['title'])): ?>
                    <h3 class="calculator-title"><?php echo esc_html($settings['title']); ?></h3>
                <?php endif; ?>
                
                <?php if (!empty($settings['description'])): ?>
                    <p class="calculator-description"><?php echo esc_html($settings['description']); ?></p>
                <?php endif; ?>
            </div>
            
            <form class="calculator-form" data-calculator="<?php echo esc_attr($this->get_calculator_type()); ?>">
                <?php $this->render_calculator_form(); ?>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary calculate-btn">
                        <?php esc_html_e('Calculate', 'uk-mortgage-calc'); ?>
                    </button>
                    
                    <?php if ($settings['show_pdf_download'] === 'yes'): ?>
                        <button type="button" class="btn btn-secondary pdf-download-btn" style="display: none;">
                            <?php esc_html_e('Download PDF', 'uk-mortgage-calc'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($settings['enable_email_reports'] === 'yes'): ?>
                        <button type="button" class="btn btn-outline email-report-btn" style="display: none;">
                            <?php esc_html_e('Email Report', 'uk-mortgage-calc'); ?>
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="calculator-result" style="display: none;">
                    <div class="result-content"></div>
                </div>
                
                <div class="calculator-loading" style="display: none;">
                    <p><?php esc_html_e('Calculating...', 'uk-mortgage-calc'); ?></p>
                </div>
            </form>
        </div>
        <?php
    }
    
    abstract protected function get_calculator_type();
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
                return ['error' => 'Invalid calculator type'];
        }
    }
    
    private function calculate_affordability($data) {
        $annual_income = floatval($data['annual_income'] ?? 0);
        $partner_income = floatval($data['partner_income'] ?? 0);
        $monthly_outgoings = floatval($data['monthly_outgoings'] ?? 0);
        $deposit = floatval($data['deposit'] ?? 0);
        $term_years = intval($data['term_years'] ?? 25);
        
        $total_income = $annual_income + $partner_income;
        $monthly_income = $total_income / 12;
        $available_monthly = $monthly_income - $monthly_outgoings;
        
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
            'loan_to_value' => round(($actual_max_lending / $actual_max_property) * 100, 1),
        ];
    }
    
    private function calculate_repayment($data) {
        $loan_amount = floatval($data['loan_amount'] ?? 0);
        $interest_rate = floatval($data['interest_rate'] ?? 0) / 100;
        $term_years = intval($data['term_years'] ?? 25);
        $overpayment = floatval($data['overpayment'] ?? 0);
        
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
        $fees = floatval($data['fees'] ?? 0);
        
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
        
        // This would typically integrate with property APIs like Rightmove, Zoopla
        // For demo purposes, we'll use estimated values
        
        $base_values = [
            'flat' => 250000,
            'terraced' => 300000,
            'semi-detached' => 350000,
            'detached' => 450000,
        ];
        
        $base_value = $base_values[$property_type] ?? 300000;
        $bedroom_multiplier = 1 + (($bedrooms - 2) * 0.15);
        $area_value = $floor_area * 300; // £300 per sq ft average
        
        $estimated_value = ($base_value * $bedroom_multiplier + $area_value) / 2;
        
        // Add regional adjustments (would be API-driven)
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
            'SW' => 1.8, // London SW
            'W1' => 2.2, // London W1
            'EC' => 1.9, // London EC
            'M1' => 0.7, // Manchester
            'B1' => 0.6, // Birmingham
            'L1' => 0.5, // Liverpool
        ];
        
        return $multipliers[$postcode_area] ?? 1.0;
    }
}

/**
 * PDF Generator Class
 */
class UK_Mortgage_PDF_Generator {
    
    public function generate($data) {
        // This would integrate with a PDF library like TCPDF or DOMPDF
        // For now, return a placeholder
        return UK_MORTGAGE_CALC_PLUGIN_URL . 'assets/sample-report.pdf';
    }
}

/**
 * Affordability Calculator Widget
 */
class UK_Mortgage_Affordability_Widget extends UK_Mortgage_Base_Widget {
    
    public function get_name() {
        return 'uk-mortgage-affordability';
    }
    
    public function get_title() {
        return esc_html__('UK Mortgage Affordability Calculator', 'uk-mortgage-calc');
    }
    
    public function get_icon() {
        return 'eicon-calculator';
    }
    
    public function get_categories() {
        return ['uk-mortgage'];
    }
    
    protected function get_calculator_type() {
        return 'affordability';
    }
    
    protected function get_default_title() {
        return esc_html__('How Much Can I Borrow?', 'uk-mortgage-calc');
    }
    
    protected function get_default_description() {
        return esc_html__('Calculate your maximum borrowing capacity based on your income and outgoings.', 'uk-mortgage-calc');
    }
    
    protected function register_calculator_specific_controls() {
        $this->add_control(
            'default_term',
            [
                'label' => esc_html__('Default Term (Years)', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 25,
                'min' => 5,
                'max' => 40,
            ]
        );
        
        $this->add_control(
            'stress_test_rate',
            [
                'label' => esc_html__('Stress Test Rate (%)', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 7,
                'min' => 3,
                'max' => 10,
                'step' => 0.1,
            ]
        );
    }
    
    protected function render_calculator_form() {
        ?>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="annual_income"><?php esc_html_e('Your Annual Income (£)', 'uk-mortgage-calc'); ?></label>
                <input type="number" class="form-control" id="annual_income" name="annual_income" 
                       placeholder="50000" min="0" step="1000" required>
            </div>
            <div class="form-group col-md-6">
                <label for="partner_income"><?php esc_html_e('Partner\'s Annual Income (£)', 'uk-mortgage-calc'); ?></label>
                <input type="number" class="form-control" id="partner_income" name="partner_income" 
                       placeholder="0" min="0" step="1000">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="monthly_outgoings"><?php esc_html_e('Monthly Outgoings (£)', 'uk-mortgage-calc'); ?></label>
                <input type="number" class="form-control" id="monthly_outgoings" name="monthly_outgoings" 
                       placeholder="1000" min="0" step="50" required>
                <small class="form-text text-muted">Include all monthly expenses, debts, and commitments</small>
            </div>
            <div class="form-group col-md-6">
                <label for="deposit"><?php esc_html_e('Available Deposit (£)', 'uk-mortgage-calc'); ?></label>
                <input type="number" class="form-control" id="deposit" name="deposit" 
                       placeholder="50000" min="0" step="1000" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="term_years"><?php esc_html_e('Mortgage Term (Years)', 'uk-mortgage-calc'); ?></label>
                <select class="form-control" id="term_years" name="term_years">
                    <?php for ($i = 5; $i <= 40; $i += 5): ?>
                        <option value="<?php echo $i; ?>" <?php selected($i, 25); ?>><?php echo $i; ?> years</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="property_type"><?php esc_html_e('Property Type', 'uk-mortgage-calc'); ?></label>
                <select class="form-control" id="property_type" name="property_type">
                    <option value="any"><?php esc_html_e('Any', 'uk-mortgage-calc'); ?></option>
                    <option value="first-time"><?php esc_html_e('First Time Buyer', 'uk-mortgage-calc'); ?></option>
                    <option value="remortgage"><?php esc_html_e('Remortgage', 'uk-mortgage-calc'); ?></option>
                    <option value="buy-to-let"><?php esc_html_e('Buy to Let', 'uk-mortgage-calc'); ?></option>
                </select>
            </div>
        </div>
        <?php
    }
}

/**
 * Monthly Repayment Calculator Widget
 */
class UK_Mortgage_Repayment_Widget extends UK_Mortgage_Base_Widget {
    
    public function get_name() {
        return 'uk-mortgage-repayment';
    }
    
    public function get_title() {
        return esc_html__('UK Mortgage Repayment Calculator', 'uk-mortgage-calc');
    }
    
    public function get_icon() {
        return 'eicon-price-list';
    }
    
    public function get_categories() {
        return ['uk-mortgage'];
    }
    
    protected function get_calculator_type() {
        return 'repayment';
    }
    
    protected function get_default_title() {
        return esc_html__('Monthly Mortgage Payments', 'uk-mortgage-calc');
    }
    
    protected function get_default_description() {
        return esc_html__('Calculate your monthly mortgage repayments and see the impact of overpayments.', 'uk-mortgage-calc');
    }
    
    protected function register_calculator_specific_controls() {
        $this->add_control(
            'show_overpayments',
            [
                'label' => esc_html__('Show Overpayment Options', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'uk-mortgage-calc'),
                'label_off' => esc_html__('Hide', 'uk-mortgage-calc'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
    }
    
    protected function render_calculator_form() {
        $settings = $this->get_settings_for_display();
        ?>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="loan_amount"><?php esc_html_e('Loan Amount (£)', 'uk-mortgage-calc'); ?></label>
                <input type="number" class="form-control" id="loan_amount" name="loan_amount" 
                       placeholder="300000" min="0" step="1000" required>
            </div>
            <div class="form-group col-md-6">
                <label for="interest_rate"><?php esc_html_e('Interest Rate (%)', 'uk-mortgage-calc'); ?></label>
                <input type="number" class="form-control" id="interest_rate" name="interest_rate" 
                       placeholder="3.5" min="0" max="15" step="0.01" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="term_years"><?php esc_html_e('Mortgage Term (Years)', 'uk-mortgage-calc'); ?></label>
                <select class="form-control" id="term_years" name="term_years">
                    <?php for ($i = 5; $i <= 40; $i += 5): ?>
                        <option value="<?php echo $i; ?>" <?php selected($i, 25); ?>><?php echo $i; ?> years</option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <?php if ($settings['show_overpayments'] === 'yes'): ?>
            <div class="form-group col-md-6">
                <label for="overpayment"><?php esc_html_e('Monthly Overpayment (£)', 'uk-mortgage-calc'); ?></label>
                <input type="number" class="form-control" id="overpayment" name="overpayment" 
                       placeholder="0" min="0" step="10">
                <small class="form-text text-muted">Optional additional monthly payment</small>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="repayment_type"><?php esc_html_e('Repayment Type', 'uk-mortgage-calc'); ?></label>
                <select class="form-control" id="repayment_type" name="repayment_type">
                    <option value="repayment"><?php esc_html_e('Repayment', 'uk-mortgage-calc'); ?></option>
                    <option value="interest-only"><?php esc_html_e('Interest Only', 'uk-mortgage-calc'); ?></option>
                </select>
            </div>
        </div>
        <?php
    }
}

/**
 * Remortgage Calculator Widget
 */
class UK_Mortgage_Remortgage_Widget extends UK_Mortgage_Base_Widget {
    
    public function get_name() {
        return 'uk-mortgage-remortgage';
    }
    
    public function get_title() {
        return esc_html__('UK Remortgage Calculator', 'uk-mortgage-calc');
    }
    
    public function get_icon() {
        return 'eicon-sync';
    }
    
    public function get_categories() {
        return ['uk-mortgage'];
    }
    
    protected function get_calculator_type() {
        return 'remortgage';
    }
    
    protected function get_default_title() {
        return esc_html__('Should I Remortgage?', 'uk-mortgage-calc');
    }
    
    protected function get_default_description() {
        return esc_html__('Compare your current mortgage with new deals to see potential savings.', 'uk-mortgage-calc');
    }
    
    protected function register_calculator_specific_controls() {
        $this->add_control(
            'show_fees_breakdown',
            [
                'label' => esc_html__('Show Fees Breakdown', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'uk-mortgage-calc'),
                'label_off' => esc_html__('Hide', 'uk-mortgage-calc'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
    }
    
    protected function render_calculator_form() {
        ?>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="current_balance"><?php esc_html_e('Current Mortgage Balance (£)', 'uk-mortgage-calc'); ?></label>
                <input type="number" class="form-control" id="current_balance" name="current_balance" 
                       placeholder="250000" min="0" step="1000" required>
            </div>
            <div class="form-group col-md-6">
                <label for="current_rate"><?php esc_html_e('Current Interest Rate (%)', 'uk-mortgage-calc'); ?></label>
                <input type="number" class="form-control" id="current_rate" name="current_rate" 
                       placeholder="4.5" min="0" max="15" step="0.01" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="new_rate"><?php esc_html_e('New Interest Rate (%)', 'uk-mortgage-calc'); ?></label>
                <input type="number" class="form-control" id="new_rate" name="new_rate" 
                       placeholder="3.2" min="0" max="15" step="0.01" required>
            </div>
            <div class="form-group col-md-6">
                <label for="remaining_term"><?php esc_html_e('Remaining Term (Years)', 'uk-mortgage-calc'); ?></label>
                <select class="form-control" id="remaining_term" name="remaining_term">
                    <?php for ($i = 1; $i <= 35; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php selected($i, 20); ?>><?php echo $i; ?> years</option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="arrangement_fee"><?php esc_html_e('Arrangement Fee (£)', 'uk-mortgage-calc'); ?></label>
                <input type="number" class="form-control" id="arrangement_fee" name="arrangement_fee" 
                       placeholder="999" min="0" step="1">
            </div>
            <div class="form-group col-md-6">
                <label for="valuation_fee"><?php esc_html_e('Valuation Fee (£)', 'uk-mortgage-calc'); ?></label>
                <input type="number" class="form-control" id="valuation_fee" name="valuation_fee" 
                       placeholder="500" min="0" step="1">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="legal_fees"><?php esc_html_e('Legal Fees (£)', 'uk-mortgage-calc'); ?></label>
                <input type="number" class="form-control" id="legal_fees" name="legal_fees" 
                       placeholder="300" min="0" step="1">
            </div>
            <div class="form-group col-md-6">
                <label for="exit_fee"><?php esc_html_e('Exit Fee (£)', 'uk-mortgage-calc'); ?></label>
                <input type="number" class="form-control" id="exit_fee" name="exit_fee" 
                       placeholder="0" min="0" step="1">
            </div>
        </div>
        <?php
    }
}

/**
 * Property Valuation Calculator Widget
 */
class UK_Mortgage_Valuation_Widget extends UK_Mortgage_Base_Widget {
    
    public function get_name() {
        return 'uk-mortgage-valuation';
    }
    
    public function get_title() {
        return esc_html__('UK Property Valuation Calculator', 'uk-mortgage-calc');
    }
    
    public function get_icon() {
        return 'eicon-home';
    }
    
    public function get_categories() {
        return ['uk-mortgage'];
    }
    
    protected function get_calculator_type() {
        return 'valuation';
    }
    
    protected function get_default_title() {
        return esc_html__('Property Value Estimator', 'uk-mortgage-calc');
    }
    
    protected function get_default_description() {
        return esc_html__('Get an estimated value for your property based on local market data.', 'uk-mortgage-calc');
    }
    
    protected function register_calculator_specific_controls() {
        $this->add_control(
            'api_integration',
            [
                'label' => esc_html__('API Integration', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'zoopla',
                'options' => [
                    'zoopla' => esc_html__('Zoopla API', 'uk-mortgage-calc'),
                    'rightmove' => esc_html__('Rightmove API', 'uk-mortgage-calc'),
                    'manual' => esc_html__('Manual Calculation', 'uk-mortgage-calc'),
                ],
            ]
        );
    }
    
    protected function render_calculator_form() {
        ?>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="postcode"><?php esc_html_e('Postcode', 'uk-mortgage-calc'); ?></label>
                <input type="text" class="form-control" id="postcode" name="postcode" 
                       placeholder="SW1A 1AA" pattern="[A-Za-z]{1,2}[0-9Rr][0-9A-Za-z]? [0-9][ABD-HJLNP-UW-Zabd-hjlnp-uw-z]{2}" required>
            </div>
            <div class="form-group col-md-6">
                <label for="property_type"><?php esc_html_e('Property Type', 'uk-mortgage-calc'); ?></label>
                <select class="form-control" id="property_type" name="property_type" required>
                    <option value=""><?php esc_html_e('Select property type', 'uk-mortgage-calc'); ?></option>
                    <option value="flat"><?php esc_html_e('Flat/Apartment', 'uk-mortgage-calc'); ?></option>
                    <option value="terraced"><?php esc_html_e('Terraced House', 'uk-mortgage-calc'); ?></option>
                    <option value="semi-detached"><?php esc_html_e('Semi-Detached House', 'uk-mortgage-calc'); ?></option>
                    <option value="detached"><?php esc_html_e('Detached House', 'uk-mortgage-calc'); ?></option>
                    <option value="bungalow"><?php esc_html_e('Bungalow', 'uk-mortgage-calc'); ?></option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="bedrooms"><?php esc_html_e('Number of Bedrooms', 'uk-mortgage-calc'); ?></label>
                <select class="form-control" id="bedrooms" name="bedrooms" required>
                    <option value=""><?php esc_html_e('Select bedrooms', 'uk-mortgage-calc'); ?></option>
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?> bedroom<?php echo $i > 1 ? 's' : ''; ?></option>
                    <?php endfor; ?>
                    <option value="7+"><?php esc_html_e('7+ bedrooms', 'uk-mortgage-calc'); ?></option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="bathrooms"><?php esc_html_e('Number of Bathrooms', 'uk-mortgage-calc'); ?></label>
                <select class="form-control" id="bathrooms" name="bathrooms">
                    <option value=""><?php esc_html_e('Select bathrooms', 'uk-mortgage-calc'); ?></option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?> bathroom<?php echo $i > 1 ? 's' : ''; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="floor_area"><?php esc_html_e('Floor Area (sq ft)', 'uk-mortgage-calc'); ?></label>
                <input type="number" class="form-control" id="floor_area" name="floor_area" 
                       placeholder="1200" min="0" step="50">
                <small class="form-text text-muted">Optional but improves accuracy</small>
            </div>
            <div class="form-group col-md-6">
                <label for="property_age"><?php esc_html_e('Property Age', 'uk-mortgage-calc'); ?></label>
                <select class="form-control" id="property_age" name="property_age">
                    <option value=""><?php esc_html_e('Select age', 'uk-mortgage-calc'); ?></option>
                    <option value="new"><?php esc_html_e('New Build (0-2 years)', 'uk-mortgage-calc'); ?></option>
                    <option value="modern"><?php esc_html_e('Modern (3-20 years)', 'uk-mortgage-calc'); ?></option>
                    <option value="established"><?php esc_html_e('Established (21-50 years)', 'uk-mortgage-calc'); ?></option>
                    <option value="period"><?php esc_html_e('Period (50+ years)', 'uk-mortgage-calc'); ?></option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="additional_features"><?php esc_html_e('Additional Features', 'uk-mortgage-calc'); ?></label>
                <div class="checkbox-group">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="features[]" value="garden"> <?php esc_html_e('Garden', 'uk-mortgage-calc'); ?>
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="features[]" value="parking"> <?php esc_html_e('Parking', 'uk-mortgage-calc'); ?>
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="features[]" value="garage"> <?php esc_html_e('Garage', 'uk-mortgage-calc'); ?>
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="features[]" value="conservatory"> <?php esc_html_e('Conservatory', 'uk-mortgage-calc'); ?>
                    </label>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize the plugin
UK_Mortgage_Calculator::instance();


register_activation_hook(__FILE__, function() {
    // Create analytics table
    $analytics = new UK_Mortgage_Analytics();
    $analytics->create_analytics_table();
    
    // Set default options
    add_option('uk_mortgage_version', UK_MORTGAGE_CALC_VERSION);
    add_option('uk_mortgage_install_date', current_time('mysql'));
    
    // Schedule daily rate updates
    if (!wp_next_scheduled('uk_mortgage_update_rates')) {
        wp_schedule_event(time(), 'daily', 'uk_mortgage_update_rates');
    }
});

register_deactivation_hook(__FILE__, function() {
    // Clear scheduled events
    wp_clear_scheduled_hook('uk_mortgage_update_rates');
});

// Schedule rate updates
add_action('uk_mortgage_update_rates', function() {
    $api = new UK_Mortgage_API_Integrations();
    $api->get_current_rates(); // This will cache the latest rates
});

// Add admin menu for settings
add_action('admin_menu', function() {
    add_options_page(
        'UK Mortgage Calculator Settings',
        'Mortgage Calculator',
        'manage_options',
        'uk-mortgage-settings',
        'uk_mortgage_settings_page'
    );
});

function uk_mortgage_settings_page() {
    ?>
    <div class="wrap">
        <h1>UK Mortgage Calculator Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('uk_mortgage_settings');
            do_settings_sections('uk_mortgage_settings');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Zoopla API Key</th>
                    <td><input type="text" name="uk_mortgage_zoopla_api_key" value="<?php echo esc_attr(get_option('uk_mortgage_zoopla_api_key')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Google Analytics Tracking ID</th>
                    <td><input type="text" name="uk_mortgage_ga_tracking_id" value="<?php echo esc_attr(get_option('uk_mortgage_ga_tracking_id')); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register settings
add_action('admin_init', function() {
    register_setting('uk_mortgage_settings', 'uk_mortgage_zoopla_api_key');
    register_setting('uk_mortgage_settings', 'uk_mortgage_ga_tracking_id');
});