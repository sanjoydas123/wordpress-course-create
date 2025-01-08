<?php

add_action('init', function () {
    // Query all posts with 'acf-custom-post-type'
    $acf_custom_posts = get_posts([
        'post_type' => 'acf-custom-post-type',
        'numberposts' => -1,
    ]);

    foreach ($acf_custom_posts as $post) {
        // Parse the content for the post type slug
        $content = json_decode($post->post_content, true);
        if (isset($content['post_type'])) {
            $slug = sanitize_text_field($content['post_type']);
            $label = get_the_title($post->ID);

            // Register the post type dynamically
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
    }
});
