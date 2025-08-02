<?php

/**
 * REST API settings for Interactive Lesson plugin.
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

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class Api_Settings
 *
 * Handles REST API settings for the Interactive Lesson plugin.
 *
 * @since 1.0.0
 */
class Api_Settings
{
    use Singleton;

    /**
     * API version.
     *
     * @var string
     */
    private const VERSION = 'v1';

    /**
     * API namespace.
     *
     * @var string
     */
    private const NAMESPACE = INTERACTIVE_LESSON_NAME;

    /**
     * Initializes the class and sets up hooks.
     *
     * @since 1.0.0
     */
    protected function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registers REST API routes.
     *
     * @since 1.0.0
     * @return void
     */
    public function register_routes(): void
    {
        // Register settings endpoint
        register_rest_route(
            self::NAMESPACE . '/' . self::VERSION,
            '/settings',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_item'],
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
                    'callback'            => [$this, 'update_item'],
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
                    'callback'            => [$this, 'retrieve_results'],
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
    public function get_item(WP_REST_Request $request)
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
    public function update_item(WP_REST_Request $request)
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
        return $this->get_item($request);
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
    public function retrieve_results(WP_REST_Request $request)
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
