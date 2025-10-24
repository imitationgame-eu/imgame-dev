<?php
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
  include_once $root_path.'/domainSpecific/mySqlObject.php';      
  include_once $root_path.'/helpers/parseJSON.php';              // parse and escape JSON elements

  $rawBody = file_get_contents('php://input');
  $jSonArray = json_decode($rawBody, true);
  $days = $jSonArray['days'];
  $judges = $days[0]['sessions'][0]['judges'];
  $summary = $days[0]['summary'];
  $dataCode = $summary['dataCode'];
  $jType = $summary['jType'];
  $exptId = $summary['exptId'];
  $dayNo = $summary['dayNo'];
  $sessionNo = $summary['sessionNo'];
  $outputExample;
  $allReviewed = true;
  $reviewing = false;
  $evenDiscard = 0;
  $oddDiscard = 0;
  foreach($judges as $j) {
    $reviewing = true;
//    $jType = ($j['evenJudge'] == 1) ? 0 : 1;  // evenJudge == 1 in JSON from page means even judge 
    $jNo = $j['jNo'] - 1;
    // revert to zero-indexed when putting back to db - is 1-indexed for display on page

    $discardValue = pow(2,$jNo);
    // get judge discard info
    $discardJudge = $j['discardJudge'];
    $discardMarker = false;
    if ($discardJudge == "True") {
      if ($jType == 0) {
        $evenDiscard += $discardValue;
        $discardMarker = true;
      }
      else {
        $oddDiscard += $discardValue;
        $discardMarker = true;
      }
    }
    
    $reviewed = $j['reviewed'];
    $reviewedValue = $reviewed == "True" ? 1 : 0;
    if (($reviewedValue == 0) && ($discardMarker==false)) { $allReviewed = false; }
    // need to delete previous versions before inserting updated values
    // (avoids conditional INSERT or UPDATE)
    $deleteCmd = sprintf("DELETE FROM md_dataStep1reviewed WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND jNo='%s' AND jType='%s'",
                        $exptId,
                        $dayNo,
                        $sessionNo,
                        $jNo,   
                        $jType );
    $igrtSqli->query($deleteCmd);
    foreach($j['questions'] as $q) {
      if ($reviewedValue == 0) {
        $useQValue = 0;
      }
      else {
        $useQValue = $q['useQ'] == "use" ? 1 : 2;        
      }
      // insert updated data
      $storeCmd = sprintf("INSERT INTO md_dataStep1reviewed "
          . "(exptId, dayNo, sessionNo, jNo, jType, qNo, q, npr, pr, reviewed, canUse, "
          . "rating, reason, "
          . "iIntention, pAlignmentStr, npAlignmentStr, categoryAlignmentStr, npLeft, hasRatingInfo, choice, "
          . "pAlignmentValue, npAlignmentValue, categoryAlignmentValue) "
          . "VALUES "
          . "('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', "
          . "'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
          $exptId,
          $dayNo,
          $sessionNo,
          $jNo,   
          $jType,
          $q['index'],
          $igrtSqli->real_escape_string($q['jQ']),
          $igrtSqli->real_escape_string($q['npR']),
          $igrtSqli->real_escape_string($q['pR']),
          $reviewedValue,
          $useQValue,
          $q['rating'],
          $igrtSqli->real_escape_string($q['reason']),
          $igrtSqli->real_escape_string($q['iIntention']),
          $q['pAlignmentStr'],
          $q['npAlignmentStr'],
          $q['categoryAlignmentStr'],
          $q['npLeft'],
          1,
          $q['choice'],
          $q['pAlignmentValue'],
          $q['npAlignmentValue'],
          $q['categoryAlignmentValue'] );
      $igrtSqli->query($storeCmd);
      echo $storeCmd.';';
    }
  }
  // once all judges processed, do discards
  $discardExistsQry = sprintf("SELECT * FROM wt_Step1Discards WHERE exptId='%s'", $exptId);
  $discardExistResult = $igrtSqli->query($discardExistsQry);
  if ($discardExistResult) {
    // update
  // insert updated discard info
    $storeCmd = sprintf("UPDATE wt_Step1Discards SET evenDiscards = '%s', oddDiscards='%s' "
        . "WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s'", 
        $evenDiscard,
        $oddDiscard, 
        $exptId,
        $dayNo,
        $sessionNo);
  }
  else {
    // insert
    $storeCmd = sprintf("INSERT INTO wt_Step1Discards "
        . "(exptId, dayNo, sessionNo, evenDiscards, oddDiscards) "
        . "VALUES "
        . "('%s','%s','%s','%s','%s')",
        $exptId,
        $dayNo,
        $sessionNo,
        $evenDiscard,
        $oddDiscard  );
  }
  $igrtSqli->query($storeCmd);

// update status explicitly
  if ($jType == 0) {
    $statusUpdate = sprintf("UPDATE edSessions SET step1EvenMarked='1', step1EvenReviewing='1' WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s'",
                            $exptId, $dayNo, $sessionNo );   
  }
  else {
    $statusUpdate = sprintf("UPDATE edSessions SET step1OddMarked='1', step1OddReviewing='1' WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s'",
                            $exptId, $dayNo, $sessionNo );       
  }
  $igrtSqli->query($statusUpdate);

