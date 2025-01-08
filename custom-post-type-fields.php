<?php
/*
Plugin Name: SKD Custom Post Type Fields (with ACF Integration)
Plugin URI:  https://example.com/
Description: A plugin to register a custom post type and associate it with an existing ACF field group dynamically.
Version:     1.0
Author:      SKD
Author URI:  https://example.com/
License:     GPLv2 or later
Text Domain: skd-custom-post-type-fields
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

define('CPTF_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CPTF_PLUGIN_URL', plugin_dir_url(__FILE__));

// Register the custom post type
require_once plugin_dir_path(__FILE__) . 'includes/woo-create-course.php';

require_once plugin_dir_path(__FILE__) . 'includes/post-type.php';

/**
 * Plugin Activation Hook: Ensure ACF is Active
 */
function cptf_plugin_activation()
{
    if (!function_exists('acf')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            'This plugin requires Advanced Custom Fields (ACF) to be active. Please install and activate ACF.',
            'Plugin Activation Error',
            ['back_link' => true]
        );
    }
}
register_activation_hook(__FILE__, 'cptf_plugin_activation');

/**
 * Plugin Deactivation Hook
 */
function cptf_plugin_deactivation()
{
    // Clean up actions if needed (not required here)
}
register_deactivation_hook(__FILE__, 'cptf_plugin_deactivation');
