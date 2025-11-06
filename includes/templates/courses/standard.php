<?php

/**
 * Standard template for displaying a course with LighterLMS
 *
 * @package LighterLMS
 */

use LighterLMS\Lessons;

/** @var \WP_Post */
global $post;

$lessons_db = new Lessons();

add_filter('posts_join', [$lessons_db, 'db_join'], 10, 2);

$lessons = get_posts([
    'post_type' => lighter_lms()->lesson_post_type,
    'numberposts' => -1,
    'lighter_course' => $post->ID,
    'suppress_filters' => false
]);

$lesson_data = array_map(function ($lesson) {
    return [
        'id' => $lesson->ID,
        'key' => get_post_meta($lesson->ID, '_lighter_lesson_key', true),
        'slug' => $lesson->post_name,
        'title' => $lesson->post_title,
        'sortOrder' => get_post_meta($lesson->ID, '_lighter_sort_order', true) ?: 0,
        'parentTopicKey' => get_post_meta($lesson->ID, '_lighter_parent_topic', true),
    ];
}, $lessons);

$image_size = apply_filters('lighter_course_image_size', 'lighter_course_main');

if (get_post_meta($post->ID, '_course_display_theme_header', true)) {
    get_header();
}
if (get_post_meta($post->ID, '_course_display_theme_sidebar', true)) {
    get_sidebar();
}
?>

<main id="main" <?= esc_attr(post_class('site-main')) ?> data-course-id="<?php echo esc_attr($post->ID) ?>">
    <?php lighterlms_course_sidebar($post); ?>
    <div class="the-content" id="the-content">
        <?php if (isset($_GET['lesson'])):
            echo '<div class="lighter-lesson-wrap">';
            foreach ($lesson_data as $lesson) {
                if (strtolower(sanitize_key($lesson['slug'])) == $_GET['lesson']) {
                    $args = [
                        'post_status' => 'publish',
                        'post_type' => lighter_lms()->lesson_post_type,
                        'p' => intval($lesson['id']),
                        'posts_per_page' => 1,
                    ];

                    $query = new WP_Query($args);

                    while ($query->have_posts()) {
                        $query->the_post();
                        the_content();
                    }
                    wp_reset_postdata();
                }
            }
            echo '</div>';
        else: ?>
            <h1><?php echo esc_html(the_title()); ?></h1>
            <?php
            $thumb = get_the_post_thumbnail($post, $image_size);
            if (! empty($thumb)) {
                echo $thumb;
            } elseif (lighter_lms()->connected_store === "woocommerce") {
                $prod_id = get_post_meta($post->ID, '_lighter_product_id', true);

                if ($prod_id) {
                    $product = \wc_get_product_object('simple', $prod_id);
                    echo wp_get_attachment_image($product->get_image_id(), $image_size);
                }
            }
            ?>
            <p><?php echo wpautop(esc_html(get_post_meta($post->ID, '_course_description', true))) ?></p>
        <?php endif; ?>
    </div>
</main>

<?php
if (get_post_meta($post->ID, '_course_display_theme_footer',  true)) {
    get_footer();
} else {
    wp_footer();
}
