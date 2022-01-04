<?php
include("cas.php");
include("database_connect.php");
include("quizPhp.php");

$title = "QuizSplash: Confirm your Submission";

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
$adminLink = "<li><a href =\"./classAdmin.php\">Administration</a></li>";
if ($cnt_adminCheck > 0) {
	$template = str_replace('<!--XXXADMINISTRATIONXXX-->', $adminLink, $template);
}
// FIND THE SPLITPOINT
$splitpoint = strpos($template,'<!--XXXSPLITPOINTXXX-->');
// ECHO THE TOP BUN
echo substr($template, 0, $splitpoint);

// FORM VALIDATION for QUESTION SUBMISSION
// define variables and initialize with empty values
$errorMessage = "";
$question = $optionA = $optionB = $optionC = $optionD = $correct = $explanation = "";

// check contents if form submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	
	$targetPool = htmlspecialchars($_POST["targetPool"]);
	$confirmed = $_POST["confirmed"];
	
	// CHECK WHETHER AUTHORIZED FOR GOTTEN POOL
	$currentTime = $_SERVER['REQUEST_TIME'];
	$sql_poolAccessCheck = "SELECT DISTINCT a.classId, b.className, a.poolName, a.poolDesc, a.requireExplanation, a.dueDate, a.closeDate, b.instructor, b.assistant, b.expireDate
							FROM pools a
							LEFT JOIN classes b ON a.classId = b.classId
							LEFT JOIN students c ON a.classId = b.classId
							WHERE (b.instructor = '".$username."'
							   OR b.assistant = '".$username."'
							   OR c.username = '".$username."')
							  AND a.poolId = ".$targetPool."
							  AND a.deleted IS NULL;";
	$res_poolAccessCheck = mysql_query($sql_poolAccessCheck);
	$cnt_poolAccessCheck = mysql_num_rows($res_poolAccessCheck);
	$row_poolAccessCheck = mysql_fetch_array($res_poolAccessCheck);
	// echo var_dump($row_poolAccessCheck);
	
	// repopulate form values
	$question = htmlspecialchars($_POST["questionText"]);
	$optionA = htmlspecialchars($_POST["optionTextA"]);
	$optionB = htmlspecialchars($_POST["optionTextB"]); 
	$optionC = htmlspecialchars($_POST["optionTextC"]);
	$optionD = htmlspecialchars($_POST["optionTextD"]);
	$correct = htmlspecialchars($_POST["correctOption"]);
	$explanation = htmlspecialchars($_POST["explanation"]);
	$tag1 = htmlspecialchars($_POST["tag1"]);
	$tag2 = htmlspecialchars($_POST["tag2"]);
	$tag3 = htmlspecialchars($_POST["tag3"]);
	
	// echo "Form Submitted";
	if (empty($question)) {
        $errorMessage = $errorMessage."<li>You must have question text.</li>";
    }
	if (!isset($_POST["correctOption"])) {
        $errorMessage = $errorMessage."<li>You must specify the correct answer to your question.</li>";
    }
	if (empty($optionA) || empty($optionB)) {
		$errorMessage = $errorMessage."<li>At minimum, options A and B both need to contain text.</li>";
	}
	if (  (empty($optionA) && $correct == "A")
		||(empty($optionB) && $correct == "B")
		||(empty($optionC) && $correct == "C")
		||(empty($optionD) && $correct == "D")) {
		$errorMessage = $errorMessage."<li>The correct answer cannot be a blank option.</li>";
	}
	if (empty($explanation) && ($row_poolAccessCheck["requireExplanation"] == 1)) {
		$errorMessage = $errorMessage."<li>This pool requires you to submit an explanation with each question. The explanation should describe why the correct answer is correct.</li>";
	}
	if (empty($tag1) && empty($tag2) && empty($tag3)) {
		$errorMessage = $errorMessage."<li>You need to add at least one tag to your question.</li>";
	}
	if (empty($errorMessage) && $confirmed == 0) {
		
		// SPIT OUT THE PAGE
		echo "<h2>Confirm your question submission to ".$row_poolAccessCheck["poolName"]."</h2>";
		echo '<div id="qSubBox">
				  <p>Submission by: '.$username.'<br />
				     Timestamp: '.date("Y-m-d H:i", $currentTime).'</p>';
			echo '<p id="qSubText">'.stripslashes($question).'</p>
				  <ol type="A">
				  	<li>'.stripslashes($optionA).'</li>
					<li>'.stripslashes($optionB).'</li>';
					if (!empty($optionC)) { echo '<li>'.stripslashes($optionC).'</li>'; }
					if (!empty($optionD)) { echo '<li>'.stripslashes($optionD).'</li>'; }
		echo '	  </ol>
				  <p id="qSubCorr"> Correct: '.$correct.'</p>
				  <p id="qSubExp"> Explanation: '.stripslashes($explanation).'</p>';
		echo '</div>';
		echo '<form method="POST" name="questionForm" action="confirmQuestion.php">';
		echo '<input type="hidden" name="errorMessage" value="'.$errorMessage.'">';
		echo '<input type="hidden" name="questionText" value="'.stripslashes($question).'">';
		echo '<input type="hidden" name="optionTextA" value="'.stripslashes($optionA).'">';
		echo '<input type="hidden" name="optionTextB" value="'.stripslashes($optionB).'">';
		echo '<input type="hidden" name="optionTextC" value="'.stripslashes($optionC).'">';
		echo '<input type="hidden" name="optionTextD" value="'.stripslashes($optionD).'">';
		echo '<input type="hidden" name="correctOption" value="'.$correct.'">';
		echo '<input type="hidden" name="explanation" value="'.stripslashes($explanation).'">';
		echo '<input type="hidden" name="tag1" value="'.stripslashes($tag1).'">';
		echo '<input type="hidden" name="tag2" value="'.stripslashes($tag2).'">';
		echo '<input type="hidden" name="tag3" value="'.stripslashes($tag3).'">';
		echo '<input type="hidden" name="targetPool" value="'.stripslashes($targetPool).'">';
		echo '<input type="hidden" name="confirmed" value="1">';
		echo '<p>Press the button below to submit this question to '.$row_poolAccessCheck["poolName"].'. You will not be able to edit this question after it is submitted (because that would mess up the responses and ratings that other students will provide)-- so treat this like the final submission of a homework assignment. If you want to change your question right now, use your browser\'s "Back" button to go back to the previous page.</p>';
		echo '<input type="submit" value="Submit Question">';
		echo '</form>';
		
	} elseif (empty($errorMessage) && $confirmed == 1) {
		
		// SUBMIT TO DATABASE
		$createDate = date('Y-m-d H:i');
		$sql_insertQuestion = "INSERT INTO questions
							   (poolId,
							    classId,
								username,
								questionText,
								optionA,
								optionB,
								optionC,
								optionD,
								explanation,
								correct,
								createDate)
							   VALUES 
							   (".$targetPool.",
							    ".$row_poolAccessCheck["classId"].",
								'".$username."',
								'".addslashes($question)."',
								'".addslashes($optionA)."',
								'".addslashes($optionB)."',
								'".addslashes($optionC)."',
								'".addslashes($optionD)."',
								'".addslashes($explanation)."',
								'".$correct."',
								'".$createDate."');";
		$res_insertQuestion = mysql_query($sql_insertQuestion);
		$thisQuestionId = mysql_insert_id();
		$sql_insertTags = "INSERT INTO tags
						   (classId,
						    poolId,
							questionId,
							tag,
							username)
						   VALUES ";
		$tagAr = array(addslashes($tag1), addslashes($tag2), addslashes($tag3));
		foreach ($tagAr as $value) {
			if ($value != "") {
				$sql_insertTags = $sql_insertTags."
								  (".$row_poolAccessCheck["classId"].",
								   ".$targetPool.", 
								   ".$thisQuestionId.", 
								   '".$value."', 
								   '".$username."'),";
			}
		}
		$sql_insertTags = rtrim($sql_insertTags, ",");
		$sql_insertTags = $sql_insertTags.";";				
		$res_tagInsert = mysql_query($sql_insertTags);
		if (! $res_insertQuestion) {
			echo "<h2>Uh oh</h2>";
			echo "<p>There seems to've been some sort of error when submitting your question. Go back to <a href=\"./listQuestionPools.php\">the list of available question pools</a>.</p>";
		} else {
			echo "<h2>Your question has been submitted!</h2>";
			echo "<p>Wanna <a href=\"./writeAquestion.php?poolId=".$targetPool."\">submit another question to this pool</a>?";
		}
		
		
		
	} else {
		
		echo '<form method="POST" name="questionForm" action="./writeAquestion.php?poolId='.$targetPool.'">';
		echo '<input type="hidden" name="errorMessage" value="'.$errorMessage.'">';
		echo '<input type="hidden" name="questionText" value="'.stripslashes($question).'">';
		echo '<input type="hidden" name="optionTextA" value="'.stripslashes($optionA).'">';
		echo '<input type="hidden" name="optionTextB" value="'.stripslashes($optionB).'">';
		echo '<input type="hidden" name="optionTextC" value="'.stripslashes($optionC).'">';
		echo '<input type="hidden" name="optionTextD" value="'.stripslashes($optionD).'">';
		echo '<input type="hidden" name="correctOption" value="'.$correct.'">';
		echo '<input type="hidden" name="explanation" value="'.stripslashes($explanation).'">';
		echo '<input type="hidden" name="tag1" value="'.$tag1.'">';
		echo '<input type="hidden" name="tag2" value="'.$tag2.'">';
		echo '<input type="hidden" name="tag3" value="'.$tag3.'">';
		echo '<h2>Op-- there\'s a problem with your question.</h2>';
		echo '<p>Click the button below to go back and fix the problem.  It\'ll bring you back to the page where you wrote the question, and there\'ll be a red error message at the top that tells you what\'s wrong.</p>';
		echo '<input type="submit" value="Go back and fix">';
		echo '</form>';
		
	}
	
	
	
} else {  // No one should arrive at this page without POST data

	echo "<h2>Um, there's nothing to confirm.</h2>";
	echo "<p>This is a page where someone would confirm the submission of a question that they just wrote.  But it doesn't look like you've gotten here by submitting a question.  If you want to write a question, please go back to the <a href=\"./listQuestionPools.php\">list of question pools that are available to you</a>.</p>";

}

// ECHO THE BOTTOM BUN
echo substr($template, $splitpoint);


?>

