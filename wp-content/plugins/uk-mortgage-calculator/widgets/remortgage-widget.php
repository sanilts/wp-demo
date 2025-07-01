<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Remortgage Calculator Widget
 */
class UK_Mortgage_Remortgage_Widget extends UK_Mortgage_Base_Widget {
    
    public function get_name() {
        return 'uk-mortgage-remortgage';
    }
    
    public function get_title() {
        return esc_html__('Remortgage Calculator', 'uk-mortgage-calc');
    }
    
    public function get_icon() {
        return 'eicon-sync';
    }
    
    public function get_categories() {
        return ['uk-mortgage-calculators'];
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
                        <option value="<?php echo $i; ?>" <?php selected($i, 20); ?>><?php echo $i; ?> <?php esc_html_e('years', 'uk-mortgage-calc'); ?></option>
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