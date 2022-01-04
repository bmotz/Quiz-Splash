<?php
include("cas.php");
include("database_connect.php");
include("quizPhp.php");

$title = "QuizSplash: Add or Remove Students";

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

$targetClass = htmlspecialchars($_GET["classId"]);
$currentTime = $_SERVER['REQUEST_TIME'];
$errorMessage = "";

// CHECK TO SEE THAT THE CLASS IS INSTRUCTED OR
// ASSISTED BY THE USER, AND THAT THE CLASS IS
// NOT EXPIRED
$canManage = 0; // ASSUME NOT
while ($row=mysql_fetch_array($res_adminCheck)) {
	if ($row["classId"] == $targetClass) {
		if ($row['expireDate'] < $currentTime) {
			$canManage = 1;
			$className = $row['className'];
		}
	}
	if ($targetClass == "") {
		printf("<script>location.href='classAdmin.php'</script>");
	}
}

if ($canManage) {
	
	// RETRIEVE ROSTER
	$sql_getRoster = "SELECT username FROM students WHERE classId = ".$targetClass.";";
	$res_getRoster = mysql_query($sql_getRoster);
	$cnt_getRoster = mysql_num_rows($res_getRoster);
	
	// VALIDATE, SANITIZE, AND INSERT THE POSTED FORM
	// check contents if form submitted
	if ($_SERVER["REQUEST_METHOD"] == "POST") {

		$inp_addStudents = htmlspecialchars($_POST["inp_addStudents"]);
		$inp_dropStudent = htmlspecialchars($_POST["inp_dropStudent"]);
		if (!empty($inp_dropStudent)) {
			$inp_dropStudent = trim($inp_dropStudent);
			if (ctype_alnum($inp_dropStudent)) {
				$sql_dropStudent = "DELETE FROM students WHERE username = '".$inp_dropStudent."' AND classId = ".$targetClass.";";
				$res_dropStudent = mysql_query($sql_dropStudent);
				echo '<META HTTP-EQUIV="Refresh" Content="0; URL=./manageRoster.php?classId='.$targetClass.'">';
			}
		} elseif (!empty($inp_addStudents)) {
			$addStudentArr = explode("\n", trim($inp_addStudents));
			$addStudentArr = array_map('trim', $addStudentArr);
			$addStudentArr = array_unique($addStudentArr);
			$alnum_problem = 0;
			$alnum_example = "";
			foreach ($addStudentArr as $s) {
				if (!ctype_alnum($s)) {
					$alnum_problem = 1;
					$alnum_example = $alnum_example.'"'.$s.'", ';
				}
			} 
			if ($alnum_problem == 1) {
				$alnum_example = rtrim($alnum_example, ", ");
				$errorMessage = $errorMessage.'<li>There was punctuation, a space, or some other unallowable symbol in the list of student usernames ('.$alnum_example.'). Please make sure that the usernames are all alphanumeric, and each on a separate line.</li>';
			}
			if (empty($errorMessage)) {
				$sql_insertStudents = "INSERT INTO students
									   (classId,
										username)
									   VALUES ";
				foreach ($addStudentArr as $s) {
					$stud = strtolower($s);
					$sql_insertStudents = $sql_insertStudents."
											  (".$targetClass.",
											  '".$stud."'),";
				}
				$sql_insertStudents = rtrim($sql_insertStudents, ",");
				$sql_insertStudents = $sql_insertStudents.";";	
				//echo $sql_insertStudents;
				$res_insertStudents = mysql_query($sql_insertStudents);	
				$inp_addStudents = "";
				echo '<META HTTP-EQUIV="Refresh" Content="0; URL=./manageRoster.php?classId='.$targetClass.'">';
			}
		}
	} else {
		$inp_addStudents = "";
	}
	
	// SPIT OUT THE FORM
	echo '<h2>Manage student roster for '.$className.'</h2>';
	echo '<form name="rosterForm" method="POST" action="'.htmlspecialchars($_SERVER["PHP_SELF"]).'?classId='.$targetClass.'">';
	echo '<h3>Add students</h3>';
	if(!empty($errorMessage)){
		echo "<div id=\"errMessage\">ERROR:<ul>".$errorMessage."</ul></div>";
	}
	echo '<p>Put student usernames in the box below to add them to this class. Students who are already in the class will not be affected. If you attempt to re-add a student who is already a participant in this class, the system will ignore the duplicate insert request for that student. You should not add yourself as a student (you\'re an instructor or an assistant), because you already have access to all functionality for your class.</p>';
	echo '<textarea id="addStudents" cols="35" name="inp_addStudents" rows="4">'.stripslashes($inp_addStudents).'</textarea><br />
		  <span id="underTextArray">Note: Enter each student username on a separate line (no commas or punctuation)</span><br />
	 	  <input type="submit" value="Add students">';
	echo '<h3>Current roster</h3>';
	if ($cnt_getRoster < 1) {
		echo "Currently there are no students in this class.  You can add students by entering their usernames above.";
	} else {
		echo 'Clicking a link below will immediately remove the student from your class roster. Please be careful. <input type="hidden" id="inp_dropStudent" name="inp_dropStudent" value=""/><ul>';
		while ($row=mysql_fetch_array($res_getRoster)) {
			echo '<li>'.$row["username"].' - <a class="remove" onclick="document.getElementById(\'inp_dropStudent\').value=\''.$row["username"].'\';document.rosterForm.submit();" href="#">Remove</a></li>';
		}
		echo "</ul>";
	}
	echo '</form>';
	
	
	
} else{
	echo "<h2>You can't manage this class's roster.</h2>
		  <p>Either you are not the administrator for this class, or this class is expired.</p>";
}

// ECHO THE BOTTOM BUN
echo substr($template, $splitpoint);
?>

