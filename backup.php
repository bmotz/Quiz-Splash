<?php
include("cas.php");
include("database_connect.php");

$title = "QuizSplash: Choose a Pool";

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
				      OR (assistant = '".$username."');";
$res_adminCheck = mysql_query($sql_adminCheck);
$cnt_adminCheck = mysql_num_rows($res_adminCheck);
$adminLink = "<li><a href =\"./classAdmin.php\">Administer My Classes</a></li>";
if ($cnt_adminCheck > 0) {
	$template = str_replace('<!--XXXADMINISTRATIONXXX-->', $adminLink, $template);
}
// FIND THE SPLITPOINT
$splitpoint = strpos($template,'<!--XXXSPLITPOINTXXX-->');
// ECHO THE TOP BUN
echo substr($template, 0, $splitpoint);

$currentTime = $_SERVER['REQUEST_TIME'];

// FIND CLASSES IF INSTRUCTOR
$inst_classAr = array();
while ($row=mysql_fetch_array($res_adminCheck)) {
	if ($row['expireDate'] < $currentTime) {
		$inst_classAr[$row["classId"]] = $row['className'];
	}
}

// FIND CLASSES IF STUDENT
$stud_classAr = array();
$sql_studCheck = "SELECT a.classId, b.className, b.expireDate
				  FROM students a
				  LEFT JOIN classes b ON a.classId = b.classId
				  WHERE a.username = '".$username."';";
$res_studCheck = mysql_query($sql_studCheck);
while ($row=mysql_fetch_array($res_studCheck)) {
	if ($row['expireDate'] < $currentTime) {
		$stud_classAr[$row["classId"]] = $row['className'];
	}
}

// REMOVE INSTANCES WHERE INSTRUCTORS ARE LISTED AS STUDENTS
$stud_classAr = array_diff($stud_classAr, $inst_classAr);

?>

<h2>Choose a Pool to Submit Questions</h2>
<p>Below you'll find the different pools where you can submit questions.  Some pools may have deadlines for you to submit questions, and minimum submission requirements.  If so, these are listed beneath the pool heading. Click the "Write a Question" link to start writing a new question for that pool.</p>

<?php 

if (!empty($inst_classAr)) {
	echo "<h3>Classes that you instruct:</h3>";
	foreach ($inst_classAr as $cid => $cname) {
		echo "<h4>".$cname."</h4>
		<ul>";
		
		// Collect Question Pools
		$sql_getInstPools = "SELECT poolId, poolName, poolDesc, fillDate, dueDate, openDate, closeDate, questionCnt, unlockCnt, requireExplanation 
							 FROM pools WHERE deleted IS NULL ORDER BY closeDate DESC;";
		$res_getInstPools = mysql_query($sql_getInstPools);
		while ($row=mysql_fetch_array($res_getInstPools)) {
			echo "<li>".$row["poolName"]."<br />
			".$row["poolDesc"]."<ul>";
			if (($row["closeDate"] < $currentTime) && (!empty($row["closeDate"]))) {
				echo "<li><i>Note: This pool closed on ".$row["closeDate"]."</i></li>";
			} else {
				echo "<li>";
				if ($row["questionCnt"] > 0) {
					echo "".$row["questionCnt"]." questions expected. ";
				} else { echo "No minimum question requirement. "; }
				if ($row["unlockCnt"] > 0) {
					echo "".$row["unlockCnt"]." needed to unlock quizzes. ";
				} else { echo "No submissions needed to unlock quizzes. "; }
				echo "</li><li>";
				if ($row["requireExplanation"] > 0) {
					echo "Explanation required with each question submission.";
				} else { echo "No explanation required with submitted questions."; }
				echo "</li>";
			}
			echo "<li><a href=\"./writeAquestion.php?poolId=".$row["poolId"]."\">Write a Question</a></li>";
			echo "<li><a href=\"./editPool.php?poolId=".$row["poolId"]."\">Change Pool Settings</a></li>";
			echo "</ul></li>";
		}
		echo "<li><a href=\"./createPool.php?classId=".$cid."\">Create a new pool for this class</a></li>";
		echo "</ul>";
	}
}
//$stud_classAr = $inst_classAr;
if (!empty($stud_classAr)) {
	echo "<h3>You're a student in the following classes:</h3>";
	foreach ($inst_classAr as $cid => $cname) {
		echo "<h4>".$cname."</h4>
		<ul>";
		
		// Collect Question Pools
		$sql_getInstPools = "SELECT poolId, poolName, poolDesc, fillDate, dueDate, openDate, closeDate, questionCnt, unlockCnt, requireExplanation 
							 FROM pools WHERE deleted IS NULL ORDER BY closeDate DESC;";
		$res_getInstPools = mysql_query($sql_getInstPools);
		while ($row=mysql_fetch_array($res_getInstPools)) {
			echo "<li>".$row["poolName"]."<br />
				 ".$row["poolDesc"]."<ul>";
			if ((strtotime($row["dueDate"]) < $currentTime) && (!empty($row["dueDate"]))) {
				echo "<li><i>It is past the due date (".$row["dueDate"].") for submitting questions to this pool.</i></li>";
			} else {
				echo "<li>";
				$sql_getSubmissionCnt = "SELECT COUNT(questionId) AS submissionCnt FROM questions WHERE poolId = ".$row["poolId"]." AND username = '".$username."';";
				$res_getSubmissionCnt = mysql_query($sql_getSubmissionCnt);
				$row_getSubmissionCnt = mysql_fetch_array($res_getSubmissionCnt);
				$submissionCnt = $row_getSubmissionCnt["submissionCnt"];
				echo "You've submitted ".$submissionCnt." questions to this pool.";
				echo "</li><li>";
				if ($row["questionCnt"] > 0) {
					echo "".$row["questionCnt"]." questions expected. ";
				} else { echo "No minimum question requirement. "; }
				if (!empty($row["dueDate"])) {
					echo "The deadline for submission is: ".date("F j, Y, g:i a",strtotime($row['dueDate']))."";
				} else {
					echo "There is no due date for question submission.";
				}
				echo "</li><li>";
				if (($row["unlockCnt"] > 0) && ($row["unlockCnt"] > $submissionCnt)) {
					echo "You need to submit ".($row["unlockCnt"] - $submissionCnt)." more questions to unlock quizzes. ";
				} else { echo "No submissions needed to unlock quizzes. "; }
				echo "</li>";
			}
			echo "<li><a href=\"./writeAquestion.php?poolId=".$row["poolId"]."\">Write a Question</a></li>";
			echo "</ul></li>";
		}
		echo "</ul>";
	}
}
if (empty($inst_classAr) && empty($stud_classAr)) {
	echo "<h3>There are no question pools available to ".$username."</h3>";
	echo "<p>If you think you should have access to a class or a question pool here, please contact your instructor.</p>";
}

//<a href="./writeAquestion.php">Example Quiz</a> [0 / 10 questions written; Due Mar 15]</li>

// ECHO THE BOTTOM BUN
echo substr($template, $splitpoint);
?>

