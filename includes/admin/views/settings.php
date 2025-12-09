<?php
global $wp_rest_additional_fields;
$tabs = [
	'general' => 'General',
	'lessons' => 'Lessons',
	'template' => 'Template'
];

// $tab = $_GET['tab'] ?? array_keys($tabs)[0];

$builders = lighter_lms()->get_builders('all');

if (isset($_GET['updated']) && $_GET['updated'] === 'true') {
	echo '<div class="notice notice-success is-dismissible"><p>Settings saved!</p></div>';
} elseif (isset($_GET['error']) && $_GET['error'] === 'true') {
	echo '<div class="notice notice-error is-dismissible"><p>There was an error saving the settings. Try again.</p></div>';
}

?>

<div class="lighter-wrap" id="lighter-lms-mount">
	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
		<?php wp_nonce_field('lighter_lms_settings', 'lighter_lms'); ?>
		<input type="hidden" name="action" value="save_lighter_lms_settings" />

		<div id="lighter-settings-mount">
			<ul class="lighter-tab-menu">
				<?php
				foreach ($tabs as $label => $name) {
					printf(
						'<li role="tab" data-tab-opt="%1$s" tabindex="0" %2$s><a href="?page=lighter-lms-settings&tab=%1$s">%3$s</a></li>',
						esc_attr($label),
						esc_attr($label === $tab ? 'aria-selected="true" class="active"' : ""),
						esc_html($name)
					);
				}
				?>
			</ul>

			<div class="box general lighter-content">
				<h2>Default editor</h2>
				<div class="editors">
					<?php foreach ($builders as $builder) {
						$attr = esc_html($builder['slug']);
						$logo = $attr == 'classic-editor' ? 'wordpress-logo' : $attr . '-logo';
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
				<h2>Course Access</h2>
				<p>Give users access to courses.</p>
			</div>
		</div>

		<div class="lighter-settings-foot">
			<button type="submit" name="submit" id="submit" class="lighter-btn"><?= esc_html__('Save Settings'); ?></button>
		</div>
	</form>
</div>
<?php var_dump($wp_rest_additional_fields); ?>
