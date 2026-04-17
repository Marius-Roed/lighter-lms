<?php
/** @var WP_Post $post */
$post;

$downloads = lighter_get_course_downloads( $post );

?>

<table class="lighter-downloads">
<thead>
<tr>
<td>File name</td>
<td>File url</td>
<td align="center">File type</td>
<td><span class="screen-reader-text">Actions</span></td>
</tr>
</thead>
<tbody>
<?php
if ( count( $downloads ) ) :
	foreach ( $downloads as $file ) :
		?>
<tr>
<td> <span class="editable-text">Taknemmelighedstræning <?php lighter_icon( 'pencil' ); ?></span> </td>
<td><input type="text"></td>
<td align="center">pdf</td>
<td><button type="button" class="lighter-btn transparent">Choose file</button></td>
</tr>
		<?php
	endforeach;
else :
	?>
	<tr><td></td><td align="center">This course has no downloads yet</td><td></td><td></td></tr>
<?php endif; ?>
</tbody>
</table>
<button type="button" class="lighter-btn">Add new file</button>
