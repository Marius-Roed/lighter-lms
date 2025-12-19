<?php

/** @var \WP_Post */
$post;

$course_description = get_post_meta($post->ID, '_course_description', true);

$tags = get_terms(['taxonomy' => 'course-tags']);
$tags = array_map(fn($tag) => [
	'id' => $tag->term_id,
	'name' => $tag->name,
	'count' => $tag->count,
	'slug' => $tag->slug,
	'taxonomy' => $tag->taxonomy
], $tags);

$selected_tags = wp_get_post_terms($post->ID, 'course-tags');
$selected_tags = array_map(fn($tag) => [
	'id' => $tag->term_id,
	'name' => $tag->name,
	'count' => $tag->count,
	'slug' => $tag->slug,
	'taxonomy' => $tag->taxonomy
], $selected_tags);

?>
<script>
	var lighterCourse = {
		tags: {
			all: <?= wp_json_encode($tags) ?>,
			selected: <?= wp_json_encode($selected_tags) ?>
		}
	};
</script>
<div id="lighter-course-settings">
	<ul class="settings-tabs">
		<li tabindex="0" role="tab" class="active"><span>General</span></li>
		<li tabindex="0" role="tab"><span>Advanced</span></li>
		<li tabindex="0" role="tab"><span>Selling</span></li>
		<li tabindex="0" role="tab"><span>Downloads</span></li>
	</ul>
	<div class="box">
		<div class="grid">
			<div class="course-vis">
				<h3>Course visibility</h3>
				<span>
					<b>Status:</b>
					<select name="post_status" value="<?= esc_attr($post->post_status) ?>">
						<option value="publish">Published</option>
						<option value="pending">Pending review</option>
						<option value="future">Schedule</option>
						<option value="private">Private</option>
						<option value="draft">Draft</option>
					</select>
				</span>
				<span><b>Published on:</b><?= esc_html($post->post_date) ?></span>
			</div>
			<div class="course-tags">
				<h3>Tags</h3>
				<div class="tag-search">
					<div class="search-wrap" role="search" style="width: 100%;">
						<div class="selected-tags">
						</div>
						<input type="text" class="search" placeholder="">
					</div>
				</div>
			</div>
			<div class="course-desc">
				<h3>Description</h3>
				<textarea id="course-description" name="course_description" cols="35" rows="8" placeholder="Enter an eye catching description..."><?= esc_textarea($course_description) ?></textarea>
			</div>
		</div>
	</div>
</div>
