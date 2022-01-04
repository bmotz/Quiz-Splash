<?php
include("cas.php");
include("database_connect.php");
include("quizPhp.php");

$title = "QuizSplash: View Question";

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {

	$qid = htmlspecialchars($_POST["qid"]);
	// FOR TESTING ONLY:
	// $qid = "4";
	// var_dump($_POST);
	
	// FIRST WE SHOULD MAKE SURE THAT THE QUESTION CAN BE DISPLAYED
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
	$errorMessage = "";

	// Gather information about the question from db
	$qid = strval(intval($qid)); // paranoia
	$sql_getPoolClass = "SELECT a.poolId, a.classId, b.openDate, b.closeDate
						 FROM questions a
						 LEFT JOIN pools b ON a.poolId = b.poolId
						 WHERE a.questionId = ".$qid.";";
	$res_getPoolClass = mysql_query($sql_getPoolClass);
	$row_getPoolClass = mysql_fetch_array($res_getPoolClass);
	
	if ((!is_int(intval($qid))) ||  // Is this a valid questionId?
		(empty($res_getPoolClass))) {
		$errorMessage = "Somehow an invalid question was passed along to this page. Please go back to the <a href=\"./listQuizPools.php\">list of quizzes that are available to you</a>, and choose your quiz setting.";
	echo $qid;
	} elseif ((!in_array($row_getPoolClass["classId"], $studAr)) && // Is this user a member of the class?
		(!in_array($row_getPoolClass["classId"], $instAr))) {
		$errorMessage = "You're trying to view a question that is associated with a class that you aren't in. Please go back to the <a href=\"./listQuizPools.php\">list of quizzes that are available to you</a>, and choose a new question.";
	} elseif ($row_getPoolClass["openDate"] > $currentTime) { // Is the pool not yet open for viewing?
		$errorMessage = "You're trying to respond a question in a quiz pool that is not yet open. Please go back to the <a href=\"./listQuizPools.php\">list of quizzes that are available to you</a>, and choose a new question.";
	} elseif (($row_getPoolClass["closeDate"] < $currentTime) && // Is the pool closed to viewing?
			  (!empty($row_getPoolClass["closeDate"]))) {
		$errorMessage = "You're trying to view a question in a quiz pool that has closed. Please go back to the <a href=\"./listQuizPools.php\">list of quizzes that are available to you</a>, and choose a new question.";
	}

	if (!empty($errorMessage)) {
		
		echo "<h2>There's been some kind of mistake.</h2>".$errorMessage;
		
	} else {
		
		echo "<h2>Quiz Question</h2>";
		
		// SOMEONE'S ONLY ARRIVED HERE IF THEY'RE "CLEAN"
		// THERE'RE A FEW THINGS THAT WE MIGHT DO HERE:
		// -- REALIZE THAT THE USER HAS ALREADY ANSWERED AND RATED, AND DISPLAY QUESTION
		// -- COLLECT A RATING FROM THE POST METHOD, SUBMIT TO DB, AND DISPLAY QUESTION
		// -- COLLECT A RESPONSE FROM THE POST METHOD
		//    AND PRESENT THE QUESTION TO COLLECT A RATING
		// -- PRESENT A QUESTION FOR THE INITIAL RATING
		
		// GET THE QUESTION, AVERAGE RATINGS AND ALL
		$sql_getQuestion = "SELECT a.questionText, a.poolId, a.classId,
								   a.optionA, a.optionB, a.optionC, a.optionD, 
								   a.explanation, a.correct,
								   ROUND(AVG(b.quality),2) AS quality_avg,
								   ROUND(AVG(b.difficulty),2) AS difficulty_avg,
								   COUNT(b.responseId) AS response_cnt
						 	FROM questions a
							LEFT JOIN responses b ON a.questionId = b.questionId
							WHERE a.questionId = ".$qid."
							GROUP BY a.questionText, a.poolId, a.classId,
								   a.optionA, a.optionB, a.optionC, a.optionD, 
								   a.explanation, a.correct;";
		$res_getQuestion = mysql_query($sql_getQuestion);
		$row_getQuestion = mysql_fetch_array($res_getQuestion);
		$stem = $row_getQuestion["questionText"];
		$oA = $row_getQuestion["optionA"];
		$oB = $row_getQuestion["optionB"];
		$oC = $row_getQuestion["optionC"];
		$oD = $row_getQuestion["optionD"];
		$expl = $row_getQuestion["explanation"];
		$correct = $row_getQuestion["correct"];
		$qual_avg = $row_getQuestion["quality_avg"];
		$diff_avg = $row_getQuestion["difficulty_avg"];
		$resp_cnt = $row_getQuestion["response_cnt"];
		
		// NEED TO MAKE A JUDGMENT OF WHETHER THE
		// STUDENT'S ALREADY ANSWERED THIS QUESTION
		$sql_respCheck = "SELECT DISTINCT *
						  FROM responses
						  WHERE (username = '".$username."')
						    AND (questionId = ".$qid.");";
		$res_respCheck = mysql_query($sql_respCheck);
		$cnt_respCheck = mysql_num_rows($res_respCheck);
		$row_respCheck = mysql_fetch_array($res_respCheck);

		if ($cnt_respCheck > 0) {
	
			// THIS IS WHERE THE QUESTION GETS PRESENTED FOR REVIEW
			// BUT NOT FOR RESPONDING OR RATING
			
			echo '<div id="qDispBox">';
			echo '<p id="qDispText">'.stripslashes($stem).'</p>
				  <table width="100%" border="0">
					<tr>
					  <td>A.</td>
					  <td width="100%">'.stripslashes($oA).'</td>
					</tr>
					<tr>
					  <td>B.</td>
					  <td width="100%">'.stripslashes($oB).'</td>
					</tr>';
			if (!empty($oC)) { echo 
					'<tr>
					  <td>C.</td>
					  <td width="100%">'.stripslashes($oC).'</td>
					</tr>'; }
			if (!empty($oD)) { echo 
					'<tr>
					  <td>D.</td>
					  <td width="100%">'.stripslashes($oD).'</td>
					</tr>'; }
			echo '</table><br />';
			echo '<button>Show answer</button>
				  <div style="display: none">
				  	  <p>"'.$correct.'" is the correct answer. Here\'s how the question\'s author explains the answer: </p>
					  <blockquote>'.stripslashes($expl).'</blockquote>
					  <p>Question statistics:</p>
					  <ul>
					  	<li>Average quality: '.$qual_avg.'/5</li>
						<li>Average difficulty: '.$diff_avg.'/5</li>
						<li>Number of responses: '.$resp_cnt.'</li>
					  </ul>
					  <p>Your response to this question was recorded on '.$row_respCheck["responseDate"].'</p>
					  <p><a href="./listQuestions.php?poolId='.$row_getQuestion["poolId"].'&classId='.$row_getQuestion["classId"].'#qt">
						 Click here</a> to view and respond to the other questions in this pool.</p>
				  </div>
				   
				  <script>
				  $( "button" ).click(function() {
					$( "div" ).show();
				  });
				  </script>';
			echo '</div>';
			
		} else {
			
			// STUDENT HASN'T RESPONDED PREVIOUSLY...
			// CHECK POST TO SEE WHETHER WE NEED TO 
			// A. DISPLAY QUESTION FOR RESPONSE
			// B. DISPLAY DETAIL FOR RATING
			// C. SUBMIT TO DB AND REFRESH
			
			if (!isset($_POST["respOption"])) {
				// A. DISPLAY QUESTION FOR RESPONSE
				echo '<form method="POST" action="./question.php">';
				echo '<div id="qDispBox">';
				echo '<p id="qDispText">'.stripslashes($stem).'</p>
					  <table width="100%" border="0">
						<tr>
						  <td><input name="respOption" type="radio" value="A" /></td>
						  <td>A.</td>
						  <td width="100%">'.stripslashes($oA).'</td>
						</tr>
						<tr>
						  <td><input name="respOption" type="radio" value="B" /></td>
						  <td>B.</td>
						  <td>'.stripslashes($oB).'</td>
						</tr>';
				if (!empty($oC)) { echo 
						'<tr>
						  <td><input name="respOption" type="radio" value="C" /></td>
						  <td>C.</td>
						  <td>'.stripslashes($oC).'</td>
						</tr>'; }
				if (!empty($oD)) { echo 
						'<tr>
						  <td><input name="respOption" type="radio" value="D" /></td>
						  <td>D.</td>
						  <td>'.stripslashes($oD).'</td>
						</tr>'; }
				echo '</table><br />';
				echo '<input type="hidden" name="qid" value="'.$qid.'">';
				echo '<input type="submit" value="Submit"></form>';
				echo '</div>';	
							
			} elseif (((!isset($_POST["qualRate"])) || (!isset($_POST["diffRate"]))) ||
					  (($_POST["qualRate"] == "") || ($_POST["diffRate"] == ""))){
				
				$response = $_POST["respOption"];
				$options = array("A","B","C","D");
				if (!in_array($response, $options)) {
					$response = "A";
				}
				
				// B. DISPLAY DETAIL FOR RATING
				echo '<form method="POST" action="./question.php">';
				echo '<div id="qDispBox">';
				echo '<p id="qDispText">'.stripslashes($stem).'</p>
					  <table width="100%" border="0">
						<tr>
						  <td><input type="radio" disabled="true"';
				if ($response == "A") echo "checked";
				echo '/></td>
						  <td>A.</td>
						  <td width="100%">'.stripslashes($oA).'</td>
						</tr>
						<tr>
						  <td><input type="radio" disabled="true"';
				if ($response == "B") echo "checked";
				echo '/></td>
						  <td>B.</td>
						  <td width="100%">'.stripslashes($oB).'</td>
						</tr>';
				if (!empty($oC)) { echo 
						'<tr>
						  <td><input type="radio" disabled="true"';
				if ($response == "C") echo "checked";
				echo '/></td>
						  <td>C.</td>
						  <td width="100%">'.stripslashes($oC).'</td>
						</tr>'; }
				if (!empty($oD)) { echo 
						'<tr>
						  <td><input type="radio" disabled="true"';
				if ($response == "D") echo "checked";
				echo '/></td>
						  <td>D.</td>
						  <td width="100%">'.stripslashes($oD).'</td>
						</tr>'; }
				echo '</table>';
				echo '<p class="qRespText">You marked "'.$response.'", and according to the author of this question, your response is ';
				if ($response == $correct) {
					echo '<span style="font-weight:bold;color:green;">correct</span>.</p>';
				} else {
					echo '<span style="font-weight:bold;color:red;">incorrect</span>. "'.$correct.'" is marked as the correct answer.</p>';
				}
				echo '<p class="qRespText">Here\'s how the question\'s author explains the answer: </p>';
				echo '<blockquote>'.stripslashes($expl).'</blockquote>';
				echo '<p class="qRespText">In order to complete your response, you need to rate this question\'s quality and difficulty. Your response will not be recorded until you submit these ratings.</p>';
				echo '<table>
						<tr>
						<td width="15%" valign="top">
							<select name="qualRate">
								<option value="" selected>Quality</option>
								<option value="1">1 - Worst</option>
								<option value="2">2</option>
								<option value="3">3</option>
								<option value="4">4</option>
								<option value="5">5 - Best</option>
							</select>
						</td>
						<td valign="top">When evaluating the quality of this question, consider whether it is good enough to include on an examination, whether the answer and explanation demonstrate good understanding of the relevant material, and whether it has been useful to help you learn the material.</td>
						</tr>
						<tr>
						<td valign="top">
							<select name="diffRate">
								<option value="" selected>Difficulty</option>
								<option value="1">1 - Easiest</option>
								<option value="2">2</option>
								<option value="3">3</option>
								<option value="4">4</option>
								<option value="5">5 - Hardest</option>
							</select>
						</td>
						<td width="100%" valign="top">The difficulty of the question should not affect your previous rating of the question\'s quality.  When evaluating the question\'s difficulty, consider how much was required to arrive at the appropriate answer.</td>
						</tr>
					  </table><br />';
				echo '<input type="hidden" name="qid" value="'.$qid.'">';
				echo '<input type="hidden" name="respOption" value="'.$response.'">';
				echo '<input type="submit" value="Submit">';
				if (((isset($_POST["qualRate"])) || (isset($_POST["diffRate"]))) &&
					  (($_POST["qualRate"] == "") || ($_POST["diffRate"] == ""))) {
					echo '<span style="color:red"> You need to rate both the quality and the difficulty of this question to submit.</span>';
				}
				echo '</form></div>';		
						
			} else {
				
				// C. SUBMIT TO DB AND REFRESH
				
				// THE THINGS THAT GO INTO THE DATABASE ARE:
				// responseId (AI)
				// username
				// responseDate (auto-timestamp)
				// questionId
				// poolId
				// correct (boolean: 0,1)
				// response
				// difficulty
				// quality
				
				// THE THINGS THAT GET SANITIZED ARE:
				// response (again, because we reposted)
				$response = $_POST["respOption"];
				$options = array("A","B","C","D");
				if (!in_array($response, $options)) {
					$response = "A";
				}
				if ($response == $correct) {
					$correctness = "1";
				} else {
					$correctness = "0";
				}
				// quality
				$quality = $_POST["qualRate"];
				$options = array("1","2","3","4","5");
				if (!in_array($quality, $options)) {
					$quality = "1";
				}
				// difficulty
				$difficulty = $_POST["diffRate"];
				if (!in_array($difficulty, $options)) {
					$difficulty = "1";
				}
				// questionId
				// already got sanitized from the outset
				
				$sql_insertResponse = "INSERT INTO responses
										(username,
										 questionId,
										 poolId,
										 correct,
										 response,
										 difficulty,
										 quality)
									   VALUES
									    ('".$username."',
										 ".$qid.",
										 ".$row_getPoolClass["poolId"].",
										 ".$correctness.",
										 '".$response."',
										 ".$difficulty.",
										 ".$quality.");";
				$res_insertResponse = mysql_query($sql_insertResponse);
				if (! $res_insertResponse) {
					echo "<h2>Uh oh</h2>";
					echo "<p>There seems to've been some sort of error when submitting your response. 
					      Go back to <a href=\"./listQuestionPools.php\">the list of available question pools</a>.</p>";
				} else {
					echo '<p style="font-weight:bold;">Your response has been submitted!</p>
						  <p><a href="./listQuestions.php?poolId='.$row_getQuestion["poolId"].'&classId='.$row_getQuestion["classId"].'#qt">
						  Click here</a> to view and respond to the other questions in this pool.</p>';
				}
				
			}

		}
		
	}

} else {
	echo "<h2>Um, what did you want to do?</h2>";
	echo "<p>This is a page where someone would respond to a question, but it doesn't look like you've arrived with any particular set of questions.  Please go back to the <a href=\"./listQuizPools.php\">list of quizzes that are available to you</a>, and choose your quiz setting.</p>";
}

// ECHO THE BOTTOM BUN
echo substr($template, $splitpoint);
?>

