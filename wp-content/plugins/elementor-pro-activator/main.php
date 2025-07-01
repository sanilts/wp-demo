<?php
/**
 * Plugin Name: Elementor Pro Activator
 * Plugin URI: https://www.gpltimes.com
 * Description: Allows users to activate Elementor Pro features without the typical license purchase.
 * Version: 1.0.5
 * Author: GPL Times
 * Author URI: https://www.gpltimes.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
defined( 'ABSPATH' ) || exit;

// Delay our custom functionality until WordPress is fully loaded
add_action('plugins_loaded', function() {
    $PLUGIN_NAME = 'Elementor Pro Activator';
    $PLUGIN_DOMAIN = 'elementor-pro-activator';

    // Load the functions file and extract the utilities
    extract(require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php');

    // Check for the GPL Times auto updater plugin and show admin notice if not installed
    add_action('admin_notices', function() use ($is_plugin_installed) {
        // Check if plugin is installed but not activated
        $plugin_installed = $is_plugin_installed('gpltimes/gpltimes.php');
        $plugin_activated = is_plugin_active('gpltimes/gpltimes.php');

        // Don't show notice if plugin is installed and activated
        if ($plugin_installed && $plugin_activated) {
            return;
        }

        // Check if user has dismissed the notice
        $current_user_id = get_current_user_id();
        $notice_dismissed = get_user_meta($current_user_id, 'gpltimes_notice_dismissed', true);

        // Don't show if user has dismissed and it's been less than 7 days
        if ($notice_dismissed && (time() - intval($notice_dismissed) < 7 * DAY_IN_SECONDS)) {
            return;
        }

        // Notice styling
        $main_color = 'var(--main-color, #BC5A94)';
        $notice_id = 'gpltimes-plugin-notice';
        ?>
        <div id="<?php echo esc_attr($notice_id); ?>" class="notice notice-warning is-dismissible" style="border-left-color: <?php echo esc_attr($main_color); ?>; padding: 15px; display: flex; align-items: flex-start;">
            <div style="margin-right: 15px;">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="<?php echo esc_attr($main_color); ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 17L12 22L22 17" stroke="<?php echo esc_attr($main_color); ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 12L12 17L22 12" stroke="<?php echo esc_attr($main_color); ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div style="flex: 1;">
                <h3 style="margin-top: 0; margin-bottom: 5px;"><?php _e('Elementor Pro Templates Access', 'elementor-pro-activator'); ?></h3>
                <?php if ($plugin_installed && !$plugin_activated): ?>
                    <p style="margin-bottom: 15px;"><?php _e('The <strong>GPL Times</strong> auto updater plugin is installed but not activated. Please activate it to enable importing Elementor Pro templates and Kit Library content.', 'elementor-pro-activator'); ?></p>
                    <p>
                        <a href="#" class="button button-primary activate-gpltimes" style="background-color: <?php echo esc_attr($main_color); ?>; border-color: <?php echo esc_attr($main_color); ?>;">
                            <?php _e('Activate Plugin', 'elementor-pro-activator'); ?>
                        </a>
                    </p>
                <?php else: ?>
                    <p style="margin-bottom: 15px;"><?php _e('To unlock all Elementor Pro templates and Kit Library features, please install the <strong>GPL Times</strong> auto updater plugin.', 'elementor-pro-activator'); ?></p>
                    <p>
                        <a href="#" class="button button-primary install-gpltimes" style="background-color: <?php echo esc_attr($main_color); ?>; border-color: <?php echo esc_attr($main_color); ?>;">
                            <?php _e('Install Now', 'elementor-pro-activator'); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php

        // Add inline script to handle the installation/activation process
        add_action('admin_footer', function() use ($plugin_installed, $notice_id) {
            ?>
            <script>
            jQuery(document).ready(function($) {
                // Handle notice dismissal
                $(document).on('click', '#<?php echo esc_js($notice_id); ?> .notice-dismiss', function() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'dismiss_gpltimes_notice',
                            nonce: '<?php echo wp_create_nonce('dismiss-gpltimes-notice'); ?>'
                        }
                    });
                });

                <?php if ($plugin_installed): ?>
                $('.activate-gpltimes').on('click', function(e) {
                    e.preventDefault();

                    var $button = $(this);
                    $button.text('<?php _e('Activating...', 'elementor-pro-activator'); ?>').attr('disabled', true);

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'activate_gpltimes_plugin',
                            nonce: '<?php echo wp_create_nonce('activate-gpltimes-plugin'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $button.text('<?php _e('Activated!', 'elementor-pro-activator'); ?>');
                                setTimeout(function() {
                                    location.reload();
                                }, 1000);
                            } else {
                                $button.text('<?php _e('Error', 'elementor-pro-activator'); ?>');
                                alert(response.data);
                                $button.attr('disabled', false).text('<?php _e('Try Again', 'elementor-pro-activator'); ?>');
                            }
                        },
                        error: function() {
                            $button.attr('disabled', false).text('<?php _e('Try Again', 'elementor-pro-activator'); ?>');
                        }
                    });
                });
                <?php else: ?>
                $('.install-gpltimes').on('click', function(e) {
                    e.preventDefault();

                    var $button = $(this);
                    $button.text('<?php _e('Installing...', 'elementor-pro-activator'); ?>').attr('disabled', true);

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'install_gpltimes_plugin',
                            nonce: '<?php echo wp_create_nonce('install-gpltimes-plugin'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $button.text('<?php _e('Installed & Activated!', 'elementor-pro-activator'); ?>');
                                setTimeout(function() {
                                    location.reload();
                                }, 1000);
                            } else {
                                $button.text('<?php _e('Error', 'elementor-pro-activator'); ?>');
                                alert(response.data);
                                $button.attr('disabled', false).text('<?php _e('Try Again', 'elementor-pro-activator'); ?>');
                            }
                        },
                        error: function() {
                            $button.attr('disabled', false).text('<?php _e('Try Again', 'elementor-pro-activator'); ?>');
                        }
                    });
                });
                <?php endif; ?>
            });
            </script>
            <?php
        });
    });

    // AJAX handler for dismissing the notice
    add_action('wp_ajax_dismiss_gpltimes_notice', function() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dismiss-gpltimes-notice')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Store dismissal time in user meta
        update_user_meta(get_current_user_id(), 'gpltimes_notice_dismissed', time());

        wp_send_json_success();
    });

    // Ajax action to activate the GPL Times plugin
    add_action('wp_ajax_activate_gpltimes_plugin', function() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'activate-gpltimes-plugin')) {
            wp_send_json_error(__('Security check failed.', 'elementor-pro-activator'));
        }

        // Check user permissions
        if (!current_user_can('activate_plugins')) {
            wp_send_json_error(__('You do not have permission to activate plugins.', 'elementor-pro-activator'));
        }

        // Include necessary files
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        // Activate the plugin
        $activate = activate_plugin('gpltimes/gpltimes.php');

        if (is_wp_error($activate)) {
            wp_send_json_error($activate->get_error_message());
        }

        wp_send_json_success(__('Plugin activated successfully.', 'elementor-pro-activator'));
    });

    // Ajax action to install and activate the GPL Times plugin
    add_action('wp_ajax_install_gpltimes_plugin', function() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'install-gpltimes-plugin')) {
            wp_send_json_error(__('Security check failed.', 'elementor-pro-activator'));
        }

        // Check user permissions
        if (!current_user_can('install_plugins')) {
            wp_send_json_error(__('You do not have permission to install plugins.', 'elementor-pro-activator'));
        }

        // Include necessary files
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        // Download URL
        $download_url = 'https://f004.backblazeb2.com/file/gpltimes/gpltimes.zip';

        // Plugin destination path
        $plugin_path = WP_PLUGIN_DIR . '/gpltimes';

        // If the plugin already exists, remove it
        if (file_exists($plugin_path)) {
            WP_Filesystem();
            global $wp_filesystem;
            $wp_filesystem->rmdir($plugin_path, true);
        }

        // Download and install the plugin
        $skin = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader($skin);
        $result = $upgrader->install($download_url);

        // Check for installation errors
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        if ($skin->get_errors()->has_errors()) {
            wp_send_json_error($skin->get_error_messages());
        }

        if (!$result) {
            wp_send_json_error(__('Plugin installation failed.', 'elementor-pro-activator'));
        }

        // Activate the plugin
        $activate = activate_plugin('gpltimes/gpltimes.php');

        if (is_wp_error($activate)) {
            wp_send_json_error($activate->get_error_message());
        }

        wp_send_json_success(__('Plugin installed and activated successfully.', 'elementor-pro-activator'));
    });

    // Check for network activation
    if (!function_exists('is_plugin_active_for_network')) {
        require_once(ABSPATH . '/wp-admin/includes/plugin.php');
    }

    function is_network_activated() {
        return is_plugin_active_for_network(plugin_basename(__FILE__));
    }

    // License data
    $license_data = [
        'success' => true,
        "status" => "ACTIVE",
        "error" => "",
        'license' => 'valid',
        'item_id' => false,
        'item_name' => 'Elementor Pro',
        'checksum' => 'B5E0B5F8DD8689E6ACA49DD6E6E1A930',
        'expires' => 'lifetime',
        'payment_id' => '0123456789',
        'customer_email' => 'noreply@gmail.com',
        'customer_name' => 'GPL',
        'license_limit' => 1000,
        'site_count' => 1,
        'activations_left' => 999,
        'renewal_url' => '',
        'features' => [
            'template_access_level_20',
            'kit_access_level_20',
            'editor_comments',
            'activity-log',
            'breadcrumbs',
            'form',
            'posts',
            'template',
            'countdown',
            'slides',
            'price-list',
            'portfolio',
            'flip-box',
            'price-table',
            'login',
            'share-buttons',
            'theme-post-content',
            'theme-post-title',
            'nav-menu',
            'blockquote',
            'media-carousel',
            'animated-headline',
            'facebook-comments',
            'facebook-embed',
            'facebook-page',
            'facebook-button',
            'testimonial-carousel',
            'post-navigation',
            'search-form',
            'post-comments',
            'author-box',
            'call-to-action',
            'post-info',
            'theme-site-logo',
            'theme-site-title',
            'theme-archive-title',
            'theme-post-excerpt',
            'theme-post-featured-image',
            'archive-posts',
            'theme-page-title',
            'sitemap',
            'reviews',
            'table-of-contents',
            'lottie',
            'code-highlight',
            'hotspot',
            'video-playlist',
            'progress-tracker',
            'section-effects',
            'sticky',
            'scroll-snap',
            'page-transitions',
            'mega-menu',
            'nested-carousel',
            'loop-grid',
            'loop-carousel',
            'theme-builder',
            'elementor_icons',
            'elementor_custom_fonts',
            'dynamic-tags',
            'taxonomy-filter',
            'email',
            'email2',
            'mailpoet',
            'mailpoet3',
            'redirect',
            'header',
            'footer',
            'single-post',
            'single-page',
            'archive',
            'search-results',
            'error-404',
            'loop-item',
            'font-awesome-pro',
            'typekit',
            'gallery',
            'off-canvas',
            'link-in-bio-var-2',
            'link-in-bio-var-3',
            'link-in-bio-var-4',
            'link-in-bio-var-5',
            'link-in-bio-var-6',
            'link-in-bio-var-7',
            'search',
            'element-manager-permissions',
            'akismet',
            'display-conditions',
            'woocommerce-products',
            'wc-products',
            'woocommerce-product-add-to-cart',
            'wc-elements',
            'wc-categories',
            'woocommerce-product-price',
            'woocommerce-product-title',
            'woocommerce-product-images',
            'woocommerce-product-upsell',
            'woocommerce-product-short-description',
            'woocommerce-product-meta',
            'woocommerce-product-stock',
            'woocommerce-product-rating',
            'wc-add-to-cart',
            'dynamic-tags-wc',
            'woocommerce-product-data-tabs',
            'woocommerce-product-related',
            'woocommerce-breadcrumb',
            'wc-archive-products',
            'woocommerce-archive-products',
            'woocommerce-product-additional-information',
            'woocommerce-menu-cart',
            'woocommerce-product-content',
            'woocommerce-archive-description',
            'paypal-button',
            'woocommerce-checkout-page',
            'woocommerce-cart',
            'woocommerce-my-account',
            'woocommerce-purchase-summary',
            'woocommerce-notices',
            'settings-woocommerce-pages',
            'settings-woocommerce-notices',
            'popup',
            'custom-css',
            'global-css',
            'custom_code',
            'custom-attributes',
            'form-submissions',
            'form-integrations',
            'dynamic-tags-acf',
            'dynamic-tags-pods',
            'dynamic-tags-toolset',
            'editor_comments',
            'stripe-button',
            'role-manager',
            'global-widget',
            'activecampaign',
            'cf7db',
            'convertkit',
            'discord',
            'drip',
            'getresponse',
            'mailchimp',
            'mailerlite',
            'slack',
            'webhook',
            'product-single',
            'product-archive',
            'wc-single-elements'
        ],
        'tier' => 'expert',
        'generation' => 'empty'
    ];

    function activate_license_for_all_sites() {
        global $wpdb, $license_data;

        $site_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

        foreach ($site_ids as $site_id) {
            switch_to_blog($site_id);

            update_option('elementor_pro_license_key', md5('GPL'));
            update_option('elementor_pro_license_data', $license_data);

            restore_current_blog();
        }
    }

    // Set license key and data
    if (is_network_activated()) {
        add_action('admin_init', 'activate_license_for_all_sites');
    } else {
        add_action('admin_init', function () use ($license_data) {
            update_option('elementor_pro_license_key', md5('GPL'));
            update_option('elementor_pro_license_data', $license_data);
        });
    }

    // Filter HTTP requests to validate license
    add_filter('pre_http_request', function ($pre, $parsed_args, $url) use ($license_data) {
        if (strpos($url, 'https://my.elementor.com/api/v2/license/validate') !== false ||
            strpos($url, 'https://my.elementor.com/api/v2/license/activate') !== false) {
            return [
                'headers' => [],
                'body' => json_encode($license_data),
                'response' => [
                    'code' => 200,
                    'message' => 'OK'
                ]
            ];
        }

        if (strpos($url, 'https://my.elementor.com/api/v2/license/deactivate') !== false) {
            return [
                'headers' => [],
                'body' => json_encode(['success' => true]),
                'response' => [
                    'code' => 200,
                    'message' => 'OK'
                ]
            ];
        }

        if (strpos($url, 'https://my.elementor.com/api/connect/v1/activate/disconnect') !== false) {
            return [
                'headers' => [],
                'body' => 'true',
                'response' => [
                    'code' => 200,
                    'message' => 'OK'
                ]
            ];
        }

        return $pre;
    }, 99, 3);
}, 10);

// Hook into the WordPress HTTP API using the 'pre_http_request' filter
add_filter('pre_http_request', function ($preempt, $r, $url) {
    $intercept_urls = [
        'https://my.elementor.com/api/connect/v1/library/get_template_content',
        'https://my.elementor.com/api/v1/kits-library/kits/', // base URL for download-link
    ];
    $redirect_url_post = 'https://www.gpltimes.com/gpldata/elementorv2.php';
    $redirect_url_get = 'https://www.gpltimes.com/gpldata/elementorv3.php';

    $gplstatus = get_option('gplstatus');
    $gpltokenid = get_option('gpltokenid');
    $domain = parse_url(get_site_url(), PHP_URL_HOST);
    $domain = strtolower(trim($domain));

    // Skip the API call if essential parameters are missing
    if (empty($gplstatus) || empty($gpltokenid)) {
        return $preempt;
    }

    // Check if the request URL matches any of the intercept URLs
    foreach ($intercept_urls as $intercept_url) {
        if (strpos($url, $intercept_url) !== false) {
            // Determine if the request is a GET or POST
            if (isset($r['method']) && strtoupper($r['method']) === 'POST') {
                // Handle POST requests
                $request_data = array_merge($r, array(
                    'gpltokenid' => $gpltokenid,
                    'gplstatus' => $gplstatus,
                    'domain' => $domain
                ));

                // Send the request data to the redirect URL
                $response = wp_remote_post($redirect_url_post, array(
                    'method'    => 'POST',
                    'body'      => json_encode($request_data),
                    'headers'   => array('Content-Type' => 'application/json')
                ));

                // Check for errors in the response
                if (is_wp_error($response)) {
                    return $preempt;
                }

                // Get the response body
                $response_body = wp_remote_retrieve_body($response);
                $decoded_body = json_decode($response_body, true);

                // Extract the body part of the response
                if (isset($decoded_body['body'])) {
                    $response_body = $decoded_body['body'];
                }

                // Return the response body to the original requester
                return array(
                    'headers' => array(),
                    'body' => $response_body,
                    'response' => array(
                        'code' => 200,
                        'message' => 'OK'
                    ),
                );

            } elseif (isset($r['method']) && strtoupper($r['method']) === 'GET' && strpos($url, 'download-link') !== false) {
                // Handle GET requests for the download-link
                $query_args = array(
                    'gpltokenid' => $gpltokenid,
                    'gplstatus' => $gplstatus,
                    'domain' => $domain,
                    'kit_id' => explode('/', rtrim(parse_url($url, PHP_URL_PATH), '/'))[5]
                );

                $redirected_url = add_query_arg($query_args, $redirect_url_get);

                // Redirect the GET request to the new URL with added query parameters
                $response = wp_remote_get($redirected_url);

                // Check for errors in the response
                if (is_wp_error($response)) {
                    return $preempt;
                }

                // Get the response body
                $response_body = wp_remote_retrieve_body($response);

                // Return the response body to the original requester
                return array(
                    'headers' => array(),
                    'body' => $response_body,
                    'response' => array(
                        'code' => 200,
                        'message' => 'OK'
                    ),
                );
            }
        }
    }

    // Allow other requests to proceed normally
    return $preempt;
}, 10, 3);
