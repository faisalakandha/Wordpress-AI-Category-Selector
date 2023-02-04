<?php
/*
Plugin Name: AI Category Selector
Author: H.A.B.M. Faisal Akandha
Description: This plugin selects a category for your posts automatically.
*/

// Include the OpenAI API client file
require_once 'openai-api-client.php';

// Hook into the save_post action to automatically categorize posts
add_action('save_post', 'ai_category_selector_save_post', 10, 3);
// add_action('revision_done', 'ai_category_selector_save_post', 10, 3);

function ai_category_selector_save_post($post_id, $post, $update)
{
    // Skip auto-save and post revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id)) {
        return;
    }

    // Get the OpenAI API key from the plugin settings
    $api_key = get_option('ai_category_selector_api_key');
    // Check if the API key is set
    if (!empty($api_key)) {
        // Get the most representative category for the post content
        $categories = get_categories(array(
            'hide_empty'      => false,
        ));
        $categories_list = array();
        foreach ($categories as $category) {
            array_push($categories_list, $category->name);
        }
        $category_name = openai_api_query($api_key, wp_strip_all_tags($post->post_content), $categories_list);

        // Check if a category was found
        if (!empty($category_name)) {
            // Remove all existing categories from the post
            wp_set_post_terms($post_id, array(), 'category');

            // Try to get the category by name
            $category = get_term_by('name', $category_name, 'category');

            // Check if the category exists
            if ($category) {
                // Apply the found category to the post
                wp_set_post_terms($post_id, array($category->term_id), 'category', true);
            } else {
                // Apply the default category to the post
                wp_set_post_terms($post_id, array(get_option('default_category')), 'category', true);
            }
        }
    }
}

add_action('admin_init', 'ai_category_selector_settings_init');

function ai_category_selector_settings_init()
{
    add_settings_section('ai_category_selector', 'AI Category Selector', 'ai_category_selector_settings_section_callback', 'writing');

    add_settings_field('ai_category_selector_api_key', 'OpenAI API Key', 'ai_category_selector_api_key_callback', 'writing', 'ai_category_selector');

    register_setting('writing', 'ai_category_selector_api_key', array(
        'type' => 'string',
    ));
}


function ai_category_selector_settings_section_callback()
{
    echo '<p>Enter your OpenAI API key to use the AI Category Selector plugin.</p>';
}

function ai_category_selector_api_key_callback()
{
    echo '<input type="password" id="ai_category_selector_api_key" name="ai_category_selector_api_key" value="' . esc_attr(get_option('ai_category_selector_api_key')) . '" class="regular-text">';
}
