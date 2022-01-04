<?php
include("cas.php");
include("database_connect.php");
include("quizPhp.php");

$title = "QuizSplash: Edit Pool Settings";

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

$targetPool = htmlspecialchars($_GET["poolId"]);
$currentTime = $_SERVER['REQUEST_TIME'];
if ($targetPool == "") {
	printf("<script>location.href='classAdmin.php'</script>");
}

// CHECK TO SEE THAT THE POOL IS INSTRUCTED OR
// ASSISTED BY THE USER, AND THAT THE CLASS IS
// NOT EXPIRED
$sql_poolCheck = "SELECT a.poolId, a.poolName, a.poolDesc, a.fillDate, a.dueDate, a.openDate, a.closeDate, a.questionCnt, a.unlockCnt, a.responseCnt, a.requireExplanation, b.instructor, b.assistant, b.expireDate
				  FROM pools a
				  LEFT JOIN classes b ON a.classId = b.classId
				  WHERE a.poolId = ".$targetPool."
				    AND a.deleted IS NULL;";
$res_poolCheck = mysql_query($sql_poolCheck);
$canManage = 0; // ASSUME NOT
while ($row=mysql_fetch_array($res_poolCheck)) {
	if (($row["instructor"] == $username) || ($row["assistant"] == $username)) {
		if ($row['expireDate'] < $currentTime) {
			$canManage = 1; // we can manage this pool
			// define variables from database pull...
			$errorMessage = "";
			$poolId = $row['poolId'];
			$old_poolname = $row['poolName'];
			$old_pooldesc = $row['poolDesc'];
			$old_fillDate = $row['fillDate'];
			$old_dueDate = $row['dueDate'];
			$old_openDate = $row['openDate'];
			$old_closeDate = $row['closeDate'];
			$old_questionCnt = $row['questionCnt'];
			$old_unlockCnt = $row['unlockCnt'];
			$old_responseCnt = $row['responseCnt'];
			$old_reqExpl = $row['requireExplanation'];
			$sql_getDefaultTags = "SELECT classId, tagId, tag
								   FROM tags 
								   WHERE poolId = ".$targetPool."
								     AND questionId IS NULL;";
			$res_getDefaultTags = mysql_query($sql_getDefaultTags);
			$old_tags = $classId = "";
			$old_tag_array = array();
			while ($rowTags = mysql_fetch_array($res_getDefaultTags)) {
				$old_tag_array[$rowTags['tagId']] = $rowTags['tag'];
				$old_tags = $old_tags.$rowTags['tag']; // Add HTML newline after each for the textarea to look right
				$classId = $rowTags['classId'];
			}
			$old_tag_array=array_map('trim',$old_tag_array);
			
		}
	}
}

