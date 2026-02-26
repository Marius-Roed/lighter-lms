<?php

$screen = get_current_screen();

$post_type_obj = get_post_type_object( $screen->post_type ?: lighter_lms()->course_post_type );

$screen_id = strpos( $screen->id, '_page_' ) !== false ? substr( $screen->id, strpos( $screen->id, '_page_' ) + 6 ) : $post_type_obj->name;

switch ( $screen_id ) {
	case lighter_lms()->course_post_type:
	case lighter_lms()->lesson_post_type:
		$title = $post_type_obj->label;
		break;
	case 'lighter-lms-settings':
		$title = 'Settings';
		break;
	default:
		$title = 'LighterLMS';
}

?>
<div class="lighter-header">
	<div class="inner">
		<div class="title">
			<h1><?php echo esc_html( $title ); ?></h1>
		</div>
		<div class="actions">
			<div id="lighter-notifs">
				<button type="button" id="lighter-notif-btn" class="lighter-notif-btn" popovertarget="lighter-notifs-panel">
					<div class="icon-wrapper" style="--icon-size: 1.5em; --icon-color: currentColor;">
						<?php lighter_icon( 'bell', ); ?>
					</div>
				</button>
				<div id="lighter-notifs-panel" popover="auto">No new notifications</div>
			</div>
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . $screen->post_type ) ); ?>" class="lighter-btn">
				<?php echo esc_html( $post_type_obj->labels->add_new_item ); ?>
				<?php lighter_icon( 'plus' ); ?>
			</a>
		</div>
	</div>
</div>
