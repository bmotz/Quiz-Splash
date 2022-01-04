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
$adminLink = "<li><a href =\"./classAdmin.php\">Administration</a></li>";
if ($cnt_adminCheck > 0) {
	$template = str_replace('<!--XXXADMINISTRATIONXXX-->', $adminLink, $template);
}
// FIND THE SPLITPOINT
$splitpoint = strpos($template,'<!--XXXSPLITPOINTXXX-->');
// ECHO THE TOP BUN
echo substr($template, 0, $splitpoint);

$currentTime = date("Y-m-d H:i");

// FIND CLASSES IF INSTRUCTOR
$inst_classAr = array();
while ($row=mysql_fetch_array($res_adminCheck)) {
	if (($row['expireDate'] > $currentTime) || ($row['expireDate'] == "")) {
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
	if (($row['expireDate'] > $currentTime) || ($row['expireDate'] == "")) {
		$stud_classAr[$row["classId"]] = $row['className'];
	}
}

// REMOVE ANY INSTANCES WHERE INSTRUCTORS ARE LISTED AS STUDENTS
$stud_classAr = array_diff($stud_classAr, $inst_classAr);

?>

<h2>Answer Questions</h2>
<p>Select a quiz pool to answer questions.  Some pools may have deadlines for you to answer questions, and minimum response requirements.  If so, these are listed alongside the pool heading.</p>
<div id="tooltip_container">&nbsp;</div>

<?php 

if (empty($inst_classAr) && empty($stud_classAr)) {
	
	echo "<h3>There are no question pools available to ".$username."</h3>";
	echo "<p>If you think you should have access to a class or a question pool here, please contact your instructor.</p>";
	
} else {

	// Page logic:	
	// STEP 1: ClassId
	// When the $classAr is more than 1,
	//   -- Check $_GET for a classId
	//   -- Sanitize the classId
	//   -- Present a select menu for class
	// If the $classAr is 1, OR $_GET yields a classId
	//   -- Sanitize the classId
	// STEP 2: PoolId
	// If the total number of pools in the class
	// (that aren't deleted) is more than 1,
	//   -- Present a table of pools with radio
	//      buttons that allows the selection of a pool
	//   -- Check $_GET for a poolId
	// If the number of pools is 1, ro $_GET yields a poolId
	//   -- Sanitize the poolId
	// STEP 3: Pool table
	// List all the questions in the pool

	// For testing only:
	// $stud_classAr = $inst_classAr;
	
	// Make all classes array
	// $classAr = array_merge($inst_classAr, $stud_classAr); BADBADBAD
	$classAr = $inst_classAr + $stud_classAr;
	
	// STEP 1
	
	// Create drop menu, if there's more than one class
	if (count($classAr) > 1) {
		// Get ClassId
		$targetClass = htmlspecialchars($_GET["classId"]);
		$targetClass = strval(intval($targetClass));
		if ((isset($_GET["classId"])) && (!array_key_exists($targetClass,$classAr))) {
			printf("<script>location.href='listQuestions.php'</script>");
		} if (!isset($_GET["classId"])) {
			$targetClass = "";
		}
		echo '<p><form action="./listQuestions.php" method="get" target="_self"> 
				<select id="classId" name="classId">
					<option value="">Select class:</option>';
					foreach ($classAr as $cid => $cname) {
						if (isset($inst_classAr[$cid])) {
							// It's a class that the user instructs
							echo '<option value="'.$cid.'" ';
							if ($cid === $targetClass) echo 'selected="selected"';
							echo '>'.$cname.' (Instructor)</option>';
						} else {
							echo '<option value="'.$cid.'" ';
							if ($cid === $targetClass) echo 'selected="selected"';
							echo '>'.$cname.'</option>';
						}
					}
		echo "	</select>
			    </form>
				<script>
				$('#classId').change(
				    function(){
						$(this).closest('form').trigger('submit');
					});
				</script>
			  </p>";
	} else {
		reset($classAr);
		$targetClass = key($classAr);
	}
	
	// STEP 2
	// a clean classId is selected in
	// the $targetClass variable.
	// List the pools in that class
	
	if ($targetClass!=="") {
		
		// Collect Question Pools for the class
		$sql_getPools = "SELECT a.poolId, a.poolName, a.poolDesc, a.fillDate, a.dueDate, a.openDate, a.closeDate, a.questionCnt, a.unlockCnt, a.responseCnt, a.requireExplanation
						 FROM pools a
						 WHERE a.classId = ".$targetClass." 
						   AND a.deleted IS NULL 
						 ORDER BY a.closeDate ASC, a.poolId;";
		$res_getPools = mysql_query($sql_getPools);
		$cnt_getPools = mysql_num_rows($res_getPools);
		
		echo '<h3>Question pools in '.$classAr[0].'</h3>';
		
		if ($cnt_getPools == 0) {
			
			echo '<p>There are no question pools in this class. If you believe that you should see a question pool here, please contact your instructor.</p>';

		} else {
		
			echo '<p>List the questions in a pool by clicking on the pool\'s name:</p>';
			
			echo '<table class="poolTable">';
			// Columns:
			//  poolName
			//  poolDesc
			//  status
			//  requirements (+questions submitted)
			//  responses submitted
			//  list questions link
			echo '<thead><tr><th scope="col" id="ptcol_name">Pool Name</th>
							 <th scope="col" id="ptcol_desc">Pool Description</th>
							 <th scope="col" id="ptcol_status">Status for<br />Answering Questions</th>
							 <th scope="col" id="ptcol_reqs">Requirements</th>
							 <th scope="col" id="ptcol_subs">Responses<br />Submitted</th>
				  </tr></thead>';
			
			// initialize array that will receive valid poolIds for this user
			$poolAr = array();
			$pendingPools = array();
			$closedPools = array();
			$lockedPools = array();
			$unavailablePools = array();
			
			while ($row=mysql_fetch_array($res_getPools)) {
				
				echo '<tbody><tr>';
				echo '<td><form action="./listQuestions.php#qt" method="get" target="_self">
						  <input type="hidden" name="poolId" value="'.$row["poolId"].'">
						  <input type="hidden" name="classId" value="'.$targetClass.'">
						  <input type="submit" class="submitLink" value="'.stripslashes($row["poolName"]).'">
						  </form></td>';
				echo '<td>'.stripslashes($row["poolDesc"]).'</td>';			
							
				// Status Column ( closed ; open ; waiting )
				if (($row["closeDate"] <= $currentTime) && (!empty($row["closeDate"]))) {
					echo '<td>Closed to responses (deadline was: '.date("F j, Y, g:i a",strtotime($row['closeDate'])).')</td>';
					$closedPools[] = $row["poolId"];
				} elseif (($row["openDate"] <= $currentTime) || (empty($row["openDate"]))) {
					echo '<td>Open for answering questions ';
					if (empty($row["closeDate"])) {
						echo '</td>';
					} else {
						echo '(close date: '.date("F j, Y, g:i a",strtotime($row['closeDate'])).')</td>';
					}
				} elseif ($row["fillDate"] > $currentTime) {
					echo '<td>Not yet accepting questions (questions can be submitted on '.date("F j, Y, g:i a",strtotime($row['fillDate'])).')</td>';
					$unavailablePools[] = $row["poolId"];
				} elseif ($row["openDate"] > $currentTime) {
					echo '<td>Not yet open for responses (opens on '.date("F j, Y, g:i a",strtotime($row['openDate'])).')</td>';
					$pendingPools[] = $row["poolId"];
				} 
				
				// Questions Submitted Column
				$sql_getSubmissionCnt = "SELECT COUNT(questionId) AS submissionCnt FROM questions WHERE poolId = ".$row["poolId"]." AND username = '".$username."';";
				$res_getSubmissionCnt = mysql_query($sql_getSubmissionCnt);
				$row_getSubmissionCnt = mysql_fetch_array($res_getSubmissionCnt);
				$submissionCnt = $row_getSubmissionCnt["submissionCnt"];
				
				// Requirements Column
				echo '<td>';
				if ($row["questionCnt"] > 0) {
					echo ''.$row["responseCnt"].' responses are expected. ';
				} else { echo 'No minimum response requirement. '; }
				if ($row["unlockCnt"] > 0) {
					echo 'You need to have submitted '.$row["unlockCnt"].' questions to unlock this pool for answering questions, and ';
					echo 'you\'ve submitted '.$submissionCnt.' questions.';
					if ($submissionCnt < intval($row["unlockCnt"])) {
						$lockedPools[$row["poolId"]] = $row["unlockCnt"];
					}
				} 
				echo '</td>';
				
				// Responses Submitted Column
				$sql_getResponseCnt = "SELECT COUNT(responseId) AS responseCnt FROM responses WHERE poolId = ".$row["poolId"]." AND username = '".$username."';";
				$res_getResponseCnt = mysql_query($sql_getResponseCnt);
				$row_getResponseCnt = mysql_fetch_array($res_getResponseCnt);
				$responseCnt = $row_getResponseCnt["responseCnt"];
				
				echo '<td>You\'ve responded to '.$responseCnt.' ';
				if ($responseCnt == 1) {
					echo 'question';
				} else {
					echo 'questions';
				}
				echo ' in this pool.</td>';
				
				echo '</tr></tbody>';				

				$poolAr[$row["poolId"]] = $row["poolName"];
				
			} // end while loop through pools
			
			echo '</table><p>&nbsp;</p>';
			
			// Get poolId
			$targetPool = htmlspecialchars($_GET["poolId"]);
			$targetPool = strval(intval($targetPool));
			if ((isset($_GET["poolId"])) && (!array_key_exists($targetPool,$poolAr))) {
				printf("<script>location.href='listQuestions.php?classId=".$targetClass."'</script>");
			} if (!isset($_GET["poolId"])) {
				$targetPool = "";
			}
			
			// STEP 3
			// a clean poolId is selected in
			// the $targetPool variable.
			// List the questions in that pool
			
			if ($targetPool!=="") {
				
				echo '<a name="qt"></a>';
				echo '<h3>Questions in '.$poolAr[$targetPool].'</h3>';
				
				// Questions Submitted Column
				$sql_getQuestionList = "SELECT DISTINCT a.questionId,
										SUBSTRING(a.questionText, 1, 30) AS preview,
										GROUP_CONCAT(DISTINCT t.tag SEPARATOR ', ') AS tags,
										COUNT(DISTINCT b.responseId) AS responses,
										ROUND(AVG(b.difficulty), 2) AS avg_difficulty,
										ROUND(AVG(b.quality), 2) AS avg_quality,
										ROUND(AVG(b.correct),2) AS accuracy,
										(SELECT COUNT(DISTINCT c.responseId) FROM responses c WHERE c.username = '".$username."' AND c.questionId = a.questionId) AS userresponses
										FROM questions a
										LEFT JOIN responses b ON a.questionId = b.questionId
										LEFT JOIN tags t ON a.questionId = t.questionId 
										WHERE a.poolId = ".$targetPool."
										GROUP BY a.questionId;";
				$res_getQuestionList = mysql_query($sql_getQuestionList);
				$cnt_getQuestionList = mysql_num_rows($res_getQuestionList);
				
				//echo $cnt_getQuestionList;
				
				
				if ((in_array($targetPool, $closedPools)) && (!array_key_exists($targetClass, $inst_classAr))) {
					// condition if the pool is closed (excl. instructors)
					echo 'The pool you selected is closed (see pool status in the table above). Please select an open pool to see questions.';
				} elseif ((array_key_exists($targetPool, $lockedPools)) && (!array_key_exists($targetClass, $inst_classAr))) {
					// condition if the student hasn't supplied the unlock requirement (excl. instructors)
					echo 'You haven\'t submitted enough questions to unlock the ability to answer questions in this pool. 
					      Students need to submit '.$lockedPools[$targetPool].' questions in order to access questions in this pool.
						  <a href="./writeAquestion.php?poolId='.$targetPool.'">Click here to write a question for this pool</a>.';
				} elseif ((in_array($targetPool, $lockedPools)) && (!array_key_exists($targetClass, $inst_classAr))) {
					echo 'The pool you selected is not yet populated with questions (see pool status in the table above).';
					// check to see whether the pool is not yet filling
				} elseif ((in_array($targetPool, $pendingPools)) && (!array_key_exists($targetClass, $inst_classAr))) {
					echo 'The pool you selected is not yet available for students to respond to questions (see pool status in the table above). 
					      Please select an open pool to see questions.';
					// check to see whether the pool is pending	
				} elseif ($cnt_getQuestionList < 1) {
					// what should be done if there're no questions
					echo 'Oh drat. There aren\'t any questions in this pool yet. Perhaps you could be the first to 
						  <a href="./writeAquestion.php?poolId='.$targetPool.'">write a question for this pool</a>.';
				} else {
					
					// datatables initialization
					echo "
					<script>
					
					$(document).ready(function() {
						var oTable = $('#qt_table').dataTable( {
							\"oLanguage\": {
								\"sSearch\": \"Search questions and tags:\"
								}
						} );
					});
						
					</script>
					";
					
					// make pool question table
					echo '<table id="qt_table">';
					echo '<thead><tr><th scope="col" id="qtcol_prvw">Question Preview</th>
							  <th scope="col" id="qtcol_tags">Tags</th>
							  <th scope="col" id="qtcol_avgq">Average Quality</th>
							  <th scope="col" id="qtcol_avgd">Average Difficulty</th>
							  <th scope="col" id="qtcol_avga">Average Accuracy</th>
							  <th scope="col" id="qtcol_cnta">Total Responses</th>
							  <th scope="col" id="qtcol_cntu">Your Status</th>
							  <th scope="col" id="qtcol_link">View or Respond</th>
						  </tr></thead>';
					echo '<tbody>';
					
					while ($row=mysql_fetch_array($res_getQuestionList)) {
						
						echo '<tr>';
						
						// Preview Column (id=qtcol_prvw)
						echo '<td>'.$row["preview"].'...</td>';
						// Tags Column (id=qtcol_tags)
						echo '<td>'.stripslashes($row["tags"]).'</td>';
						// Quality Column (id=qtcol_avgq)
						echo '<td>'.$row["avg_quality"].'</td>';
						// Difficulty Column (id=qtcol_avgd)
						echo '<td>'.$row["avg_difficulty"].'</td>';
						// Accuracy Column (id=qtcol_avga)
						echo '<td>'.$row["accuracy"].'</td>';
						// Total Responses Column (id=qtcol_cnta)
						echo '<td>'.$row["responses"].'</td>';
						// Your Status Column (id=qtcol_cntu)
						if ($row["userresponses"] > 0) {
							echo '<td>Answered</td>';
							// View link
							echo '<td><form action="./question.php" method="post" target="_self">
								  <input type="hidden" name="qid" value="'.$row["questionId"].'">
								  <input type="submit" class="submitLink" value="View Question"></form>';
							echo '</td>';
						} else {
							echo '<td>No response yet</td>';
							// Respond link
							echo '<td><form action="./question.php" method="post" target="_self">
								  <input type="hidden" name="qid" value="'.$row["questionId"].'">
								  <input type="submit" class="submitLink" value="Respond"></form>';
							echo '</td>';
						}
						echo '</tr>';
					}
						  
					echo '</tbody></table>';
					
					
				}
				
			}
			
		} // end "if" regarding there being pools
	}
	
	
}


// ECHO THE BOTTOM BUN
echo substr($template, $splitpoint);
?>

