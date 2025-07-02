<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Only define the class if Elementor is available
if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

/**
 * Enhanced Base Widget Class for UK Mortgage Calculators
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
                'default' => 'yes',
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
        
        $this->add_control(
            'collect_email',
            [
                'label' => esc_html__('Collect User Email', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'uk-mortgage-calc'),
                'label_off' => esc_html__('No', 'uk-mortgage-calc'),
                'return_value' => 'yes',
                'default' => 'no',
                'description' => esc_html__('Add email field to collect user data', 'uk-mortgage-calc'),
            ]
        );
        
        // Register calculator-specific controls
        $this->register_calculator_specific_controls();
        
        $this->end_controls_section();
        
        // Style Controls
        $this->register_enhanced_style_controls();
    }
    
    /**
     * Register enhanced style controls
     */
    protected function register_enhanced_style_controls() {
        // Container Styling
        $this->start_controls_section(
            'container_style_section',
            [
                'label' => esc_html__('Container Style', 'uk-mortgage-calc'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_responsive_control(
            'container_padding',
            [
                'label' => esc_html__('Padding', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'container_margin',
            [
                'label' => esc_html__('Margin', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'container_background',
                'label' => esc_html__('Background', 'uk-mortgage-calc'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .uk-mortgage-calc',
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'container_border',
                'label' => esc_html__('Border', 'uk-mortgage-calc'),
                'selector' => '{{WRAPPER}} .uk-mortgage-calc',
            ]
        );
        
        $this->add_responsive_control(
            'container_border_radius',
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
                'name' => 'container_shadow',
                'label' => esc_html__('Box Shadow', 'uk-mortgage-calc'),
                'selector' => '{{WRAPPER}} .uk-mortgage-calc',
            ]
        );
        
        $this->end_controls_section();
        
        // Header Styling
        $this->start_controls_section(
            'header_style_section',
            [
                'label' => esc_html__('Header Style', 'uk-mortgage-calc'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'title_color',
            [
                'label' => esc_html__('Title Color', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc .calculator-title' => 'color: {{VALUE}}',
                ],
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
        
        $this->add_responsive_control(
            'title_spacing',
            [
                'label' => esc_html__('Title Spacing', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc .calculator-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'description_color',
            [
                'label' => esc_html__('Description Color', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc .calculator-description' => 'color: {{VALUE}}',
                ],
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
        
        $this->end_controls_section();
        
        // Form Styling
        $this->start_controls_section(
            'form_style_section',
            [
                'label' => esc_html__('Form Style', 'uk-mortgage-calc'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'label_color',
            [
                'label' => esc_html__('Label Color', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc .form-group label' => 'color: {{VALUE}}',
                ],
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
        
        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'input_background',
                'label' => esc_html__('Input Background', 'uk-mortgage-calc'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .uk-mortgage-calc .form-control',
            ]
        );
        
        $this->add_control(
            'input_text_color',
            [
                'label' => esc_html__('Input Text Color', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc .form-control' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'input_typography',
                'label' => esc_html__('Input Typography', 'uk-mortgage-calc'),
                'selector' => '{{WRAPPER}} .uk-mortgage-calc .form-control',
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'input_border',
                'label' => esc_html__('Input Border', 'uk-mortgage-calc'),
                'selector' => '{{WRAPPER}} .uk-mortgage-calc .form-control',
            ]
        );
        
        $this->add_responsive_control(
            'input_border_radius',
            [
                'label' => esc_html__('Input Border Radius', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc .form-control' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'input_padding',
            [
                'label' => esc_html__('Input Padding', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc .form-control' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        // Focus state
        $this->add_control(
            'input_focus_color',
            [
                'label' => esc_html__('Focus Border Color', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc .form-control:focus' => 'border-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'input_focus_shadow',
                'label' => esc_html__('Focus Box Shadow', 'uk-mortgage-calc'),
                'selector' => '{{WRAPPER}} .uk-mortgage-calc .form-control:focus',
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
        
        $this->start_controls_tabs('button_style_tabs');
        
        // Normal state
        $this->start_controls_tab(
            'button_normal_tab',
            [
                'label' => esc_html__('Normal', 'uk-mortgage-calc'),
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'button_background',
                'label' => esc_html__('Background', 'uk-mortgage-calc'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .uk-mortgage-calc .btn-primary',
            ]
        );
        
        $this->add_control(
            'button_text_color',
            [
                'label' => esc_html__('Text Color', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc .btn-primary' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'button_border',
                'label' => esc_html__('Border', 'uk-mortgage-calc'),
                'selector' => '{{WRAPPER}} .uk-mortgage-calc .btn-primary',
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_shadow',
                'label' => esc_html__('Box Shadow', 'uk-mortgage-calc'),
                'selector' => '{{WRAPPER}} .uk-mortgage-calc .btn-primary',
            ]
        );
        
        $this->end_controls_tab();
        
        // Hover state
        $this->start_controls_tab(
            'button_hover_tab',
            [
                'label' => esc_html__('Hover', 'uk-mortgage-calc'),
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'button_hover_background',
                'label' => esc_html__('Background', 'uk-mortgage-calc'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .uk-mortgage-calc .btn-primary:hover',
            ]
        );
        
        $this->add_control(
            'button_hover_text_color',
            [
                'label' => esc_html__('Text Color', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc .btn-primary:hover' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'button_hover_border',
                'label' => esc_html__('Border', 'uk-mortgage-calc'),
                'selector' => '{{WRAPPER}} .uk-mortgage-calc .btn-primary:hover',
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_hover_shadow',
                'label' => esc_html__('Box Shadow', 'uk-mortgage-calc'),
                'selector' => '{{WRAPPER}} .uk-mortgage-calc .btn-primary:hover',
            ]
        );
        
        $this->add_control(
            'button_hover_transition',
            [
                'label' => esc_html__('Transition Duration', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['s', 'ms'],
                'default' => [
                    'unit' => 's',
                    'size' => 0.3,
                ],
                'range' => [
                    's' => [
                        'min' => 0,
                        'max' => 3,
                        'step' => 0.1,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc .btn-primary' => 'transition: all {{SIZE}}{{UNIT}}',
                ],
            ]
        );
        
        $this->end_controls_tab();
        
        $this->end_controls_tabs();
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'label' => esc_html__('Typography', 'uk-mortgage-calc'),
                'selector' => '{{WRAPPER}} .uk-mortgage-calc .btn',
                'separator' => 'before',
            ]
        );
        
        $this->add_responsive_control(
            'button_padding',
            [
                'label' => esc_html__('Padding', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc .btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'button_border_radius',
            [
                'label' => esc_html__('Border Radius', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc .btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Results Styling
        $this->start_controls_section(
            'results_style_section',
            [
                'label' => esc_html__('Results Style', 'uk-mortgage-calc'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'results_background',
                'label' => esc_html__('Results Background', 'uk-mortgage-calc'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .uk-mortgage-calc .calculator-result',
            ]
        );
        
        $this->add_responsive_control(
            'results_padding',
            [
                'label' => esc_html__('Results Padding', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc .calculator-result' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'results_border',
                'label' => esc_html__('Results Border', 'uk-mortgage-calc'),
                'selector' => '{{WRAPPER}} .uk-mortgage-calc .calculator-result',
            ]
        );
        
        $this->add_responsive_control(
            'results_border_radius',
            [
                'label' => esc_html__('Results Border Radius', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc .calculator-result' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        // Result cards
        $this->add_control(
            'result_card_heading',
            [
                'label' => esc_html__('Result Cards', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'result_card_background',
                'label' => esc_html__('Card Background', 'uk-mortgage-calc'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .uk-mortgage-calc .result-card',
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'result_card_border',
                'label' => esc_html__('Card Border', 'uk-mortgage-calc'),
                'selector' => '{{WRAPPER}} .uk-mortgage-calc .result-card',
            ]
        );
        
        $this->add_responsive_control(
            'result_card_border_radius',
            [
                'label' => esc_html__('Card Border Radius', 'uk-mortgage-calc'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .uk-mortgage-calc .result-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'result_card_shadow',
                'label' => esc_html__('Card Shadow', 'uk-mortgage-calc'),
                'selector' => '{{WRAPPER}} .uk-mortgage-calc .result-card',
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
                
                <?php if ($settings['collect_email'] === 'yes'): ?>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="user_email"><?php esc_html_e('Email Address', 'uk-mortgage-calc'); ?></label>
                        <input type="email" class="form-control" id="user_email" name="user_email" 
                               placeholder="your@email.com">
                        <small class="form-text text-muted"><?php esc_html_e('Optional - to receive your results via email', 'uk-mortgage-calc'); ?></small>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="user_name"><?php esc_html_e('Your Name', 'uk-mortgage-calc'); ?></label>
                        <input type="text" class="form-control" id="user_name" name="user_name" 
                               placeholder="Your name">
                        <small class="form-text text-muted"><?php esc_html_e('Optional', 'uk-mortgage-calc'); ?></small>
                    </div>
                </div>
                
                <?php if ($settings['enable_email_reports'] === 'yes'): ?>
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="email_consent" value="1">
                            <?php echo wp_kses_post($this->get_gdpr_text()); ?>
                        </label>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                
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
                            <?php esc_html_e('Email Results', 'uk-mortgage-calc'); ?>
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
     * Get GDPR compliance text
     */
    private function get_gdpr_text() {
        $settings = get_option('uk_mortgage_settings', []);
        $default_text = 'I consent to receiving my calculation results via email and understand my data will be processed according to the privacy policy.';
        return $settings['gdpr_compliance'] ?? $default_text;
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