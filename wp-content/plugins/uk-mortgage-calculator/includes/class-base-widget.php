<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Only define the class if Elementor is available
if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

/**
 * Base Widget Class for UK Mortgage Calculators
 */
abstract class UK_Mortgage_Base_Widget extends \Elementor\Widget_Base {
    
    /**
     * Get widget keywords for searching
     */
    public function get_keywords() {
        return ['mortgage', 'calculator', 'uk', 'loan', 'finance'];
    }
    
    /**
     * Widget categories - can be overridden by child classes
     */
    public function get_categories() {
        return ['uk-mortgage-calculators'];
    }
    
    /**
     * Register widget controls
     */
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
                'label_block' => true,
            ]
        );
        
        $this->add_control(
            'description',
            [
                'label' => esc_html__('Description', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => $this->get_default_description(),
                'placeholder' => esc_html__('Enter description', 'uk-mortgage-calc'),
                'label_block' => true,
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
                'default' => 'no',
            ]
        );
        
        $this->add_control(
            'auto_calculate',
            [
                'label' => esc_html__('Auto Calculate', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'uk-mortgage-calc'),
                'label_off' => esc_html__('No', 'uk-mortgage-calc'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => esc_html__('Calculate automatically when inputs change', 'uk-mortgage-calc'),
            ]
        );
        
        // Register calculator-specific controls
        $this->register_calculator_specific_controls();
        
        $this->end_controls_section();
        
        // Style Controls
        $this->register_style_controls();
    }
    
    /**
     * Register style controls
     */
    protected function register_style_controls() {
        // General Styling
        $this->start_controls_section(
            'general_style_section',
            [
                'label' => esc_html__('General Style', 'uk-mortgage-calc'),
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
                    '{{WRAPPER}} .uk-mortgage-calc .result-card.primary' => 'border-color: {{VALUE}}',
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
                    '{{WRAPPER}} .uk-mortgage-calc .calculator-result' => 'background: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'calculator_border',
                'label' => esc_html__('Calculator Border', 'uk-mortgage-calc'),
                'selector' => '{{WRAPPER}} .uk-mortgage-calc',
            ]
        );
        
        $this->add_control(
            'calculator_border_radius',
            [
                'label' => esc_html__('Border Radius', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'calculator_shadow',
                'label' => esc_html__('Box Shadow', 'uk-mortgage-calc'),
                'selector' => '{{WRAPPER}} .uk-mortgage-calc',
            ]
        );
        
        $this->end_controls_section();
        
        // Typography
        $this->start_controls_section(
            'typography_style_section',
            [
                'label' => esc_html__('Typography', 'uk-mortgage-calc'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => esc_html__('Title Typography', 'uk-mortgage-calc'),
                'selector' => '{{WRAPPER}} .uk-mortgage-calc .calculator-title',
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'description_typography',
                'label' => esc_html__('Description Typography', 'uk-mortgage-calc'),
                'selector' => '{{WRAPPER}} .uk-mortgage-calc .calculator-description',
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'label_typography',
                'label' => esc_html__('Label Typography', 'uk-mortgage-calc'),
                'selector' => '{{WRAPPER}} .uk-mortgage-calc .form-group label',
            ]
        );
        
        $this->end_controls_section();
        
        // Button Styling
        $this->start_controls_section(
            'button_style_section',
            [
                'label' => esc_html__('Button Style', 'uk-mortgage-calc'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'label' => esc_html__('Button Typography', 'uk-mortgage-calc'),
                'selector' => '{{WRAPPER}} .uk-mortgage-calc .btn',
            ]
        );
        
        $this->add_control(
            'button_padding',
            [
                'label' => esc_html__('Button Padding', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc .btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'button_border_radius',
            [
                'label' => esc_html__('Button Border Radius', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc .btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    /**
     * Abstract methods that must be implemented by child classes
     */
    abstract protected function register_calculator_specific_controls();
    abstract protected function get_default_title();
    abstract protected function get_default_description();
    abstract protected function render_calculator_form();
    abstract protected function get_calculator_type();
    
    /**
     * Render the widget
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        $calculator_type = $this->get_calculator_type();
        ?>
        <div class="uk-mortgage-calc" 
             data-calculator-type="<?php echo esc_attr($calculator_type); ?>"
             data-auto-calculate="<?php echo esc_attr($settings['auto_calculate'] ?? 'yes'); ?>">
            
            <div class="calculator-header">
                <?php if (!empty($settings['title'])): ?>
                    <h3 class="calculator-title"><?php echo esc_html($settings['title']); ?></h3>
                <?php endif; ?>
                
                <?php if (!empty($settings['description'])): ?>
                    <p class="calculator-description"><?php echo esc_html($settings['description']); ?></p>
                <?php endif; ?>
            </div>
            
            <form class="calculator-form" data-calculator="<?php echo esc_attr($calculator_type); ?>">
                <?php 
                wp_nonce_field('uk_mortgage_form', 'uk_mortgage_form_nonce');
                $this->render_calculator_form(); 
                ?>
                
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
                    
                    <button type="button" class="btn btn-outline compare-rates-btn">
                        <?php esc_html_e('Compare Rates', 'uk-mortgage-calc'); ?>
                    </button>
                </div>
            </form>
            
            <div class="calculator-result" style="display: none;">
                <div class="result-content"></div>
            </div>
            
            <div class="calculator-loading" style="display: none;">
                <p><?php esc_html_e('Calculating...', 'uk-mortgage-calc'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render widget in the editor (Elementor preview)
     */
    protected function content_template() {
        ?>
        <# 
        var calculatorType = '<?php echo esc_js($this->get_calculator_type()); ?>';
        var autoCalculate = settings.auto_calculate || 'yes';
        #>
        <div class="uk-mortgage-calc" data-calculator-type="{{ calculatorType }}" data-auto-calculate="{{ autoCalculate }}">
            <div class="calculator-header">
                <# if (settings.title) { #>
                    <h3 class="calculator-title">{{{ settings.title }}}</h3>
                <# } #>
                
                <# if (settings.description) { #>
                    <p class="calculator-description">{{{ settings.description }}}</p>
                <# } #>
            </div>
            
            <div class="elementor-preview-notice">
                <p><?php esc_html_e('Calculator preview - live calculations available on frontend', 'uk-mortgage-calc'); ?></p>
            </div>
        </div>
        <?php
    }
}