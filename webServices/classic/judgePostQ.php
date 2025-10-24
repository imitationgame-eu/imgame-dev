<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL); 
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
  include_once $root_path.'/domainSpecific/mySqlObject.php';       
  $uid = $_POST['userid'];
  $exptId = $_POST['experimentID'];
$jQ = $igrtSqli->real_escape_string($_POST['jQtext']);
$iIntention = $igrtSqli->real_escape_string($_POST['intentionText']);

  $qNo = $_POST['qNo'];
  $groupNo = $_POST['groupNo'];
  // get day and session info from igActiveClassicUsers
  $sql=sprintf("SELECT * FROM igActiveClassicUsers WHERE uid='%s' AND exptId='%s'", $uid, $exptId);
  $result=$igrtSqli->query($sql);
  if ($result) {
      $row = $result->fetch_object();
      $dayNo = $row->dayNo;
      $sessionNo=$row->sessionNo;      
  }
  $sql=sprintf("INSERT INTO dataClassic (owner,exptId,dayNo,sessionNo,insertDT,jQ,groupNo,qNo,iIntention) "
      . "VALUES('%s','%s','%s','%s',NOW(),'%s','%s','%s','%s')",
      $uid, $exptId, $dayNo, $sessionNo, $jQ, $groupNo, $qNo, $iIntention);
  $igrtSqli->query($sql);
  $sqlUpdate=sprintf("UPDATE igActiveClassicUsers SET jState='1', qNo='%s' "
      . "WHERE uid='%s' AND exptId='%s' AND dayNo='%s' AND sessionNo='%s'",
      $qNo, $uid, $exptId, $dayNo, $sessionNo);
  $igrtSqli->query($sqlUpdate);
  //now tell NP that question waiting
  $npUpdate=sprintf("UPDATE igActiveClassicUsers SET respState='1', qNo='%s' "
      . "WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND role='NP' AND groupNo='%s'",
      $qNo, $exptId, $dayNo, $sessionNo, $groupNo);
  $igrtSqli->query($npUpdate);
  //now tell P that question waiting
  $pUpdate=sprintf("UPDATE igActiveClassicUsers SET respState='1', qNo='%s' "
      . "WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND role='P' AND groupNo='%s'",
      $qNo, $exptId, $dayNo, $sessionNo, $groupNo);
  $igrtSqli->query($pUpdate);   
  $xml=sprintf("<message><messageType>jStateInfo</messageType>"
      . "<jState>waiting</jState><jQ><![CDATA[%s]]></jQ></message>", $jQ);
  echo $xml;
 
