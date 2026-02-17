<?php

use LighterLMS\Topics;
use LighterLMS\Lessons;

$lessons_db = new Lessons(
	array(
		'parent' => $post->ID,
		'status' => 'any',
	)
);

// add_filter('posts_join', [$lessons_db, 'db_join'], 10, 2);

$lessons     = $lessons_db->get_lessons();
$lesson_data = array_map(
	function ( $lesson ) {
		return array(
			'id'             => $lesson->ID,
			'key'            => get_post_meta( $lesson->ID, '_lighter_lesson_key', true ),
			'title'          => $lesson->post_title,
			'postStatus'     => $lesson->post_status,
			'sortOrder'      => get_post_meta( $lesson->ID, '_lighter_sort_order', true ) ?: 0,
			'parentTopicKey' => get_post_meta( $lesson->ID, '_lighter_parent_topic', true ),
		);
	},
	$lessons
);

$topic_db = new Topics();

$topics_raw = $topic_db->get_by_course( $post->ID );

$topics = array_map(
	function ( $row ) {
		return (object) array(
			'key'       => $row->topic_key,
			'title'     => $row->title,
			'sortOrder' => $row->sort_order,
			'courseId'  => $row->post_id,
		);
	},
	$topics_raw
);

$topic_keys = array_map(
	function ( $row ) {
		return $row->topic_key;
	},
	$topics_raw
);

$lesson_data = array_values(
	array_filter(
		$lesson_data,
		function ( $lesson ) use ( $topic_keys ) {
			return in_array( $lesson['parentTopicKey'], $topic_keys );
		}
	)
);

wp_nonce_field( 'lighter_course_topics', '_lighter_course_content' );
?>

<input type="hidden" name="topics_length" value="<?php echo count( $topics ); ?>" />
<div id="lighter-course-mount"
	data-course="<?php echo esc_attr( $post->ID ); ?>"
	data-topics="<?php echo esc_attr( json_encode( $topics ) ); ?>"
	data-lessons="<?php echo esc_attr( json_encode( $lesson_data ) ); ?>">
	<div class="lighter-topics-wrap<?php echo empty( $topics ) ? ' empty' : ''; ?>">
		<div class="lighter-no-topics">
			<h3><?php echo esc_html__( 'This course has no topics yet.', 'lighterlms' ); ?></h3>
			<button type="button" class="lighter-btn"><?php echo esc_html__( 'Add the first topic', 'lighterlms' ); ?></button>
		</div>
		<ol class="topics-wrap">
			<?php foreach ( $topics as $i => $topic ) : ?>
				<li class="lighter-course-module" id="<?php echo esc_attr( $topic->key ); ?>">
					<div class="module-wrap">
						<div class="module-data hidden"></div>
						<div class="head">
							<div class="drag-handle">
								<?php lighter_icon( 'six-dots' ); ?>
							</div>
							<div class="title">
								<h3 class="editable-text module-title"><?php echo esc_html( $topic->title ); ?></h3>
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
			<button type="submit" name="action" value="add_topic" formaction="/wp-admin/admin-post.php" class="lighter-btn transparent"><?php lighter_icon( 'plus' ); ?>Add topic</button>
		</div>
	</div>
</div>
