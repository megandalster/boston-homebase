<?php
/*
 * Copyright 2015 by Adrienne Beebe, Connor Hargus, Phuong Le, 
 * Xun Wang, and Allen Tucker. This program is part of RMHP-Homebase, which is free 
 * software.  It comes with absolutely no warranty. You can redistribute and/or 
 * modify it under the terms of the GNU General Public License as published by the 
 * Free Software Foundation (see <http://www.gnu.org/licenses/ for more information).
 */
/**
 * Person class for RMHP-Homebase.
 * @author Oliver Radwan, Judy Yang, Phuong Le and Allen Tucker
 * @version May 1, 2008 (v1), Jan 21, 2015 (v2), and Mar 3, 2015 (v3, last modified)
 **/
include_once(dirname(__FILE__).'/../database/dbZipCodes.php');
include_once(dirname(__FILE__).'/../database/dbShifts.php');
include_once(dirname(__FILE__).'/../database/dbPersons.php');
include_once('Shift.php');
include_once('Person.php');

class Person {
    private $id;    // id (unique key) = first_name . phone1
    private $first_name; // first name as a string
    private $last_name;  // last name as a string
    private $gender; // gender - string
    private $address;   // address - string
    private $city;    // city - string
    private $state;   // state - string
    private $zip;    // zip code - integer
    private $phone1;   // primary phone -- cell or home
    private $phone2;   // secondary phone -- cell or home
    private $work_phone; // office phone
    private $email;   // email address as a string
    private $type;       // array of "volunteer", "sub", "mealprep", "activities", "other", "manager"
    private $screening_type; // if status is "applicant", type of screening used for this applicant
    private $screening_status; // array of dates showing completion of 
    // screening steps for this applicant 
    private $status;     // a person may be an "applicant", "active", "LOA", or "former"
    private $references;   // array of name:phone of up to 2 references 
    private $maywecontact; // "yes" or "no" for permission to contact references
    private $motivation;   // App: why interested in RMH?
    private $specialties;  // App: special interests and hobbies related to RMH
    private $availability; // array of week_no:day:hours:venue quads; e.g., odd:Mon:9-1:house
    private $schedule;     // array of scheduled quads; e.g., odd:Mon:9-1:house
    private $hours;        // array of actual hours logged; e.g., 01-05-15:0930-1300:house:3.5
    private $birthday;     // format: 03-12-64
    private $start_date;   // format: 03-12-99
    private $end_date;     // format: 03-12-10
    private $reason_left;  // reason for leaving
    private $notes;        // notes that only the manager can see and edit
    private $password;     // password for calendar and database access: default = $id

    /**
     * constructor for all persons
     */

    function __construct($f, $l, $g, $a, $c, $s, $z, $p1, $p2, $p3, $e, $t, 
    		$screening_type, $screening_status, $st, $re, $mwc, $mot, $spe, 
    		$av, $sch, $hrs, $bd, $sd, $ed, $rl, $notes, $pass) {
        $this->id = $f . $p1;
        $this->first_name = $f;
        $this->last_name = $l;
        $this->gender = $g;
        $this->address = $a;
        $this->city = $c;
        $this->state = $s;
        $this->zip = $z;
        $this->phone1 = $p1;
        $this->phone2 = $p2;
        $this->work_phone = $p3;
        $this->email = $e;
        if ($t !== "")
            $this->type = explode(',', $t);
        else
            $this->type = array();
        $this->screening_type = $screening_type;
        if ($screening_status !== "")
            $this->screening_status = explode(',', $screening_status);
        else
            $this->screening_status = array();
        $this->status = $st;
        if ($re != null) {
            $this->references = explode(',', $re);
            $this->maywecontact = "yes";
        }
        else
            $this->references = array();
        $this->motivation = $mot;
        $this->specialties = $spe;
        if ($av == "")
            $this->availability = array();
        else
            $this->availability = explode(',', $av);
        if ($sch !== "")
            $this->schedule = explode(',', $sch);
        else
            $this->schedule = array();
        if ($hrs !== "")
            $this->hours = explode(',', $hrs);
        else
            $this->hours = array();

        $this->birthday = $bd;
        $this->start_date = $sd;
        $this->end_date = $ed;
        $this->reason_left = $rl;
        $this->notes = $notes;
        if ($pass == "")
            $this->password = md5($this->id);
        else
            $this->password = $pass;  // default password == md5($id)
    }

    function get_id() {
        return $this->id;
    }

    function get_first_name() {
        return $this->first_name;
    }

    function get_last_name() {
        return $this->last_name;
    }

    function get_gender() {
        return $this->gender;
    }

    function get_address() {
        return $this->address;
    }

    function get_city() {
        return $this->city;
    }

    function get_state() {
        return $this->state;
    }

    function get_zip() {
        return $this->zip;
    }

    function get_phone1() {
        return $this->phone1;
    }

    function get_phone2() {
        return $this->phone2;
    }
    
 	function get_work_phone() {
        return $this->work_phone;
    }

    function get_email() {
        return $this->email;
    }


    /**
     * @return: "volunteer", "guestchef", "sub", etc.
     */
    function get_type() {
        return $this->type;
    }

    function get_screening_type() {
        return $this->screening_type;
    }

    function get_screening_status() {
        return $this->screening_status;
    }

    function get_status() {
        return $this->status;
    }

    function get_references() {
        return $this->references;
    }

    function get_maywecontact() {
        return $this->maywecontact;
    }

    function get_motivation() {
        return $this->motivation;
    }

    function get_specialties() {
        return $this->specialties;
    }

    function get_availability() {   // array of week_no:day:hours:venue
        return $this->availability;
    }
    
	function get_availdays() {		// array of week_no:day:hours (extracted from availability)		
		$availdays = array();
        foreach ($this->availability as $a) {
        	$ex = explode(":",$a);
        	$ad = $ex[0].":".$ex[1].":".$ex[2];
        	if (!in_array($ad,$availdays))
        		$availdays[] = $ad;
        }
        return $availdays;
    }
    function get_availhours() {     // array of hours (extracted from availability)
    	$availhours = array();
    	foreach ($this->availability as $a) {
        	$ex = explode(":",$a);
        	$ad = $ex[2];
        	if (!in_array($ad,$availhours))
        		$availhours[] = $ad;
        }
        return $availhours;
    }
    function get_availvenues() {     // array of venues (extracted from availability)
    	$availvenue = array();
    	foreach ($this->availability as $a) {
        	$ex = explode(":",$a);
        	$ad = $ex[3];
        	if (!in_array($ad,$availvenue))
        		$availvenue[] = $ad;
        }
    	return $availvenue;
    }
    function set_availability($days,$hours,$venue) { // reconstruct availability array from parts
     	$this->availability = array();
    	foreach($days as $day)
    		foreach ($hours as $hour)
    			foreach ($venues as $venue)
    				$this->availability[] = $day.":".$hours.":".$venue;
    }

    function get_schedule() {
        return $this->schedule;
    }
    function get_hours() {
        return $this->hours;
    }
    
    function get_birthday() {
        return $this->birthday;
    }

    function get_start_date() {
        return $this->start_date;
    }

 	function get_end_date() {
        return $this->end_date;
    }
    
 	function get_reason_left() {
        return $this->reason_left;
    }
    
    function get_notes() {
        return $this->notes;
    }

    function get_password() {
        return $this->password;
    }
}

?>
