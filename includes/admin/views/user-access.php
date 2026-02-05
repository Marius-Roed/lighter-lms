<?php

defined('ABSPATH') || exit;

use LighterLMS\User_Access;

$ua = new User_Access($user);
$owned = $ua->get_owned();

$courses_limit = $_GET['lighter-courses'] ?? 6;
$courses = lighter_lms()->get_courses($courses_limit);

?>

<h2>Lighter LMS</h2>
<div class="lighter">
	<table class="form-table lighter-user-courses">
		<tr>
			<th>
				<p>
					<?php _e('Grant access', 'textdomain') ?>
				</p>
			</th>
			<td class="lighter-course-access-container bordered" id="lighter-course-user-access">
				<?php if (count($courses)): ?>
					<?php if (user_can($user, 'manage_options')): ?>
						<p>As administrator this user has access to all courses by default</p>
					<?php endif; ?>
					<div class="lighter-courses small">
						<?php foreach ($courses as $course): ?>
							<div class="lighter-course bordered">
								<?php
								if (get_post_thumbnail_id($course['id'])) {
									echo get_the_post_thumbnail($course['id']);
								} else {
									printf(
										'<img src="%s" alt="course thumbnail placeholder" loading="lazy"',
										esc_attr(esc_url("https://placehold.co/230/D2C8E1/663399?text=%3F"))
									);
								}	?>
								<h3><?php echo esc_html($course['title']); ?></h3>
								<?php if (count($course['topics'])):
									foreach ($course['topics'] as $topic): ?>
										<div class="course-topics">
											<b><?php echo esc_html($topic['title']); ?></b>
											<?php foreach ($topic['lessons'] as $lesson):
												$owns_lesson = false;
												foreach ($owned as $item) {
													if (in_array($lesson->ID, $item['lessons']) && $item['access_type'] !== 'revoked') {
														$owns_lesson = true;
														break;
													}
												}
											?>
												<div>
													<label>
														<?php echo esc_html($lesson->post_title); ?>
														<input type="hidden" name="<?php echo esc_attr("lighter-courses[" . $course['id'] . "][" . $lesson->ID . "]") ?>" value="false" />
														<input type="checkbox" name="<?php echo esc_attr("lighter-courses[" . $course['id'] . "][" . $lesson->ID . "]") ?>" id="<?php echo esc_attr($lesson->ID); ?>" value="<?php echo esc_attr($lesson->ID) ?>" <?php echo ($owns_lesson ? esc_attr("checked") : "") ?> />
													</label>
												</div>
											<?php endforeach; ?>
										</div>
									<?php endforeach;
								else: ?>
									<p>No course data found</p>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
					<?php if (count($courses) > 3 && $courses_limit != -1): // WARN: This is the wrong way to do it 
					?>
						<div class="lighter-show-more">
							<a href="<?php echo esc_url(strtok(add_query_arg('lighter-courses', '-1'), '#') . '#lighter-course-user-access') ?>" class="show-lighter-courses">Show all courses</a>
						</div>
					<?php endif;
				else: ?>
					<div class="lighter-no-courses">
						<p>No courses created yet</p>
						<a href="#">Create your first course</a>
					</div>
				<?php endif; ?>
			</td>
		</tr>
	</table>
	<?php
	wp_nonce_field('lighter_lms_access_update', 'lighter_lms_access_nonce');
	?>
</div>
