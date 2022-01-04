<?php
include("cas.php");
include("database_connect.php");
include("quizPhp.php");

$title = "QuizSplash: Take a Quiz";

// PREPARE THE TEMPLATE
$template = file_get_contents('./template.html');
// REPLACE THE TITLE
$template = str_replace('<!--XXXTITLEXXX-->', $title, $template);
// REPLACE INSTANCES OF USERNAME
$username = $_SESSION['user'];
$template = str_replace('<!--XXXUSERNAMEXXX-->', $username, $template);
// ADD ADMINISTRATION LINK TO TOPNAV IF ADMINISTRATOR
$sql_adminCheck = "SELECT DISTINCT * 
				   FROM classes 
				   WHERE (instructor = '".$username."')
				      OR (assistant = '".$username."');";
$res_adminCheck = mysql_query($sql_adminCheck);
$cnt_adminCheck = mysql_num_rows($res_adminCheck);
$adminLink = "<li><a href =\"./classAdmin.php\">Administration</a></li>";
if ($cnt_adminCheck > 0) {
	$template = str_replace('<!--XXXADMINISTRATIONXXX-->', $adminLink, $template);
}

// FIND THE SPLITPOINT
$splitpoint = strpos($template,'<!--XXXSPLITPOINTXXX-->');
// ECHO THE TOP BUN
echo substr($template, 0, $splitpoint);

$currentTime = date("Y-m-d H:i");

if (($_SERVER["REQUEST_METHOD"] == "POST") || ($username == "bmotz")) {

	//$questionSet = htmlspecialchars($_POST["questionSet"]);
	//$questionSet = "4,5,6,7,8";
	$questionSet = "4";
	$questionArr = explode(",",$questionSet);
	
	// In what classes is this user authorized to respond?
	$studAr = array();
	$instAr = array();
	$sql_getUserClass = "SELECT classId
						 FROM students
						 WHERE username = '".$username."';";
	$res_getUserClass = mysql_query($sql_getUserClass);
	while ($row=mysql_fetch_array($res_getUserClass)) {
		array_push($studAr, $row["classId"]);
	}
	$sql_getInstClass = "SELECT classId
						 FROM classes
						 WHERE instructor = '".$username."'
						    OR assistant = '".$username."';";
	$res_getInstClass = mysql_query($sql_getInstClass);
	while ($row=mysql_fetch_array($res_getInstClass)) {
		array_push($instAr, $row["classId"]);
	}
	
	// First, check that the set is okay
	$errorMessage = "";
	foreach ($questionArr as $qid) {
		
		$sql_getPoolClass = "SELECT a.poolId, a.classId, b.openDate, b.closeDate
							 FROM questions a
							 LEFT JOIN pools b ON a.poolId = b.poolId
							 WHERE a.questionId = ".$qid.";";
		$res_getPoolClass = mysql_query($sql_getPoolClass);
		$row_getPoolClass = mysql_fetch_array($res_getPoolClass);
		
		// Is this a valid questionId?
		// Is this in an open pool?
		// Is this user a member of the class?
		if ((!is_int(intval($qid))) ||
			(empty($res_getPoolClass))) {
			$errorMessage = "Somehow an invalid question was passed along to this page. Please go back to the <a href=\"./listQuizPools.php\">list of quizzes that are available to you</a>, and choose your quiz setting.";
			break;
		} elseif ($row_getPoolClass["openDate"] > $currentTime) {
			$errorMessage = "You're trying to respond to questions in a quiz pool that is not yet open. Please go back to the <a href=\"./listQuizPools.php\">list of quizzes that are available to you</a>, and choose your quiz setting.";
			break;
		} elseif (($row_getPoolClass["closeDate"] < $currentTime) &&
				  (!empty($row_getPoolClass["closeDate"]))) {
			$errorMessage = "You're trying to respond to questions in a quiz pool that has closed. Please go back to the <a href=\"./listQuizPools.php\">list of quizzes that are available to you</a>, and choose your quiz setting.";
			break;
		} elseif ((!in_array($row_getPoolClass["classId"], $studAr)) &&
				  (!in_array($row_getPoolClass["classId"], $instAr))) {
			$errorMessage = "You're trying to respond to questions that are associated with a class that you aren't in. Please go back to the <a href=\"./listQuizPools.php\">list of quizzes that are available to you</a>, and choose your quiz setting.";
			break;
		}	

	}
	if (!empty($errorMessage)) {
		echo "<h2>There's been some kind of mistake.</h2>".$errorMessage;
	} else {
		
		// THIS IS WHERE FUN STUFF STARTS HAPPENING
		echo "you're good to go";
	}

} else {
	echo "<h2>Um, what did you want to do?</h2>";
	echo "<p>This is a page where someone would respond to questions, but it doesn't look like you've arrived with any particular set of questions.  Please go back to the <a href=\"./listQuizPools.php\">list of quizzes that are available to you</a>, and choose your quiz setting.</p>";
}

// ECHO THE BOTTOM BUN
echo substr($template, $splitpoint);
?>

