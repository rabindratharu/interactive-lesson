<?php

/**
 * REST Endpoint for Product Reviews
 *
 * @package interactive-lesson
 * @since 1.0.0
 */

namespace Interactive_Lesson\Inc;

use Interactive_Lesson\Inc\Traits\Singleton;
use Interactive_Lesson\Inc\Utils;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_Query;
use WP_Post;
use stdClass;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class REST Endpoint
 *
 * Handles REST API endpoints for product reviews.
 */
class Rest_Endpoint
{
    use Singleton;

    private const VERSION = 'v1';
    private const NAMESPACE = 'interactive-lesson';

    private const DEFAULT_POSTS_PER_PAGE = 9;
    private const DEFAULT_PAGE = 1;
    private const POST_TYPE = 'interactive_lesson';
    private const RATING_META_KEY = 'reviewer_rating';
    private const REVIEWER_META_KEY = 'reviewer_name';
    private const PRODUCT_META_KEY = 'review_item';

    /**
     * Initializes the class and sets up hooks.
     */
    protected function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registers REST API routes.
     * e.g. https://example.com/wp-json/interactive_lesson/v1/reviews?q='Hello'&category=23,43&post_tag=23,32&page_no=1&posts_per_page=9
     * e.g  https://example.com/wp-json/interactive_lesson/v1/reviews?rating=4
     * e.g  https://example.com/wp-json/interactive_lesson/v1/reviews?rating=4-5
     */
    public function register_routes(): void
    {
        // Register search endpoint
        register_rest_route(
            self::NAMESPACE . '/' . self::VERSION,
            '/reviews',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_search'],
                'permission_callback' => '__return_true',
                'args'                => $this->get_route_args(),
            ]
        );

