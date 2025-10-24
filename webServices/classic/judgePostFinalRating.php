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
  $groupNo = $_POST['groupNo'];
  $npLeft = $_POST['npLeft'];
  $reason = $igrtSqli->real_escape_string($_POST['uReason']);    
  $dayNo=$_POST['dayNo'];
  // get day and session info from igActiveClassicUsers
  $sqlDS=sprintf("SELECT * FROM igActiveClassicUsers WHERE uid='%s' AND exptId='%s'", $uid, $exptId);
  $DSresult=$igrtSqli->query($sqlDS);
  if ($DSresult) {
    $rowDS=$DSresult->fetch_object();
    $dayNo=$rowDS->dayNo;
    $sessionNo=$rowDS->sessionNo;
  }
  // create FINAL data entry
  $sql=sprintf("INSERT INTO dataClassic "
      . "(owner, exptId, dayNo, sessionNo, choice, reason, "
      . "confidence, jQ, npA, pA, insertDT, groupNo, npLeft, qNo) "
      . "VALUES('%s','%s','%s','%s','%s','%s','%s','FINAL','FINAL','FINAL',NOW(),'%s', '%s', 256)",
      $uid, $exptId, $dayNo, $sessionNo, $choice, $reason, $confidence, $groupNo, $npLeft);
  $igrtSqli->query($sql);
  // set self to done
  $sqlUpdate=sprintf("UPDATE igActiveClassicUsers SET jState='4' "
      . "WHERE uid='%s' AND exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND groupNo='%s'",
      $uid, $exptId, $dayNo, $sessionNo, $groupNo);
  $igrtSqli->query($sqlUpdate);
  // set NP and P to done
  $sqlUpdate=sprintf("UPDATE igActiveClassicUsers SET respState='3' "
      . "WHERE role='NP' AND exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND groupNo='%s'",
      $exptId, $dayNo, $sessionNo, $groupNo);
  $igrtSqli->query($sqlUpdate);
  $sqlUpdate=sprintf("UPDATE igActiveClassicUsers SET respState='3' "
      . "WHERE role='P' AND exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND groupNo='%s'",
      $exptId, $dayNo, $sessionNo, $groupNo);
  $igrtSqli->query($sqlUpdate);
  $xml='<message><messageType>jStateInfo</messageType><jState>doneJudging</jState></message>';
  echo $xml;

