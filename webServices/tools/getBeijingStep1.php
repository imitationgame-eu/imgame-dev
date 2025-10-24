<?php
// -----------------------------------------------------------------------------
// 
// web service to retrieve raw data from completed sessions
// 
// -----------------------------------------------------------------------------

if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
$permissions=$_GET['permissions'];
$uid=$_GET['uid'];
$exptId=$_GET['exptId'];
$uidArray = array();



if (($uid==28) && ($permissions==1024)) {
  header('Content-type: text/html charset=utf-8');
  
  
  //echo 'data extract';
  include_once $root_path.'/domainSpecific/mySqlObject.php';      
  // firstly find each unique uid in jUid field
  $gameSql=sprintf("SELECT * FROM dataSTEP1 WHERE exptId='%s' AND q='FINAL' ORDER BY dayNo ASC, sessionNo ASC, jType ASC, jNo ASC", $exptId);
  $gameResults=$igrtSqli->query($gameSql);
  if ($gameResults) {
    while ($row=$gameResults->fetch_object()) {
      $jUid = $row->uid;
      $login = $jUid + 1;
      $dayNo = $row->dayNo;
      $sessionNo = $row->sessionNo;
      $jType = $row->jType == 1 ? "Odd" : "Even";
      $jNo= $row->jNo;
      $finalQNo = $row->qNo;
      if ($jNo % 2==0)  {
        $cb = 1;
        $side="NPR on left";
      }
      else {
        $cb = 0;
        $side="NPR on right";
      }
      $jnoStr = $jNo+1;

      $getSummary = sprintf("SELECT * FROM wt_Step1Discards WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s'", $exptId, $dayNo, $sessionNo);
      $summaryResults = $igrtSqli->query($getSummary);
      if ($summaryResults) {
        $summaryRow = $summaryResults->fetch_object();
        $evenDiscards = $summaryRow->evenDiscards;
        $oddDiscards = $summaryRow->oddDiscards;            
      }
      else {
        $evenDiscards = 0;
        $oddDiscards = 0;
      }
      $discardMarker = pow(2, $jNo);
      $discardType = ($jType == 1) ? $evenDiscards : $oddDiscards;
      if ( ($discardType & $discardMarker) != $discardMarker) {
        $emailQry = "SELECT * FROM igUsers WHERE id=$jUid";
        $eResult = $igrtSqli->query($emailQry);
        $eRow = $eResult->fetch_object();
        $email = $eRow->email;
        
        
        echo "Day $dayNo, Session $sessionNo, jType=$jType, jNo=$jnoStr, email=$email, side: $side<hr />";
        $gameSetSql = sprintf("SELECT * FROM dataSTEP1 WHERE exptId='%s' AND uid='%s' AND dayNo='%s' AND sessionNo='%s' ORDER BY qNo", $exptId, $jUid, $dayNo, $sessionNo);
        //echo $gameSetSql;
        $gameSetResults = $igrtSqli->query($gameSetSql);
        if ($gameSetResults) {
          echo '';
          while ($gameSetRow = $gameSetResults->fetch_object()) {
            $qNo = $gameSetRow->qNo;
              if ($cb == 0) {
                if ($row->choice == 1) {$sel="NPR";} else {$sel="PR";}
              }
              else {
                if ($row->choice == 0) {$sel="NPR";} else {$sel="PR";}
              }
              if ($gameSetRow->qNo == $finalQNo) {
                //$reason = rawurlencode
                $fr = rawurlencode($gameSetRow->q);
                echo "Final Rating: $fr <br />";
                echo "choice$gameSetRow->qNo: $sel <br />";
                echo "rating$gameSetRow->qNo: $gameSetRow->rating <br />";
                echo "reason$gameSetRow->qNo: $gameSetRow->reason <br /><hr />";            
              }
              else {
                $q = rawurlencode($gameSetRow->q);
                echo "Q$gameSetRow->qNo: $q <br />";
                echo "NPR$gameSetRow->qNo: $gameSetRow->npr <br />";
                echo "PR$gameSetRow->qNo: $gameSetRow->pr <br />";
                echo "choice$gameSetRow->qNo: $sel <br />";
                echo "rating$gameSetRow->qNo: $gameSetRow->rating <br />";
                //echo "reason$gameSetRow->qNo: $gameSetRow->reason <br />";            
              }
          }
        }
      }
    }
  }
 }
else {
    echo 'not authorised';
}

