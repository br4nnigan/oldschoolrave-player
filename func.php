<?php
if(!function_exists('str_putcsv')) {
	function str_putcsv($input, $delimiter = ',', $enclosure = '"') {
		// Open a memory "file" for read/write...
		$fp = fopen('php://temp', 'r+');
		// ... write the $input array to the "file" using fputcsv()...
		fputcsv($fp, $input, $delimiter, $enclosure);
		// ... rewind the "file" so we can read what we just wrote...
		rewind($fp);
		// ... read the entire line into a variable...
		$data = fgets($fp);
		// ... close the "file"...
		fclose($fp);
		// ... and return the $data to the caller, with the trailing newline from fgets() removed.
		return rtrim( $data, "\n" );
	}
}
if(!function_exists('str_putcsv_array')) {
	function str_putcsv_array( $array )
	{
		$csv = "";

		foreach ($array as $item) {
		   $csv .= str_putcsv($item) . "\n";
		}
		return $csv;
	}
}
if(!function_exists('str_getcsv_array')) {
	function str_getcsv_array( $string )
	{
		$array = array();
		$csv_array = explode("\n", $string);

		foreach ($csv_array as $item)
		{
			if ( $item != "" )
			{
				 array_push($array, str_getcsv($item));
			}
		}
		return $array;
	}
}
// ini_set("register_argc_argv", "On");
function console($v1 = null, $v2 = null, $v3 = null, $v4 = null, $v5 = null)
{
	if ( !isset($argv) )
	{
		$argv = array();
		if ( $v1 ) array_push($argv, $v1);
		if ( $v2 ) array_push($argv, $v2);
		if ( $v3 ) array_push($argv, $v3);
		if ( $v4 ) array_push($argv, $v4);
		if ( $v5 ) array_push($argv, $v5);
	}

	$args = array();
	foreach ($argv as $arg) {
		if ( gettype($arg) == "string" ) {
			array_push($args, "'".$arg."'");
		}
		if ( gettype($arg) == "integer" || gettype($arg) == "double" ) {
			array_push($args, $arg);
		}
		if ( gettype($arg) == "array" || gettype($arg) == "object" ) {
			array_push($args, json_encode($arg));
		}
	}

	?><script>
		console.log(<?php
			foreach ($args as $arg) {
				echo $arg . ",";
			}
		?>)
	</script><?php

}

