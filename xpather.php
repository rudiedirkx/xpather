<?php

if ( empty($_FILES['xml']['tmp_name']) || !($xml = file_get_contents($_FILES['xml']['tmp_name'])) ) {
	?>
	<style>
	table {
		border-collapse: collapse;
		width: 100%;
	}
	td, th {
		border: solid 1px #aaa;
		padding: 6px;
		background-color: #eee;
	}
	input, textarea {
		box-sizing: border-box;
		width: 100%;
	}
	</style>

	<form method="post" enctype="multipart/form-data">
		<p>
			XML file:<br>
			<input type="file" name="xml" />
		</p>
		<p>
			Row selector:<br>
			<table>
				<tr>
					<th>Selector</th>
				</tr>
				<tr>
					<td><input name="row" value='table[@name="diaro_entries"]/r' /></td>
				</tr>
			</table>
		</p>
		<p>
			Columns:<br>
			<table>
				<tr>
					<th>Name</th>
					<th>Selector</th>
					<th>Subselector</th>
					<th>Type</th>
				</tr>
				<tr>
					<td><input name="column[0][name]" value="date" /></td>
					<td><input name="column[0][selector]" value="date" /></td>
					<td><input name="column[0][subselector]" value="" /></td>
					<td><input name="column[0][type]" value="date" /></td>
				</tr>
				<tr>
					<td><input name="column[1][name]" value="subject" /></td>
					<td><input name="column[1][selector]" value="title" /></td>
					<td><input name="column[1][subselector]" value="" /></td>
					<td><input name="column[1][type]" value="text" /></td>
				</tr>
				<tr>
					<td><input name="column[2][name]" value="body" /></td>
					<td><input name="column[2][selector]" value="text" /></td>
					<td><input name="column[2][subselector]" value="" /></td>
					<td><input name="column[2][type]" value="text" /></td>
				</tr>
				<tr>
					<td><input name="column[3][name]" value="location_name" /></td>
					<td><input name="column[3][selector]" value="location_uid" /></td>
					<td><input name="column[3][subselector]" value='table[@name="diaro_locations"]/r/uid[text()="VALUE"]/../address' /></td>
					<td><input name="column[3][type]" value="text" /></td>
				</tr>
				<tr>
					<td><input name="column[4][name]" value="location_lat" /></td>
					<td><input name="column[4][selector]" value="location_uid" /></td>
					<td><input name="column[4][subselector]" value='table[@name="diaro_locations"]/r/uid[text()="VALUE"]/../lat'  /></td>
					<td><input name="column[4][type]" value="" /></td>
				</tr>
				<tr>
					<td><input name="column[5][name]" value="location_lon" /></td>
					<td><input name="column[5][selector]" value="location_uid" /></td>
					<td><input name="column[5][subselector]" value='table[@name="diaro_locations"]/r/uid[text()="VALUE"]/../long' /></td>
					<td><input name="column[5][type]" value="" /></td>
				</tr>
			</table>
		</p>

		<p>
			Header template:<br>
			<table>
				<tr>
					<th>Template</th>
				</tr>
				<tr>
					<td><textarea name="head">date,subject,body,location,coord</textarea></td>
				</tr>
			</table>
		</p>

		<p>
			Content template:<br>
			<table>
				<tr>
					<th>Template</th>
					<th>Encoding</th>
				</tr>
				<tr>
					<td width="75%"><textarea name="content"><?= htmlspecialchars('"<date>","<subject>","<body>","<location_name>","<location_lat>,<location_lon>"') ?></textarea></td>
					<td width="25%"><select><option selected>csv<option>html</select></td>
				</tr>
			</table>
		</p>

		<p>
			<button>Parse &amp; format</button>
			<button onclick="localStorage.xpatherValues = ''; location.reload(); return false">Really reset</button>
		</p>
	</form>

	<script>
	document.querySelector('form').addEventListener('change', function(e) {
		var values = [];
		[].forEach.call(this.elements, function(el, i) {
			if (el.name) {
				values.push([el.name, el.value]);
			}
		});
		localStorage.xpatherValues = JSON.stringify(values);
	});

	if (localStorage.xpatherValues) {
		var values = JSON.parse(localStorage.xpatherValues);
		values.forEach(function(value) {
			var el = document.querySelector('input[name="' + value[0] + '"]');
			if (el) {
				try {
					el.value = value[1];
				}
				catch (ex) {}
			}
		});
	}
	</script>
	<?php
	exit;
}

header('Content-type: text/plain; charset=utf-8');

$cfg_columns = array_filter($_POST['column'], function($column) {
	return !empty($column['selector']);
});

// De-namespace
$xml = str_replace(' xmlns=', ' abc=', $xml);
$xml = preg_replace('#(<\/?)\w+:(\w+)#', '$1$2', $xml);

// Find rows
$xml = simplexml_load_string($xml);
$source_rows = $xml->xpath($_POST['row']);

// For every row, find columns
$rows = array();
foreach ($source_rows as $source_row) {
	$row = array();
	foreach ($cfg_columns as $column) {
		$column = array_map('trim', $column);

		// Find value
		$matches = $source_row->xpath($column['selector']);
		$node = $matches[0];
		$value = trim((string) $node);

		// Find real value via subselector
		if ($column['subselector']) {
			$selector = str_replace('VALUE', $value, $column['subselector']);
			$matches2 = $xml->xpath($selector);
			if ($matches2) {
				$value = trim((string) $matches2[0]);
			}
		}

		// Convert/cast to type
		switch ($column['type']) {
			case '':
			case 'text':
				break;

			case 'date':
				$utc = preg_match('#^\d+$#', $value) ? $value : strtotime($value);
				if ( $utc > 4e9 ) {
					$utc /= 1e3;
				}

				$value = date('Y-m-d H:i:s', $utc);
				break;

			case 'float':
				$value = (float) $value;
				break;

			case 'int':
				$value = (int) $value;
				break;
		}

		$row[ $column['name'] ] = $value;

		// echo "======\n";
		// echo $value . "\n";
	}

	$rows[] = $row;

	// echo "======\n\n\n\n\n\n\n";
}

print_r($rows);
