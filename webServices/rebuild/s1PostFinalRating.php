<?php
  ini_set('display_errors', 'On');
  error_reporting(E_ALL); 
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
  include_once $root_path.'/domainSpecific/mySqlObject.php';       
  $uid=$_POST['userid'];
  $exptId=$_POST['experimentID'];
  //$stage=$_POST['stage'];
  $choice=$_POST['uChoice'];
  $confidence=$_POST['uConfidence'];
  $reason=$_POST['uReason'];    
  // create FINAL data entry
  $sql=sprintf("INSERT INTO dataSTEP1_301 (uid, exptId, choice, reason, rating) "
      . "VALUES('%s', '301','%s','%s','%s')",
      $uid, $choice, $reason, $confidence);
  $igrtSqli->query($sql);
  //echo $sql;
  // set self to done    
  $xml='<message><messageType>jStateInfo</messageType><jState>doneJudging</jState></message>';
  echo $xml;

