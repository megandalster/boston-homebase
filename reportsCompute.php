<?php
/*
 * Copyright 2015 by Adrienne Beebe, Connor Hargus, Phuong Le, 
 * Xun Wang, and Allen Tucker. This program is part of RMHP-Homebase, which is free 
 * software.  It comes with absolutely no warranty. You can redistribute and/or 
 * modify it under the terms of the GNU General Public License as published by the 
 * Free Software Foundation (see <http://www.gnu.org/licenses/ for more information).
 */
include_once('database/dbPersons.php');
include_once('domain/Person.php');
include_once('database/dbShifts.php');
include_once('domain/Shift.php');

if (isset($_POST['_form_submit'])) {
	if  ($_POST['_form_submit'] == 'report')
		show_report();
}

function show_report() {

	$from = $_POST["from"];
	$to   = $_POST["to"];
	$name_from = $_POST["name_from"];
	$name_to = $_POST["name_to"];
	$venue   = $_POST["venue"];
	if ($_POST['export'])	
		$export = "yes";
	else $export = "no";
	
	if (isset($_POST['report-types'])) {
		if (in_array('volunteer-hours', $_POST['report-types'])) {
			report_volunteer_hours_by_day($from, $to, $venue);
		}
	    else if (in_array('shifts-staffed-vacant', $_POST['report-types'])) {
			report_shifts_staffed_vacant_by_day($from, $to, $venue);
		}
		if (in_array('volunteers', $_POST['report-types'])) {
				report_all_volunteers($name_from, $name_to, $export);	
		}
	}
}

function report_volunteer_hours_by_day($from, $to, $venue) { 
	if($from == ""){$from ="00-00-00";}
	if($to == ""){$to = date("m-d-y");}
		
	echo "<br><b>Total Volunteer Hours Report</b><br>"; 
	if ($from!="00-00-00")
		echo " from " .pretty_date($from);
	if ($to!="")
		echo " through ".pretty_date($to);
	echo " for the ".pretty_venue($venue).".";

	$report = get_volunteer_hours($from, $to, $venue);
	$row_labels = array("8-12","12-4","4-8","Total");
	$col_labels = array("Mon","Tue","Wed","Thu","Fri","Sat","Sun","Total");
	display_totals_table($col_labels, $row_labels, $report, $export);	
}

function report_shifts_staffed_vacant_by_day($from, $to, $venue) {
	if($from == ""){$from ="00-00-00";}
	if($to == ""){$to = date("m-d-y");}
		
	echo "<br><b>Volunteers in Shifts/Vacancies Report</b><br>"; 
	if ($from!="00-00-00")
		echo " from " .pretty_date($from);
	if ($to!="")
		echo " through ".pretty_date($to);
	echo " for the ".pretty_venue($venue).".";

	$report = get_shifts_staffed($from, $to, $venue);
	$row_labels = array("8-12","12-4","4-8","Total");
	$col_labels = array("Mon","Tue","Wed","Thu","Fri","Sat","Sun","Total");
	display_vacancies_table($col_labels, $row_labels, $report, $export);
}

function report_volunteer_birthdays($from, $to, $name_from, $name_to, $venue, $export) {
	echo ("<br><b>Active Volunteer Birthdays Report</b> (ordered by month) <br> Report date: ");
	echo date("F d, Y")."<br><br>";
	if($name_from == ""){$name_from="A";}
	if($name_to == ""){$name_to = "ZZZ";}
	
	$report = get_birthdays($from, $to, $name_from, $name_to,$venue);
	//display_birthdays($col_labels,$report);
	display_birthdays($report, $export);
}

function report_volunteer_history($from, $to, $name_from, $name_to, $venue, $export) {
	if($from == ""){$from ="00-00-00";}
	if($to == ""){$to = date("m-d-y");}
	if($name_from == ""){$name_from="A";}
	if($name_to == ""){$name_to = "ZZZ";}
	
	echo ("<br><b>Active Volunteer History Report</b><br> Report date: ");
	echo date("F d, Y")."<br><br>";
	
	$report = get_logged_hours($from, $to, $name_from,$name_to, $venue);
    display_logged_hours($report, $export);
}

