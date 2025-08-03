<?php

/**
 * GraphQL Endpoint for Lesson Data
 *
 * @package interactive-lesson
 * @since 1.0.0
 */

namespace Interactive_Lesson\Inc;

use Interactive_Lesson\Inc\Traits\Singleton;
use WP_Post;
use WPGraphQL;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class Graphql
 *
 * Handles GraphQL API endpoint for lesson data.
 */
class Graphql
{
    use Singleton;

    private const POST_TYPE = 'interactive_lesson';

    /**
     * Initializes the class and sets up hooks.
     */
    protected function __construct()
    {
        add_action('graphql_init', [$this, 'register_graphql_fields']);
    }

    /**
     * Registers GraphQL fields for lessons.
     */
    public function register_graphql_fields(): void
    {
        if (!class_exists('WPGraphQL')) {
            return;
        }

        register_graphql_object_type('Lesson', [
            'description' => esc_html__('Interactive Lesson data', 'interactive-lesson'),
            'fields'      => [
                'id'        => ['type' => 'ID', 'description' => esc_html__('Lesson ID', 'interactive-lesson')],
                'title'     => ['type' => 'String', 'description' => esc_html__('Lesson title', 'interactive-lesson')],
                'content'   => ['type' => 'String', 'description' => esc_html__('Lesson content', 'interactive-lesson')],
                'date'      => ['type' => 'String', 'description' => esc_html__('Publication date', 'interactive-lesson')],
                'permalink' => ['type' => 'String', 'description' => esc_html__('Lesson URL', 'interactive-lesson')],
                'thumbnail' => ['type' => 'String', 'description' => esc_html__('Thumbnail URL', 'interactive-lesson')],
                'acf'       => ['type' => 'String', 'description' => esc_html__('ACF fields as JSON', 'interactive-lesson')],
            ],
        ]);

        register_graphql_field('RootQuery', 'lesson', [
            'type'        => 'Lesson',
            'description' => esc_html__('Retrieve a single lesson by ID', 'interactive-lesson'),
            'args'        => [
                'id' => ['type' => 'ID', 'description' => esc_html__('Lesson ID', 'interactive-lesson')],
            ],
            'resolve'     => function ($source, $args) {
                $post_id = absint($args['id'] ?? 0);
                $post = get_post($post_id);

                if (!$post || $post->post_type !== self::POST_TYPE || $post->post_status !== 'publish') {
                    return null;
                }

                return $this->format_lesson_data($post);
            },
        ]);
    }

    /**
     * Formats lesson data for GraphQL responses.
     *
     * @param WP_Post $post Lesson post object.
     * @return array
     */
    private function format_lesson_data(WP_Post $post): array
    {
        $acf_fields = function_exists('get_fields') ? get_fields($post->ID) : [];
        $acf_json = $acf_fields ? wp_json_encode($acf_fields) : '';

        return [
            'id'        => $post->ID,
            'title'     => $post->post_title,
            'content'   => $post->post_content,
            'date'      => wp_date(get_option('date_format'), get_post_timestamp($post->ID)),
            'permalink' => get_permalink($post->ID),
            'thumbnail' => get_the_post_thumbnail_url($post->ID, 'thumbnail') ?: '',
            'acf'       => $acf_json,
        ];
    }
}
