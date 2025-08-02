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

if (!defined('ABSPATH')) {
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
    const NONCE_FIELD = 'meta_box_nonce'; // Fixed typo in constant name
    const POST_TYPE = 'interactive_lesson';

    /**
     * Private constructor to prevent direct object creation.
     */
    protected function __construct()
    {
        $this->setup_hooks();
    }

    /**
     * Set up action hooks.
     */
    protected function setup_hooks()
    {
        // Register meta on 'init' with proper priority
        add_action('init', [$this, 'register_meta'], 20);

        // Add meta box only for our post type
        add_action('add_meta_boxes_' . self::POST_TYPE, [$this, 'add_custom_meta_box']);

        // Save hook with specific priority
        add_action('save_post_' . self::POST_TYPE, [$this, 'save_post_meta_data'], 10, 2);
    }

    /**
     * Register post meta specifically for our post type.
     */
    public function register_meta()
    {
        $post_meta = [
            self::PRODUCT_NAME_FIELD => [
                'type' => 'integer',
                'description' => __('The ID of the product being reviewed', 'interactive-lesson'),
                'sanitize_callback' => 'absint',
            ],
            self::RATING_FIELD => [
                'type' => 'number',
                'description' => __('The rating given in the review (1-5)', 'interactive-lesson'),
                'sanitize_callback' => [$this, 'sanitize_rating'],
            ],
            self::REVIEWER_NAME_FIELD => [
                'type' => 'string',
                'description' => __('The name of the reviewer', 'interactive-lesson'),
                'sanitize_callback' => 'sanitize_text_field',
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
                    'sanitize_callback' => $args['sanitize_callback'],
                    'auth_callback' => function () {
                        return current_user_can('edit_posts');
                    },
                ]
            );
        }
    }

    /**
     * Special sanitization for rating field
     */
    public function sanitize_rating($rating)
    {
        $rating = absint($rating);
        return ($rating >= 1 && $rating <= 5) ? $rating : '';
    }

    /**
     * Add custom meta box to our post type edit screen.
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
     */
    public function render_meta_box_content($post)
    {
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
                            <?php echo esc_html(sprintf(_n('%d Star', '%d Stars', $i, 'interactive-lesson'), $i)); ?>
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
     */
    public function save_post_meta_data($post_id, $post)
    {
        // Verify nonce
        if (
            !isset($_POST[self::NONCE_FIELD]) ||
            !wp_verify_nonce($_POST[self::NONCE_FIELD], basename(__FILE__))
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
            update_post_meta(
                $post_id,
                self::RATING_FIELD,
                $this->sanitize_rating($_POST[self::RATING_FIELD])
            );
        }

        // Save Reviewer's Name
        if (isset($_POST[self::REVIEWER_NAME_FIELD])) {
            update_post_meta(
                $post_id,
                self::REVIEWER_NAME_FIELD,
                sanitize_text_field($_POST[self::REVIEWER_NAME_FIELD])
            );
        }
    }
}
