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
                'default' => 'manual',
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
                        <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php echo $i > 1 ? esc_html__('bedrooms', 'uk-mortgage-calc') : esc_html__('bedroom', 'uk-mortgage-calc'); ?></option>
                    <?php endfor; ?>
                    <option value="7"><?php esc_html_e('7+ bedrooms', 'uk-mortgage-calc'); ?></option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="bathrooms"><?php esc_html_e('Number of Bathrooms', 'uk-mortgage-calc'); ?></label>
                <select class="form-control" id="bathrooms" name="bathrooms">
                    <option value=""><?php esc_html_e('Select bathrooms', 'uk-mortgage-calc'); ?></option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php echo $i > 1 ? esc_html__('bathrooms', 'uk-mortgage-calc') : esc_html__('bathroom', 'uk-mortgage-calc'); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="floor_area"><?php esc_html_e('Floor Area (sq ft)', 'uk-mortgage-calc'); ?></label>
                <input type="number" class="form-control" id="floor_area" name="floor_area" 
                       placeholder="1200" min="0" step="50">
                <small class="form-text text-muted"><?php esc_html_e('Optional but improves accuracy', 'uk-mortgage-calc'); ?></small>
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