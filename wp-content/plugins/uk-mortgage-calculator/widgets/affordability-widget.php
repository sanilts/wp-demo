<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Affordability Calculator Widget
 */
class UK_Mortgage_Affordability_Widget extends UK_Mortgage_Base_Widget {
    
    public function get_name() {
        return 'uk-mortgage-affordability';
    }
    
    public function get_title() {
        return esc_html__('Mortgage Affordability Calculator', 'uk-mortgage-calc');
    }
    
    public function get_icon() {
        return 'eicon-calculator';
    }
    
    public function get_categories() {
        return ['uk-mortgage-calculators'];
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
                <small class="form-text text-muted"><?php esc_html_e('Include all monthly expenses, debts, and commitments', 'uk-mortgage-calc'); ?></small>
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
                        <option value="<?php echo $i; ?>" <?php selected($i, 25); ?>><?php echo $i; ?> <?php esc_html_e('years', 'uk-mortgage-calc'); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="buyer_type"><?php esc_html_e('Buyer Type', 'uk-mortgage-calc'); ?></label>
                <select class="form-control" id="buyer_type" name="buyer_type">
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