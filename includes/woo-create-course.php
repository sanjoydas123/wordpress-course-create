<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Add a custom admin menu page
add_action('admin_menu', 'wpc_add_admin_menu_new');
function wpc_add_admin_menu_new()
{
    add_menu_page(
        'Create Course',
        'Create Course',
        'manage_options',
        'wpc-create-product',
        'wpc_create_product_page',
        'dashicons-cart',
        20
    );
}

// Render the admin page form
function wpc_create_product_page()
{
    // Verify WooCommerce and ACF are active
    if (!class_exists('WooCommerce') || !function_exists('acf_get_field_groups')) {
        echo '<div class="error"><p>WooCommerce or Advanced Custom Fields (ACF) is not active. Please activate both plugins.</p></div>';
        return;
    }

    // Fetch ACF field groups
    $field_groups = function_exists('acf_get_field_groups') ? acf_get_field_groups() : [];

    // Include nonce for security
    $nonce = wp_create_nonce('wpc_create_course');

    // Render the form
    include CPTF_PLUGIN_PATH . 'templates/export_import.php';

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_name'], $_POST['product_slug'], $_POST['acf_field_group'])) {
        check_admin_referer('wpc_create_course');
        wpc_handle_product_creation();
    }
}

// Handle form submission and product creation
function wpc_handle_product_creation()
{
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    $name = sanitize_text_field($_POST['product_name']);
    $slug = sanitize_title($_POST['product_slug']);
    $acf_field_group_key = sanitize_text_field($_POST['acf_field_group']);

    if (empty($name) || empty($slug) || empty($acf_field_group_key)) {
        wp_die('<b style="color:red;">All fields are required.</b>');
    }

    // Check if slug exists
    if (wpc_slug_exists($slug)) {
        wp_die('<b style="color:red;">Slug already exists. Use a unique slug.</b>');
    }

    // Create the WooCommerce product and custom post type
    $product_id = wpc_create_new_product($name, $slug, $acf_field_group_key);
    if ($product_id) {
        wpc_register_custom_post_type($name, $slug, $acf_field_group_key);
        echo '<div class="updated"><p>Course and associated resources created successfully.</p></div>';
    } else {
        wp_die('<b style="color:red;">Error creating course.</b>');
    }
}

// Check if a slug exists
function wpc_slug_exists($slug)
{
    $existing_post = get_page_by_path($slug, OBJECT, ['product', 'post', 'page']);
    return $existing_post ? true : false;
}

// Create a WooCommerce product
function wpc_create_new_product($name, $slug, $acf_field_group_key)
{
    // Ensure the WooCommerce classes are loaded
    if (!class_exists('WC_Product')) {
        return false;
    }

    $slug = strtolower(str_replace(' ', '-', $slug));

    // Step 1: Create a WooCommerce product
    $product = new WC_Product_Simple();
    $product->set_name($name); // Set the product name
    $product->set_slug("lessons-$slug"); // Set the product slug with "lessons-" prefix
    $product->set_status('publish'); // Set product status to 'publish'
    $product->save();
    $product_id = $product->get_id();

    $field_key = 'related_course_slug'; // Replace with the actual ACF field key
    $field_value = "lessons-" . $slug; // Replace with the value you want to set
    update_field($field_key, $field_value, $product_id);

    // Step 2: Create a post in the 'acf-custom-post-type'
    $acf_post_id = wp_insert_post([
        'post_title' => $name,
        'post_content' => wp_json_encode([
            'post_type' => $slug, // Store the slug for the dynamic post type
        ]),
        'post_type' => 'acf-custom-post-type',
        'post_status' => 'publish',
    ]);

    if ($acf_post_id) {
        // Step 3: Dynamically register the new custom post type
        register_custom_post_type($slug, $name);

        // Step 4: Associate the ACF field group with the new custom post type
        if ($acf_field_group_key) {
            $field_group = acf_get_field_group($acf_field_group_key);

            if ($field_group) {
                // Add the custom post type to the field group's locations
                $field_group['location'][] = [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => $slug,
                    ],
                ];

                // Update the field group
                acf_update_field_group($field_group);
            }
        }

        echo "<script type='text/javascript'>
            alert('Product, Custom Post Type Created Successfully and Assigned to ACF Field Group!');
            location.reload();
        </script>";
    }

    return $product_id;
}

// Function to dynamically register custom post types
function register_custom_post_type($slug, $label)
{
    register_post_type($slug, [
        'labels' => [
            'name' => $label,
            'singular_name' => $label,
            'add_new_item' => "Add New $label",
            'edit_item' => "Edit $label",
            'new_item' => "New $label",
            'view_item' => "View $label",
            'search_items' => "Search $label",
        ],
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'has_archive' => true,
        'show_in_rest' => true,
    ]);
}

// Register custom post type dynamically
function wpc_register_custom_post_type($name, $slug, $acf_field_group_key)
{
    $post_type = sanitize_title($slug);
    $data = [
        'label' => $name,
        'acf_field_group_key' => $acf_field_group_key,
    ];

    $saved_types = get_option('custom_post_types', []);
    $saved_types[$post_type] = $data;
    update_option('custom_post_types', $saved_types);

    wpc_update_acf_field_group($acf_field_group_key, $post_type);
}

// Update ACF field group to associate with custom post type
function wpc_update_acf_field_group($acf_field_group_key, $post_type)
{
    $field_group = acf_get_field_group($acf_field_group_key);
    if ($field_group) {
        $field_group['location'][] = [
            [
                'param' => 'post_type',
                'operator' => '==',
                'value' => $post_type,
            ],
        ];
        acf_update_field_group($field_group);
    }
}
