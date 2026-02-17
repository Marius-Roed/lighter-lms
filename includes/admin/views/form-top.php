<?php

use LighterLMS\Lessons;

global $title, $post_new_file, $post_type_object, $post;

$title_placeholder = apply_filters( 'enter_title_here', __( 'Add title' ), $post );
$post_title        = $post->post_title;
$post_type         = is_object( $post_type_object ) ? $post_type_object->name : '';
$post_status       = $post->post_status;
$post_link         = get_permalink( $post->ID );
$add_new           = is_object( $post_type_object ) ? $post_type_object->labels->add_new_item : 'Add new';
$publish_btn       = 'save';

if ( 'publish' !== $post_status ) {
	$publish_btn = 'publish';
}

if ( $post_type == lighter_lms()->lesson_post_type ) {

	$lessons_db = new Lessons();

	// add_filter('posts_join', [$lessons_db, 'db_join'], 10, 2);

	$parent = get_posts(
		array(
			'post_type'        => lighter_lms()->course_post_type,
			'numberposts'      => 1,
			'lesson_parent'    => $post->ID,
			'suppress_filters' => false,
		)
	) ?: 0;

	$post_link = $parent ? esc_url( get_permalink( $parent[0]->ID ) . '?lesson=' . $post->post_name ) : false;
}

?>
<div class="lighter-header">
	<div class="inner">
		<div class="content">
			<h1>Edit</h1>
			<?php if ( in_array( $post_type, lighter_lms()->post_types ) ) : ?>
				<div class="title-wrapper col">
					<label id="title-prompt-text" class="screen-text-reader" for="title"><?php echo esc_html( $title_placeholder ); ?></label>
					<input form="post" type="text" name="post_title" value="<?php echo esc_attr( $post_title ); ?>" id="title" class="title" spellcheck="true" autocomplete="off" placeholder="Course name" />
				</div>
				<div>
					<a href="#" class="lighter-btn transparent"><?php echo lighter_icon( 'plus' ) . esc_html( $add_new ); ?></a>
				</div>
				<input type="hidden" form="post" name="lighter_nonce" value="<?php echo wp_create_nonce( $post_type . '_fields' ); ?>" />
			<?php endif; ?>
		</div>
		<div class="actions row">
			<?php if ( $post_link ) : ?>
				<a href="<?php echo esc_attr( esc_url( $post_link ) ); ?>" class="lighter-btn transparent"><?php echo __( 'View' ); ?></a>
			<?php endif; ?>
			<button form="post" class="lighter-btn" id="save-post"><?php echo $publish_btn === 'save' ? lighter_icon( 'save' ) . esc_html__( $publish_btn ) : esc_html__( $publish_btn ); ?></button>
		</div>
	</div>
</div>
