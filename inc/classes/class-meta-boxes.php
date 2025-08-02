<?php

/**
 * Register Meta Boxes
 *
 * @package interactive-lesson
 * @since 1.0.0
 */

namespace Interactive_Lesson\Inc;

use Interactive_Lesson\Inc\Traits\Singleton;
use Interactive_Lesson\Inc\Utils;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Register meta boxes class.
 *
 * Handles registration of custom meta boxes for product reviews.
 *
 * @since 1.0.0
 */
class Meta_Boxes
{
    use Singleton;

    /**
     * Meta field keys
     */
    const PRODUCT_NAME_FIELD = 'review_item';
    const RATING_FIELD = 'reviewer_rating';
    const REVIEWER_NAME_FIELD = 'reviewer_name';
    const NONCE_FIELD = 'met_box_nonce';
    const POST_TYPE = 'interactive_lesson';

    /**
     * Private constructor to prevent direct object creation.
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
        add_action('init', [$this, 'register_meta'], 20);
        add_action('add_meta_boxes', [$this, 'add_custom_meta_box']);
        add_action('save_post', [$this, 'save_post_meta_data']);
    }

    /**
     * Register post meta.
     * 
     * @since 1.0.0
     * @return void
     */
    public function register_meta()
    {
        $post_meta = [
            self::PRODUCT_NAME_FIELD => [
                'type' => 'integer',
                'description' => __('The ID of the product being reviewed', 'interactive-lesson'),
            ],
            self::RATING_FIELD => [
                'type' => 'number',
                'description' => __('The rating given in the review (1-5)', 'interactive-lesson'),
            ],
            self::REVIEWER_NAME_FIELD => [
                'type' => 'string',
                'description' => __('The name of the reviewer', 'interactive-lesson'),
            ]
        ];

        foreach ($post_meta as $meta_key => $args) {
            register_post_meta(
                self::POST_TYPE,
                $meta_key,
                [
                    'show_in_rest' => true,
                    'single' => true,
                    'type' => $args['type'],
                    'description' => $args['description'],
                    'sanitize_callback' => [$this, 'sanitize_meta_data'],
                    'auth_callback' => function () {
                        return current_user_can('edit_posts');
                    },
                ]
            );
        }
    }

    /**
     * Sanitize meta data before saving
     *
     * @param mixed $meta_value The meta value to sanitize
     * @param string $meta_key The meta key
     * @param string $object_type The object type
     * @return mixed Sanitized meta value
     */
    public function sanitize_meta_data($meta_value, $meta_key, $object_type)
    {
        if ($object_type !== self::POST_TYPE) {
            return $meta_value;
        }

        switch ($meta_key) {
            case self::PRODUCT_NAME_FIELD:
                return absint($meta_value);
            case self::RATING_FIELD:
                $rating = absint($meta_value);
                return ($rating >= 1 && $rating <= 5) ? $rating : '';
            case self::REVIEWER_NAME_FIELD:
                return sanitize_text_field($meta_value);
            default:
                return $meta_value;
        }
    }

    /**
     * Add custom meta box for product reviews.
     *
     * @since 1.0.0
     * @return void
     */
    public function add_custom_meta_box()
    {
        add_meta_box(
            'interactive_lesson_meta_box',
            esc_html__('Review Details', 'interactive-lesson'),
            [$this, 'render_meta_box_content'],
            self::POST_TYPE,
            'normal',
            'high',
            ['__back_compat_meta_box' => true]
        );
    }

