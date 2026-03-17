<?php

use LighterLMS\Topics;

$parents = lighter()->lms->lesson->get_course( $post->ID );

?>

<div id="lighter-settings-mount">
	<div class="parents">
		<h2>Linked courses</h2>
		<div class="tag-search">
			<div class="search-wrap" role="search">
				<div class="selected-tags">
					<?php foreach ( $parents as $parent ) : ?>
						<span class="tag">
							<?php echo esc_html( $parent->post_title ?? $parent ); ?>
							<button type="button" class="remove-tag">×</button>
						</span>
					<?php endforeach; ?>
				</div>

				<input type="text" class="search" placeholder="<?php echo empty( $parents ) ? 'Link to parents' : ''; ?>" />
			</div>
		</div>
	</div>
</div>
