<?php
  include_once('/var/www/config/connectionStrings.php');
  global $username;
  global $password;
  // create a mysql object for use in classes and web-services
  $igrtSqli = new mysqli("localhost", $username, $password, "igrt"); //
  //check connection
  $connectionOK = true;
  if ($igrtSqli->connect_error) {
    echo 'mysql error';
    $connectionOK = false;        
  }
  else {
    // double-check
    $qry = "SELECT * FROM igUsers";
    $check = $igrtSqli->query($qry);
    if ($igrtSqli->affected_rows == 0) { $connectionOK = false; } 
  }
  // if not, pass mysql object through.....

