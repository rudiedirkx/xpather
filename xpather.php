<?php

if ( empty($_FILES['xml']) || !($xml = file_get_contents($_FILES['xml']['tmp_name'])) ) {
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
					<td><input name="columns[]" value="date" /></td>
					<td><input name="types[]" value="date" /></td>
				</tr>
				<tr>
					<td><input name="columns[]" value="title" /></td>
					<td><input name="types[]" value="" /></td>
				</tr>
				<tr>
					<td><input name="columns[]" value="text" /></td>
					<td><input name="types[]" value="" /></td>
				</tr>
				<tr>
					<td><input name="columns[]" value="location_uid" /></td>
					<td><input name="types[]" value='table[@name="diaro_locations"]/r/uid[text()="VALUE"]/../address' /></td>
				</tr>
				<tr>
					<td><input name="columns[]" value="location_uid" /></td>
					<td><input name="types[]" value='table[@name="diaro_locations"]/r/uid[text()="VALUE"]/../lat' /></td>
				</tr>
				<tr>
					<td><input name="columns[]" value="location_uid" /></td>
					<td><input name="types[]" value='table[@name="diaro_locations"]/r/uid[text()="VALUE"]/../long' /></td>
				</tr>
			</table>
		</p>
		<button>Parse &amp; format</button>
	</form>
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
