<?php
global $wp_rest_additional_fields;
$tabs = array(
	'general'      => 'General',
	'lessons'      => 'Lessons',
	'template'     => 'Template',
	'importexport' => 'Import/Export',
);

$view = 'settings-' . $tab;

if ( isset( $_GET['updated'] ) && $_GET['updated'] === 'true' ) {
	echo '<div class="notice notice-success is-dismissible"><p>Settings saved!</p></div>';
} elseif ( isset( $_GET['error'] ) && $_GET['error'] === 'true' ) {
	echo '<div class="notice notice-error is-dismissible"><p>There was an error saving the settings. Try again.</p></div>';
}

?>

<div class="lighter-wrap" id="lighter-lms-mount">
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'lighter_lms_settings', 'lighter_lms' ); ?>
		<input type="hidden" name="action" value="save_lighter_lms_settings" />

		<div id="lighter-settings-mount">
			<ul class="lighter-tab-menu">
				<?php
				foreach ( $tabs as $label => $name ) {
					printf(
						'<li role="tab" data-tab-opt="%1$s" tabindex="0" %2$s><a href="?page=lighter-lms-settings&tab=%1$s">%3$s</a></li>',
						esc_attr( $label ),
						esc_attr( $label === $tab ? 'aria-selected="true" class="active"' : '' ),
						esc_html( $name )
					);
				}
				?>
			</ul>

			<?php lighter_view( $view, array( 'admin' => true ) ); ?>
		</div>

		<div class="lighter-settings-foot">
			<button type="submit" name="submit" id="submit" class="lighter-btn"><?php echo esc_html__( 'Save Settings' ); ?></button>
		</div>
	</form>
</div>
