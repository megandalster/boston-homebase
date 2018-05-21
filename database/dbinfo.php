<?php
/*
 * Copyright 2015 by student1, student2, student3, student4, and Allen Tucker. 
 * This program is part of RMHP-Homebase, which is free software.  It comes with 
 * absolutely no warranty. You can redistribute and/or modify it under the terms 
 * of the GNU General Public License as published by the Free Software Foundation
 * (see <http://www.gnu.org/licenses/ for more information).
 * 
 */

/*
 * This file is only the connection information for the database.
 * This file will be modified for every installation.
 * @author Max Palmer <mpalmer@bowdoin.edu>
 * @version updated 2/12/08
 */

function connect() {
    $host = "localhost";
    $database = "rmhphomebasedb";
    $user = "rmhphomebasedb";
    $pass = "math204";

    $connected = mysqli_connect($host,$user,$pass);
    if (!$connected) { echo "not connected"; return mysqli_error($connected);}
    $selected = mysqli_select_db($connected,$database);
    if (!$selected) { echo "not selected"; return mysqli_error($connected); }
    else return $connected;
}

?>
