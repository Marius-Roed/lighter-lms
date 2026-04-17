<?php
/**
 * @var WP_Post $post
 */
$post;

$course_post = get_post_type_object( lighter_lms()->course_post_type );
$settings    = lighter_get_course_settings( $post );

?>

<div class="grid">
	<div class="course-settings">
		<h3>Course settings</h3>
		<label class="lighter-switch">
			<?php
			printf(
				'<input type="checkbox" name="sync-prod-img" role="switch" aria-checked="%s"%s>',
				(bool) $settings['syncProductImg'],
				$settings['syncProductImg'] ? ' checked' : ''
			);
			?>
			<span class="lighter-slider"> </span>
			Synchronise product image and course thumbnail
		</label>
	</div>
	<div class="course-slug">
		<h3>Lesson url</h3>
		<div class="content grid">
			<span class="col-full"><?php echo esc_html( site_url( $course_post->rewrite['slug'] ) ); ?>/<b class="editable-text"><?php echo esc_html( $post->post_name ); ?></b>
			</span>
		</div>
	</div>
	<div class="lessons">
		<h3>Lesson settings</h3>
		<div class="content grid">
			<label class="lighter-switch">
				<?php
				printf(
					'<input type="checkbox" name="lesson-icons" role="switch" aria-checked="%s"%s>',
					(bool) $settings['showIcons'],
					$settings['showIcons'] ? ' checked' : ''
				);
				?>
				<span class="lighter-slider"></span>
				Show lesson icons
			</label>
			<label class="lighter-switch">
				<?php
				printf(
					'<input type="checkbox" name="lesson-progress" role="switch" aria-checked="%s"%s>',
					(bool) $settings['showProgress'],
					$settings['showProgress'] ? ' checked' : ''
				);
				?>
				<span class="lighter-slider"></span>
				Hide lesson progress
			</label>
		</div>
	</div>
	<div class="course-template-set">
		<h3>Template</h3>
		<div class="content grid">
			<label class="lighter-switch">
				<?php
				printf(
					'<input type="checkbox" name="displayHeader" role="switch" aria-checked="%s"%s>',
					(bool) $settings['displayHeader'],
					$settings['displayHeader'] ? ' checked' : ''
				);
				?>
				<span class="lighter-slider"></span>
				Disable theme header
			</label>
			<label class="lighter-switch">
				<?php
				printf(
					'<input type="checkbox" name="displaySidebar" role="switch" aria-checked="%s"%s>',
					(bool) $settings['displaySidebar'],
					$settings['displaySidebar'] ? ' checked' : ''
				);
				?>
				<span class="lighter-slider"></span>
				Display theme sidebar
			</label>
			<label class="lighter-switch">
				<?php
				printf(
					'<input type="checkbox" name="displayFooter" role="switch" aria-checked="%s"%s>',
					(bool) $settings['displayFooter'],
					$settings['displayFooter'] ? ' checked' : ''
				);
				?>
				<span class="lighter-slider"></span>
				Disable theme footer
			</label>
		</div>
	</div>
</div>
