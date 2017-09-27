<?php

$source = $argv[1];
$target = $argv[2];

$source_build = $source . "/build/";
$target_build = $target . "/build/";

$map = [];

if ($handle = opendir($source_build)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            $source_file = $source_build . '/' . $entry;
            $target_file = $target_build . '/' . $entry;
            if (file_exists($source_file) && file_exists($target_file)) {	
            	$map = processFiles($source_file,$target_file,$map);
            }
        }
    }   
}

remap($map,$target);

function remap($map,$target) {
	$dir = $target . "/course/" . $target . "/";
	if ($handle = opendir($dir)) {
		while (false !== ($entry = readdir($handle))) {
			if ($entry != "." && $entry != "..") {
            	$source_file = $dir . '/' . $entry;
            	if (file_exists($source_file) && strpos($source_file, ".json") > 0) {	
            		updateFile($source_file,$map);
            	}
        	}
    	}   
	}
}

function updateFile($source_file,$map) {
		$json = file_get_contents($source_file);
		foreach($map as $target => $source) {
			$count = 0;
			$json = str_replace($target, $source, $json, $count);
			if ($count > 0) {
				//echo $source . " : " . $target . " : " . $count . "\n";
			} 
		}
		$bytes = file_put_contents($source_file, $json);
		if ($bytes > 0) {
			echo "UPDATED " . $source_file . "\n";
		}
}

function processFiles($source,$target,$map) {

	$source_map = getMapping($source);
	$target_map = getMapping($target);

	if (count($source_map) == count($target_map)) {
		echo "Stage 1 clear: Number of IDs matches.\n";
	} else {
		echo "Stage 1 warning: Number of IDs is different\n";
		echo "\nSource (".$source."): " . count($source_map);
		echo "\nTarget: (".$target."): " . count($target_map);
	}

	$map = checkMatch($source_map,$target_map,$map);

	return $map;
}

function checkMatch($source_map,$target_map,$map) {
	for ($i=0;$i<count($source_map);$i++) {
		$source_id = $source_map[$i]["id"];
		$source_line = trim($source_map[$i]["line"]);
		$target_id = $target_map[$i]["id"];
		$target_line = trim($target_map[$i]["line"]);

		//echo 'Processing line: ' . $i . "\n";
		//echo 'Source line: ' . $source_line . "\n";
		//echo 'Target Line: ' . $target_line . "\n";
		
		if ($source_line == $target_line) {
		//	echo 'Perfect match for line... outputting' . "\n";
			$map[$target_id] = $source_id;
		} else {
		//	echo "Inputs didn't match... stripping tags" . "\n";
			$source_content = strip_tags($source_line);
			$start = strpos($source_line,$source_content);
			$source2 = substr($source_line, 0, $start);
			$source2 .= substr($source_line, $start + strlen($source_content),strlen($source_line));
			$source_line = $source2;
			
			$target_content = strip_tags($target_line);
			$start = strpos($target_line,$target_content);
			$target2 = substr($target_line, 0, $start);
			$target2 .= substr($target_line, $start + strlen($target_content),strlen($target_line));
			$target_line = $target2;

			//echo 'Source line: ' . $source_line . "\n";
			//echo 'Target Line: ' . $target_line . "\n";

			if ($source_line == $target_line && strlen($source_line) > 0 && strlen($target_line) > 0) {
		//		echo 'Perfect match for line... outputting' . "\n";
				$map[$target_id] = $source_id;
			} else {
				echo 'NO MATCH! ... over to you on this one, here are the source lines and IDs' . "\n";
				echo 'Source id: ' . $source_id . "\n";
				echo 'Source line: ' . $source_line . "\n";
				echo 'Target id: ' . $target_id . "\n";
				echo 'Target Line: ' . $target_line . "\n";
			}
			
		}
		//echo "\n";
	}
	return $map;
}

function getMapping($file) {
	
	$array = [];
	$handle = fopen($file,"r");
	$pos = 0;
	while($line = fgets($handle)) {
		$search = 'adapt-id="';
		if (strpos($line, $search) !== false) {
			$current = $line;
			$current = substr($current, strpos($current, $search)+strlen($search),strlen($current));
			$current = substr($current,0,strpos($current,'"'));
			$array[$pos]["id"] = $current;
			$array[$pos]["line"] = str_replace($current, "", $line);
			$pos++;
		}
	}

	fclose($handle);

	return $array;
}
?>
