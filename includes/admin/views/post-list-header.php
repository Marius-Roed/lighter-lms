<?php

$screen = get_current_screen();

$post_type_obj = get_post_type_object($screen->post_type);

?>
<div class="lighter-header">
	<div class="inner">
		<div class="title">
			<h1><?= esc_html($post_type_obj->label) ?></h1>
		</div>
		<div class="actions">
			<a href="<?= esc_url(admin_url('post-new.php?post_type=' . $screen->post_type)) ?>" class="lighter-btn">
				<?= lighter_icon('plus') ?>
				<?= esc_html($post_type_obj->labels->add_new_item) ?>
			</a>
		</div>
	</div>
</div>
