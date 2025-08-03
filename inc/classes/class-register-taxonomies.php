<?php

/**
 * Register Custom Taxonomies
 *
 * @package interactive-lesson
 * @since 1.0.0
 */

namespace Interactive_Lesson\Inc;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use Interactive_Lesson\Inc\Traits\Singleton;

/**
 * Register Post Types class.
 *
 * Handles registration of custom post types for the current theme/plugin.
 *
 * @since 1.0.0
 */
class Register_Taxonomies
{
    use Singleton;

    /**
     * Private constructor to prevent direct object creation.
     *
     * Sets up hooks for post type registration.
     *
     * @since 1.0.0
     */
    protected function __construct()
    {
        $this->setup_hooks();
    }

    /**
     * Set up action hooks.
     *
     * @since 1.0.0
     * @return void
     */
    protected function setup_hooks()
    {
        add_action('init', [$this, 'register_taxonomies'], 0);
    }

    /**
     * Register a taxonomy, post_types_categories for the post types.
     *
     * @link https://codex.wordpress.org/Function_Reference/register_taxonomy
     * 
     * @since 1.0.0
     * @return void
     */
    public function register_taxonomies()
    {

        if (! is_blog_installed()) {
            return;
        }

        // Add new taxonomy, make it hierarchical
        $custom_taxonomy_types = self::taxonomy_args();

        if ($custom_taxonomy_types) {

            foreach ($custom_taxonomy_types as $key =>  $value) {

                if ('category' == $value['hierarchical']) {

                    // Add new taxonomy, make it hierarchical (like categories)
                    $labels = array(
                        'name'              => esc_html_x($value['general_name'], 'taxonomy general name', 'interactive-lesson'),
                        'singular_name'     => esc_html_x($value['singular_name'], 'taxonomy singular name', 'interactive-lesson'),
                        'search_items'      => esc_html__('Search ' . $value['general_name'], 'interactive-lesson'),
                        'all_items'         => esc_html__('All ' . $value['general_name'], 'interactive-lesson'),
                        'parent_item'       => esc_html__('Parent ' . $value['singular_name'], 'interactive-lesson'),
                        'parent_item_colon' => esc_html__('Parent ' . $value['singular_name'] . ':', 'interactive-lesson'),
                        'edit_item'         => esc_html__('Edit ' . $value['singular_name'], 'interactive-lesson'),
                        'update_item'       => esc_html__('Update '  . $value['singular_name'], 'interactive-lesson'),
                        'add_new_item'      => esc_html__('Add ' . $value['singular_name'], 'interactive-lesson'),
                        'new_item_name'     => esc_html__('New ' . $value['singular_name'] . ' Name', 'interactive-lesson'),
                        'menu_name'         => esc_html__($value['general_name'], 'interactive-lesson'),
                    );

                    $args = array(
                        'hierarchical'      => true,
                        'labels'            => $labels,
                        'show_ui'           => true,
                        'show_in_menu'      => true,
                        'show_admin_column' => true,
                        'show_in_nav_menus' => true,
                        'show_in_rest'      => true,
                        'rewrite'           => array('slug' => $value['slug'], 'hierarchical' => true, 'with_front' => false),
                    );
                    register_taxonomy($key, $value['post_type'], $args);
                }

                if ('tag' == $value['hierarchical']) {

                    $labels = array(
                        'name'                       => esc_html_x($value['general_name'], 'taxonomy general name', 'interactive-lesson'),
                        'singular_name'              => esc_html_x($value['singular_name'], 'taxonomy singular name', 'interactive-lesson'),
                        'search_items'               => esc_html__('Search ' . $value['general_name'], 'interactive-lesson'),
                        'popular_items'              => esc_html__('Popular ' . $value['general_name'], 'interactive-lesson'),
                        'all_items'                  => esc_html__('All ' . $value['general_name'], 'interactive-lesson'),
                        'parent_item'                => null,
                        'parent_item_colon'          => null,
                        'edit_item'                  => esc_html__('Edit ' . $value['singular_name'], 'interactive-lesson'),
                        'update_item'                => esc_html__('Update ' . $value['singular_name'], 'interactive-lesson'),
                        'add_new_item'               => esc_html__('Add ' . $value['singular_name'], 'interactive-lesson'),
                        'new_item_name'              => esc_html__('New ' . $value['singular_name'] . ' Name', 'interactive-lesson'),
                        'separate_items_with_commas' => esc_html__('Separate ' . strtolower($value['general_name']) . ' with commas', 'interactive-lesson'),
                        'add_or_remove_items'        => esc_html__('Add or remove ' . strtolower($value['general_name']), 'interactive-lesson'),
                        'choose_from_most_used'      => esc_html__('Choose from the most used ' . strtolower($value['singular_name']), 'interactive-lesson'),
                        'not_found'                  => esc_html__('No ' . strtolower($value['singular_name']) . ' found.', 'interactive-lesson'),
                        'menu_name'                  => esc_html__($value['general_name'], 'interactive-lesson'),
                    );

                    $args = array(
                        'hierarchical'      => false,
                        'labels'            => $labels,
                        'show_ui'           => true,
                        'show_admin_column' => true,
                        'show_in_nav_menus' => true,
                        'show_in_rest'      => true,
                        'rewrite'           => array('slug' => $value['slug'], 'hierarchical' => true, 'with_front' => false),
                    );
                    register_taxonomy($key, $value['post_type'], $args);
                }
            }
        }
    }

    /**
     * Get taxonomy types arguments
     *
     * This function returns an array of arguments for each taxonomy type.
     * The keys of the array are the taxonomy names and the values are arrays
     * with the following keys:
     * - hierarchical: whether the taxonomy is hierarchical (true) or not (false)
     * - slug: the slug of the taxonomy
     * - singular_name: the singular name of the taxonomy
     * - general_name: the general name of the taxonomy
     * - post_type: the post type to which the taxonomy is assigned
     *
     * @return array of default settings
     */
    public static function taxonomy_args()
    {

        return array(
            'grade_level'           => array(
                'hierarchical'      => 'category',
                'slug'              => 'grade-level',
                'singular_name'     => esc_html__('Grade Level', 'interactive-lesson'),
                'general_name'      => esc_html__('Grade Levels', 'interactive-lesson'),
                'post_type'         => array('interactive_lesson'),
            ),
        );
    }
}
