<?php

/**
 * Standard template for displaying a course with LighterLMS
 *
 * @package LighterLMS
 */

use LighterLMS\Topics;
use LighterLMS\Lessons;

/** @var \WP_Post */
global $post;

/** @var Topics */
$topic_db = new Topics();
$lessons_db = new Lessons();

$topics_raw = $topic_db->get_by_course($post->ID);

add_filter('posts_join', [$lessons_db, 'db_join'], 10, 2);

$topics = array_map(function ($row) {
    return [
        'key' => $row->topic_key,
        'title' => $row->title,
        'sortOrder' => $row->sort_order,
        'courseId' => $row->post_id,
    ];
}, $topics_raw);

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
        'title' => $lesson->post_title,
        'sortOrder' => get_post_meta($lesson->ID, '_lighter_sort_order', true) ?: 0,
        'parentTopicKey' => get_post_meta($lesson->ID, '_lighter_parent_topic', true),
    ];
}, $lessons);

$course_structure = array_map(function ($topic) use ($lessons) {
    $lesson = [];

    if ($topic['key'] === "") {
    }

    return [
        ...$topic,
        'lessons' => $lesson
    ];
}, $topics);

if (get_post_meta($post->ID, '_course_display_theme_header', true)) {
    get_header();
}
if (get_post_meta($post->ID, '_course_display_theme_sidebar', true)) {
    get_sidebar();
}

$course_sidebar = [['title' => $post->post_title, 'href' => get_permalink($post)]];
foreach ($topics as $topic) {
    $course_sidebar[] = [
        'title' => $topic['title'],
        'lessons' => array_values(array_filter($lesson_data, fn($lesson) => $lesson['parentTopicKey'] === $topic['key'])),
    ];
}
?>

<main id="main" <?= esc_attr(post_class('site-main')) ?>>
    <?php // lighterlms_course_sidebar(); 
    // TODO: Move sidebar into function above.
    ?>
    <div class="lighterlms nav-wrap course-sidebar">
        <?php do_action('lighter_lms_course_before_topics_nav'); ?>
        <nav class="course-nav lighterlms">
            <ul class="course-topics">
                <?php foreach ($course_sidebar as $sb_item) :
                    if (array_key_exists('lessons', $sb_item)): ?>
                        <li>
                            <h3>
                                <button type="button" aria-expanded="true" aria-controls="<?= strtolower(esc_attr($sb_item['title'])) ?>-lessons" class="togglable-btn">
                                    <?= esc_html($sb_item['title']) ?>
                                </button>
                            </h3>
                            <ul class="course-lessons open">
                                <?php foreach ($sb_item['lessons'] as $lesson) {
                                    printf(
                                        '<li><a href="?lesson=%1$s" class="course-lesson %1$s" data-lesson="%1$s" data-lesson-id="%2$s">%3$s</a></li>',
                                        strtolower(sanitize_key($lesson['title'])),
                                        $lesson['id'],
                                        $lesson['title']
                                    );
                                } ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li>
                            <h1><a href="<?= esc_attr(esc_url($sb_item['href'])) ?>"><?= $sb_item['title'] ?></a></h1>
                        </li>
                <?php endif;
                endforeach;
                ?>
            </ul>
        </nav>
        <?php do_action('lighter_lms_course_after_topics_nav'); ?>
    </div>
    <div class="the-content" id="the-content">
        <?php if (isset($_GET['lesson'])) {
            echo '<div class="lesson-wrap">';
            foreach ($lesson_data as $lesson) {
                if (strtolower(sanitize_key($lesson['title'])) == $_GET['lesson']) {
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
        } else { ?>
            <h1><? esc_html(the_title()); ?></h1>
        <?php } ?>
    </div>
</main>

<?php
if (get_post_meta($post->ID, '_course_display_theme_footer',  true)) {
    get_footer();
}
