<?php

/** @var \WP_Post */
$post;

$current_tab = $_GET['tab'] ?? 'general';

$tabs = array(
	'general'   => 'General',
	'advanced'  => 'Advanced',
	'selling'   => 'Selling',
	'downloads' => 'Downloads',
);
?>
<div id="lighter-course-settings">
	<ul class="settings-tabs">
	<?php
	foreach ( $tabs as $key => $tab ) {
		$is_active = $key === $current_tab;
		printf(
			'<li role="0" id="%s-tab" %stabindex="%s" aria-selected="%s" aria-controls="%s"><span><a href="%s">%s</a></span></li>',
			$key,
			$is_active ? 'class="active" ' : '',
			$is_active ? '0' : '-1',
			$is_active ? 'true' : 'false',
			$key . '-panel',
			admin_url( "post.php?post={$post->ID}&action=edit&tab=$key" ),
			$tab
		);
	}
	?>
	</ul>
	<div role="tabpanel" tabindex="0" id="<?php echo esc_attr( $current_tab ); ?>-panel" aria-labelledby="<?php echo esc_attr( $current_tab ); ?>-panel" class="box <?php echo esc_attr( $current_tab ); ?>">
			<?php
			lighter_view(
				"course-settings/$current_tab",
				array(
					'admin' => true,
					'post'  => $post,
				)
			);
			?>
	</div>
</div>
