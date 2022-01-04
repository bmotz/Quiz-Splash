<?php

function validateMysqlDate ($date) { 
    if (preg_match("/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/", $date, $matches)) { 
        if (checkdate($matches[2], $matches[3], $matches[1])) { 
            return true; 
        } 
    } 
    return false; 
}

function makeTagSelect ($tagArray,$tag1,$tag2,$tag3) {
	// echo var_dump($tagArray);
	$ntags = count($tagArray);
	$ncol1 = ceil($ntags/2);
	echo '<table class="tagTable" width="100%">';
	echo ' <tr>';
	echo ' <td width="33%" valign="top">';  // contain the three tag buckets
	echo '   This question\'s tags: <br />';
	echo '   <span class="tagInput">
				<input type="text" name="tag1" id="tag1" value="'.$tag1.'"><input name="clearTag1" type="button" value="X" onclick="javascript: clearTag(1);">
			 </span><br />';
	echo '   <span class="tagInput">
				<input type="text" name="tag2" id="tag2" value="'.$tag2.'"><input name="clearTag2" type="button" value="X" onclick="javascript: clearTag(2);">
			 </span><br />';
	echo '   <span class="tagInput">
				<input type="text" name="tag3" id="tag3" value="'.$tag3.'"><input name="clearTag3" type="button" value="X" onclick="javascript: clearTag(3);">
			 </span><br />';	 
	echo ' </td>';
    echo ' <td width="33%" valign="top">
			Existing tags: <br />';  // contain the first column
	$half = array_slice($tagArray, 0, $ncol1);
	foreach ($half as $tn => $tc) {
		echo '<a id="etag" href="javascript: sendTag(\''.$tn.'\')">'.$tn.'</a>';
		if ($tc > 1) { echo ' ('.$tc.')'; }
		echo '<br />
		';
	}
	echo ' </td>';
	echo ' <td width="33%" valign="top">
	        &nbsp; <br />';  // contain the second column
	$half = array_slice($tagArray, $ncol1 , $ntags);
	foreach ($half as $tn => $tc) {
		echo '<a id="etag" href="javascript: sendTag(\''.$tn.'\')">'.$tn.'</a>';
		if ($tc > 1) { echo ' ('.$tc.')'; }
		echo '<br />
		';
	}
	echo ' </td>';
	echo ' </tr></table>';

}

function validateDate($date, $format = 'Y-m-d H:i')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

function makeOptions($dropType, $defSelect)
{
    if ($dropType == "year") {
		$currYear = date("Y");
		$my_array = array(""=>"",$currYear=>$currYear,$currYear+1=>$currYear+1,$currYear+2=>$currYear+2);
	}
	if ($dropType == "month") {
		$my_array = array(""=>"",
						  "01"=>"JAN",
						  "02"=>"FEB",
						  "03"=>"MAR",
						  "04"=>"APR",
						  "05"=>"MAY",
						  "06"=>"JUN",
						  "07"=>"JUL",
						  "08"=>"AUG",
						  "09"=>"SEP",
						  "10"=>"OCT",
						  "11"=>"NOV",
						  "12"=>"DEC");
	}
	if ($dropType == "day") {
		$my_array = array(""=>"",
						"01"=>"01",
						"02"=>"02",
						"03"=>"03",
						"04"=>"04",
						"05"=>"05",
						"06"=>"06",
						"07"=>"07",
						"08"=>"08",
						"09"=>"09",
						"10"=>"10",
						"11"=>"11",
						"12"=>"12",
						"13"=>"13",
						"14"=>"14",
						"15"=>"15",
						"16"=>"16",
						"17"=>"17",
						"18"=>"18",
						"19"=>"19",
						"20"=>"20",
						"21"=>"21",
						"22"=>"22",
						"23"=>"23",
						"24"=>"24",
						"25"=>"25",
						"26"=>"26",
						"27"=>"27",
						"28"=>"28",
						"29"=>"29",
						"30"=>"30",
						"31"=>"31");
	}
	if ($dropType == "hour") {
		$my_array = array(""=>"",
						"00"=>"12 AM",
						"01"=>"01 AM",
						"02"=>"02 AM",
						"03"=>"03 AM",
						"04"=>"04 AM",
						"05"=>"05 AM",
						"06"=>"06 AM",
						"07"=>"07 AM",
						"08"=>"08 AM",
						"09"=>"09 AM",
						"10"=>"10 AM",
						"11"=>"11 AM",
						"12"=>"12 AM",
						"13"=>"01 PM",
						"14"=>"02 PM",
						"15"=>"03 PM",
						"16"=>"04 PM",
						"17"=>"05 PM",
						"18"=>"06 PM",
						"19"=>"07 PM",
						"20"=>"08 PM",
						"21"=>"09 PM",
						"22"=>"10 PM",
						"23"=>"11 PM");
	}
	if ($dropType == "min") {
		$my_array = array(""=>"",
						"00"=>"00",
						"01"=>"01",
						"02"=>"02",
						"03"=>"03",
						"04"=>"04",
						"05"=>"05",
						"06"=>"06",
						"07"=>"07",
						"08"=>"08",
						"09"=>"09",
						"10"=>"10",
						"11"=>"11",
						"12"=>"12",
						"13"=>"13",
						"14"=>"14",
						"15"=>"15",
						"16"=>"16",
						"17"=>"17",
						"18"=>"18",
						"19"=>"19",
						"20"=>"20",
						"21"=>"21",
						"22"=>"22",
						"23"=>"23",
						"24"=>"24",
						"25"=>"25",
						"26"=>"26",
						"27"=>"27",
						"28"=>"28",
						"29"=>"29",
						"30"=>"30",
						"31"=>"31",
						"32"=>"32",
						"33"=>"33",
						"34"=>"34",
						"35"=>"35",
						"36"=>"36",
						"37"=>"37",
						"38"=>"38",
						"39"=>"39",
						"40"=>"40",
						"41"=>"41",
						"42"=>"42",
						"43"=>"43",
						"44"=>"44",
						"45"=>"45",
						"46"=>"46",
						"47"=>"47",
						"48"=>"48",
						"49"=>"49",
						"50"=>"50",
						"51"=>"51",
						"52"=>"52",
						"53"=>"53",
						"54"=>"54",
						"55"=>"55",
						"56"=>"56",
						"57"=>"57",
						"58"=>"58",
						"59"=>"59");
	}
	foreach ($my_array as $x=>$x_value) {
		if ($defSelect == $x) {
		   	echo '<option value="'.$x.'" selected>'.$x_value.'</option>';
		}
		else {
			echo '<option value="'.$x.'">'.$x_value.'</option>';
		}
	}
}


?>  