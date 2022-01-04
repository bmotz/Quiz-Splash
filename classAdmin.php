<?php
include("cas.php");
include("database_connect.php");

$title = "QuizSplash: Class Administration";

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

// FIND THE SPLITPOINT
$splitpoint = strpos($template,'<!--XXXSPLITPOINTXXX-->');
// ECHO THE TOP BUN
echo substr($template, 0, $splitpoint);

// ECHO THE HEADER
echo "<h2>Class Administration</h2>";

// WHAT'S THE DATETIME?
$currentTime = $_SERVER['REQUEST_TIME'];
$expiredFlag = 0;

// LOOP THROUGH CLASSES
while ($row=mysql_fetch_array($res_adminCheck)) {
	if ($row['expireDate'] < $currentTime) {
		echo "<p>".$row['className']." (ID#".$row['classId'].")</p>";
		echo '<ul>
				<li><a href="./createPool.php?classId='.$row['classId'].'">Create a New Quiz Pool</a></li>
				<li>Manage an Existing Quiz Pool';
		$sql_getPools = "SELECT * 
						 FROM pools
						 WHERE classId = ".$row['classId']."
						   AND deleted IS NULL
						 ORDER BY openDate ASC;";
		$res_getPools = mysql_query($sql_getPools);
		$cnt_getPools = mysql_num_rows($res_getPools);
		if ($cnt_getPools > 0) {
			echo '<ul>';
			while ($poolrow=mysql_fetch_array($res_getPools)) {
				echo '<li>'.$poolrow['poolName'].': <a href="./editPool.php?poolId='.$poolrow['poolId'].'">Pool Settings</a><br />
				&nbsp;&nbsp;&nbsp;Download: <a href="./export.php?poolId='.$poolrow['poolId'].'&repType=student">Student Summary</a></li>';
			}
			echo '</ul>';
		}		
		echo '</li><li><a href="./manageRoster.php?classId='.$row['classId'].'">Add or Remove Students</a></li>
				<li>Edit Class Settings</li>
			  </ul>';
	}
	if ($row['expireDate'] >= $currentTime) {
		if ($expiredFlag = 0) {
			echo "<h4>Expired Classes</h4>";
			$expiredFlag = 1;
		}
		echo "<p>".$row['className']." (ID#".$row['classId'].")</p>";
	}
}



// ECHO THE BOTTOM BUN
echo substr($template, $splitpoint);
?>

