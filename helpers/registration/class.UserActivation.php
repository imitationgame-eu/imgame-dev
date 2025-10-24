<?php
$php_call="code";
include_once($_SERVER['DOCUMENT_ROOT'] . '/domainSpecific/mySqlObject.php');

class UserActivation {
  private $activationcode;
  private $activationId;
  private $userId;
  
  function __construct($activationCode) {
    $this->activationcode=$activationCode;
  }
  
  function processActivation() {
    global $igrtSqli;
    $sqlcmd_activateUser=sprintf("UPDATE igUsers SET activated=1 WHERE id='%s';",$this->userId);
    $igrtSqli->query($sqlcmd_activateUser);
//        // check update ok
    $sqlqry_userActive=sprintf("SELECT * FROM igUsers WHERE id='%s';",$this->userId);
    $userActiveResult=$igrtSqli->query($sqlqry_userActive);
    if ($userActiveResult->num_rows > 0) {
      $row=$userActiveResult->fetch_object();
      if ($row->activated==1) {
        // remove activation entry from table
        $sqlcmd_deleteActivation=sprintf("DELETE FROM igActivations WHERE id='%s';",$this->activationId);
        $igrtSqli->query($sqlcmd_deleteActivation);
        return true;
      }
    }
    return false;
  }
  
  function isActivationValid() {
    global $igrtSqli;
    $sqlqry_codeexists=sprintf("SELECT * FROM igActivations WHERE activationCode='%s';", $this->activationcode);
    $codeExistsResult=$igrtSqli->query($sqlqry_codeexists);
    if ($codeExistsResult->num_rows > 0) {
      $row = $codeExistsResult->fetch_object();
      $this->activationId = $row->id;
      $this->userId = $row->userId;
      return true;
    }
    else {
      // assume a previous activation attempt, so will revert to appropriate error page
      return false;
    }
  }
}