if ($canManage) {

	// VALIDATE, SANITIZE, AND INSERT THE POSTED FORM
	// check contents if form submitted
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		
		$inp_poolname = $_POST["inp_poolname"];
		$inp_poolname = trim($inp_poolname);
		$inp_pooldesc = $_POST["inp_pooldesc"];
		$inp_pooldesc = trim($inp_pooldesc);
		$inp_fdy = htmlspecialchars($_POST["inp_fdy"]);
		$inp_fdm = htmlspecialchars($_POST["inp_fdm"]);
		$inp_fdd = htmlspecialchars($_POST["inp_fdd"]);
		$inp_fdh = htmlspecialchars($_POST["inp_fdh"]);
		$inp_fdmin = htmlspecialchars($_POST["inp_fdmin"]);
		$inp_ddy = htmlspecialchars($_POST["inp_ddy"]);
		$inp_ddm = htmlspecialchars($_POST["inp_ddm"]);
		$inp_ddd = htmlspecialchars($_POST["inp_ddd"]);
		$inp_ddh = htmlspecialchars($_POST["inp_ddh"]);
		$inp_ddmin = htmlspecialchars($_POST["inp_ddmin"]);
		$inp_ody = htmlspecialchars($_POST["inp_ody"]);
		$inp_odm = htmlspecialchars($_POST["inp_odm"]);
		$inp_odd = htmlspecialchars($_POST["inp_odd"]);
		$inp_odh = htmlspecialchars($_POST["inp_odh"]);
		$inp_odmin = htmlspecialchars($_POST["inp_odmin"]);
		$inp_cdy = htmlspecialchars($_POST["inp_cdy"]);
		$inp_cdm = htmlspecialchars($_POST["inp_cdm"]);
		$inp_cdd = htmlspecialchars($_POST["inp_cdd"]);
		$inp_cdh = htmlspecialchars($_POST["inp_cdh"]);
		$inp_cdmin = htmlspecialchars($_POST["inp_cdmin"]);
		$inp_questioncnt = htmlspecialchars($_POST["inp_questioncnt"]);
		$inp_unlockquizcnt = htmlspecialchars($_POST["inp_unlockquizcnt"]);
		$inp_responsecnt = htmlspecialchars($_POST["inp_responsecnt"]);
		$inp_reqExpl = htmlspecialchars($_POST["inp_reqExpl"]);
		if ($inp_reqExpl != "1") {
			$inp_reqExpl = "0";
		}
		$inp_defaulttags = htmlspecialchars($_POST["inp_defaulttags"]);
		$tags = explode("\n", trim($inp_defaulttags));
		$tagAr = array_filter($tags, 'trim'); 
		$tagAr = array_map('trim', $tagAr);
		
		if (empty($inp_poolname)) {
	       	$errorMessage = $errorMessage."<li>Your pool must have a name.</li>";
	    }
		$inp_filldate = htmlspecialchars($_POST["inp_fdy"]).'-'.htmlspecialchars($_POST["inp_fdm"]).'-'.htmlspecialchars($_POST["inp_fdd"]).' '.htmlspecialchars($_POST["inp_fdh"]).':'.htmlspecialchars($_POST["inp_fdmin"]); 
		$inp_duedate = htmlspecialchars($_POST["inp_ddy"]).'-'.htmlspecialchars($_POST["inp_ddm"]).'-'.htmlspecialchars($_POST["inp_ddd"]).' '.htmlspecialchars($_POST["inp_ddh"]).':'.htmlspecialchars($_POST["inp_ddmin"]); 
		$inp_opendate = htmlspecialchars($_POST["inp_ody"]).'-'.htmlspecialchars($_POST["inp_odm"]).'-'.htmlspecialchars($_POST["inp_odd"]).' '.htmlspecialchars($_POST["inp_odh"]).':'.htmlspecialchars($_POST["inp_odmin"]);
		$inp_closedate = htmlspecialchars($_POST["inp_cdy"]).'-'.htmlspecialchars($_POST["inp_cdm"]).'-'.htmlspecialchars($_POST["inp_cdd"]).' '.htmlspecialchars($_POST["inp_cdh"]).':'.htmlspecialchars($_POST["inp_cdmin"]); 
		if (($inp_closedate != "-- :") && validateDate($inp_closedate) && ($inp_duedate == "-- :")) { 
			$inp_duedate = $inp_closedate;
		}
		if ($inp_filldate == "-- :") {
			$inp_filldate = "";
		} elseif (!validateDate($inp_filldate)) {
			$errorMessage = $errorMessage."<li>The datetime you entered for when students can begin submitting questions (".$inp_filldate.") is not valid. Please enter a valid date and time, or leave all fields blank if students can begin submitting questions immediately.</li>";
		}
		if ($inp_duedate == "-- :") {
			$inp_duedate = "";
		} elseif (!validateDate($inp_duedate)) {
			$errorMessage = $errorMessage."<li>The datetime you entered for when submitted questions are due (".$inp_duedate.") is not valid. Please enter a valid date and time, or leave all fields blank if there is no deadline for submission.</li>";
		} elseif ($inp_duedate <= date('Y-m-d H:i')) {
			$errorMessage = $errorMessage."<li>The datetime when submitted questions are due (you entered: ".$inp_duedate.") must be in the future.  Please enter a valid date and time (in Eastern time), or leave all fields blank if there is no deadline for submission.</li>";
		}
		if (($inp_filldate != "-- :") && ($inp_duedate != "-- :") && (validateDate($inp_filldate)) && validateDate($inp_duedate) && ($inp_filldate >= $inp_duedate)) {
			$errorMessage = $errorMessage."<li>The datetime when submitted questions are due (you entered: ".$inp_duedate.") must be after the time when students can submit their questions (you entered: ".$inp_filldate.").</li>";
		}
		if ($inp_opendate == "-- :") {
			$inp_opendate = "";
		} elseif (!validateDate($inp_opendate)) {
			$errorMessage = $errorMessage."<li>The datetime you entered for when students can begin taking quizzes (".$inp_opendate.") is not valid. Please enter a valid date and time, or leave all fields blank if students can begin taking quizzes immediately.</li>";
		}
		if ((validateDate($inp_opendate)) && validateDate($inp_filldate) && ($inp_opendate < $inp_filldate)) {
			$errorMessage = $errorMessage."<li>The datetime when students can begin taking quizzes (you entered: ".$inp_opendate.") must be after (or the same as) the time when students can submit their questions (you entered: ".$inp_filldate.").</li>";
		}
		if ($inp_closedate == "-- :") {
			$inp_closedate = "";
		} elseif (!validateDate($inp_closedate)) {
			$errorMessage = $errorMessage."<li>The datetime you entered for when students can no longer take quizzes (".$inp_closedate.") is not valid. Please enter a valid date and time, or leave all fields blank if quizzes should remain available indefinitely.</li>";
		} elseif ($inp_closedate <= date('Y-m-d H:i')) {
			$errorMessage = $errorMessage."<li>The datetime when this pool becomes unavailable for students to take quizzes (you entered: ".$inp_closedate.") must be in the future.  Please enter a valid date and time (in Eastern time), or leave all fields blank if quizzes should remain available indefinitely.</li>";
		}
		if (($inp_opendate != "-- :") && ($inp_closedate != "-- :") && (validateDate($inp_opendate)) && validateDate($inp_closedate) && ($inp_opendate >= $inp_closedate)) {
			$errorMessage = $errorMessage."<li>The datetime when this pool becomes unavailable for students to take quizzes (you entered: ".$inp_closedate.") must be after the time when students can begin taking quizzes (you entered: ".$inp_opendate.").</li>";
		}
		if (($inp_filldate != "-- :") && ($inp_closedate != "-- :") && (validateDate($inp_filldate)) && validateDate($inp_closedate) && ($inp_filldate >= $inp_closedate)) {
			$errorMessage = $errorMessage."<li>The datetime when this pool becomes unavailable for students to take quizzes (you entered: ".$inp_closedate.") must be after the time when students can begin submitting questions (you entered: ".$inp_filldate.").</li>";
		}
		if (($inp_duedate != "-- :") && ($inp_closedate != "-- :") && (validateDate($inp_duedate)) && validateDate($inp_closedate) && ($inp_duedate > $inp_closedate)) {
			$errorMessage = $errorMessage."<li>The datetime when this pool becomes unavailable for students to take quizzes (you entered: ".$inp_closedate.") must be after the time when submitted questions are due (you entered: ".$inp_duedate.").</li>";
		}
		if ((!is_numeric($inp_questioncnt)) || (strpos($inp_questioncnt,'.') !== false)) {
			$errorMessage = $errorMessage."<li>The number of questions each student is expected to submit to the pool must be a number without decimals (you entered: ".$inp_questioncnt.")</li>";
		}
		if ((!is_numeric($inp_unlockquizcnt)) || (strpos($inp_unlockquizcnt,'.') !== false)) {
			$errorMessage = $errorMessage."<li>The number of questions each student must submit before taking quizzes must be a number without decimals (you entered: ".$inp_unlockquizcnt.")</li>";
		}
		if ((!is_numeric($inp_responsecnt)) || (strpos($inp_responsecnt,'.') !== false)) {
			$errorMessage = $errorMessage."<li>The number of questions each student is expected to answer must be a number without decimals (you entered: ".$inp_responsecnt.")</li>";
		}
		if ((is_numeric($inp_questioncnt)) && (strpos($inp_questioncnt,'.') == false) && (is_numeric($inp_unlockquizcnt)) && (strpos($inp_unlockquizcnt,'.') == false) && ($inp_questioncnt < $inp_unlockquizcnt)) {
			$errorMessage = $errorMessage."<li>The number of questions students must submit to take a quiz (you entered: ".$inp_unlockquizcnt.") should be less than or equal to the number of questions you expect students to submit (you entered: ".$inp_questioncnt.").</li>";
		}
		
		if (empty($errorMessage)) {
		// no errors; submit, and redirect
			if ($inp_filldate == "") {
				$inp_filldate = date('Y-m-d H:i');
			}
			$inp_filldate = "'".$inp_filldate."'";
			if ($inp_opendate == "") {
				$inp_opendate = date('Y-m-d H:i');
			}
			$inp_opendate = "'".$inp_opendate."'";
			if ($inp_duedate == "") {
				$inp_duedate = "NULL";
			} else {
				$inp_duedate = "'".$inp_duedate."'";
			}
			if ($inp_closedate == "") {
				$inp_closedate = "NULL";
			} else {
				$inp_closedate = "'".$inp_closedate."'";
			}
			$sql_updatePool = "UPDATE pools
							   SET 
							    poolName = '".$inp_poolname."',
								poolDesc = '".$inp_pooldesc."',
								fillDate = ".$inp_filldate.",
								dueDate = ".$inp_duedate.",
								openDate = ".$inp_opendate.",
								closeDate = ".$inp_closedate.",
								questionCnt = ".$inp_questioncnt.",
								unlockCnt = ".$inp_unlockquizcnt.",
								responseCnt = ".$inp_responsecnt.", 
								requireExplanation = ".$inp_reqExpl."
							   WHERE 
							    poolId = ".$targetPool.";";
			// echo $sql_updatePool;
			$res_updatePool = mysql_query($sql_updatePool);
			if (!empty($inp_defaulttags)) {
				$tagsToAdd = array_diff($tagAr,$old_tag_array);
				$tagsToDrop = array_diff($old_tag_array,$tagAr);
				if (!empty($tagsToAdd)) {
					$sql_insertTags = "INSERT INTO tags
									   (classId,
										poolId,
										questionId,
										tag,
										username)
									   VALUES ";
					foreach ($tagsToAdd as $value) {
						$sql_insertTags = $sql_insertTags."
										  (".$classId.",
										   ".$targetPool.", 
										   NULL,
										   '".$value."', 
										   '".$username."'),";
					}
					$sql_insertTags = rtrim($sql_insertTags, ",");
					$sql_insertTags = $sql_insertTags.";";	
					// echo $sql_insertTags
					$res_tagInsert = mysql_query($sql_insertTags);	
				}
				if (!empty($tagsToDrop)) {
					$sql_deleteTags = "";
					foreach ($tagsToDrop as $tagkey => $tagvalue) {
						$sql_deleteTags = $sql_deleteTags."DELETE FROM tags WHERE tagId = ".$tagkey."; ";
					}
					// echo $sql_deleteTags;
					$res_tagDelete = mysql_query($sql_deleteTags);
				}
			}
			printf("<script>location.href='editPoolSuccess.php'</script>");
		}
		
	} else {
		// This is if the method isn't "post"... so we'd
		// need to define the fields from the existing 
		// database values.
		$inp_poolname = $old_poolname;
		$inp_pooldesc = $old_pooldesc;
		$inp_questioncnt = $old_questionCnt;
		$inp_unlockquizcnt = $old_unlockCnt;
		$inp_responsecnt = $old_responseCnt;
		$inp_defaulttags = $old_tags;
		$inp_reqExpl = $old_reqExpl;
		$inp_fdy = date("Y",strtotime($old_fillDate));
		$inp_fdm = date("m",strtotime($old_fillDate));
		$inp_fdd = date("d",strtotime($old_fillDate));
		$inp_fdh = date("H",strtotime($old_fillDate));
		$inp_fdmin = date("i",strtotime($old_fillDate));
		if (empty($old_dueDate)) {
			$inp_ddy = $inp_ddm = $inp_ddd = $inp_ddh = $inp_ddmin = "";
		} else {
			$inp_ddy = date("Y",strtotime($old_dueDate));
			$inp_ddm = date("m",strtotime($old_dueDate));
			$inp_ddd = date("d",strtotime($old_dueDate));
			$inp_ddh = date("H",strtotime($old_dueDate));
			$inp_ddmin = date("i",strtotime($old_dueDate));
		}
		$inp_ody = date("Y",strtotime($old_openDate));
		$inp_odm = date("m",strtotime($old_openDate));
		$inp_odd = date("d",strtotime($old_openDate));
		$inp_odh = date("H",strtotime($old_openDate));
		$inp_odmin = date("i",strtotime($old_openDate));
		if (empty($old_closeDate)) {
			$inp_cdy = $inp_cdm = $inp_cdd = $inp_cdh = $inp_cdmin = "";
		} else {
			$inp_cdy = date("Y",strtotime($old_closeDate));
			$inp_cdm = date("m",strtotime($old_closeDate));
			$inp_cdd = date("d",strtotime($old_closeDate));
			$inp_cdh = date("H",strtotime($old_closeDate));
			$inp_cdmin = date("i",strtotime($old_closeDate));
		}
	}
	
	// SPIT OUT THE FORM
	echo '<h2>Edit settings for '.$inp_poolname.' (poolId#'.$poolId.')</h2>
	<p>A quiz pool is a repository where students can submit questions, and take practice quizzes made from their classmates\' questions. By setting a deadline for students to submit questions to this pool and a minimum number of contributions, you can treat this pool as an assignment.</p>';
	if(!empty($errorMessage)){
		echo "<div id=\"errMessage\">ERROR:<ul>".$errorMessage."</ul></div>";
	}
	echo '<p>&nbsp;</p>
		  <form method="POST" action="'.htmlspecialchars($_SERVER["PHP_SELF"]).'?poolId='.$poolId.'">
		  <table width="100%" border="0">
			<tr>
			  <td width="250" style="padding-bottom:20px;vertical-align:top;">Pool Name:</td>
			  <td style="padding-bottom:20px;"><input type="text" name="inp_poolname" maxlength="75" size="40" value="'.stripslashes($inp_poolname).'"></td>
			</tr>
			<tr>
			  <td width="250" style="padding-bottom:20px;vertical-align:top;">Pool Description:</td>
			  <td style="padding-bottom:20px;vertical-align:top;"><textarea name="inp_pooldesc" cols="40" rows="3" maxlength="255">'.stripslashes($inp_pooldesc).'</textarea></td>
			</tr>
			<tr>
			  <td width="250" style="padding-bottom:25px;">When can students begin submitting questions to this pool?</td>
			  <td style="padding-bottom:20px;vertical-align:top;">
			  	<select name="inp_fdy">';
	makeOptions("year",$inp_fdy);
	echo '</select> - 
				<select name="inp_fdm">';
	makeOptions("month",$inp_fdm);
	echo '</select> - 
				<select name="inp_fdd">';
	makeOptions("day",$inp_fdd);
	echo '</select>&nbsp;&nbsp;&nbsp;
				<select name="inp_fdh">';
	makeOptions("hour",$inp_fdh);
	echo '</select> : 
				<select name="inp_fdmin">';
	makeOptions("min",$inp_fdmin);
	echo '</select>
				<span class="datetimeformat">(Eastern Time)</span>
				<br />
				Leave all fields blank, or keep the date in the past, to allow immediate submission.
			  </td>
			</tr>
			<tr>
			  <td width="250" style="padding-bottom:25px;vertical-align:top;">When is the deadline for submitting questions to this pool?</td>
			  <td style="padding-bottom:20px;vertical-align:top;">
			  	<select name="inp_ddy">';
	makeOptions("year",$inp_ddy);
	echo '</select> - 
				<select name="inp_ddm">';
	makeOptions("month",$inp_ddm);
	echo '</select> - 
				<select name="inp_ddd">';
	makeOptions("day",$inp_ddd);
	echo '</select>&nbsp;&nbsp;&nbsp;
				<select name="inp_ddh">';
	makeOptions("hour",$inp_ddh);
	echo '</select> : 
				<select name="inp_ddmin">';
	makeOptions("min",$inp_ddmin);
	echo '</select>
				<span class="datetimeformat">(Eastern Time)</span>
				<br />
				Leave all fields blank if there is no deadline for submission.
			  </td>
			</tr>
			<tr>
			  <td width="250" style="padding-bottom:25px;vertical-align:top;">When can students begin taking quizzes from this pool?</td>
			  <td style="padding-bottom:20px;vertical-align:top;">
			  	<select name="inp_ody">';
	makeOptions("year",$inp_ody);
	echo '</select> - 
				<select name="inp_odm">';
	makeOptions("month",$inp_odm);
	echo '</select> - 
				<select name="inp_odd">';
	makeOptions("day",$inp_odd);
	echo '</select>&nbsp;&nbsp;&nbsp;
				<select name="inp_odh">';
	makeOptions("hour",$inp_odh);
	echo '</select> : 
				<select name="inp_odmin">';
	makeOptions("min",$inp_odmin);
	echo '</select>
				<span class="datetimeformat">(Eastern Time)</span>
				<br />
				Leave all fields blank, or keep the date in the past, to allow students to take quizzes immediately. If you do provide a date here, it should be after (or the same as) the time when students can begin submitting questions.
			  </td>
			</tr>
			<tr>
			  <td width="250" style="padding-bottom:25px;vertical-align:top;">When will this pool become unavailable for students to take quizzes?</td>
			  <td style="padding-bottom:20px;vertical-align:top;">
			  	<select name="inp_cdy">';
	makeOptions("year",$inp_cdy);
	echo '</select> - 
				<select name="inp_cdm">';
	makeOptions("month",$inp_cdm);
	echo '</select> - 
				<select name="inp_cdd">';
	makeOptions("day",$inp_cdd);
	echo '</select>&nbsp;&nbsp;&nbsp;
				<select name="inp_cdh">';
	makeOptions("hour",$inp_cdh);
	echo '</select> : 
				<select name="inp_cdmin">';
	makeOptions("min",$inp_cdmin);
	echo '</select>
				<span class="datetimeformat">(Eastern Time)</span>
				<br />
				Leave all fields blank if this pool should be available for quizzing indefinitely. If you do provide a date here, it should be after all other dates.  Also, this date (if provided) will become the due date for question submission if you left that field blank above.
			  </td>
			</tr>
			<tr>
			  <td width="200" style="padding-bottom:20px;vertical-align:top;">How many questions, at minimum, do you expect each student to contribute to this pool?</td>
			  <td style="padding-bottom:20px;vertical-align:top;"><input type="text" name="inp_questioncnt" maxlength="2" size="2" value="'.$inp_questioncnt.'"><br />
				  Set this value to "0" if you have no minimum contribution requirement for this pool.
			  </td>
			</tr>
			<tr>
			  <td width="200" style="padding-bottom:20px;vertical-align:top;">How many questions must each student submit before they can take a quiz from this pool?</td>
			  <td style="padding-bottom:20px;vertical-align:top;"><input type="text" name="inp_unlockquizcnt" maxlength="2" size="2" value="'.$inp_unlockquizcnt.'"><br />
			  Set this value to "0" to allow quiz access without question submission.
			  </td>
			</tr>
			<tr>
			  <td width="200" style="padding-bottom:20px;vertical-align:top;">How many questions, at minimum, do you expect each student to answer in this pool?</td>
			  <td style="padding-bottom:20px;vertical-align:top;"><input type="text" name="inp_responsecnt" maxlength="2" size="2" value="'.$inp_responsecnt.'"><br />
			  Set this value to "0" if you have no minimum response requirement for this pool.
			  </td>
			</tr>
			<tr>
			  <td width="200" style="padding-bottom:20px;vertical-align:top;">Require submitted questions to include explanations?</td>
			  <td style="padding-bottom:20px;vertical-align:top;"><input type="checkbox" name="inp_reqExpl" value="1"';
			  if ($inp_reqExpl) {
				  echo ' checked';
			  }
			  echo '>
			  Require students to include explanations with each submitted question.
			  </td>
			</tr>
			<tr>
			  <td width="200" style="padding-bottom:20px;vertical-align:top;">Default question tags</td>
			  <td style="padding-bottom:20px;vertical-align:top;">In the space below, type some keywords or key phrases that students can use to label the questions that they submit to this pool.  Each tag should be on a separate line.  Students will be able to add their own tags to questions, so it\'s okay if you don\'t include all possible topics or just leave this blank.  If you remove a tag below, it will no longer appear as a default option, unless it was already applied to a submitted question (or questions).<br /><textarea name="inp_defaulttags" cols="30" rows="8" maxlength="255">'.stripslashes($inp_defaulttags).'</textarea>
			  </td>
			</tr>
			  <tr>
			  <td width="200" style="padding-bottom:20px;vertical-align:top;"><input type="submit" value="Update Pool"></td>
			  <td style="padding-bottom:20px;vertical-align:top;">&nbsp;
			  </td>
			</tr>
		  </table>
		  </form>
					
		  ';
	
}
else{
	echo "<h2>You can't edit this pool.</h2>
		  <p>Either you are not the administrator for the class that this pool is in, or this class is expired.</p>";
}

// ECHO THE BOTTOM BUN
echo substr($template, $splitpoint);
?>

