<?php
// -----------------------------------------------------------------------------
// web service to expose JSON encoded STEP 2 data
// 
// for use by Knockout-JS Step2 reviewer
// 
// -----------------------------------------------------------------------------

ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
$permissions=$_GET['permissions'];
$uid=$_GET['uid'];
$exptId = $_GET['exptId'];
$jType = $_GET['jType'];

include_once $root_path.'/domainSpecific/mySqlObject.php';      
include_once $root_path.'/helpers/parseJSON.php';              // parse and escape JSON elements
include_once $root_path.'/helpers/debug/class.debugLogger.php';
include_once $root_path.'/helpers/models/class.experimentModel.php';

// -----------------------------------------------------------------------------
// functions
// -----------------------------------------------------------------------------

  function markedPtr($pptNo) {
    global $markedPPTs;
    $i = 0;
    foreach ($markedPPTs as $mpa) {
      if ($mpa['pptNo'] == $pptNo) { return $i; }
      ++$i;
    }
    return -1;
  }

  function countSpaces($source) {
    return substr_count($source, ' ');
//    $components = explode(' ', $source);
//    return count($components);
//    $count_var = preg_replace('[^\s]', '', $source);
//    $count = strlen($count_var);
//    return $count;
  }
  
  function getsdListPtr($haystack, $needle) {
    $i = 0;
    while ( $i < count($haystack) ) {
      if ( ($needle['dayNo'] == $haystack[$i]['dayNo']) && ($needle['sessionNo'] == $haystack[$i]['sessionNo']) && ($needle['jNo'] == $haystack[$i]['jNo']) ) { return $i; }
      ++$i;
    }
    return -1;
  }

  function prettyPrintPPTs($PPTs) {
    $html = '';
    foreach ($PPTs as $ppt) {
      $formattedStr = sprintf("pptNo: %s   jNo: %s", $ppt['pptNo'], $ppt['jNo']);
      $html.= '<br />'.$formattedStr;
      foreach ($ppt['turns'] as $turn) {
        $formattedTurn = sprintf("q: %s  reply: %s", $turn['question'], $turn['reply']);
        $html.= '<br />'.$formattedTurn;
      }
    }
    return $html;
  }
  
  function storeContiguousReview($exptId, $jType, $dsList) {
    global $igrtSqli;
    $clearSummariesSql = sprintf("DELETE FROM wt_Step2summaries WHERE exptId='%s' AND jType='%s'", $exptId, $jType);
    $igrtSqli->query($clearSummariesSql);
    $clearReviewsSql = sprintf("DELETE FROM wt_Step2pptReviews WHERE exptId='%s' AND jType='%s'", $exptId, $jType);
    $igrtSqli->query($clearReviewsSql);
    $clearMarkedDataSql = sprintf("DELETE FROM md_dataStep2reviewed WHERE exptId='%s' AND jType='%s'", $exptId, $jType);
    $igrtSqli->query($clearMarkedDataSql);
    foreach ($dsList as $dsi) {
      $pptCnt = 0;
      $jNo = $dsi['jNo'];
      $actualJNo = $dsi['actualJNo'];
      $dayNo = $dsi['dayNo'];
      $sessionNo = $dsi['sessionNo'];
      foreach ($dsi['ppts'] as $ppt) {
        $respNo = $ppt['respNo'];
        $uid = $ppt['uid'];
        $restartUID = $ppt['restartUID'];
        $ignorePpt = $ppt['ignorePpt'];
        foreach ($ppt['turns'] as $turn) {
          $q = $igrtSqli->real_escape_string($turn['question']);
          $r = $igrtSqli->real_escape_string($turn['reply']);
          $insertDataQry = sprintf("INSERT INTO md_dataStep2reviewed "
              . "(uid, exptId, jType, chrono, reviewedRespNo, respNo, qNo, q, reply, canUse, actualJNo, restartUID) "
              . "VALUES('%s', '%s', '%s', NOW(), '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
            $uid, $exptId, $jType, $pptCnt, $respNo, $turn['qNo'], $q, $r, $turn['canUse'], $actualJNo, $restartUID);
          $igrtSqli->query($insertDataQry);
        }
        $reviewed = 1;
        $finished = $ppt['pptFinished'];
        $isVirtual = $ppt['isVirtual'];
        $updatePptReviews = sprintf("INSERT INTO wt_Step2pptReviews "
            . "(exptId, jType, actualJNo, jNo, dayNo, sessionNo, reviewedRespNo, respNo, ignorePpt, reviewed, finished, isVirtual) "
            . "VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
            $exptId, $jType, $actualJNo, $jNo, $dayNo, $sessionNo, $pptCnt, $respNo, $ignorePpt, $reviewed, $finished, $isVirtual);
        $igrtSqli->query($updatePptReviews);
        //echo $updatePptReviews;
        ++$pptCnt;
      }
      // ensure correct counts put into initial summaries
      // (final summaries are placed into wt_Step3summaries for use in shuffle at another point)
      $insertSummarySql = sprintf("INSERT INTO wt_Step2summaries (exptId, jType, dayNo, sessionNo, jNo, pptCnt, actualJNo) "
          . "VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s')",
          $exptId, $jType, $dayNo, $sessionNo, $jNo, $pptCnt, $actualJNo);
      $igrtSqli->query($insertSummarySql);
    }
  }

