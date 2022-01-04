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

<h2>Choose a Pool to Submit Questions</h2>
<p>Below you'll find the different pools where you can submit questions.  Some pools may have deadlines for you to submit questions, and minimum submission requirements.  If so, these are listed alongside the pool heading. Click the "Write a Question" link to start writing a new question for that pool.</p>
<div id="tooltip_container">&nbsp;</div>

<?php 

if (empty($inst_classAr) && empty($stud_classAr)) {
	
	echo "<h3>There are no question pools available to ".$username."</h3>";
	echo "<p>If you think you should have access to a class or a question pool here, please contact your instructor.</p>";
	
} else {
	
	// For testing only
	// $stud_classAr = $inst_classAr;
	
	// Make all classes array
	// $classAr = array_merge($inst_classAr, $stud_classAr); BADBADBAD
	$classAr = $inst_classAr + $stud_classAr;
	
	// Create drop menu, if there's more than one class
	if (count($classAr) > 1) {
		echo '<p>Select class: 
				<select id="classSelect">';
					foreach ($classAr as $cid => $cname) {
						if (isset($inst_classAr[$cid])) {
							// It's a class that the user instructs
							echo '<option value="block_'.$cid.'">'.$cname.' (Instructor)</option>';
						} else {
							echo '<option value="block_'.$cid.'">'.$cname.'</option>';
						}
					}
		echo '	</select>
			  </p>';
	}
	reset($classAr);
	$firstCID = key($classAr);
	
	// Create div containers for each class
	foreach ($classAr as $cid => $cname) {
		
		echo '<div id="block_'.$cid.'" class="classGroup">';
		// Collect Question Pools for the class
		$sql_getPools = "SELECT poolId, poolName, poolDesc, fillDate, dueDate, openDate, closeDate, questionCnt, unlockCnt, requireExplanation 
						 FROM pools WHERE classId = ".$cid." AND deleted IS NULL ORDER BY closeDate ASC;";
		$res_getPools = mysql_query($sql_getPools);
		
		if (isset($inst_classAr[$cid])) {
			echo '<h3>Question pools in '.$cname.' (Instructor)</h3>';
			echo '<div class="poolTableContainer">';
			echo '<table class="poolTable">';
			echo '<thead><tr><th scope="col" id="ptcol_name">Pool Name</th><th scope="col" id="ptcol_status">Status for<br />Submitting Questions</th><th scope="col" id="ptcol_subs">Questions<br />Submitted</th><th scope="col" id="ptcol_reqs">Requirements</th><th scope="col" id="ptcol_write">Write a<br />Question</th><th scope="col" id="ptcol_edit">Edit<br />Pool</th></tr></thead>';
			while ($row=mysql_fetch_array($res_getPools)) {
				echo '<tbody><tr>';
				echo '<td><a class="tooltip" href="#" tooltip-data="'.stripslashes($row["poolDesc"]).'">'.$row["poolName"].'</a></td>';
				
				// Status Column ( closed ; filling ; waiting )
				if (($row["dueDate"] <= $currentTime) && (!empty($row["dueDate"]))) {
					echo '<td>Closed to question submissions (deadline was: '.date("F j, Y, g:i a",strtotime($row['dueDate'])).')</td>';
				} elseif (($row["fillDate"] <= $currentTime) || (empty($row["fillDate"]))) {
					echo '<td>Open to question submissions ';
					if (empty($row["dueDate"])) {
						echo '</td>';
					} else {
						echo '(due date: '.date("F j, Y, g:i a",strtotime($row['dueDate'])).')</td>';
					}
				} elseif ($row["fillDate"] > $currentTime) {
					echo '<td>Not yet open to new question submission (opens on '.date("F j, Y, g:i a",strtotime($row['fillDate'])).')</td>';
				} 
				
				// Questions Submitted Column
				$sql_getSubmissionCnt = "SELECT COUNT(questionId) AS submissionCnt FROM questions WHERE poolId = ".$row["poolId"]." AND username = '".$username."';";
				$res_getSubmissionCnt = mysql_query($sql_getSubmissionCnt);
				$row_getSubmissionCnt = mysql_fetch_array($res_getSubmissionCnt);
				$submissionCnt = $row_getSubmissionCnt["submissionCnt"];
				echo '<td>You\'ve submitted '.$submissionCnt.' questions to this pool.</td>';
				
				// Requirements Column
				echo '<td>';
				if ($row["questionCnt"] > 0) {
					echo ''.$row["questionCnt"].' questions expected. ';
				} else { echo 'No minimum question requirement. '; }
				if ($row["unlockCnt"] > 0) {
					echo ''.$row["unlockCnt"].' questions required to unlock quizzes.';
				} else { echo 'No submissions needed to unlock quizzes.'; }
				echo '</td>';
				
				// Submit Column
				echo '<td><a href="./writeAquestion.php?poolId='.$row["poolId"].'">Write a question</a></td>';
				
				// Edit Pool Column
				echo '<td><a href="./editPool.php?poolId='.$row["poolId"].'">Edit Pool</a></td>';
				
				echo '</tr></tbody>';
			}
			echo '</table>';
			echo '</div>';
			echo '<p>And since you\'re an instructor, you could also <a href="./createPool.php?classId='.$cid.'">create a new pool</a> for this class.</p>';
			
		} else {
			echo '<h3>Question pools in '.$cname.'</h3>';
			echo '<div class="poolTableContainer">';
			echo '<table class="poolTable">';
			echo '<thead><tr><th scope="col" id="ptcol_name">Pool Name</th><th scope="col" id="ptcol_status">Status for<br />Submitting Questions</th><th scope="col" id="ptcol_subs">Questions<br />Submitted</th><th scope="col" id="ptcol_reqs">Requirements</th><th scope="col" id="ptcol_write">Write a<br />Question</th></tr></thead>';
			while ($row=mysql_fetch_array($res_getPools)) {
				echo '<tbody><tr>';
				echo '<td><a class="tooltip" href="#" tooltip-data="'.stripslashes($row["poolDesc"]).'">'.$row["poolName"].'</a></td>';
				
				// Status Column ( closed ; filling ; waiting )
				$submittable = 0;
				if (($row["dueDate"] <= $currentTime) && (!empty($row["dueDate"]))) {
					echo '<td>Closed to question submissions (deadline was: '.date("F j, Y, g:i a",strtotime($row['dueDate'])).')</td>';
				} elseif (($row["fillDate"] <= $currentTime) || (empty($row["fillDate"]))) {
					echo '<td>Open to question submissions ';
					if (empty($row["dueDate"])) {
						echo '</td>';
					} else {
						echo '(due date: '.date("F j, Y, g:i a",strtotime($row['dueDate'])).')</td>';
					}
					$submittable = 1;
				} elseif ($row["fillDate"] > $currentTime) {
					echo '<td>Not yet open to new question submission (opens on '.date("F j, Y, g:i a",strtotime($row['fillDate'])).')</td>';
				} 
				
				// Questions Submitted Column
				$sql_getSubmissionCnt = "SELECT COUNT(questionId) AS submissionCnt FROM questions WHERE poolId = ".$row["poolId"]." AND username = '".$username."';";
				$res_getSubmissionCnt = mysql_query($sql_getSubmissionCnt);
				$row_getSubmissionCnt = mysql_fetch_array($res_getSubmissionCnt);
				$submissionCnt = $row_getSubmissionCnt["submissionCnt"];
				echo '<td>You\'ve submitted '.$submissionCnt.' questions to this pool.</td>';
				
				// Requirements Column
				echo '<td>';
				if ($row["questionCnt"] > 0) {
					echo ''.$row["questionCnt"].' questions expected. ';
				} else { echo 'No minimum question requirement. '; }
				if ($row["unlockCnt"] > 0) {
					echo ''.$row["unlockCnt"].' questions required to unlock quizzes.';
				} else { echo 'No submissions needed to unlock quizzes.'; }
				echo '</td>';
				
				// Submit Column
				if ($submittable) {
					echo '<td><a href="./writeAquestion.php?poolId='.$row["poolId"].'">Write a question</a></td>';
				} else {
					echo '<td>Closed to submissions</td>';
				}
				
				echo '</tr></tbody>';
			}
			echo '</table>';
			echo '</div>';
		}
		echo '</div>';
	}
	
	// Do the jQuery thing, to allow for more than one class	
	echo '<script type="text/javascript">
	var parentOffset;';
	echo "
	$(document).ready(function () {
	$('.classGroup').hide();";
	echo "$('#block_".$firstCID."').show()";
	echo "
	$('#classSelect').change(function () {
		$('.classGroup').hide();
		$('#'+$(this).val()).show();
	});
	
    $('.tooltip').mouseover(function(e){
        var data=$(this).attr('tooltip-data');
        $('#tooltip_container').html(data).fadeIn(200);
    }).mousemove(function(e){
		parentOffset = $(this).parent().offset();
        $('#tooltip_container').css('left',(e.pageX - parentOffset.left + 10)+'px');
        $('#tooltip_container').css('top',(e.pageY + 10)+'px');        
    }).mouseout(function(e){
        $('#tooltip_container').css('display','none').html('');
    });
	
	});
	";
	
	echo '</script>';
	
}


// ECHO THE BOTTOM BUN
echo substr($template, $splitpoint);
?>

