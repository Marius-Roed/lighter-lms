<?php

$topics = lighter()->lms->course->get_topics( $post->ID );

wp_nonce_field( 'lighter_course_topics', '_lighter_course_content' );
?>

<input type="hidden" name="topics_length" />
<div id="lighter-course-mount">
	<div class="lighter-topics-wrap<?php echo empty( $topics ) ? ' empty' : ''; ?>">
		<div class="lighter-no-topics">
			<h3><?php echo esc_html__( 'This course has no topics yet.', 'lighterlms' ); ?></h3>
			<button type="button" class="lighter-btn"><?php echo esc_html__( 'Add the first topic', 'lighterlms' ); ?></button>
		</div>
		<ol class="topics-wrap">
			<?php
			foreach ( $topics as $i => $topic ) :
				$lesson_count = lighter()->lms->topic->get_lesson_count( $topic->ID );
				?>
				<li class="lighter-course-module" id="<?php echo esc_attr( $topic->topic_key ); ?>">
					<div class="module-wrap">
						<div class="module-data hidden"></div>
						<div class="head">
							<div class="drag-handle">
								<?php lighter_icon( 'six-dots' ); ?>
							</div>
							<div class="title">
								<h3 class="editable-text module-title"><?php echo esc_html( $topic->title ); ?></h3>
							</div>
							<div class="lessons-amount">
								<?php /** Translators: %d the amount of lessons as a whole number. */ ?>
								<p><?php esc_html_e( sprintf( 'Lessons (%d)', $lesson_count ), 'lighterlms' ); ?></p>
							</div>
							<div class="actions">
								<div class="add">
									<button type="button" class="add-lesson"><?php lighter_icon( 'plus' ); ?></button>
								</div>
								<div class="expand">
									<button type="button" class="expand-module"><?php lighter_icon( 'chevron-down' ); ?></button>
								</div>
							</div>
						</div>
						<div class="lighter-lesson-wrap"></div>
					</div>
				</li>
			<?php endforeach; ?>
		</ol>
		<div class="foot">
			<button type="submit" name="action" value="lighter_lms_add_topic" formaction="/wp-admin/admin-post.php" class="lighter-btn transparent"><?php lighter_icon( 'plus' ); ?>Add topic</button>
		</div>
	</div>
</div>
