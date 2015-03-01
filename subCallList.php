<?php
/*
 * Copyright 2013 by Jerrick Hoang, Ivy Xing, Sam Roberts, James Cook, 
 * Johnny Coster, Judy Yang, Jackson Moniaga, Oliver Radwan, 
 * Maxwell Palmer, Nolan McNair, Taylor Talmage, and Allen Tucker. 
 * This program is part of RMH Homebase, which is free software.  It comes with 
 * absolutely no warranty. You can redistribute and/or modify it under the terms 
 * of the GNU General Public License as published by the Free Software Foundation
 * (see <http://www.gnu.org/licenses/ for more information). Show
 * 
 */

	session_start();
	session_cache_expire(30);
?>

<!-- page generated by the BowdoinRMH software package -->

<html>
	<head>
		<title>
			Sub Call Lists
		</title>
		<link rel="stylesheet" href="styles.css" type="text/css" />
		<link rel="stylesheet" href="calendar.css" type="text/css" />
	</head>
	<body>
		<div id="container">
			<?PHP include('header.php');?>
			<div id="content">
				<?php
					include_once('database/dbSCL.php');
					include_once('database/dbShifts.php');
					include_once('database/dbLog.php');
					include_once('database/dbPersons.php');
					$id=$_GET['shift'];  
					$venue=$_GET['venue'];	
					generate_scl($id); 		//creates a sub call list based on the id of the shift
					if(array_key_exists('_submit_generate_scl',$_POST)) {
						$id=$_POST['_shiftid'];
					}
					else if (array_key_exists('_submit_view_scl',$_POST)) {
						$id=$_POST['_shiftid'];
					}
					else if(array_key_exists('_submit_save_scl_changes',$_POST)) {
						$id=process_edit_scl($_POST);
					}
					if($id) {
						$id=view_scl($id,$venue);
					}
					if(!$id) {
						// The first 8 characters of the shift id shows the dates for the week. 
						$mm_dd_yy = substr($_GET['shift'], 0, 8);
						// Displays the option of going back to the Calendar.
						back_to_calendar($mm_dd_yy, 700, $venue);
						
						do_scl_index($id,$venue);
					}
				?>
			</div>
				<?PHP include('footer.inc');?>
		</div>
	</body>
</html>

