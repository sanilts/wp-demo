<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Base Widget Class for UK Mortgage Calculators
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
    abstract protected function get_calculator_type();
    
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
                <?php wp_nonce_field('uk_mortgage_form', 'uk_mortgage_form_nonce'); ?>
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
}