        // Register settings endpoint
        register_rest_route(
            self::NAMESPACE . '/' . self::VERSION,
            '/settings',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_setting'],
                    'permission_callback' => [$this, 'get_item_permissions_check'],
                    'args'                => [
                        'context' => [
                            'default' => 'view',
                            'type'    => 'string',
                            'enum'    => ['view', 'edit'],
                        ],
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [$this, 'update_setting'],
                    'permission_callback' => [$this, 'get_item_permissions_check'],
                    'args' => rest_get_endpoint_args_for_schema($this->get_item_schema(), WP_REST_Server::EDITABLE),
                ],
            ]
        );
        // Register submission endpoint for quiz block
        register_rest_route(
            'quiz/' . self::VERSION,
            '/submit',
            [
                [
                    'methods'             => 'POST',
                    'callback'            => [$this, 'update_submission'],
                    //'permission_callback' => [$this, 'get_item_permissions_check'],
                    'permission_callback' => function () {
                        return is_user_logged_in();
                    }
                ],
            ]
        );
        // Register results endpoint for quiz block
        register_rest_route(
            'quiz/' . self::VERSION,
            '/results',
            [
                [
                    'methods'             => 'GET',
                    'callback'            => [$this, 'get_result'],
                    //'permission_callback' => [$this, 'get_item_permissions_check'],
                    'permission_callback' => function () {
                        return is_user_logged_in();
                    }
                ],
            ]
        );

        // Register meta fields
        register_meta('user', 'quiz_score', [
            'type' => 'integer',
            'description' => 'User quiz score',
            'single' => true,
            'show_in_rest' => true,
            'default' => 0
        ]);

        register_meta('user', 'quiz_answer_', [
            'type' => 'string',
            'description' => 'User answer for quiz question',
            'single' => true,
            'show_in_rest' => true
        ]);

        register_meta('user', 'quiz_correct_', [
            'type' => 'string',
            'description' => 'Correctness of quiz answer (1 or 0)',
            'single' => true,
            'show_in_rest' => true
        ]);
    }

    /**
     * Defines route arguments with validation and sanitization.
     *
     * @return array<string, array>
     */
    private function get_route_args(): array
    {
        return [
            'q' => [
                'required'          => false,
                'type'              => 'string',
                'description'       => esc_html__('Search query', 'interactive-lesson'),
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => [$this, 'validate_string'],
            ],
            'categories' => [
                'required'          => false,
                'type'              => 'string',
                'description'       => esc_html__('Comma-separated category IDs', 'interactive-lesson'),
                'sanitize_callback' => [$this, 'sanitize_comma_separated_ids'],
                'validate_callback' => [$this, 'validate_comma_separated_ids'],
            ],
            'tags' => [
                'required'          => false,
                'type'              => 'string',
                'description'       => esc_html__('Comma-separated tag IDs', 'interactive-lesson'),
                'sanitize_callback' => [$this, 'sanitize_comma_separated_ids'],
                'validate_callback' => [$this, 'validate_comma_separated_ids'],
            ],
            'page_no' => [
                'required'          => false,
                'type'              => 'integer',
                'description'       => esc_html__('Page number', 'interactive-lesson'),
                'sanitize_callback' => 'absint',
                'validate_callback' => [$this, 'validate_positive_integer'],
            ],
            'posts_per_page' => [
                'required'          => false,
                'type'              => 'integer',
                'description'       => esc_html__('Posts per page', 'interactive-lesson'),
                'sanitize_callback' => 'absint',
                'validate_callback' => [$this, 'validate_positive_integer'],
            ],
            'rating' => [
                'required'          => false,
                'type'              => 'string',
                'description'       => esc_html__('Rating value or range (e.g., 4.5 or 3.0-5.0)', 'interactive-lesson'),
                'sanitize_callback' => [$this, 'sanitize_rating'],
                'validate_callback' => [$this, 'validate_rating'],
            ],
        ];
    }

    /**
     * Retrieves search results for product reviews.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_search(WP_REST_Request $request): WP_REST_Response
    {
        $search_query = $this->build_search_query(
            $request->get_param('q'),
            $request->get_param('categories'),
            $request->get_param('tags'),
            $request->get_param('page_no'),
            $request->get_param('posts_per_page'),
            $request->get_param('rating')
        );

        $results = new WP_Query($search_query);
        $response = $this->build_response($results);

        return rest_ensure_response($response);
    }

    /**
     * Builds WP_Query arguments for product review search.
     *
     * @param string|null $search_term Search term.
     * @param string|null $category_ids Comma-separated category IDs.
     * @param string|null $tag_ids Comma-separated tag IDs.
     * @param int|null $page_no Page number.
     * @param int|null $posts_per_page Posts per page.
     * @param string|null $rating Rating value or range.
     * @return array<string, mixed>
     */
    private function build_search_query(
        ?string $search_term,
        ?string $category_ids,
        ?string $tag_ids,
        ?int $page_no,
        ?int $posts_per_page,
        ?string $rating
    ): array {
        $query = [
            'post_type'              => self::POST_TYPE,
            'posts_per_page'         => $posts_per_page ?? self::DEFAULT_POSTS_PER_PAGE,
            'post_status'            => 'publish',
            'paged'                  => $page_no ?? self::DEFAULT_PAGE,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        ];

        if (!empty($search_term)) {
            $query['s'] = $search_term;
        }

        if (!empty($category_ids) || !empty($tag_ids)) {
            $query['tax_query'] = ['relation' => 'AND'];
        }

        if (!empty($category_ids)) {
            $query['tax_query'][] = [
                'taxonomy' => 'category',
                'field'    => 'id',
                'terms'    => array_map('intval', explode(',', $category_ids)),
                'operator' => 'IN',
            ];
        }

        if (!empty($tag_ids)) {
            $query['tax_query'][] = [
                'taxonomy' => 'post_tag',
                'field'    => 'id',
                'terms'    => array_map('intval', explode(',', $tag_ids)),
                'operator' => 'IN',
            ];
        }

        if (!empty($rating)) {
            $query['meta_query'] = $this->build_rating_meta_query($rating);
        }

        return $query;
    }

    /**
     * Builds meta query for rating filter.
     *
     * @param string $rating Rating value or range (e.g., '4.5' or '3.0-5.0').
     * @return array<string, mixed>
     */
    private function build_rating_meta_query(string $rating): array
    {
        $meta_query = [
            'relation' => 'AND',
            [
                'key'     => self::RATING_META_KEY,
                'compare' => 'EXISTS',
            ],
        ];

        if (strpos($rating, '-') !== false) {
            // Handle range (e.g., '3.0-5.0')
            [$min, $max] = array_map('floatval', explode('-', $rating));
            $meta_query[] = [
                'key'     => self::RATING_META_KEY,
                'value'   => [$min, $max],
                'type'    => 'DECIMAL(3,1)',
                'compare' => 'BETWEEN',
            ];
        } else {
            // Handle exact match (e.g., '4.5')
            $meta_query[] = [
                'key'     => self::RATING_META_KEY,
                'value'   => floatval($rating),
                'type'    => 'DECIMAL(3,1)',
                'compare' => '=',
            ];
        }

        return $meta_query;
    }

    /**
     * Builds response data for product review search results.
     *
     * @param WP_Query $results Query results.
     * @return stdClass
     */
    private function build_response(WP_Query $results): stdClass
    {
        $posts = array_map(
            function (WP_Post $post): array {
                $rating = get_post_meta($post->ID, self::RATING_META_KEY, true);
                $reviewer_name = get_post_meta($post->ID, self::REVIEWER_META_KEY, true);
                $product_id = get_post_meta($post->ID, self::PRODUCT_META_KEY, true);

                $product_title = null;
                $product_url = '#';
                if (is_numeric($product_id) && get_post_status($product_id) === 'publish') {
                    $product_title = get_the_title($product_id);
                    $product_url = get_permalink($product_id);
                }

                return [
                    'id'           => $post->ID,
                    'title'        => $post->post_title,
                    'content'      => $post->post_content,
                    'date'         => wp_date(get_option('date_format'), get_post_timestamp($post->ID)),
                    'permalink'    => get_permalink($post->ID),
                    'thumbnail'    => get_the_post_thumbnail_url($post->ID, 'thumbnail') ?: '',
                    'rating'       => is_numeric($rating) ? floatval($rating) : null,
                    'product'      => $product_title,
                    'product_url'  => $product_url,
                    'reviewer'     => $reviewer_name ? sanitize_text_field($reviewer_name) : '',
                ];
            },
            array_filter((array) $results->posts, fn($post) => $post instanceof WP_Post)
        );

        return (object) [
            'posts'          => $posts,
            'posts_per_page' => $results->query['posts_per_page'],
            'total_posts'    => $results->found_posts,
            'no_of_pages'    => $this->calculate_page_count(
                $results->found_posts,
                $results->query['posts_per_page']
            ),
        ];
    }

    /**
     * Calculates total page count.
     *
     * @param int $total_posts Total posts found.
     * @param int $posts_per_page Posts per page.
     * @return int
     */
    private function calculate_page_count(int $total_posts, int $posts_per_page): int
    {
        return $posts_per_page > 0 ? (int) ceil($total_posts / $posts_per_page) : 0;
    }

    /**
     * Validates positive integers.
     *
     * @param mixed $value Value to validate.
     * @return bool
     */
    public function validate_positive_integer($value): bool
    {
        return is_numeric($value) && $value > 0;
    }

    /**
     * Validates string input.
     *
     * @param mixed $value Value to validate.
     * @return bool
     */
    public function validate_string($value): bool
    {
        return is_string($value) && strlen($value) <= 255;
    }

    /**
     * Validates comma-separated IDs.
     *
     * @param mixed $value Value to validate.
     * @return bool
     */
    public function validate_comma_separated_ids($value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $ids = array_filter(explode(',', $value), 'is_numeric');
        return count($ids) === count(explode(',', $value));
    }

    /**
     * Sanitizes comma-separated IDs.
     *
     * @param mixed $value Value to sanitize.
     * @return string
     */
    public function sanitize_comma_separated_ids($value): string
    {
        if (!is_string($value)) {
            return '';
        }
        $ids = array_filter(array_map('absint', explode(',', $value)));
        return implode(',', $ids);
    }

    /**
     * Validates rating input (single value or range).
     *
     * @param mixed $value Value to validate.
     * @return bool
     */
    public function validate_rating($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Validate single value (e.g., '4.5')
        if (is_numeric($value)) {
            $rating = floatval($value);
            return $rating >= 0 && $rating <= 5;
        }

        // Validate range (e.g., '3.0-5.0')
        if (strpos($value, '-') !== false) {
            [$min, $max] = array_map('floatval', explode('-', $value));
            return $min >= 0 && $max <= 5 && $min <= $max && count(explode('-', $value)) === 2;
        }

        return false;
    }

    /**
     * Sanitizes rating input.
     *
     * @param mixed $value Value to sanitize.
     * @return string
     */
    public function sanitize_rating($value): string
    {
        if (!is_string($value)) {
            return '';
        }

        // Handle single value
        if (is_numeric($value)) {
            return sprintf('%.1f', floatval($value));
        }

        // Handle range
        if (strpos($value, '-') !== false) {
            [$min, $max] = array_map('floatval', explode('-', $value));
            return sprintf('%.1f-%.1f', $min, $max);
        }

        return '';
    }

    /**
     * Checks if a given request has access to read and manage settings.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error True if the request has access, WP_Error otherwise.
     */
    public function get_item_permissions_check(WP_REST_Request $request)
    {
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                __('You do not have permission to access this resource.', 'interactive-lesson'),
                ['status' => rest_authorization_required_code()]
            );
        }
        return true;
    }

    /**
     * Retrieves the settings.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error on failure.
     */
    public function get_setting(WP_REST_Request $request)
    {
        $saved_options = Utils::get_options();
        $schema = $this->get_registered_schema();

        $prepared_value = $this->prepare_value($saved_options, $schema);

        if (is_wp_error($prepared_value)) {
            return $prepared_value;
        }

        return new WP_REST_Response($prepared_value, 200);
    }

    /**
     * Updates settings.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error on failure.
     */
    public function update_setting(WP_REST_Request $request)
    {
        $schema = $this->get_registered_schema();
        $params = $request->get_params();

        // Validate the input against the schema
        $validation = rest_validate_value_from_schema($params, $schema);
        if (is_wp_error($validation)) {
            return new WP_Error(
                'rest_invalid_params',
                __('Invalid settings data provided.', 'interactive-lesson'),
                ['status' => 400, 'errors' => $validation->get_error_messages()]
            );
        }

        // Sanitize the input
        $sanitized_options = $this->prepare_value($params, $schema);
        if (is_wp_error($sanitized_options)) {
            return $sanitized_options;
        }

        // Update options
        Utils::update_options($sanitized_options);

        // Return the updated settings
        return $this->get_setting($request);
    }

    /**
     * Retrieves all registered options for the Settings API.
     *
     * @since 1.0.0
     * @return array Schema array, or default schema if not available.
     */
    protected function get_registered_schema(): array
    {
        static $cached_schema = null;

        if (null !== $cached_schema) {
            return $cached_schema;
        }

        // Try to fetch schema from Utils class
        if (method_exists(Utils::class, 'get_settings_schema')) {
            $schema = Utils::get_settings_schema();
        } else {
            // Fallback schema if Utils::get_settings_schema is not defined
            $schema = [
                'type'       => 'object',
                'properties' => Utils::get_default_options(),
            ];
        }

        // Ensure properties are defined
        if (!isset($schema['properties']) || !is_array($schema['properties'])) {
            $schema['properties'] = Utils::get_default_options();
        }

        $cached_schema = $schema;
        return $schema;
    }

    /**
     * Retrieves the site setting schema, conforming to JSON Schema.
     *
     * @since 1.0.0
     * @return array Item schema data.
     */
    public function get_item_schema(): array
    {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => self::NAMESPACE,
            'type'       => 'object',
            'properties' => $this->get_registered_schema()['properties'],
        ];

        /**
         * Filters the item's schema.
         *
         * @since 1.0.0
         * @param array $schema Item schema data.
         */
        $schema = apply_filters('rest_' . self::NAMESPACE . '_item_schema', $schema);

        return $schema;
    }

    /**
     * Prepares a value for output based on a schema.
     *
     * @since 1.0.0
     * @param mixed $value  Value to prepare.
     * @param array $schema Schema to match.
     * @return mixed|WP_Error Prepared value or WP_Error on failure.
     */
    protected function prepare_value($value, array $schema)
    {
        $sanitized_value = rest_sanitize_value_from_schema($value, $schema);

        if (is_null($sanitized_value)) {
            return new WP_Error(
                'rest_invalid_stored_value',
                __('The settings data could not be sanitized.', 'interactive-lesson'),
                ['status' => 400]
            );
        }

        return $sanitized_value;
    }

    /**
     * Handles the submission of quiz answers.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error on failure.
     */
    public function update_submission(WP_REST_Request $request)
    {
        // Verify nonce for security
        $nonce = $request->get_header('X-WP-Nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('invalid_nonce', 'Invalid nonce.', ['status' => 403]);
        }

        $params = $request->get_json_params();
        $question = sanitize_text_field($params['question'] ?? '');
        $answer = sanitize_text_field($params['answer'] ?? '');
        $correct_answer = sanitize_text_field($params['correct_answer'] ?? '');
        $user_id = get_current_user_id();

        // Validate input
        if (empty($question) || empty($answer) || empty($correct_answer)) {
            return new WP_Error('missing_params', 'Missing required parameters.', ['status' => 400]);
        }

        $question_hash = md5($question);
        $is_correct = (trim($answer) === trim($correct_answer));

        // Update user meta
        update_user_meta($user_id, 'quiz_answer_' . $question_hash, $answer);
        update_user_meta($user_id, 'quiz_correct_' . $question_hash, $is_correct ? '1' : '0');

        // Update score if correct
        if ($is_correct) {
            $current_score = (int) get_user_meta($user_id, 'quiz_score', true);
            update_user_meta($user_id, 'quiz_score', $current_score + 1);
        }

        return rest_ensure_response([
            'success' => true,
            'answer' => $answer,
            'message' => $is_correct ? 'Correct!' : 'Incorrect. The correct answer is ' . $correct_answer . '.'
        ]);
    }

    /**
     * Handles the retrieval of quiz results.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error on failure.
     */
    public function get_result(WP_REST_Request $request)
    {
        $user_id = get_current_user_id();
        global $wpdb;

        $meta_keys = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_key, meta_value FROM $wpdb->usermeta WHERE user_id = %d AND meta_key LIKE %s",
                $user_id,
                'quiz_answer_%'
            )
        );

        $results = [];
        foreach ($meta_keys as $meta) {
            $question_hash = substr($meta->meta_key, strlen('quiz_answer_'));
            $is_correct = get_user_meta($user_id, 'quiz_correct_' . $question_hash, true) === '1';
            $results[] = [
                'question_hash' => $question_hash,
                'answer' => esc_html($meta->meta_value),
                'is_correct' => $is_correct
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'results' => $results,
            'total_score' => (int) get_user_meta($user_id, 'quiz_score', true)
        ]);
    }
}
