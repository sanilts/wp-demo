<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Property Valuation Calculator Widget
 */
class UK_Mortgage_Valuation_Widget extends UK_Mortgage_Base_Widget {
    
    public function get_name() {
        return 'uk-mortgage-valuation';
    }
    
    public function get_title() {
        return esc_html__('Property Valuation Calculator', 'uk-mortgage-calc');
    }
    
    public function get_icon() {
        return 'eicon-home';
    }
    
    public function get_categories() {
        return ['uk-mortgage-calculators'];
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