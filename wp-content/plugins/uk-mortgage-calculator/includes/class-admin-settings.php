<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple Admin Settings Class
 */
class UK_Mortgage_Calculator_Admin {
    
    private $options;
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'admin_init']);
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
    }
    
    public function add_admin_menu() {
        add_options_page(
            __('UK Mortgage Calculator Settings', 'uk-mortgage-calc'),
            __('Mortgage Calculator', 'uk-mortgage-calc'),
            'manage_options',
            'uk-mortgage-calculator',
            [$this, 'settings_page']
        );
        
        // Add data collection page
        add_management_page(
            __('Mortgage Calculator Data', 'uk-mortgage-calc'),
            __('Calculator Data', 'uk-mortgage-calc'),
            'manage_options',
            'uk-mortgage-data',
            [$this, 'data_page']
        );
    }
    
    public function admin_init() {
        register_setting(
            'uk_mortgage_settings_group',
            'uk_mortgage_settings',
            [$this, 'sanitize_settings']
        );
        
        // Email Settings Section
        add_settings_section(
            'email_settings_section',
            __('Email Settings', 'uk-mortgage-calc'),
            [$this, 'email_section_callback'],
            'uk-mortgage-calculator'
        );
        
        // Data Collection Section
        add_settings_section(
            'data_settings_section',
            __('Data Collection Settings', 'uk-mortgage-calc'),
            [$this, 'data_section_callback'],
            'uk-mortgage-calculator'
        );
        
        // Email Fields
        add_settings_field(
            'enable_email_notifications',
            __('Enable Email Notifications', 'uk-mortgage-calc'),
            [$this, 'email_notifications_callback'],
            'uk-mortgage-calculator',
            'email_settings_section'
        );
        
        add_settings_field(
            'admin_email',
            __('Admin Email', 'uk-mortgage-calc'),
            [$this, 'admin_email_callback'],
            'uk-mortgage-calculator',
            'email_settings_section'
        );
        
        add_settings_field(
            'email_template_subject',
            __('Email Subject Template', 'uk-mortgage-calc'),
            [$this, 'email_subject_callback'],
            'uk-mortgage-calculator',
            'email_settings_section'
        );
        
        add_settings_field(
            'email_template_content',
            __('Email Content Template', 'uk-mortgage-calc'),
            [$this, 'email_content_callback'],
            'uk-mortgage-calculator',
            'email_settings_section'
        );
        
        // Data Collection Fields
        add_settings_field(
            'collect_user_data',
            __('Collect User Data', 'uk-mortgage-calc'),
            [$this, 'collect_data_callback'],
            'uk-mortgage-calculator',
            'data_settings_section'
        );
        
        add_settings_field(
            'require_email_consent',
            __('Require Email Consent', 'uk-mortgage-calc'),
            [$this, 'email_consent_callback'],
            'uk-mortgage-calculator',
            'data_settings_section'
        );
        
        add_settings_field(
            'gdpr_compliance',
            __('GDPR Compliance Text', 'uk-mortgage-calc'),
            [$this, 'gdpr_text_callback'],
            'uk-mortgage-calculator',
            'data_settings_section'
        );
    }
    
    public function admin_scripts($hook) {
        if ('settings_page_uk-mortgage-calculator' !== $hook && 'tools_page_uk-mortgage-data' !== $hook) {
            return;
        }
        
        wp_enqueue_script('uk-mortgage-admin-js', UK_MORTGAGE_CALC_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], UK_MORTGAGE_CALC_VERSION, true);
        
        wp_localize_script('uk-mortgage-admin-js', 'ukMortgageAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('uk_mortgage_admin_nonce'),
            'messages' => [
                'confirm_delete' => __('Are you sure you want to delete this entry?', 'uk-mortgage-calc'),
                'confirm_export' => __('Export all data to CSV?', 'uk-mortgage-calc')
            ]
        ]);
    }
    
    public function sanitize_settings($input) {
        $sanitized = [];
        
        if (isset($input['enable_email_notifications'])) {
            $sanitized['enable_email_notifications'] = (bool) $input['enable_email_notifications'];
        }
        
        if (isset($input['admin_email'])) {
            $sanitized['admin_email'] = sanitize_email($input['admin_email']);
        }
        
        if (isset($input['email_template_subject'])) {
            $sanitized['email_template_subject'] = sanitize_text_field($input['email_template_subject']);
        }
        
        if (isset($input['email_template_content'])) {
            $sanitized['email_template_content'] = wp_kses_post($input['email_template_content']);
        }
        
        if (isset($input['collect_user_data'])) {
            $sanitized['collect_user_data'] = (bool) $input['collect_user_data'];
        }
        
        if (isset($input['require_email_consent'])) {
            $sanitized['require_email_consent'] = (bool) $input['require_email_consent'];
        }
        
        if (isset($input['gdpr_compliance'])) {
            $sanitized['gdpr_compliance'] = wp_kses_post($input['gdpr_compliance']);
        }
        
        return $sanitized;
    }
    
    public function settings_page() {
        $this->options = get_option('uk_mortgage_settings', []);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('UK Mortgage Calculator Settings', 'uk-mortgage-calc'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('uk_mortgage_settings_group');
                do_settings_sections('uk-mortgage-calculator');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    public function data_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Mortgage Calculator Data', 'uk-mortgage-calc'); ?></h1>
            
            <div class="uk-mortgage-data-actions">
                <a href="#" class="button button-secondary export-data-btn">
                    <?php esc_html_e('Export to CSV', 'uk-mortgage-calc'); ?>
                </a>
                <a href="#" class="button button-secondary clear-data-btn">
                    <?php esc_html_e('Clear All Data', 'uk-mortgage-calc'); ?>
                </a>
            </div>
            
            <div id="uk-mortgage-data-table">
                <?php $this->display_data_table(); ?>
            </div>
        </div>
        <?php
    }
    
    // Section Callbacks
    public function email_section_callback() {
        echo '<p>' . esc_html__('Configure email notifications and templates for sending calculation results to users.', 'uk-mortgage-calc') . '</p>';
    }
    
    public function data_section_callback() {
        echo '<p>' . esc_html__('Configure data collection and GDPR compliance settings.', 'uk-mortgage-calc') . '</p>';
    }
    
    // Field Callbacks
    public function email_notifications_callback() {
        $value = isset($this->options['enable_email_notifications']) ? $this->options['enable_email_notifications'] : false;
        echo '<input type="checkbox" name="uk_mortgage_settings[enable_email_notifications]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label>' . esc_html__('Enable email notifications for calculation results', 'uk-mortgage-calc') . '</label>';
    }
    
    public function admin_email_callback() {
        $value = isset($this->options['admin_email']) ? $this->options['admin_email'] : get_option('admin_email');
        echo '<input type="email" name="uk_mortgage_settings[admin_email]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('Admin email for notifications and copies', 'uk-mortgage-calc') . '</p>';
    }
    
    public function email_subject_callback() {
        $value = isset($this->options['email_template_subject']) ? $this->options['email_template_subject'] : 'Your Mortgage Calculation Results';
        echo '<input type="text" name="uk_mortgage_settings[email_template_subject]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('Subject line for emails sent to users. Use {calculator_type} for dynamic content.', 'uk-mortgage-calc') . '</p>';
    }
    
    public function email_content_callback() {
        $default_content = "Hello,\n\nThank you for using our mortgage calculator. Please find your calculation results attached.\n\nBest regards,\nThe Team";
        $value = isset($this->options['email_template_content']) ? $this->options['email_template_content'] : $default_content;
        echo '<textarea name="uk_mortgage_settings[email_template_content]" rows="8" class="large-text">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . esc_html__('Email template content. Available placeholders: {user_name}, {calculator_type}, {results}', 'uk-mortgage-calc') . '</p>';
    }
    
    public function collect_data_callback() {
        $value = isset($this->options['collect_user_data']) ? $this->options['collect_user_data'] : false;
        echo '<input type="checkbox" name="uk_mortgage_settings[collect_user_data]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label>' . esc_html__('Collect and store user calculation data', 'uk-mortgage-calc') . '</label>';
    }
    
    public function email_consent_callback() {
        $value = isset($this->options['require_email_consent']) ? $this->options['require_email_consent'] : true;
        echo '<input type="checkbox" name="uk_mortgage_settings[require_email_consent]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label>' . esc_html__('Require user consent before sending emails', 'uk-mortgage-calc') . '</label>';
    }
    
    public function gdpr_text_callback() {
        $default_text = 'By using this calculator and providing your email, you consent to us processing your data and sending you the calculation results. We will not share your data with third parties.';
        $value = isset($this->options['gdpr_compliance']) ? $this->options['gdpr_compliance'] : $default_text;
        echo '<textarea name="uk_mortgage_settings[gdpr_compliance]" rows="4" class="large-text">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . esc_html__('GDPR compliance text displayed to users', 'uk-mortgage-calc') . '</p>';
    }
    
    private function display_data_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'uk_mortgage_calculations';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            echo '<p>' . esc_html__('No data table found. Please activate the plugin to create database tables.', 'uk-mortgage-calc') . '</p>';
            return;
        }
        
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
        
        if (empty($results)) {
            echo '<p>' . esc_html__('No calculation data found.', 'uk-mortgage-calc') . '</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('ID', 'uk-mortgage-calc') . '</th>';
        echo '<th>' . esc_html__('Type', 'uk-mortgage-calc') . '</th>';
        echo '<th>' . esc_html__('User Email', 'uk-mortgage-calc') . '</th>';
        echo '<th>' . esc_html__('Date', 'uk-mortgage-calc') . '</th>';
        echo '<th>' . esc_html__('Actions', 'uk-mortgage-calc') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->id) . '</td>';
            echo '<td>' . esc_html(ucfirst($row->calculator_type)) . '</td>';
            echo '<td>' . esc_html($row->user_email ?: __('Not provided', 'uk-mortgage-calc')) . '</td>';
            echo '<td>' . esc_html(date('Y-m-d H:i:s', strtotime($row->created_at))) . '</td>';
            echo '<td>';
            echo '<a href="#" class="button button-small view-details" data-id="' . esc_attr($row->id) . '">' . esc_html__('View', 'uk-mortgage-calc') . '</a> ';
            echo '<a href="#" class="button button-small delete-entry" data-id="' . esc_attr($row->id) . '">' . esc_html__('Delete', 'uk-mortgage-calc') . '</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
}