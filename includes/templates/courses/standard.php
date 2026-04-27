<?php

/**
 * Standard template for displaying a course with LighterLMS
 *
 * @package LighterLMS
 */

/** @var \WP_Post */
global $post;
$lesson_id = null;

$lessons = lighter()->lms->course->get_flat_lessons($post);

$image_size = apply_filters("lighter_course_image_size", "lighter_course_main");

if (
    lighter_get_post_meta($post->ID, "course_display_theme_header", true) ??
    lighter_lms()->defaults()->course_display_theme_header
) {
    get_header();
}
if (
    lighter_get_post_meta($post->ID, "course_display_theme_sidebar", true) ??
    lighter_lms()->defaults()->course_display_theme_sidebar
) {
    get_sidebar();
}
?>

<main
    id="main"
    <?php echo esc_attr(post_class("site-main")); ?>
    data-course-id="<?php echo esc_attr($post->ID); ?>"
>
	<?php lighter_lms_course_sidebar($post); ?>
	<div class="content-wrap">
		<div class="the-content" id="the-content">
			<?php if (isset($_GET["lesson"])):
       echo '<div class="lighter-lesson-wrap">';
       foreach ($lessons as $lesson) {
           if (
               strtolower(sanitize_key($lesson->post_name)) == $_GET["lesson"]
           ) {
               $lesson_id = $lesson->ID;
               if (
                   lighter()->lms->user->check_lesson_access(
                       $lesson_id,
                       $post->ID,
                   )
               ) {
                   echo get_the_content(post: $lesson_id);
               }
               break;
           }
       }
       echo "</div>";
   else:
        ?>
				<h1><?php echo esc_html(the_title()); ?></h1>
				<?php
    $thumb = get_the_post_thumbnail($post, $image_size);
    if (!empty($thumb)) {
        echo $thumb;
    } elseif (lighter_lms()->connected_store === "woocommerce") {
        $prod_id = get_post_meta($post->ID, "_lighter_product_id", true);

        if ($prod_id) {
            $product = \wc_get_product_object("simple", $prod_id);
            echo wp_get_attachment_image($product->get_image_id(), $image_size);
        }
    }
    ?>
				<p><?php the_excerpt(); ?></p>
			<?php
   endif; ?>
		</div>
		<?php $btn_class = $lesson_id
      ? ["complete-lesson"]
      : ["complete-lesson", "lighter-hidden"]; ?>
		<div class="<?php echo implode(" ", $btn_class); ?>">
            <form
                action="<?php echo esc_url(admin_url("admin-post.php")); ?>"
                method="post"
                id="complete-form"
            >
				<?php wp_nonce_field("complete_lesson", "lighter_lesson_nonce"); ?>
				<input type="hidden" name="action" value="lighter_complete_lesson" />
                <input
                    type="hidden"
                    name="lesson_id"
                    value="<?php echo esc_attr($lesson_id ?? $post->ID); ?>"
                    id="lesson_id"
                />
                <input
                    type="hidden"
                    name="course_id"
                    value="<?php echo esc_attr($post->ID); ?>"
                    id="course_id"
                />
                <input
                    type="hidden"
                    name="user_id"
                    value="<?php echo esc_attr(get_current_user_id()); ?>"
                    id="user_id"
                />
                <button
                    type="submit"
                    class="lighter-btn complete-lesson-btn"
                    id="complete-btn"
                    aria-label="Complete lesson"
                >
                    <?php esc_html_e("Mark complete", "lighterlms"); ?>
                </button>
			</form>
		</div>
	</div>
</main>

<?php if (
    lighter_get_post_meta($post->ID, "course_display_theme_footer", true) ??
    lighter_lms()->defaults()->course_display_theme_footer
) {
    get_footer();
} else {
    wp_footer();
}
