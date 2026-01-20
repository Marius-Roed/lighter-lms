<?php

$screen = get_current_screen();

$post_type_obj = get_post_type_object($screen->post_type ?: lighter_lms()->course_post_type);

?>
<div class="lighter-header">
	<div class="inner">
		<div class="title">
			<h1><?php echo strpos($screen->base, "settings") ? "Settings" : esc_html($post_type_obj->label) ?></h1>
		</div>
		<div class="actions">
			<div id="lighter-notifs"><button type="button" id="lighter-notif-btn" class="lighter-notif-btn"><?php lighter_icon('bell') ?></button></div>
			<a href="<?php echo esc_url(admin_url('post-new.php?post_type=' . $screen->post_type)) ?>" class="lighter-btn">
				<?php echo esc_html($post_type_obj->labels->add_new_item) ?>
				<?php lighter_icon('plus') ?>
			</a>
		</div>
	</div>
</div>
