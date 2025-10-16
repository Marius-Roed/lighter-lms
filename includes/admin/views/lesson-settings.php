<?php

/** @var \WP_Post */

use LighterLMS\Topics;

global $post;

$topic_db = new Topics();

$parents = get_posts([
	'post_type' => lighter_lms()->course_post_type,
	'numberposts' => -1,
	'lesson_parent' => $post->ID,
	'suppress_filters' => false,
]);

if (empty($parents)) {
	$parent_key = get_post_meta($post->ID, '_lighter_parent_topic', true);
	$parent = $topic_db->get($parent_key);
	$parents = [$parent->title];
}

?>

<div id="lighter-settings-mount">
	<div class="parents">
		<h2>Linked courses</h2>
		<div class="tag-search">
			<div class="search-wrap" role="search">
				<div class="selected-tags">
					<?php foreach ($parents as $parent) : ?>
						<span class="tag">
							<?= esc_html($parent->post_title ?? $parent) ?>
							<button type="button" class="remove-tag">×</button>
						</span>
					<?php endforeach; ?>
				</div>

				<input type="text" class="search" placeholder="<?= empty($parents) ? "Link to parents" : "" ?>" />
			</div>
		</div>
	</div>
</div>
