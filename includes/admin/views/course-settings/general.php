<?php
/** @var \WP_Post */
$post;

$statuses = [
    'publish' => 'Published',
    'pending' => 'Pending review',
    'draft' => 'Draft',
    'future' => 'Schedule',
    'private' => 'Private',
];

$course_description = get_the_excerpt( $post );

$selected_tags = wp_get_post_terms( $post->ID, 'course-tags' );
?>
<div class="grid">
    <div class="course-vis">
        <h3>Course visibility</h3>
        <div class="content row">
            <span>
                <b>Status:</b>
                <select name="post_status">
                    <?php foreach( $statuses as $value => $label ) : ?>
                        <option
                            value="<?php echo esc_attr($value); ?>"
                            <?php selected( $post->post_status, $value ); ?>
                        >
                            <?php echo esc_html( $label ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </span>
        </div>
        <span><b>Published on:</b><?php echo esc_html( $post->post_date ); ?></span>
    </div>
    <div class="course-tags">
        <h3>Tags</h3>
        <div class="tag-search">
            <div class="search-wrap" role="search" style="width: 100%;">
                <div class="selected-tags">
                <?php
                foreach ( $selected_tags as $tag ) {
                    ?>
                    <span class="tag"><?php echo esc_html( $tag->name ); ?></span>
                    <?php
                }
                ?>
                </div>
                <input type="text" class="search" placeholder="">
            </div>
        </div>
    </div>
    <div class="course-desc">
        <h3>Description</h3>
        <textarea id="course-description" cols="35" rows="8" placeholder="Enter an eye catching description..."><?php echo esc_textarea( $course_description ); ?></textarea>
    </div>
    <div class="course-img">
        <h3>Feature image</h3>
        <div class="col center">
            <div class="course-img-wrap"><?php the_post_thumbnail(); ?></div>
        </div>
    </div>
</div>