function report_all_volunteers($name_from, $name_to, $export) {
	echo ("<br><b>Active Volunteer Contact Info</b><br> Report date: ");
	echo date("F d, Y")."<br><br>";
	if($name_from == ""){$name_from="A";}
	if($name_to == ""){$name_to = "ZZZ";}
	
	$report = getall_dbPersons($name_from, $name_to);
	display_volunteers($report, $export);
}

function display_birthdays($report, $export) { //Create a table to display birthdays
	$col_labels = array("Volunteer Name ","Address", "City", "State", "Zip", "Birth Date ","Age ");
	$res = "
		<table id = 'report'> 
			<thead>
			<tr>";
	$row = "<tr>";
	
	foreach($col_labels as $col_name){
		$row .= "<td><b>".$col_name."</b></td>";
	}
	$row .="</tr>";
	$res .= $row;
	$res .= "
			</thead>
			<tbody>";
	
	$full_names = array();
	$dobs = array();
	$ages = array();
	echo '<div id="target" style="overflow: scroll; width: variable; height: 400px;">';

	$export_data = array();
	foreach($report as $person){
		//check if the person's date of birth is known 
		if (strlen($person->get_birthday()) == 8 && substr($person->get_birthday(), 6) != "XX" ){
			$dob = pretty_date($person->get_birthday());
			$age = calculate_age($person->get_birthday());
		}
		else if (strlen($person->get_birthday()) == 8){
			$dob = pretty_date($person->get_birthday());
			$age = "N/A";
		}
		else {
			$dob = "N/A";
			$age = "N/A";
		}
		if ($dob != "N/A") {
			
			$p = array($person->get_first_name()." ".$person->get_last_name(),
				   $person->get_address(), $person->get_city(), $person->get_state(), 
				   $person->get_zip(),$dob,$age);
			$export_data[] = $p;
			$res .= "<tr>";
			foreach ($p as $info)
				$res .= "<td>". $info . "</td>";
		    $res .= "</tr>";
	/*    $row = "<tr>";
			$row .= "<td>".$person->get_first_name()." ".$person->get_last_name().
					"</td><td>".$person->get_address() ."</td><td>".$person->get_city()."</td>". 
			        "</td><td>".$person->get_state() ."</td><td>".$person->get_zip()."</td>". 
			        "</td><td>".$dob ."</td><td align=right>".$age."</td>";
			$row .= "</tr>";
			$res .= $row;
	*/	}
		
	}
	$res .= "</tbody></table>";
	echo $res;
	echo "</div>";
	if ($export=="yes") 
		export_report ("Active Volunteer Birthdays Report", $col_labels, $export_data);
}

function pretty_date($date){
	//eg. date is 03-30-78, this function can convert it into "March 30, 1978"
  	//explode the date to get month, day and year
	$dob=explode("-",$date); 
	//if the year is less than 30, we can assume the person was born after 2000; if the year is greater than 30, we can 
	//assume the person was born before 2000. 
	if ($dob[2]=="XX")
	    $dob[2] = "19XX";
	elseif ( ((int) $dob[2] ) <= date("y")){
		$year = "20".$dob[2];  	
	} else{
		$year = "19".$dob[2];
	}
    if ( ((int) $dob[1] ) < 10)
		$dob[1] = substr($dob[1],1);  		
	return date("F j", mktime(0,0,0,$dob[0],$dob[1],(int)$year)).", ".$year;
}



function display_totals_table($col_lab, $row_lab, $report){  //Creates a table for the Total Hours report
	$res = "
		<table id = 'areport'> 
			<thead>
			<tr>
				<td></td>";
	$row = "<tr>
				<td><b>Shift</b></td>";
	foreach($col_lab as $col_name){
		$row .= "<td><b>".$col_name."</b></td>";
	}
	$row .="</tr>";
	$res .= $row;
	$res .= "
			</thead>
			<tbody>";
	foreach($row_lab as $row_name){
		$row_total = 0;
		$row = "<tr>";
		$row .= "<td><b>".$row_name."</b></td>";
		if($row_name == "Total"){
			$grand_total = 0;
			foreach($col_lab as $col_name){
				$count = 0;
				if($col_name =="Total"){
					$row .= "<td>".$grand_total."</td>";
				}else {
					foreach($report as $entry){
						$elements = explode(":",$entry); 
						if ($col_name==$elements[0]){
							$num = (int)$elements[2];
							$count = $count + $num;
							$row_total = $row_total + $num;
						}
					}
					$row .= "<td>".$count."</td>";
					$grand_total += $count;
				}
			}
		}else{
			foreach($col_lab as $col_name){
				$count = 0;
				if($col_name =="Total"){
					$row .= "<td>".$row_total."</td>";
				}else {
					foreach($report as $entry){
						$elements = explode(":",$entry); 
						if ($col_name==$elements[0] && $row_name==$elements[1]){
							$num = (int)$elements[2];
							$count += $num;
							$row_total += $num;
						}
					}
					$row .= "<td>".$count."</td>";
				}
			}
		}
		$row .= "</tr>";
		$res .= $row;
	}
	$res .= "</tbody></table>";
	echo $res;
}

