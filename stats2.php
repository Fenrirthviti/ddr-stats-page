<?php 
if (file_exists('stats.xml')) {
	$xml = simplexml_load_file('stats.xml');
	echo '<pre>'; print_r($xml); echo '</pre>';
} 
else {
	exit('Failed to open stats.xml.');
}
?>