<?php
/*
 * Generate a new SCL for a shift.  If one is already there, remove it and regenerate it.
 */
	function generate_scl($id) {
	//	echo "<br>we are here";
		$shift=select_dbShifts($id);  				
		$vacancies=$shift->num_vacancies();  		
		$day=$shift->get_day();  					
		$time=$shift->get_time_of_day();  			 
		$venue = $shift->get_venue(); 				 
		$shift_persons=$shift->get_persons();  		
		if(!$shift_persons[0])
			array_shift($shift_persons);
		connect();
		$query="SELECT * FROM dbPersons WHERE (status = 'active' AND type LIKE '%sub%' AND availability LIKE '%".$day.":".$time.":".$venue."%') ORDER BY last_name,first_name";
		$persons_result=mysql_query($query);		
		mysql_close(); 
		for($i=0;$i<mysql_num_rows($persons_result);++$i) {
			$row=mysql_fetch_row($persons_result);
			$id_and_name=$row[0]."+".$row[1]."+".$row[2];	
			$match=false;
//			for($j=0;$j<count($shift_persons);++$j) {
//				if($id_and_name==$shift_persons[$j] || in_array($id_and_name, )) {
//					$match=true;
//				}
//			}
			if (!in_array($id_and_name, $shift_persons) && !in_array($id_and_name, $shift->get_removed_persons())) {
				$persons[]=array($row[0], $row[1], $row[2], $row[9], $row[10], "", "", "?");
			}	
		}
		$new_scl=new SCL($id, $persons, "open", $vacancies, get_sub_call_list_timestamp($id));   
		if (!select_dbSCL($id)){
		   insert_dbSCL($new_scl);
		   $shift->open_sub_call_list();
		   update_dbShifts($shift);
		}
		else $new_scl = select_dbSCL($id);
		
		add_log_entry('<a href=\"personEdit.php?id='.$_SESSION['_id'].'\">'.$_SESSION['f_name'].' '.
		    $_SESSION['l_name'].'</a> generated a <a href=\"subCallList.php?shift='.$shift->get_id().'\">sub call list</a> for the shift: <a href=\"editShift.php?shift='.
		    $shift->get_id().'&venue='.$venue.'\">'.get_shift_name_from_id($shift->get_id()).'</a>.');
		//$_POST['_shiftid']=$id;
		//echo "<p>SCL ".$id." added successfully.</p>";
	}

	function do_scl_index($id,$venue) {
		//$venue = substr(strrchr($id,":"),1);
		connect();
		$query="SELECT * FROM dbSCL  ORDER BY time";
		$result=mysql_query($query);
		mysql_close();
		$cur_date=date("Ymd",time());
		if(array_key_exists('_shiftid',$_POST))
			show_back_navigation($_POST['_shiftid'],494,$venue);
		echo "<p><table width=\"600\" align=\"center\" border=\"1px\"><tr><td align=\"center\" colspan=\"2\"><b>Index of Sub Call Lists with Vacancies</b></td></tr>
		<tr><td>&nbsp;Shift<br>&nbsp;</td><td>&nbsp;Vacancies<br>&nbsp;</td></tr>";
		for($i=0;$i<mysql_num_rows($result);++$i) {
			$row=mysql_fetch_row($result);
			$scl_date_formatted=substr($row[4], 0, strlen(date("Ymd",time())));
			if($row[2]=="open" && $scl_date_formatted<=$cur_date) {
				$scl=select_dbSCL($row[0]);
				$scl->set_status("closed");
				echo "<br><br><br>";
				update_dbSCL($scl);
				$row[2]="closed";
			}
		    echo "<tr><td>&nbsp;<a href=\"subCallList.php?shift=".$row[0]."\">"
				.get_shift_name_from_id($row[0]).
				"</a></td>
					<td>&nbsp;".$row[3]."</td></tr>";
		}
		echo "<tr><td colspan=\"2\" align=\"center\">";
	    echo "</td></tr></table></p>";
	} 

	function view_scl($id,$venue) {
	    $scl=select_dbSCL($id);
		$shift=select_dbShifts($id);
		if(!$scl instanceof SCL) {
			return null;
		}
		$persons=$scl->get_persons();
		$status=$scl->get_status();
		$venue = substr(strrchr($id,":"),1);
			if(array_key_exists('_shiftid',$_POST))
			  show_back_navigation($_POST['_shiftid'],692,$venue); // show_back_navigation($id,692);
			echo "<table width=\"700\" align=\"center\" border=\"1px\">
				<tr><td colspan=\"5\" align=\"center\"><b>Sub Call List for ".get_shift_name_from_id($id)."</b></td></tr>" ;
			echo "<tr><td colspan=\"5\"><br>";
				$v=$shift->num_vacancies();
				if($v==1)
					echo "&nbsp;1 sub";
				else
					echo "&nbsp;".$v." subs";
				echo " needed for this shift.<br><br></td></tr>
				<br><br><tr><td>&nbsp;Name</td><td>Phone</td><td>Date Called</td><td>Notes</td><td>Accepted</td></tr>";
			    echo "<form method=\"POST\" style=\"margin-bottom:0;\">";
			    for($i=0;$i<count($persons);++$i) {
					    if ($_SESSION['access_level']>=2)
						    echo "<tr><td>&nbsp;<a href=\"personEdit.php?id=".$persons[$i][0]."\">".$persons[$i][1]." ".$persons[$i][2]."</a></td><td>";
						else echo "<tr><td>&nbsp;".$persons[$i][1]." ".$persons[$i][2]."</td><td>";
						echo format_phone_number($persons[$i][3])."<br>".format_phone_number($persons[$i][4])."</td>
							<td><textarea rows=\"2\" cols=\"20\" name=\"datecalled_".$i."\">".$persons[$i][5]."</textarea></td>
							<td><textarea rows=\"2\" cols=\"20\" name=\"notes_".$i."\">".$persons[$i][6]."</textarea></td>
							<td valign=\"top\">";
						if($persons[$i][7]=="Yes") {
							echo "<br>Yes<input type=\"hidden\" name=\"accepted_".$i."\" value=\"Yes\">";
						}
						else if($persons[$i][7]=="No") 
							echo "<br>No<input type=\"hidden\" name=\"accepted_".$i."\" value=\"No\">";
						else {
							echo "<select name=\"accepted_".$i."\">
								<option value=\"-\" selected=\"selected\">-</option>
								<option value=\"Yes\">Yes</option>
								<option value=\"No\">No</option></select>";
						}
						echo "</td></tr>";
			    }
				echo "<tr><td align=\"right\" colspan=\"5\"><br>
						<input type=\"hidden\" name=\"_submit_save_scl_changes\" value=\"1\">
						<input type=\"hidden\" name=\"_shiftid\" value=\"".$id."\">
						<input type=\"submit\" value=\"Assign Subs / Save Changes\" name=\"submit\" style=\"width: 200px\">&nbsp;
						</td></tr>";
				echo "</table>";
		return $id;
	}

	function process_edit_scl($post) {
		$id=$post['_shiftid'];
		$shift=select_dbShifts($id);
		$venue = substr(strrchr($id,":"),1);
		$scl=select_dbSCL($id);
		$persons_old=$scl->get_persons();
		$vacancies=$shift->num_vacancies();
		$new_acceptances=0;
		for($i=0;$i<count($persons_old);++$i) {
			$p_new=array($persons_old[$i][0],$persons_old[$i][1],$persons_old[$i][2],$persons_old[$i][3],
				$persons_old[$i][4], trim(str_replace(',','&#44;',str_replace('+','&#43;',str_replace('\'','\\\'',htmlentities($post['datecalled_'.$i]))))),
				trim(str_replace(',','&#44;',str_replace('+','&#43;',str_replace('\'','\\\'',htmlentities($post['notes_'.$i]))))),$post['accepted_'.$i]);
			$persons_new[]=$p_new;
			if($post['accepted_'.$i]=="Yes" && $persons_old[$i][7]!="Yes") {
				++$new_acceptances;
				$accepted_people[]=$i;
			}
		}
		if($new_acceptances>$vacancies){
			for($j=0;$j<count($accepted_people);++$j) {
				if($j==0)
					$s=$persons_new[$accepted_people[$j]][1]." ".$persons_new[$accepted_people[$j]][2];
				else if($j==count($accepted_people)-1)
					$s=$s." and ".$persons_new[$accepted_people[$j]][1]." ".$persons_new[$accepted_people[$j]][2];
				else
					$s=$s.", ".$persons_new[$accepted_people[$j]][1]." ".$persons_new[$accepted_people[$j]][2];
				$persons_new[$accepted_people[$j]][7]="?";
			}
			if($vacancies==1) {
				echo "You assigned <b>".$s."</b> to this shift, but there is only ".$vacancies." open slot.<br>
					Please assign volunteers again.</p>";
			}
			else{
				echo "You assigned <b>".$s."</b> to this shift, but there are only ".$vacancies." open slots.<br>
					Please assign volunteers again.</p>";
			}
			update_sub_call_list($scl,$persons_new,$vacancies,"open");
			return $id;
		}
		else {
			$p=$shift->get_persons();
			for($j=0;$j<count($accepted_people);++$j) {
				$s=$persons_new[$accepted_people[$j]][0]."+".$persons_new[$accepted_people[$j]][1].
				"+".$persons_new[$accepted_people[$j]][2];
				$p[]=$s;
				--$vacancies;
				$shift->ignore_vacancy();
			}
			$shift->assign_persons($p);
			update_dbShifts($shift);
			for($j=0;$j<count($accepted_people);++$j) {
				add_log_entry('<a href=\"personEdit.php?id='.$_SESSION['_id'].'\">'.
				$_SESSION['f_name'].' '.$_SESSION['l_name'].'</a> assigned <a href=\"personEdit.php?id='.$persons_new[$accepted_people[$j]][0].'\">'.
				$persons_new[$accepted_people[$j]][1].' '.$persons_new[$accepted_people[$j]][2].'</a> to the shift: <a href=\"editShift.php?shift='.
				$shift->get_id().'&venue='.$venue.'\">'.get_shift_name_from_id($shift->get_id()).'</a>.');
			}
			//print_r($shift);
			if($vacancies==0)
				$status="closed";
			else
				$status="open";
			update_sub_call_list($scl,$persons_new,$vacancies,$status);
		}
	}

	function update_sub_call_list($scl,$persons,$vacancies,$status) {
		$scl->set_persons($persons);
		$scl->set_vacancies($vacancies);
		$scl->set_status($status);
		update_dbSCL($scl);
	}

	function do_name($id) {
		$id=substr($id,9);
		$i=strpos($id,"-");
		if ($i>0) {
			$start = substr($id,0,$i);
			if ($start>12) 
				$start = $start - 12 . "pm";
			else if ($start==12)
					$start = $start."pm";
				else $start = $start."am";
			$end = substr($id,$i+1);
			if ($end>12) $end = $end - 12 . "pm";
			else if ($end==12) 
					$end = $end . "pm";
				else $end = $end . "am";
			return("from ". $start . " to " . $end );
		}
		else return $id;
	}

	function get_sub_call_list_timestamp($id) {
		$m=substr($id,0,2);
		$d=substr($id,3,2);
		$y="20".substr($id,6,2);
		$s=substr($id,9,1);
		return $y.$m.$d.$s;
	}

	function show_back_navigation($id,$width,$venue) {
		echo "<br><table align=\"center\"><tr><td align=\"center\" width=\"".$width."\">
		<a href=\"editShift.php?shift=".substr($id, 0, strrpos($id, ":")) ."&venue=".$venue."\">Back to Shift</a><br>" ;
		return true;
	}
	
 	function back_to_calendar($date,$width,$venue) {
		echo "<br><table align=\"center\"><tr><td align=\"center\" width=\"".$width."\">
		<a href=\"calendar.php?id=".$date.":".$venue."&venue=".$venue."\">Back to Calendar</a><br>" ;
		return true;
	}

	function format_phone_number($s){
		if(strlen($s)!=10)
			return $s;
		else return "(".substr($s,0,3).") ".substr($s,3,3)."-".substr($s,6);
	}


?>

