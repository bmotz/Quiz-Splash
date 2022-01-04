<?php

// CONNECT & SELECT
$db = mysql_connect('mysql.iu.edu:3859', 'webuser', 'XXXXXXX');
if (!$db) {
   die('Could not connect: ' . mysql_error());
}
mysql_select_db("quizdb");

?>