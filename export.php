<?php
include("cas.php");
include("database_connect.php");

$title = "QuizSplash: Data Export";

// PREPARE THE TEMPLATE
$template = file_get_contents('./template.html');
// REPLACE THE TITLE
$template = str_replace('<!--XXXTITLEXXX-->', $title, $template);
// REPLACE INSTANCES OF USERNAME
$username = $_SESSION['user'];
$template = str_replace('<!--XXXUSERNAMEXXX-->', $username, $template);
// ADD ADMINISTRATION LINK TO TOPNAV IF ADMINISTRATOR
$sql_adminCheck = "SELECT * 
				   FROM classes 
				   WHERE (instructor = '".$username."')
				      OR (assistant = '".$username."')
				   ORDER BY expireDate DESC;";
$res_adminCheck = mysql_query($sql_adminCheck);
$cnt_adminCheck = mysql_num_rows($res_adminCheck);
$adminLink = "<li><a href =\"./classAdmin.php\">Administration</a></li>";
if ($cnt_adminCheck > 0) {
	$template = str_replace('<!--XXXADMINISTRATIONXXX-->', $adminLink, $template);
}

// Check that the user can receive the pool data
// CHECK TO SEE THAT THE POOL IS INSTRUCTED OR
// ASSISTED BY THE USER
$errorMessage = "";
$targetPool = htmlspecialchars($_GET["poolId"]);
if ($targetPool == "") {
	printf("<script>location.href='classAdmin.php'</script>");
}
$targetPool = strval(intval($targetPool));
$sql_poolCheck = "SELECT DISTINCT a.poolId, a.poolName, a.poolDesc, a.fillDate, a.dueDate, a.openDate, a.closeDate, a.questionCnt, a.unlockCnt, a.responseCnt, a.requireExplanation, b.instructor, b.assistant, b.expireDate
				  FROM pools a
				  LEFT JOIN classes b ON a.classId = b.classId
				  WHERE a.poolId = ".$targetPool."
				    AND a.deleted IS NULL;";
$res_poolCheck = mysql_query($sql_poolCheck);
$canManage = 0; // ASSUME NOT
while ($row=mysql_fetch_array($res_poolCheck)) {
	if (($row["instructor"] == $username) || ($row["assistant"] == $username)) {
		$canManage = 1; // we can manage this pool
	}
}
if ($canManage == 0) {
	$errorMessage = "You do not have permission to view student data for this pool.";
}

// Find report type
// repType = student
// repType = questions
// repType = responses
$repType = htmlspecialchars($_GET["repType"]);

// Get Data
$sql_studentRep =  "SELECT DISTINCT a.username,
					  COUNT(DISTINCT b.questionId) AS questionsWritten,
					  COUNT(DISTINCT c.responseId) AS responsesSubmitted,
					  AVG(x.quality) AS questionQualityAvg,
					  AVG(x.difficulty) AS questionDifficultyAvg,
					  AVG(c.correct) AS responseAccuracy
					FROM students a
					LEFT JOIN questions b ON a.username = b.username
					LEFT JOIN responses c ON a.username = c.username AND b.poolId = c.poolId
					LEFT JOIN responses x ON b.questionId = x.questionId
					WHERE b.poolId = ".$targetPool."
					GROUP BY a.username";

if ($errorMessage == "") {
	
	function cleanData(&$str)
	{
	  $str = preg_replace("/\t/", "\\t", $str);
	  $str = preg_replace("/\r?\n/", "\\n", $str);
	  if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
	}
	
	// filename for download
	$filename = "studentdata.xls";
	
	header("Content-Disposition: attachment; filename=\"$filename\"");
	header("Content-Type: application/vnd.ms-excel");
	
	$flag = false;
	$result = mysql_query($sql_studentRep);
	while(false !== ($row = mysql_fetch_assoc($result))) {
	  if(!$flag) {
		// display field/column names as first row
		echo implode("\t", array_keys($row)) . "\r\n";
		$flag = true;
	  }
	  array_walk($row, 'cleanData');
	  echo implode("\t", array_values($row)) . "\r\n";
	}
	exit;
	
} else {

	// FIND THE SPLITPOINT
	$splitpoint = strpos($template,'<!--XXXSPLITPOINTXXX-->');
	// ECHO THE TOP BUN
	echo substr($template, 0, $splitpoint);
	// ECHO THE HEADER
	echo "<h2>Data Export</h2>";
	echo $errorMessage;
	// ECHO THE BOTTOM BUN
	echo substr($template, $splitpoint);
	
}

?>