//  function getUnreviewedData($exptId, $jType) {
//    global $igrtSqli;
//    // get list of data sets and build up list of complete ppts
//    $dsQry = sprintf("SELECT * FROM wt_Step2Balancer WHERE exptId='%s' AND jType='%s' ORDER BY actualJNo ASC",
//        $exptId, $jType);
//    $dsResult = $igrtSqli->query($dsQry);
//    //echo $dsQry.';';
//    $dsList = array();
//    while ($dsRow = $dsResult->fetch_object()) {
//      $dayNo = $dsRow->dayNo;
//      $sessionNo = $dsRow->sessionNo;
//      $actualJNo = $dsRow->actualJNo;
//      $jNo = $dsRow->jNo;
//      $dsDef = array(
//        'exptId' => $exptId,
//        'jType' => $jType,
//        'dayNo' => $dayNo,
//        'sessionNo' => $sessionNo,
//        'jNo' => $jNo,
//        'actualJNo' => $actualJNo,
//        'ppts' => array(),
//      );
//      if (isset($pptList)) { unset($pptList); }
//      $pptList = array();
//      // now make array of complete and incomplete pptNo mappings
//      $pptQry = sprintf("SELECT DISTINCT(pptNo) AS pptNo FROM dataSTEP2 WHERE exptId='%s' "
//          . "AND jType='%s' AND dayNo='%s' AND sessionNo='%s' AND jNo='%s' ORDER BY pptNo ASC",
//          $exptId, $jType, $dayNo, $sessionNo, $jNo);
//      $pptListResult = $igrtSqli->query($pptQry);
//      // echo $pptQry.';';
//      //$debug = $qnQry.':'.$pptQry;
//      while ($pptListRow = $pptListResult->fetch_object()) {
//        $pptNo = $pptListRow->pptNo;
//        $respondentStatus = sprintf("SELECT * FROM wt_Step2pptStatus WHERE "
//            . "exptId='%s' AND jType='%s' AND actualJNo='%s' AND respNo='%s'",
//            $exptId, $jType, $actualJNo, $pptNo);
//        //echo $respondentStatus.';';
//        $respondentResult = $igrtSqli->query($respondentStatus);
//        if ($respondentResult->num_rows == 0) { echo 'houston'; }
//        $respondentRow = $respondentResult->fetch_object();
//        $discardPpt = $respondentRow->discarded;
//        // this ppt can be used
//        $pptFinished = $respondentRow->finished;
//        $uid = $respondentRow->id;
//        $restartUID = $respondentRow->restartUID;
//        $userCode = $respondentRow->userCode;
//        // 05/04/2014 - changed so that even unfinished respondents are included
//        $pptDef = array(
//          'respNo' => $pptNo,
//          'ignorePpt' => $discardPpt,
//          'pptFinished' => $pptFinished,
//          'isVirtual' => 0,
//          'marked' => 1,
//          'wordCnt' => 0,
//          'uid' => $uid,
//          'restartUID' => $restartUID,
//          'turns' => array(),
//        );
//        // now attach turns and wordcounts to each complete ppt
//        if (isset($turns)) { unset($turns); }
//        $turns = array();
//        $wordCnt = 0;
//        // get questions first, and then build replies
//        $qQry = sprintf("SELECT * FROM md_dataStep1reviewed WHERE exptId='%s' AND jType='%s' AND "
//            . "dayNo='%s' AND sessionNo='%s' AND jNo='%s' AND canUse>0 ORDER BY qNo ASC",
//            $exptId, $jType, $dayNo, $sessionNo, $jNo);
//        $qResult = $igrtSqli->query($qQry);
//        //echo $qQry.';';
//        while ($qRow = $qResult->fetch_object()) {
//          $canUse = $qRow->canUse;
//          if ($canUse == 1) {
//            $question = $qRow->q;
//            $qNo = $qRow->qNo;
//            $replyQry = sprintf("SELECT * FROM dataSTEP2 WHERE exptId='%s' AND jType='%s' "
//                . "AND dayNo='%s' AND sessionNo='%s' AND jNo='%s' AND pptNo='%s' AND qNo='%s'",
//                $exptId, $jType, $dayNo, $sessionNo, $jNo, $pptNo, $qNo);
//            //echo $replyQry.';';
//            $replyResult = $igrtSqli->query($replyQry);
//            $reply = '';
//            if ($replyResult->num_rows > 0) {
//              while ($replyRow = $replyResult->fetch_object()) {
//                if (strlen($replyRow->reply) > strlen($reply)) { $reply = $replyRow->reply; }
//              }
//              $wordCnt+= countSpaces($reply);
//            }
//            $canUse = 1;
//            $turnDef = array (
//              'pptNo' => $pptNo,
//              'qNo' => $qNo,
//              'canUse' => $canUse,
//              'reply' => $reply,
//              'question' => $question
//            );
//            array_push($turns, $turnDef);
//          }
//        }
//        $pptDef['wordCnt'] = $wordCnt;
//        $pptDef['turns'] = $turns;
//        array_push($pptList, $pptDef);
//      }
//      $dsDef['ppts'] = $pptList;
//      array_push($dsList, $dsDef);
//    }
//    return $dsList;
//  }
  
  function getReviewedData($exptId, $jType) {
   global $igrtSqli;
    // get list of data sets and build up list of complete ppts
    $dsQry = sprintf("SELECT * FROM wt_Step2Balancer WHERE exptId='%s' AND jType='%s' ORDER BY actualJNo ASC",
        $exptId, $jType);
    $dsResult = $igrtSqli->query($dsQry);
    $dsList = array();
    while ($dsRow = $dsResult->fetch_object()) {
      $dayNo = $dsRow->dayNo;
      $sessionNo = $dsRow->sessionNo;
      $actualJNo = $dsRow->actualJNo;
      $jNo = $dsRow->jNo;
      $dsDef = array(
        'exptId' => $exptId,
        'jType' => $jType,
        'dayNo' => $dayNo,
        'sessionNo' => $sessionNo,
        'jNo' => $jNo,
        'actualJNo' => $actualJNo,
        'ppts' => array(),
      );    
      if (isset($pptList)) { unset($pptList); }
      $pptList = array();
      // don't need to check for equal qCnt, as this is done when getting unreviewed data only
      // now make array of complete and incomplete pptNo mappings
      $pptQry = sprintf("SELECT DISTINCT(reviewedRespNo) AS reviewedRespNo FROM md_dataStep2reviewed WHERE exptId='%s' "
          . "AND jType='%s' AND actualJNo='%s' ORDER BY reviewedRespNo ASC",
          $exptId, $jType, $actualJNo);
      $pptListResult = $igrtSqli->query($pptQry);
			$reviewedRespNo = 0; // in case there is no reviewed data yet
      while ($pptListRow = $pptListResult->fetch_object()) {
        $reviewedRespNo = $pptListRow->reviewedRespNo;
        $respNoQry = sprintf("SELECT respNo,uid,restartUID FROM md_dataStep2reviewed WHERE exptId='%s' "
          . "AND jType='%s' AND actualJNo='%s' AND reviewedRespNo='%s'",
          $exptId, $jType, $actualJNo, $reviewedRespNo);
        $respNoResult = $igrtSqli->query($respNoQry);
        $respNoRow = $respNoResult->fetch_object();
        $respNo = $respNoRow->respNo;
        $uid = $respNoRow->uid;
        $restartUID = $respNoRow->restartUID;
        $pptDef = [
          'respNo' => $respNo,
          'newData' => 0,
          'reviewedRespNo' => $reviewedRespNo,
          'ignorePpt' => 0,
          'marked' => 1, 
          'wordCnt' => 0,
          'uid' => $uid,
          'restartUID' => $restartUID,
          'finished' => 1,
          'isVirtual'=> false,
          'turns' => []
        ];
        // get reviewed/ignore status from wt_Step2pptReviews - if it doesn't yet exist make default values
        $ignoreQry = sprintf("SELECT * FROM wt_Step2pptReviews WHERE exptId='%s' AND jType='%s' AND actualJNo='%s' AND respNo='%s'", 
            $exptId, $jType, $actualJNo, $respNo);
        $ignoreResults = $igrtSqli->query($ignoreQry);
        $ignoreRow = $ignoreResults->fetch_object();
        if (is_null($ignoreRow)) {
	        $pptDef['ignorePpt'] = 0;
	        $pptDef['marked'] = 1;
	        $pptDef['pptFinished'] = 1;
	        $pptDef['isVirtual'] = 1;

        }
        else {
	        $pptDef['ignorePpt'] = $ignoreRow->ignorePpt;
	        $pptDef['marked'] = $ignoreRow->reviewed;
	        $pptDef['pptFinished'] = $ignoreRow->finished;
	        $pptDef['isVirtual'] = $ignoreRow->isVirtual;
        }
        // get restartUID as the one in md_dataStep2reviewed is 0 for some reason
            $pptStatusQry = sprintf("SELECT * FROM wt_Step2pptStatus WHERE exptId='%s' AND jType='%s' AND actualJNo='%s' AND respNo='%s'",
                $exptId, $jType, $actualJNo, $respNo);
            //echo $pptStatusQry;
            $pptStatusResult = $igrtSqli->query($pptStatusQry);
            if ($pptStatusResult->num_rows > 0) {
              $pptStatusRow = $pptStatusResult->fetch_object();
              $restartUID = $pptStatusRow->restartUID; 
              $userCode = $pptStatusRow->userCode;
            }
        $pptDef['restartUID'] = $restartUID;
        $pptDef['userCode'] = $userCode;
        // now attach turns and wordcounts to each complete ppt 
        if (isset($turns)) { unset($turns); }
        $turns = array();
        $turnQry = sprintf("SELECT * FROM md_dataStep2reviewed WHERE exptId='%s' AND jType='%s' AND actualJNo='%s' AND respNo='%s' ORDER BY qNo ASC",
          $exptId, $jType, $actualJNo, $respNo);
        //echo $turnQry;
        $turnResult = $igrtSqli->query($turnQry);
        $wordCnt = 0;
        //echo $igrtSqli->affected_rows;
        while ($turnRow = $turnResult->fetch_object()) {
          //echo $turnRow->qNo;
          $qNo = $turnRow->qNo;
          //echo $qNo;
          $canUse = $turnRow->canUse;
          $reply = $turnRow->reply;
          $question = $turnRow->q;
          $wordCnt += countSpaces($turnRow->reply);
          // this is really expensive, but in a hurry  TODO
          $turnDef = array (
            'pptNo' => -1,
            'qNo' => $qNo,
            'canUse' => $canUse,
            'reply' => $reply,
            'question' => $question
          );    
          array_push($turns, $turnDef);
        }
        $pptDef['wordCnt'] = $wordCnt;
        $pptDef['turns'] = $turns;
        array_push($pptList, $pptDef);
      }
      // now get any new unreviewed data
      $pptStatusQry = sprintf("SELECT * FROM wt_Step2pptStatus WHERE exptId='%s' AND jType='%s' AND actualJNo='%s' ORDER BY respNo ASC",
          $exptId, $jType, $actualJNo);
      $pptStatusResult = $igrtSqli->query($pptStatusQry);
      if ($pptStatusResult->num_rows > 0) {
        while ($pptStatusRow = $pptStatusResult->fetch_object()) {
          $discardedPpt = $pptStatusRow->discarded;
          $respNo = $pptStatusRow->respNo;
          $uid = $pptStatusRow->id;
          $restartUID = $pptStatusRow->restartUID;            
          $userCode = $pptStatusRow->userCode;            
          if (notProcessed($pptList, $respNo)) {
          	if (canProcessNewRespNo($respNo, $exptId, $jType, $dayNo, $sessionNo, $jNo)) {
							$pptDef = [
								'respNo' => $respNo,
								'newData' => 1,
								'reviewedRespNo' => ++$reviewedRespNo,
								'ignorePpt' => $discardedPpt,
								'marked' => 0,
								'wordCnt' => 0,
								'uid' => $uid,
								'restartUID' => $restartUID,
								'userCode' => $userCode,
								'finished' => 1,
								'isVirtual'=> false,
								'turns' => []
							];
							$pptDef['turns'] = processNewRespNo($pptDef, $exptId, $jType, $dayNo, $sessionNo, $jNo, $wordCnt);
							$pptDef['wordCnt'] = $wordCnt;
							array_push($pptList, $pptDef);

						}
          }
        }
      }
      $dsDef['ppts'] = $pptList;
      array_push($dsList, $dsDef);
    }
    return $dsList;
  }

  function canProcessNewRespNo($respNo, $exptId, $jType, $dayNo, $sessionNo, $jNo) {
		global $igrtSqli;
		$getStep1Qry = sprintf("SELECT * FROM md_dataStep1reviewed WHERE exptId='%s' AND jType='%s' AND "
			. "dayNo='%s' AND sessionNo='%s' AND jNo='%s' AND canUse=1 AND q!= 'FINAL' ORDER by qNo ASC",
			$exptId, $jType, $dayNo, $sessionNo, $jNo);
		$getStep1Result = $igrtSqli->query($getStep1Qry);
		$noOfQuestions = $getStep1Result->num_rows;
		$getReplyQry = sprintf("SELECT * FROM dataSTEP2 WHERE exptId='%s' AND jType='%s' AND dayNo='%s'"
			. " AND sessionNo='%s' AND jNo='%s' AND pptNo='%s'",
			$exptId, $jType, $dayNo, $sessionNo, $jNo, $respNo);
		$getReplyResult = $igrtSqli->query($getReplyQry);
		$noOfReplies = $getReplyResult->num_rows;
		return $noOfQuestions == $noOfReplies;
	}
  
  function processNewRespNo($pptDef, $exptId, $jType, $dayNo, $sessionNo, $jNo, &$wordCnt) {
    global $igrtSqli;
    $turns = array();
    $wordCnt = 0;
    $pptNo = $pptDef['respNo'];
    $getStep1Qry = sprintf("SELECT * FROM md_dataStep1reviewed WHERE exptId='%s' AND jType='%s' AND "
        . "dayNo='%s' AND sessionNo='%s' AND jNo='%s' AND canUse=1 AND q!='FINAL' ORDER by qNo ASC",
        $exptId, $jType, $dayNo, $sessionNo, $jNo);
    $getStep1Result = $igrtSqli->query($getStep1Qry);
    if ($getStep1Result->num_rows > 0) {
    	$sequentialQNo = 0;	// answers are stored in dataSTEP2 sequentially to take account of discarded STEP1 questions
      while ($getStep1Row = $getStep1Result->fetch_object()) {
        $qNo = $getStep1Row->qNo;
        $q = $getStep1Row->q;
        $turnDef = array (
          'pptNo' => $pptNo,
          'qNo' => $qNo,
          'canUse' => 1,  
          'reply' => '',
          'question' => $q
        );
        // get reply
        $getReplyQry = sprintf("SELECT * FROM dataSTEP2 WHERE exptId='%s' AND jType='%s' AND dayNo='%s'"
            . " AND sessionNo='%s' AND jNo='%s' AND pptNo='%s' AND qNo='%s'",
            $exptId, $jType, $dayNo, $sessionNo, $jNo, $pptNo, $sequentialQNo++);
        $getReplyResult = $igrtSqli->query($getReplyQry);
        if ($getReplyResult->num_rows > 0) {
          $getReplyRow = $getReplyResult->fetch_object();
          $turnDef['reply'] = $getReplyRow->reply;
          $wordCnt += countSpaces($turnDef['reply']);
        }
        array_push($turns, $turnDef);
      }
    }
    return $turns;
  }
  
  function notProcessed($pptList, $respNo) {
    for ($i=0; $i < count($pptList); $i++) {
      if ($pptList[$i]['respNo'] == $respNo) { return false; } 
    }
    return true;
  }
  
