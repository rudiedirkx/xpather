<?php

if ( empty($_FILES['xml']['tmp_name']) || !($xml = file_get_contents($_FILES['xml']['tmp_name'])) ) {
	?>
	<style>
	table {
		border-collapse: collapse;
	}
	td, th {
		border: solid 1px #aaa;
		padding: 6px;
		background-color: #eee;
	}
	input {
		width: 30em;
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
					<th>Selector</th>
					<th>Type</th>
				</tr>
				<tr>
					<td><input name="columns[0]" value="date" /></td>
					<td><input name="types[0]" value="date" /></td>
				</tr>
				<tr>
					<td><input name="columns[1]" value="title" /></td>
					<td><input name="types[1]" value="" /></td>
				</tr>
				<tr>
					<td><input name="columns[2]" value="text" /></td>
					<td><input name="types[2]" value="" /></td>
				</tr>
				<tr>
					<td><input name="columns[3]" value="location_uid" /></td>
					<td><input name="types[3]" value='table[@name="diaro_locations"]/r/uid[text()="VALUE"]/../address' /></td>
				</tr>
				<tr>
					<td><input name="columns[4]" value="location_uid" /></td>
					<td><input name="types[4]" value='table[@name="diaro_locations"]/r/uid[text()="VALUE"]/../lat' /></td>
				</tr>
				<tr>
					<td><input name="columns[5]" value="location_uid" /></td>
					<td><input name="types[5]" value='table[@name="diaro_locations"]/r/uid[text()="VALUE"]/../long' /></td>
				</tr>
			</table>
		</p>
		<button>Parse &amp; format</button>
		<button onclick="localStorage.xpatherValues = ''; location.reload(); return false">Really reset</button>
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
				el.value = value[1];
			}
		});
	}
	</script>
	<?php
	exit;
}

header('Content-type: text/plain; charset=utf-8');

$row = trim($_POST['row']);
$columns = array_filter(array_map('trim', $_POST['columns']));
$types = array_filter(array_map('trim', $_POST['types']));

$xml = simplexml_load_string($xml);

$rows = $xml->xpath($_POST['row']);

foreach ($rows as $row) {
	foreach ($columns as $i => $column) {
		$type = (string) @$types[$i];

		$matches = $row->xpath($column);
		$node = $matches[0];
		$value = trim((string) $node);

		switch ($type) {
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

			default:
				$selector = str_replace('VALUE', $value, $type);
				$matches2 = $xml->xpath($selector);
				if ($matches2) {
					$value = trim((string) $matches2[0]);
				}
				break;
		}

		echo "======\n";
		echo $value . "\n";
	}

	echo "======\n\n\n\n\n\n\n";
}
