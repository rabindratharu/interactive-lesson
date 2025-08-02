<?php

/**
 * Shortcode class for displaying product reviews.
 *
 * @package interactive-lesson
 * @since 1.0.0
 */

namespace Interactive_Lesson\Inc;

use Interactive_Lesson\Inc\Traits\Singleton;
use WP_Query;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Handles to display reviews via shortcode.
 *
 * @since 1.0.0
 */
class Shortcode
{
    use Singleton;

    const PRODUCT_NAME_KEY  = 'review_item';
    const RATING_KEY        = 'reviewer_rating';
    const REVIEWER_NAME_KEY = 'reviewer_name';
    const POST_TYPE         = 'interactive_lesson';
    const REVIEWS_PER_PAGE  = 5;

    /**
     * Initializes the class and sets up hooks.
     *
     * @since 1.0.0
     */
    protected function __construct()
    {
        $this->setup_hooks();
    }


    /**
     * Sets up hooks for the class.
     *
     * Adds a shortcode to retrieve and display product reviews.
     * use [interactive_lesson_reviews] to display reviews.
     *
     * @since 1.0.0
     * @return void
     */
    protected function setup_hooks()
    {
        add_shortcode('interactive_lesson_reviews', [$this, 'render_shortcode']);
    }


    /**
     * Renders the product reviews using a shortcode.
     *
     * Retrieves and displays product reviews based on the specified shortcode attributes.
     *
     * @param array $atts Shortcode attributes.
     * @return string The rendered HTML content of the product reviews.
     */
    public function render_shortcode($atts)
    {
        shortcode_atts([], $atts, 'reviews');

        $paged = max(1, get_query_var('paged') ?: (isset($_GET['paged']) ? absint($_GET['paged']) : 1));

        $query_args = [
            'post_type'         => self::POST_TYPE,
            'posts_per_page'    => self::REVIEWS_PER_PAGE,
            'post_status'       => 'publish',
            'orderby'           => 'date',
            'order'             => 'DESC',
            'paged'             => $paged,
        ];

        $reviews = new WP_Query($query_args);
        return $this->render_reviews($reviews);
    }


    /**
     * Renders the HTML output for displaying product reviews.
     *
     * Iterates over the provided reviews, extracting and displaying review data
     * such as author, product, rating, and description. Calculates the average
     * rating for all displayed reviews. Handles pagination if multiple pages
     * of reviews exist.
     *
     * @param WP_Query $reviews Query object containing the reviews to be displayed.
     * @return string The rendered HTML content of the product reviews.
     */

    private function render_reviews($reviews)
    {
        ob_start();

?>
        <div class="interactive-lesson-reviews" itemscope itemtype="http://schema.org/AggregateRating">
            <?php if (!$reviews->have_posts()) : ?>
                <p><?php esc_html_e('No reviews found.', 'interactive-lesson'); ?></p>
            <?php else : ?>
                <?php
                $ratings = [];
                while ($reviews->have_posts()) :
                    $reviews->the_post();
                    $review = $this->get_review_data(get_the_ID());
                    if (!$review) {
                        continue;
                    }
                    $ratings[] = $review['rating'];
                ?>
                    <div class="interactive-lesson-review" itemscope itemtype="http://schema.org/Review">
                        <meta itemprop="author" content="<?php echo esc_attr($review['reviewer_name']); ?>">
                        <h3 itemprop="name"><?php the_title(); ?></h3>
                        <?php if ($review['product_id']) : ?>
                            <p>
                                <strong><?php esc_html_e('Product:', 'interactive-lesson'); ?></strong>
                                <a href="<?php echo esc_url(get_permalink($review['product_id'])); ?>" itemprop="itemReviewed" itemscope
                                    itemtype="http://schema.org/Product">
                                    <?php echo esc_html(get_the_title($review['product_id'])); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                        <p itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating">
                            <meta itemprop="ratingValue" content="<?php echo esc_attr($review['rating']); ?>">
                            <strong><?php esc_html_e('Rating:', 'interactive-lesson'); ?></strong>
                            <?php echo $this->render_star_rating((int) $review['rating']); ?>
                        </p>
                        <div itemprop="description"><?php the_content(); ?></div>
                        <p>
                            <strong><?php esc_html_e('Reviewer:', 'interactive-lesson'); ?></strong>
                            <?php echo esc_html($review['reviewer_name']); ?>
                        </p>
                    </div>
                <?php endwhile; ?>

                <?php $average_rating = $this->calculate_average_rating($ratings); ?>
                <meta itemprop="ratingValue" content="<?php echo esc_attr($average_rating); ?>">
                <meta itemprop="reviewCount" content="<?php echo esc_attr($reviews->found_posts); ?>">

                <?php if ($reviews->max_num_pages > 1) : ?>
                    <div class="interactive-lesson-pagination">
                        <?php
                        echo paginate_links([
                            'total'     => $reviews->max_num_pages,
                            'current'   => $reviews->query_vars['paged'],
                            'format'    => '?paged=%#%',
                            'prev_text' => __('« Previous', 'interactive-lesson'),
                            'next_text' => __('Next »', 'interactive-lesson'),
                        ]);
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
<?php

        wp_reset_postdata();
        return ob_get_clean();
    }


    /**
     * Retrieves review data associated with a post ID.
     *
     * Retrieves post meta values for the product ID, rating, and reviewer name.
     * If the rating is invalid (not between 1 and 5) or the reviewer name is empty,
     * returns null.
     *
     * @since 1.0.0
     * @param int $post_id Post ID.
     * @return array|null Array of review data containing product ID, rating, and reviewer name.
     */
    private function get_review_data($post_id)
    {
        $product_id = absint(get_post_meta($post_id, self::PRODUCT_NAME_KEY, true));
        $rating = absint(get_post_meta($post_id, self::RATING_KEY, true));
        $reviewer_name = sanitize_text_field(get_post_meta($post_id, self::REVIEWER_NAME_KEY, true));

        if (!$reviewer_name || $rating < 1 || $rating > 5) {
            return null;
        }

        return [
            'product_id'    => $product_id && get_post($product_id) ? $product_id : 0,
            'rating'        => $rating,
            'reviewer_name' => $reviewer_name,
        ];
    }


    /**
     * Calculates the average rating for a given array of ratings.
     *
     * Rounds the result to one decimal place.
     *
     * @since 1.0.0
     * @param array $ratings Array of ratings (1-5).
     * @return float The average rating.
     */
    private function calculate_average_rating($ratings)
    {
        return !empty($ratings) ? round(array_sum($ratings) / count($ratings), 1) : 0.0;
    }


    /**
     * Renders a star rating for a given rating.
     *
     * @since 1.0.0
     * @param int $rating Rating (1-5).
     * @return string A string of stars and empty stars representing the rating.
     */
    private function render_star_rating($rating)
    {
        $rating = min(max($rating, 0), 5); // Ensure rating is between 0 and 5
        return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
    }
}