function display_vacancies_table($col_lab, $row_lab, $report){
	$res = "
		<table id = 'areport'> 
			<thead>
			<tr>
				<td></td>";
	//row 1
	$row = "<tr>
				<td></td>";
	foreach($col_lab as $col_name){
		$row .= "<td><b>".$col_name."</b></td>";
	}
	$row .="</tr>";
	$res .= $row;
	$res .= "
			</thead>
			<tbody>";
	foreach($row_lab as $row_name){
		$row_total_vacs = 0;
		$row_total_slots = 0;
		$row = "<tr>";
		$row .= "<td><b>".$row_name."</b></td>";
		if($row_name == "Total"){
			$grand_total_vacs = 0;
			$grand_total_slots = 0;
			foreach($col_lab as $col_name){
				$col_total_slots = 0;
				$col_total_vacs = 0;
				if($col_name =="Total"){
					$row .= "<td>".$grand_total_slots."/".$grand_total_vacs."</td>";
				}else{
					foreach($report as $entry){
						$elements = explode(":",$entry); //turn each entry into an arry, hrs is final item in array
						if ($col_name==$elements[0]){
							$slots = $elements[3];
							$vacs = $elements[2];
							$slotsint = (int)$slots;
							$vacsint = (int)$vacs;
							$col_total_slots += $slotsint;
							$col_total_vacs += $vacsint;
						}
					}
					$row .= "<td>".$col_total_slots."/".$col_total_vacs."</td>";
					$grand_total_slots += $col_total_slots;
					$grand_total_vacs += $col_total_vacs;
				}
			}
		}else{
			foreach($col_lab as $col_name){
				$slots_count = 0;
				$vacs_count = 0;
				if($col_name =="Total"){
					$row .= "<td>".$row_total_slots."/".$row_total_vacs."</td>";
				}else {
					foreach($report as $entry){
						$elements = explode(":",$entry); //turn each entry into an arry, hrs is final item in array
						if ($col_name==$elements[0] && $row_name==$elements[1]){
							$slots = $elements[3];
							$vacs = $elements[2];
							$slots_count += $slots;
							$vacs_count += $vacs;
							$slotsint = (int)$slots;
							$vacsint = (int)$vacs;
							$row_total_slots += $slotsint;
							$row_total_vacs += $vacsint;
						}
					}
					$row .= "<td>".$slots_count."/".$vacs_count."</td>";
				}
			}
		}
		$row .= "</tr>";
		$res .= $row;
	}
	$res .= "</tbody></table>";
	echo $res;
}

function calculate_age($date){
  	//eg. date is 03-30-78 and today is 05-20-18 then age is 40.  Bit if today is 01-01-18 then age is 39.
  	//explode today's date to get month, day and year for today
	$dob=explode("-",$date); 
	
	//if the birth year is <= today's year, we can assume the person was born after 2000; if the year is greater than 30, we can 
	//assume the person was born before 2000. 
	if ( ((int) $dob[2] ) <= date("y")){
		$dob[2] = "20".$dob[2];  	
	} else{
		$dob[2] = "19".$dob[2];
	}	
	$curMonth = date("m");
	$curDay = date("j");
	$curYear = date("Y");
	$age = $curYear - $dob[2]; 
	if($curMonth<$dob[0] || ($curMonth==$dob[0] && $curDay<$dob[1])){ 
		$age--; 
	}
    return $age; 
}
// 24-hour time to 12-hour time 
//eg. time is 0900, this function can convert it into "1:00 pm"
function civil_time($army_time){
		$time_in_12_hour_format = date("g:i a", strtotime($army_time)); 
	return $time_in_12_hour_format;
}

