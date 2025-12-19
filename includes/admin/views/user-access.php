<?php

use LighterLMS\User_Access;

$ua = new User_Access($user);
$owned = $ua->get_owned();
do_action('qm/debug', $owned);

?>
<h2>Lighter LMS</h2>
<table class="form-table">
	<tr>
		<th>
			<label for="lighter-access">
				<?php _e('Grant access', 'textdomain') ?>
			</label>
		</th>
		<td>
			<label>
				<input type="text" name="course-search" id="course-search" />
			</label>
		</td>
	</tr>
</table>
