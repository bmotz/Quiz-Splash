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
echo "<h2>Successfully added a new pool!</h2>";
?>
<p>Your pool has been successfully added!  Now you can either go add some questions to your pool, or go back to manage your class settings.</p>
<ul>
    <li><a href="./listQuestionPools.php">Write new questions</a></li>
    <li><a href="./classAdmin.php">Class administration</a></li>
</ul>

<?php
// ECHO THE BOTTOM BUN
echo substr($template, $splitpoint);
?>

