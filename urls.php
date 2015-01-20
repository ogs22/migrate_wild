<?php


/*
	* Clean up script for use after import to deal with missing aliases for /somepath/index.html being accessed by /somepath/ or /somepath
	* run with drush
	* Drush php-script urls.php
*/

$q = "%/index.html";
$result = db_query('SELECT * FROM {url_alias}  WHERE url_alias.alias like :index',array(':index' => '%'. db_like('index.html')))->fetchAll();
//print_r($result);
// Result is returned as a iterable object that returns a stdClass object on each iteration
foreach ($result as $record) {
	print "\nChecking:";
	print_r($record->alias);
	//check if alias already has a /dir/ alias too
	$index = $record->alias;
	$index = str_replace('/index.html', '', $index);
	$check = db_query('SELECT * FROM {url_alias}  WHERE url_alias.alias = :index',array(':index' => $index));
	//print $check->rowCount()."\n";
	if ($check->rowCount()==0) {
		// no alias with out index.html
		$new = array(
        'alias' => $index,
        'source' =>  $record->source
        );
      //print_r($new);exit();
	print "\n Adding alias ".$index." for ".$record->source;
      path_save($new);
	}


}

print "\n Finished\n";

?>