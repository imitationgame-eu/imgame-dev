<?php
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
  include_once $root_path.'/domainSpecific/mySqlObject.php';     
  include_once $root_path.'/helpers/parseJSON.php';              
  $rawBody = file_get_contents('php://input');
  $jSonArray = json_decode($rawBody, true);
  
  //echo print_r($jSonArray, true);
  $summary = $jSonArray['summary'];
//  $dataCode = $summary['dataCode'];
//  $elements = explode('_', $dataCode);
  $exptId = $jSonArray['exptId'];
  $allReviewed = true;
  $step3Cnt = 0;
  
  $datasets = $jSonArray['datasets'];
  $jType = $datasets[0]['jType'];
  $clearSql = sprintf("DELETE FROM wt_Step3summariesInverted WHERE exptId='%s' AND jType='%s'",
      $exptId, $jType);
  $igrtSqli->query($clearSql);
  $clearSql = sprintf("DELETE FROM md_invertedStep2reviewed WHERE exptId='%s' AND jType='%s'",
      $exptId, $jType);
  $igrtSqli->query($clearSql);
  $clearSql = sprintf("DELETE FROM wt_Step2pptReviewsInverted WHERE exptId='%s' AND jType='%s'",
      $exptId, $jType);
  $igrtSqli->query($clearSql);
  
  foreach ($datasets as $dsi) {
    $jNo = $dsi['jNo'];
    $actualJNo = $dsi['actualJNo'];
    $dayNo = $dsi['dayNo'];
    $sessionNo = $dsi['sessionNo'];
    foreach ($dsi['ppts'] as $ppt) {
      $discardPpt = ($ppt['discardPpt'] == "True") ? 1 : 0;      
      $uid = $ppt['uid'];
      $restartUID = $ppt['restartUID'];
      $respNo = $ppt['respNo'];
      $insertPptStatus = sprintf("INSERT INTO wt_Step2pptReviewsInverted (exptId, jType, actualJNo, jNo, dayNo, sessionNo, restartUID, uid, ignorePpt, reviewed, finished, respNo) "
          . "VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
          $exptId, $jType, $actualJNo, $jNo, $dayNo, $sessionNo, $restartUID, $uid, $discardPpt, 1, 1, $respNo);
      $igrtSqli->query($insertPptStatus);
      foreach ($ppt['turns'] as $turn) {
        $q = $igrtSqli->real_escape_string($turn['question']);
        $r = $igrtSqli->real_escape_string($turn['reply']);
        $insertDataQry = sprintf("INSERT INTO md_invertedStep2reviewed "
            . "(exptId, jType, chrono, qNo, q, reply, canUse, restartUID, uid, actualJNo, respNo) "
            . "VALUES('%s', '%s', NOW(), '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
          $exptId, $jType, $turn['index'], $q, $r, ($turn['useQ'] == "use") ? 1 : 2, $restartUID, $uid, $actualJNo, $respNo);
        if (!$igrtSqli->query($insertDataQry)) {
          //$debug.= ' : '.$insertDataQry.$igrtSqli->error;
        }
      }
      // only put into wt_Step3summaries if not ignored
      if ($discardPpt == 0) {
        $addStep3summaryQry = sprintf("INSERT INTO wt_Step3summariesInverted (exptId, jType, actualJNo, restartUID, s3respNo) "
            . "VALUES('%s', '%s', '%s', '%s', '%s')",
            $exptId, $jType, $actualJNo, $restartUID, $step3Cnt);
        //$debug.= $addStep3summaryQry;
        $igrtSqli->query($addStep3summaryQry);
        ++$step3Cnt;
      }
    }
  }
//  echo 'okay';
 