// Improve venue display by using associative array, i.e, turning fam --> "Family Room" 
function pretty_venue($v){
	$venue = array('house' => 'House', 'fam' => 'Family Room', 'mealprep' => 'Meal Prep', 
	            'activities' => 'Activities', 'other' => 'Other', ""=>"House and Family Room");
		return $venue["$v"];
}

//Create a table to display volunteer history report
function display_logged_hours ($report, $export) { 
	$col_labels = array("Name","Date","Start time","End time","Venue","Hours");
	$res = "
		<table id = 'report'> 
			<thead>
			<tr>";
	$row = "<tr>";
	
	foreach($col_labels as $col_name){
		$row .= "<td><b>".$col_name."</b></td>";
	}
	$row .="</tr>";
	$res .= $row;
	$res .= "
			</thead>
			<tbody>";
	
	$full_name = array();
	$first_name = array();
	$dates = array();
	$shifts_worked = array();
	$hours_count = array();
    $export_data = array();
	
    echo '<div id="target" style="overflow: scroll; width: variable; height: 400px;">';			       
	foreach($report as $key){
		$entry = explode(";",$key);
		$last_name = $entry[0];
		$first_name = $entry[1];
		$dates = explode(",",$entry[2]);
		$res .= "<tr><td>".$last_name . ", ". $first_name."</td>";
		$p = array($last_name . ", ". $first_name);
		$total_hours=0;
		foreach ($dates as $date) {
			$d = explode(":",$date);
			$total_hours += $d[3];
			$times = explode(",",$d[1]);
			foreach ($times as $time) {
				$t = explode("-",$time);
				$start_time = civil_time($t[0]);
				$end_time = civil_time($t[1]);
				$res .= "<td align=right>".pretty_date($d[0])."</td><td align=right>".$start_time.
				        "</td><td align=right>".$end_time."</td><td>".
				        pretty_venue($d[2])."</td><td align=right>".$d[3]."</td></tr><tr><td></td>";
				$p = array($last_name . ", ". $first_name,
							pretty_date($d[0]),$start_time,$end_time,pretty_venue($d[2]),$d[3]);
				$export_data[] = $p; 
			}
		}
		$res .= "<td></td><td></td><td></td><td><b>Total hours</b></td><td align=right>".$total_hours."</td></tr>";
	}
	
	$res .= "</tbody></table>";
	echo $res;
	echo "</div>";
	if ($export=="yes") 
		export_report ("Active Volunteer History Report", $col_labels, $export_data);
}

//Create a table to display volunteer contact info
function display_volunteers ($report, $export) { 
	$col_labels = array("Name","Address","City","State","Zip","Phone 1", "Phone 2", "Work Phone", "Email","Start Date", "End Date", "Reason Left", "Notes");
	$res = "
		<table id = 'report'> 
			<thead>
			<tr>";
	$row = "<tr>";
	
	foreach($col_labels as $col_name){
		$row .= "<td><b>".$col_name."</b></td>";
	}
	$row .="</tr>";
	$res .= $row;
	$res .= "
			</thead>
			<tbody>";
	
	echo '<div id="target" style="overflow: scroll; width: variable; height: 400px;">';
	$export_data = array();			       
	foreach($report as $person){
		$p = array($person->get_last_name() . ", ". $person->get_first_name(), 
				$person->get_address(), $person->get_city(), $person->get_state(), $person->get_zip(),
			    $person->get_phone1(), $person->get_phone2(), $person->get_work_phone(), $person->get_email(),
			    $person->get_start_date(), $person->get_end_date(), $person->get_reason_left(),
				$person->get_notes());
		$export_data[] = $p;
		$res .= "<tr>";
		foreach ($p as $info)
			$res .= "<td>". $info . "</td>";
	    $res .= "</tr>";
	}
	$res .= "</tbody></table>";
	echo $res;
	echo "</div>";
	if ($export=="yes") 
		export_report ("Active Volunteer Contact Info", $col_labels, $export_data);
}

function export_report($heading, $col_labels, $data) {
	$filename = "export.csv";
	$handle = fopen($filename, "w");
	fputcsv($handle, array($heading,"","Report date: ".date("F d, Y")));
	fputcsv($handle, array());
	fputcsv($handle, $col_labels);
	foreach ($data as $aline) {
		fputcsv($handle, $aline);
	}
	fclose($handle);
}

?>