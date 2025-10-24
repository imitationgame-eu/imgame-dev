<?php
/*
 * log debug and profiler info -MH
 * 
 */
class debugLogger {

  function logInfo($igrtSqli, $module, $msg) {
    $sql = sprintf("INSERT INTO sysdiags_debug (chrono, module, msg) VALUES(NOW(), '%s', '%s')", $module, $igrtSqli->real_escape_string($msg));
    $igrtSqli->query($sql);
  }
  
  //--------------------------------------------------------------------------
  // constructor and initialisation
  //--------------------------------------------------------------------------   
    
  function __construct() {
  }

}

