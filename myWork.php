<?php
include("cas.php");
include("database_connect.php");

$title = "QuizSplash: My Work";

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

<h2>My Work</h2>
<h4>Here's where you can review the questions and responses you've submitted for your classes.</h4>

<?php 

if (empty($inst_classAr) && empty($stud_classAr)) {
	
	echo "<h3>There are no classes available to ".$username."</h3>";
	echo "<p>If you think you should have access to something here, please contact your instructor.</p>";
	
} else {

	// Page logic:	
	// STEP 1: ClassId
	// When the $classAr is more than 1,
	//   -- Check $_GET for a classId
	//   -- Sanitize the classId
	//   -- Present a select menu for class
	// If the $classAr is 1, OR $_GET yields a classId
	//   -- Sanitize the classId
	// STEP 2: Dump the questions and responses
	// List all the questions written by the username in the class
	// List all the responses written by the username in the class

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
			printf("<script>location.href='myWork.php'</script>");
		} if (!isset($_GET["classId"])) {
			$targetClass = "";
		}
		echo '<p><form action="./myWork.php" method="get" target="_self"> 
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
	// List the questions in that pool
	
	if ($targetClass!=="") {

		echo '<h3>Questions submitted to '.$classAr[$targetClass].'</h3>';
		
		// Questions Submitted Column
		$sql_getQuestionList = "SELECT DISTINCT a.questionId, c.poolName, a.createDate,
								SUBSTRING(a.questionText, 1, 30) AS preview,
								COUNT(DISTINCT b.responseId) AS responses,
								ROUND(AVG(b.difficulty), 2) AS avg_difficulty,
								ROUND(AVG(b.quality), 2) AS avg_quality,
								ROUND(AVG(b.correct),2) AS accuracy
								FROM questions a
								LEFT JOIN responses b ON a.questionId = b.questionId
								LEFT JOIN pools c ON a.poolId = c.poolId 
								WHERE a.classId = ".$targetClass."
								  AND a.username = '".$username."'
								  AND c.deleted IS NULL
								GROUP BY a.questionId, c.poolName;";
		$res_getQuestionList = mysql_query($sql_getQuestionList);
		$cnt_getQuestionList = mysql_num_rows($res_getQuestionList);
		
		//echo $cnt_getQuestionList;
		
		if ($cnt_getQuestionList < 1) {
			// what should be done if there're no questions
			echo 'You haven\'t submitted any questions to this class yet. <a href="./writeAquestion.php">Click here</a> to
				  write a question for this class.';
		} else {
			
			// datatables initialization
			echo "
			<script>
			
			$(document).ready(function() {
				var qTable = $('#qt_table').dataTable();
				var rTable = $('#rt_table').dataTable();
			});
				
			</script>
			";
			
			// make pool question table
			echo '<table id="qt_table">';
			echo '<thead><tr>
					  <th scope="col" id="qtcol_pool">Pool Name</th>
					  <th scope="col" id="qtcol_qid">Question ID</th>
					  <th scope="col" id="qtcol_date">Date/Time Submitted</th>
					  <th scope="col" id="qtcol_prvw">Question Preview</th>
					  <th scope="col" id="qtcol_avgq">Avg Quality</th>
					  <th scope="col" id="qtcol_avgd">Avg Difficulty</th>
					  <th scope="col" id="qtcol_avga">Avg Accuracy</th>
					  <th scope="col" id="qtcol_cnta">Responses</th>
				  </tr></thead>';
			echo '<tbody>';
			
			while ($row=mysql_fetch_array($res_getQuestionList)) {
				
				echo '<tr>';
				
				// Pool Column (id=qtcol_pool)
				echo '<td>'.$row["poolName"].'</td>';
				// QID Column (id=qtcol_qid)
				echo '<td>'.$row["questionId"].'</td>';
				// Pool Column (id=qtcol_date)
				echo '<td>'.$row["createDate"].'</td>';
				// Preview Column (id=qtcol_prvw)
				echo '<td>'.$row["preview"].'...</td>';
				// Quality Column (id=qtcol_avgq)
				echo '<td>'.$row["avg_quality"].'</td>';
				// Difficulty Column (id=qtcol_avgd)
				echo '<td>'.$row["avg_difficulty"].'</td>';
				// Accuracy Column (id=qtcol_avga)
				echo '<td>'.$row["accuracy"].'</td>';
				// Total Responses Column (id=qtcol_cnta)
				echo '<td>'.$row["responses"].'</td>';
				echo '</tr>';
			}
				  
			echo '</tbody></table>';
			
		} // end "if" regarding there being questions
		
		echo '<h3>Responses submitted to '.$classAr[$targetClass].'</h3>';
		
		// Resuponses Submitted Column
		$sql_getResponseList = "SELECT DISTINCT a.responseId, 
								c.poolName, 
								a.responseDate,
								SUBSTRING(b.questionText, 1, 30) AS preview,
								a.response,
								a.correct,
								a.difficulty,
								a.quality
								FROM responses a
								LEFT JOIN questions b ON a.questionId = b.questionId
								LEFT JOIN pools c ON a.poolId = c.poolId 
								WHERE c.classId = ".$targetClass."
								  AND a.username = '".$username."'
								  AND c.deleted IS NULL";
		$res_getResponseList = mysql_query($sql_getResponseList);
		$cnt_getResponseList = mysql_num_rows($res_getResponseList);
		
		if ($cnt_getResponseList < 1) {
			// what should be done if there're no responses
			echo 'You haven\'t submitted any responses to this class yet. <a href="./listQuestions.php">Click here</a> to
				  respond to questions in this class.';
		} else {
			
			// make pool question table
			echo '<table id="rt_table">';
			echo '<thead><tr>
					  <th scope="col" id="rtcol_pool">Pool Name</th>
					  <th scope="col" id="rtcol_qid">Response ID</th>
					  <th scope="col" id="rtcol_date">Date/Time Submitted</th>
					  <th scope="col" id="rtcol_prvw">Question Preview</th>
					  <th scope="col" id="rtcol_resp">Response</th>
					  <th scope="col" id="rtcol_corr">Correct</th>
					  <th scope="col" id="rtcol_diff">Difficulty Rating</th>
					  <th scope="col" id="rtcol_qual">Quality Rating</th>
				  </tr></thead>';
			echo '<tbody>';
			
			while ($row=mysql_fetch_array($res_getResponseList)) {
				
				echo '<tr>';
				
				// Pool Column (id=qtcol_pool)
				echo '<td>'.$row["poolName"].'</td>';
				// QID Column (id=qtcol_qid)
				echo '<td>'.$row["responseId"].'</td>';
				// Pool Column (id=qtcol_date)
				echo '<td>'.$row["responseDate"].'</td>';
				// Preview Column (id=qtcol_prvw)
				echo '<td>'.$row["preview"].'...</td>';
				// Response Column 
				echo '<td>'.$row["response"].'</td>';
				// Correct Column 
				echo '<td>'.$row["correct"].'</td>';
				// Difficulty Column (id=qtcol_avga)
				echo '<td>'.$row["difficulty"].'</td>';
				// Total Responses Column (id=qtcol_cnta)
				echo '<td>'.$row["quality"].'</td>';
				echo '</tr>';
			}
				  
			echo '</tbody></table>';
			
		}
	}
	
	
}


// ECHO THE BOTTOM BUN
echo substr($template, $splitpoint);
?>

