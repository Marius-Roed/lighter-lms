<div role="tabpanel" tabindex="0" id="importexport-panel" aria-labelledby="importexport-tab" class="box importexport">
	<div class="grid bordered">
		<h2>Import users</h2>
		<p>Import users and give them their appropriate courses.</p>
		<div class="example">
			<p>Example CSV file:</p>
			<table>
				<thead>
					<tr>
						<td>First name</td>
						<td>Last name</td>
						<td>Email</td>
						<td>phone</td>
						<td>Courses</td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>John</td>
						<td>Smith</td>
						<td>john.smith@plugin-dev.local</td>
						<td>+1-123-555-1234</td>
						<td>143 152 98 120</td>
					</tr>
					<tr>
						<td>Jane</td>
						<td>Doe</td>
						<td>jane.doe@plugin-dev.local</td>
						<td>+1-321-555-4321</td>
						<td>123</td>
					</tr>
				</tbody>
			</table>
		</div>
		<fieldset>
			<legend>
				<h3>Options</h3>
			</legend>
			<div class="grid-2">
				<label class="lighter-switch">
					<input type="checkbox" name="first_header" role="switch" aria-checked="true">
					<span class="lighter-slider"></span>
					Use first row as header row
				</label>
				<label class="lighter-switch">
					<input type="checkbox" name="skip_new" role="switch" aria-checked="false">
					<span class="lighter-slider"></span>
					Skip new users
				</label>
				<label class="lighter-switch">
					<input type="checkbox" name="login_email" role="switch" aria-checked="true">
					<span class="lighter-slider"></span>
					Use email as user login
				</label>
				<label class="lighter-switch">
					<input type="checkbox" name="notify" role="switch" aria-checked="true">
					<span class="lighter-slider"></span>
					Notify users
				</label>
				<label class="lighter-switch">
					<input type="checkbox" name="orders" role="switch" aria-checked="false">
					<span class="lighter-slider"></span>
					Create order in store
				</label>
			</div>
			<div class="separator-group">
				<p>Separator</p>
				<label>
					<input type="radio" name="separator" value=","> Comma (,)
				</label>
				<label>
					<input type="radio" name="separator" value="."> Period (.)
				</label>
				<label>
					<input type="radio" name="separator" value=":"> Colon (:)
				</label>
				<label>
					<input type="radio" name="separator" value=";"> Semicolon (;)
				</label>
				<label>
					<input type="radio" name="separator" value=" "> Space ( )
				</label>
			</div>
		</fieldset>
		<div class="file-input"><input type="file" name="import-user" id="user-import" accept=".csv"></div>
		<div class="submit"><button type="button" class="lighter-btn">Import</button></div>
	</div>
	<div class="grid bordered">
		<h2>Export users</h2>
		<p>Generate a CSV-file with your users data.</p>
	</div>
</div>
