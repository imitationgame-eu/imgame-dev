<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
  include_once $root_path.'/domainSpecific/mySqlObject.php';      
  include_once $root_path.'/helpers/parseJSON.php';              // parse and escape JSON elements
  $rawBody = file_get_contents('php://input');
  $jSonArray = json_decode($rawBody, true);
  
  //echo print_r($jSonArray, true);
  $summary = $jSonArray['summary'];
  $dataCode = $summary['dataCode'];
  $exptId = $summary['exptId'];
  $jType = $summary['jType'];
  $allReviewed = true;
  $debug = '';
  
  
  $datasets = $jSonArray['datasets'];
  $clearSql = sprintf("DELETE FROM wt_Step3summaries WHERE exptId='%s' AND jType='%s'",
      $exptId, $jType);
  $igrtSqli->query($clearSql);
  $clearSql = sprintf("DELETE FROM md_dataStep2reviewed WHERE exptId='%s' AND jType='%s'",
      $exptId, $jType);
  $igrtSqli->query($clearSql);
  foreach ($datasets as $dsi) {
    $step3Cnt = 0;  // we use incrementing step3Cnt to give conjunct list of pptNo in case of gaps
                  // in the pptNo from original raw data, and if a ppt is ignored during marking
    $jNo = $dsi['jNo'];
    $actualJNo = $dsi['actualJNo'];
    $dayNo = $dsi['dayNo'];
    $sessionNo = $dsi['sessionNo'];
    foreach ($dsi['ppts'] as $ppt) {
      $discardPpt = ($ppt['discardPpt'] == "True") ? 1 : 0;      
      $respNo = $ppt['respNo'];
      $reviewedRespNo = $ppt['reviewedRespNo'];
      $isVirtual = $ppt['isVirtual'];
      // set discard in Reviews - this is used when reloading review page
      $updatePptStatus = sprintf("UPDATE wt_Step2pptReviews "
          . "SET ignorePpt='%s' WHERE exptId='%s' AND jType='%s' AND actualJNo='%s' AND reviewedRespNo='%s' AND respNo='%s'",
          $discardPpt, $exptId, $jType, $actualJNo, $reviewedRespNo, $respNo);
      $igrtSqli->query($updatePptStatus); 
      // reflect discard status in wt_Step2pptStatus - used in shuffle 
      $updatePptStatus = sprintf("UPDATE wt_Step2pptStatus "
          . "SET discarded='%s' WHERE exptId='%s' AND jType='%s' AND actualJNo='%s' AND respNo='%s'",
          $discardPpt, $exptId, $jType, $actualJNo, $respNo);
      $igrtSqli->query($updatePptStatus); 
      foreach ($ppt['turns'] as $turn) {
        $q = $igrtSqli->real_escape_string($turn['question']);
        $r = $igrtSqli->real_escape_string($turn['reply']);
        $insertDataQry = sprintf("INSERT INTO md_dataStep2reviewed "
            . "(exptId, jType, chrono, qNo, q, reply, canUse, respNo, reviewedRespNo, actualJNo) "
            . "VALUES('%s', '%s', NOW(), '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
          $exptId, $jType, $turn['index'], $q, $r, ($turn['useQ'] == "use") ? 1 : 2, $respNo, $reviewedRespNo, $actualJNo);
        if (!$igrtSqli->query($insertDataQry)) {
          //$debug.= ' : '.$insertDataQry.$igrtSqli->error;
        }
      }
      // only put into wt_Step3summaries if not ignored
      if ($discardPpt == 0) {
        $addStep3summaryQry = sprintf("INSERT INTO wt_Step3summaries (exptId, jType, actualJNo, respNo, s3respNo, isVirtual) "
            . "VALUES('%s', '%s', '%s', '%s', '%s', '%s')",
            $exptId, $jType, $actualJNo, $respNo, $step3Cnt, $isVirtual);
        //$debug.= $addStep3summaryQry;
        $igrtSqli->query($addStep3summaryQry);
        ++$step3Cnt;
      }
    }
  }
  //echo 'final - ' . $insertDataQry;
 