    /**
     * Render meta box content.
     *
     * @param \WP_Post $post Post object.
     * @return void
     */
    public function render_meta_box_content($post)
    {
        // Verify post type
        if ($post->post_type !== self::POST_TYPE) {
            return;
        }

        // Get current values with proper sanitization
        $product_name = get_post_meta($post->ID, self::PRODUCT_NAME_FIELD, true);
        $rating = get_post_meta($post->ID, self::RATING_FIELD, true);
        $reviewer_name = get_post_meta($post->ID, self::REVIEWER_NAME_FIELD, true);

        // Get posts for dropdown
        $products = Utils::get_posts([
            'post_type' => 'post',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        // Security field
        wp_nonce_field(
            basename(__FILE__),
            self::NONCE_FIELD
        );
?>
        <div class="interactive-lesson-meta-box-container">
            <!-- Product Selection -->
            <div class="interactive-lesson-meta-box-field">
                <label for="<?php echo esc_attr(self::PRODUCT_NAME_FIELD); ?>">
                    <?php esc_html_e('Review Item', 'interactive-lesson'); ?>
                </label>
                <?php if (!empty($products)) : ?>
                    <select name="<?php echo esc_attr(self::PRODUCT_NAME_FIELD); ?>"
                        id="<?php echo esc_attr(self::PRODUCT_NAME_FIELD); ?>" class="interactive-lesson-select-field">
                        <option value="">
                            <?php esc_html_e('Select a Item', 'interactive-lesson'); ?>
                        </option>
                        <?php foreach ($products as $product_id => $product_title) : ?>
                            <option value="<?php echo esc_attr($product_id); ?>" <?php selected($product_name, $product_id); ?>>
                                <?php echo esc_html($product_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else : ?>
                    <p class="interactive-lesson-no-products">
                        <?php esc_html_e('No items found', 'interactive-lesson'); ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Rating Selection -->
            <div class="interactive-lesson-meta-box-field">
                <label for="<?php echo esc_attr(self::RATING_FIELD); ?>">
                    <?php esc_html_e('Rating (1-5)', 'interactive-lesson'); ?>
                </label>
                <select name="<?php echo esc_attr(self::RATING_FIELD); ?>" id="<?php echo esc_attr(self::RATING_FIELD); ?>"
                    class="interactive-lesson-select-field">
                    <option value="">
                        <?php esc_html_e('Select Rating', 'interactive-lesson'); ?>
                    </option>
                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                        <option value="<?php echo esc_attr($i); ?>" <?php selected($rating, $i); ?>>
                            <?php
                            printf(
                                esc_html__('%d Star%s', 'interactive-lesson'),
                                $i,
                                $i > 1 ? 's' : ''
                            );
                            ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Reviewer Name -->
            <div class="interactive-lesson-meta-box-field">
                <label for="<?php echo esc_attr(self::REVIEWER_NAME_FIELD); ?>">
                    <?php esc_html_e('Reviewer\'s Name', 'interactive-lesson'); ?>
                </label>
                <input type="text" name="<?php echo esc_attr(self::REVIEWER_NAME_FIELD); ?>"
                    id="<?php echo esc_attr(self::REVIEWER_NAME_FIELD); ?>" value="<?php echo esc_attr($reviewer_name); ?>"
                    class="interactive-lesson-text-field">
            </div>
        </div>
<?php
    }

    /**
     * Save post meta data when the post is saved.
     *
     * @param int $post_id Post ID.
     * @return void
     */
    public function save_post_meta_data(int $post_id)
    {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verify post type
        if (!isset($_POST['post_type']) || $_POST['post_type'] !== self::POST_TYPE) {
            return;
        }

        // Verify nonce
        if (
            !isset($_POST[self::NONCE_FIELD]) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST[self::NONCE_FIELD])),
                basename(__FILE__)
            )
        ) {
            return;
        }

        // Check user capabilities
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save Review Item
        if (isset($_POST[self::PRODUCT_NAME_FIELD])) {
            update_post_meta(
                $post_id,
                self::PRODUCT_NAME_FIELD,
                absint($_POST[self::PRODUCT_NAME_FIELD])
            );
        }

        // Save Rating (1-5 only)
        if (isset($_POST[self::RATING_FIELD])) {
            $rating = min(max(absint($_POST[self::RATING_FIELD]), 1), 5);
            update_post_meta(
                $post_id,
                self::RATING_FIELD,
                $rating ?: ''
            );
        }

        // Save Reviewer's Name
        if (isset($_POST[self::REVIEWER_NAME_FIELD])) {
            update_post_meta(
                $post_id,
                self::REVIEWER_NAME_FIELD,
                sanitize_text_field(wp_unslash($_POST[self::REVIEWER_NAME_FIELD]))
            );
        }
    }
}
