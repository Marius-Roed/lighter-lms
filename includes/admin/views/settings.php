<?php
$tabs = [
	'general' => 'General',
	'lessons' => 'Lessons',
	'template' => 'Template'
];

// $tab = $_GET['tab'] ?? array_keys($tabs)[0];

$builders = lighter_lms()->get_builders();


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

		<ul>
			<?php
			foreach ($tabs as $label => $name) {
				printf(
					'<li class="%1$s" role="tab" data-tab-opt="%1$s" tabindex="0" %2$s>%3$s</li>',
					esc_attr($label),
					esc_attr($label === $tab ? 'aria-selected="true"' : ""),
					esc_html($name)
				);
			}
			?>
		</ul>

		<div class="lighter-content">
			<h2>Default editor</h2>
			<?php foreach ($builders as $idx => $builder) {
				$attr = esc_attr(lighter_attrify($builder) . "-" . $idx + 1);
				printf(
					'<label for="%s">%s</label><input type="radio" id="%s" name="default-editor" value="%s"%s />',
					$attr,
					esc_html($builder),
					$attr,
					esc_attr(lighter_attrify($builder)),
					lighter_attrify($builder) == lighter_lms()->defaults()->editor ? ' checked' : ''
				);
			}
			?>
		</div>

		<div class="lighter-settings-foot">
			<button type="submit" name="submit" id="submit" class="lighter-btn"><?= esc_html__('Save Settings'); ?></button>
		</div>
	</form>
</div>