// -----------------------------------------------------------------------------
// end of functions
// -----------------------------------------------------------------------------

// allow use of web-service
if ($permissions >= 128) {
  // check for previously marked data
  $hasMarkedData = false;
  $mdQry = sprintf("SELECT * FROM md_dataStep2reviewed WHERE exptId='%s' AND jType='%s'",
      $exptId, $jType);
  $mdResult = $igrtSqli->query($mdQry);
  if ($mdResult->num_rows > 0) { $hasMarkedData = true; }
	$dsList = getReviewedData($exptId, $jType); // getReviewedData also gets new un-reviewed data, so will get all unreviewed for initial review
	storeContiguousReview($exptId, $jType, $dsList);

	$eModel = new experimentModel($exptId);
  $oddS1Label = $eModel->oddS1Label;
  $evenS1Label = $eModel->evenS1Label;
  $operationLabel = $jType == 0 ? "$oddS1Label pretending to be $evenS1Label" : "$evenS1Label pretending to be $oddS1Label";
  
//<!--  // rank order by wordcount within each question set
  $jsonArray = array();
  foreach ($dsList as $dsi) {
    usort($dsi['ppts'], function($a, $b) { return $a["wordCnt"] - $b["wordCnt"]; });
    array_push($jsonArray, $dsi);    
  }
//  echo print_r($jsonArray, true);
//  $jsonArray = $dsList;
  $jCnt = 0; 
  $totalQ = 0;
  $totalReviewed = 0;
  $allReviewed = true;
  // inject into JSON structure
  $jSonRep='{'; 
    $jSonRep.="\"datasets\":[";
      $dsCnt = 0;
      foreach ($jsonArray as $ds) {   //DO datasets
        if ($dsCnt++ > 0) { $jSonRep .= ","; }
        $jSonRep .= sprintf("{\"dayNo\":%s, \"sessionNo\":%s, \"jNo\":%s, \"actualJNo\":%s, \"jType\":%s,", $ds['dayNo'], $ds['sessionNo'], $ds['jNo'], ($ds['actualJNo']), $jType);
        $jSonRep.="\"ppts\":[";
        $jCnt = 0;
        $actualJNo = $ds['actualJNo'];
        foreach ($ds['ppts'] as $umi) {   //DO ppts 
          if ($jCnt++ > 0) { $jSonRep.=","; }
          $jSonRep.=sprintf("{\"wordCnt\":%s, \"reviewedRespNo\":%s, \"respNo\":%s, \"jNoLabel\":%s, \"reviewed\":%s, "
              . "\"finished\":\"%s\", \"discardPpt\":\"%s\",", 
              $umi['wordCnt'], 
              $umi['reviewedRespNo'], 
              $umi['respNo'], 
              $actualJNo,
              $umi['newData'],
              ($umi['finished'] == 1) ? "finished" : "not finished",
              ($umi['ignorePpt'] == 1) ? "True" : "" ); 
          $jSonRep.= "\"uid\":".$umi['uid'].",";
					$jSonRep.= "\"isVirtual\":0,";
					//$jSonRep.= "\"isVirtual\":".$umi['isVirtual'].",";
          $jSonRep.= "\"newData\":".$umi['newData'].",";
          $jSonRep.= "\"restartUID\":".$umi['restartUID'].",";
          $jSonRep.= "\"pptLabel\":". JSONparse("s2_".$exptId."_".$jType."_".$actualJNo."_".$umi['uid']."_".$umi['restartUID']."_".$umi['respNo']). ",";            
          // iterate over questions and replies
          $jSonRep.= "\"turns\":[";
          $qCnt = 0;
          $warning = false;
          foreach($umi['turns'] as $turn) {
            if ($qCnt > 0) { $jSonRep.=","; }
            $qIndex = $turn['qNo']; // keep real qNo
            $jSonRep.=sprintf("{\"question\":%s,", JSONparse($turn['question']));
            $jSonRep.=sprintf("\"reply\":%s,", JSONparse($turn['reply']));
            if ($turn['reply'] == '') { $warning = true; }
            switch ($turn['canUse']) {
              case 0: { $cuStr=" {},"; break; }
              case 1: { $cuStr="\"use\","; break; }
              case 2: { $cuStr="\"discard\","; break; }
            }
            $jSonRep.="\"useQ\": ".$cuStr; 
            $jSonRep .= "\"index\":".$qIndex."}";
            ++$qCnt;      
          }
          $totalQ += $qCnt;
          $jSonRep.="],";   // close turns array
          if ($warning) {
             $jSonRep.= "\"warning\":\"missing replies!\",";
          }
          else {
             $jSonRep.= "\"warning\":\" \",";            
          }
          $jSonRep .= "\"dummyPpt\":0";
          $jSonRep .= "}";  // close ppt def
        }
      $jSonRep .= "]}";    //close ppts array within dataset        
      }
    $jSonRep .= "],";    // close datasets array
    $jSonRep.="\"summary\":{";                                                //  add summary
    $jSonRep.=sprintf("\"dataCode\": %s,", JSONparse($operationLabel));
    $jSonRep.=sprintf("\"totalPPts\": %s,", $jCnt);
    $jSonRep.=sprintf("\"exptId\": %s,", $exptId);
    $jSonRep.=sprintf("\"jType\": %s,", $jType);
    $jSonRep.=sprintf("\"totalQuestions\": %s,", $totalQ);
    $jSonRep.=sprintf("\"totalReviewed\": %s,", $totalReviewed);
    $jSonRep.=sprintf("\"allReviewed\": %s", ($allReviewed == false) ? 0 : 1);
    $jSonRep .= "}";    // close summary
  $jSonRep.="}";        //  close JSON
  echo $jSonRep;
}  
else {
  echo '{"days:"[]}'; //' nominal json object that doesn't help anyone
}


