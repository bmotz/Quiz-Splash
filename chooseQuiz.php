<?php
include("cas.php");
include("database_connect.php");

$title = "QuizSplash: Choose a Quiz";

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
?>

<h2>Choose a Quiz</h2>
<h4>Example Course</h4>
<ul>
	<li>Example Quiz [0 questions in pool]
    	<ul>
        	<li>Average quiz score in this pool: 85%</li>
            <li>Take a quiz from this pool:
            	<ul>
                	<li><a href="./takeQuiz.php">Ten random questions</a></li>
                    <li><a href="./takeQuiz.php">Questions I've gotten wrong</a></li>
                    <li><a href="./takeQuiz.php">The best 10 questions</a></li>
                </ul>
            </li>
        </ul>
    </li>
</ul>


<?php
// ECHO THE BOTTOM BUN
echo substr($template, $splitpoint);
?>

