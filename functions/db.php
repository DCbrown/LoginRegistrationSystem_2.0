<?php

//connect to the database
$connection = mysqli_connect('localhost', 'root', '', 'login_db');


function row_count($result){

	return mysqli_num_rows($result);
	 

}


//escape the database
function escape($string) {

	global $connection;

	return mysqli_real_escape_string($connection, $string);

}


//query the datatbase
function query($query) {

	global $connection;

	return mysqli_query($connection, $query);

	confirm($result);

}

function confirm($result){

	global $connection;

	if(!$result) {

		die("Query failed" . mysqli_error($connection));
	}
}


//fetch Data
function fetch_array($result) {

	global $connection;

	return mysqli_fetch_array($result);

}

?>