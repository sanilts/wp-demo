<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Monthly Repayment Calculator Widget
 */
class UK_Mortgage_Repayment_Widget extends UK_Mortgage_Base_Widget {
    
    public function get_name() {
        return 'uk-mortgage-repayment';
    }
    
    public function get_title() {
        return esc_html__('Monthly Repayment Calculator', 'uk-mortgage-calc');
    }
    
    public function get_icon() {
        return 'eicon-price-list';
    }
    
    public function get_categories() {
        return ['uk-mortgage-calculators'];
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
                        <option value="<?php echo $i; ?>" <?php selected($i, 25); ?>><?php echo $i; ?> <?php esc_html_e('years', 'uk-mortgage-calc'); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <?php if ($settings['show_overpayments'] === 'yes'): ?>
            <div class="form-group col-md-6">
                <label for="overpayment"><?php esc_html_e('Monthly Overpayment (£)', 'uk-mortgage-calc'); ?></label>
                <input type="number" class="form-control" id="overpayment" name="overpayment" 
                       placeholder="0" min="0" step="10">
                <small class="form-text text-muted"><?php esc_html_e('Optional additional monthly payment', 'uk-mortgage-calc'); ?></small>
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