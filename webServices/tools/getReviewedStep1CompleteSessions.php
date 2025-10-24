<?php
// -----------------------------------------------------------------------------
// 
// web service to retrieve raw data from completed sessions
// raw backup service in case Steps 2 or 4 not required
// better to use review and printer-friendly button to get properly 
// encoded content.
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
      $jType = $row->jType;
      $dispjType = ($jType == 1) ? "X" : "Y";
      $jNo= $row->jNo;
      $finalReason = $row->reason;
      $finalRating = $row->rating;
      $finalChoice = $row->choice;
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
        echo "Day $dayNo, Session $sessionNo, jType=$dispjType, jNo=$jnoStr, email=$email, side: $side<hr />";
        $gameSetSql = sprintf("SELECT * FROM md_dataStep1reviewed WHERE exptId='%s' AND jType='%s' AND jNo='%s' AND dayNo='%s' AND sessionNo='%s'",
                              $exptId, $jType, $jNo, $dayNo, $sessionNo);
        //echo $gameSetSql;
        $gameSetResults = $igrtSqli->query($gameSetSql);
        
        if ($gameSetResults->numd_rows > 0) {
          $qNo = 0;
          while ($gameSetRow = $gameSetResults->fetch_object()) {
            ++$qNo;
            //echo "EVERY discardQ info ".$discardQRow->canUse;
            if ($gameSetRow->canUse == 1) {            
              if ($cb == 0) {
                if ($row->choice == 1) {$sel="NPR";} else {$sel="PR";}
              }
              else {
                if ($row->choice == 0) {$sel="NPR";} else {$sel="PR";}
              }
              echo "Q$gameSetRow->qNo: $gameSetRow->q <br />";
              echo "NPR$gameSetRow->qNo: $gameSetRow->npr <br />";
              echo "PR$gameSetRow->qNo: $gameSetRow->pr <br />";
              echo "choice$gameSetRow->qNo: $sel <br />";
              echo "rating$gameSetRow->qNo: $gameSetRow->rating <br />";
              echo "reason$gameSetRow->qNo: $gameSetRow->reason <br />";            
            }
            else {
            }
          }
          echo "Final Rating: <br />";
          echo "choice$gameSetRow->qNo: $finalChoice <br />";
          echo "rating$gameSetRow->qNo: $finalRating <br />";
          echo "reason$gameSetRow->qNo: $finalReason <br /><hr />";            

        }
      }
    }
  }
 }
else {
    echo 'not authorised';
}

