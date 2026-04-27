<?php

/** @var \WP_Post $post */
$post;

$settings = lighter_lms_get_lesson_settings($post);
?>

<div id="lighter-settings-mount">
<div class="settings-wrap">
<div class="slug">
<h2>Lesson slug</h2>
<p><?php echo site_url(
    "my-course?lesson=",
); ?><input type="text" name="post-new-slug" value="<?php echo $post->post_name; ?>"/></p>
</div>
	<div class="parents">
		<h2>Linked courses</h2>
		<div class="tag-search">
			<div class="search-wrap" role="search">
				<div class="selected-tags">
					<?php foreach ($settings["parents"] as $course):
         foreach ($course["topics"] as $topic): ?>
						<span class="tag">
							<?php echo esc_html($course["course_title"]); ?> → <?php echo esc_html(
     $topic["title"],
 ); ?>
							<button type="button" class="remove-tag">×</button>
						</span>
							<?php endforeach;
     endforeach; ?>
				</div>

				<input type="text" class="search" />
			</div>
		</div>
	</div>
</div>
</div>
