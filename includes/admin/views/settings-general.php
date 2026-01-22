<?php
$builders = lighter_lms()->get_builders('all');
$stores = lighter_lms()->get_stores('all');
$courses = lighter_lms()->get_courses();

?>

<div role="tabpanel" tabindex="0" id="general-panel" aria-labelledby="lessons-tab" class="box general lighter-content">
	<h2>Course Access</h2>
	<p>Give users access to courses.</p>
	<div class="course-access">
		<div class="users-wrap bordered">
			<div class="user-list col">
				<input type="text" style="border-bottom-left-radius: 0.625em; border-bottom-right-radius: 0.625em;">
				<span class="no-users">Select users to grant course access.</span>
			</div>
		</div>
		<span class="row center middle">
			<div class="icon-wrapper s-h58VsaBwmicF" style="--icon-size: 1.5rem; --icon-color: rebeccapurple;">
				<svg width="14" height="9" viewBox="0 0 14 9" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M13.0036 1.05524L6.98621 7.05524L1.00363 1.05524" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
				</svg>
			</div>
		</span>
		<div class="course-list bordered col">
			<?php foreach ($courses as $course):
				$title = $course['title']; ?>
				<div class="course-wrap col" aria-expanded="false">
					<div class="course">
						<label>
							<input type="checkbox" name="<?php echo esc_attr($course['id']) ?>" id="<?php echo esc_attr($course['id']) ?>">
							<?php echo esc_html($title) ?>
						</label>
						<button type="button">
							<div class="icon-wrapper" style="--icon-size: 1.5rem; --icon-color: currentColor;">
								<?php echo lighter_icon("chevron-down") ?>
							</div>
						</button>
					</div>
					<?php foreach ($course['topics'] as $topic): ?>
						<div class="topic col">
							<b><?php echo esc_html($topic['title']) ?></b>
							<div class="grid">
								<?php foreach ($topic['lessons'] as $idx => $lesson): ?>
									<label>
										<input
											type="checkbox"
											name="<?php echo esc_attr(lighter_attrify($title)) ?>"
											value="<?php echo esc_attr($lesson->ID) ?>"
											id="<?php echo esc_attr(lighter_attrify("$title-$idx")) ?>" />
										<?php echo esc_html($lesson->post_title) ?>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<h2>Default editor</h2>
	<div class="editors">
		<?php foreach ($builders as $builder) {
			$attr = esc_html($builder['slug']);
			$logo = $attr == 'classic-editor' || $attr == 'gutenberg' ? 'wordpress-logo' : $attr . '-logo';
			echo "<label for=\"{$attr}\" style=\"--bg-color:{$builder['background']}\">";
			printf(
				'<input type="radio" id="%1$s" name="default-editor" value="%1$s"%2$s />',
				$attr,
				$builder['slug'] == lighter_lms()->defaults()->editor ? ' checked' : ''
			);
		?>
			<div class="editor-card col <?php echo $attr; ?>">
				<div class="icon-wrapper" style="--icon-size:222px;--icon-color:<?php echo $builder['foreground'] ?>;">
					<?php esc_html(lighter_icon($logo)); ?>
				</div>
				<span><?php echo esc_html($builder['name'][0]) ?></span>
			</div>
			</label>
		<?php
		}
		?>
	</div>
	<h2>Connected store</h2>
	<div class="stores">
		<?php foreach ($stores as $store):
			$attr = esc_html($store['slug']);
			$logo = $attr . '-logo';
			printf(
				'<label for="%s" style="--bg-color:%s;">',
				$attr,
				$store['background']
			);
			printf(
				'<input type="radio" id="%1$s" name="connected-store" value="%1$s"%2$s />',
				$attr,
				$store['slug'] == lighter_lms()->defaults()->store ? ' checked' : ''
			) ?>
			<div class="store-card col <?php echo $attr; ?>">
				<div class="icon-wrapper" style="--icon-size:222px;--icon-color:<?php echo $builder['foreground'] ?>;">
					<?php esc_html(lighter_icon($logo)); ?>
				</div>
				<span><?php echo esc_html($store['name'][0]) ?></span>
			</div>
			</label>
		<?php endforeach; ?>
	</div>
</div>
