<?php
// -----------------------------------------------------------------------------
// web service to expose JSON encoded STEP 1 data
// 
// can be used either in review page or raw download page
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
$permissions=$_GET['permissions'];
$uid=$_GET['uid'];
$exptId = $_GET['exptId'];
$dayNo = $_GET['dayNo'];
$sessionNo = $_GET['sessionNo'];
$choosingNP = -1;
//$buttonAction = $_GET['buttonAction'];  // 0 = get complete from raw data, 
//                                        // 1 = get complete from reviewed data 
$jType = $_GET['jType'];                // for ease of review, only Odd or Even judges are shown in one page
                                        // 0 == Even, 1 == Odd

include_once $root_path.'/domainSpecific/mySqlObject.php';      
include_once $root_path.'/helpers/parseJSON.php';              // parse and escape JSON elements
include_once $root_path.'/helpers/models/class.experimentModel.php';

function getDataStatus($exptId, $jType, $dayNo, $sessionNo) {
  global $igrtSqli;
  $statusArray = [];
  $qry = sprintf("SELECT * FROM edSessions WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s'", $exptId, $dayNo, $sessionNo);
  $result = $igrtSqli->query($qry);
  if ($result) {
    $row = $result->fetch_object();
    // note that injected experiments may have data in the reviewed data tables but not the raw data tables
    $markedDataQry = sprintf("SELECT * FROM md_dataStep1reviewed WHERE exptId='%s' AND jType='%s' AND dayNo='%s' AND sessionNo='%s'", $exptId, $jType, $dayNo, $sessionNo);
    $result = $igrtSqli->query($markedDataQry);
    $hasMarked = $result ? ($result->num_rows > 0 ? true : false) : false;
    $rawDataQry = sprintf("SELECT * FROM dataSTEP1 WHERE exptId='%s' AND jType='%s' AND dayNo='%s' AND sessionNo='%s'", $exptId, $jType, $dayNo, $sessionNo);
    $result = $igrtSqli->query($rawDataQry);
    $hasRaw = $result ? ($result->num_rows > 0 ? true : false) : false;
    $statusArray = [
      'hasData' => true,
      'hasMarked'=>$hasMarked,
      'hasRaw'=>$hasRaw,
      'previouslyMarked'=> $jType == 0 ? $row->step1EvenMarked : $row->step1OddMarked,
      'iActualCnt'=> $row->iActualCnt         
    ];
  }
  else {
    $statusArray = ['hasData' => false];
  }
  return $statusArray;
}

function getDiscardInfo($exptId, $jType, $dayNo, $sessionNo) {
  global $igrtSqli;
  $discards = 0;
  $getSummary = sprintf("SELECT * FROM wt_Step1Discards WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s'", $exptId, $dayNo, $sessionNo);
  $summaryResults = $igrtSqli->query($getSummary);
  if ($summaryResults) {
    $summaryRow = $summaryResults->fetch_object();
    $discards = ($jType == 0) ? $summaryRow->evenDiscards : $summaryRow->oddDiscards;
  }
  return $discards;
}

function getInterrogatorInfo($statusArray, $exptId, $jType, $dayNo, $sessionNo) {
  global $igrtSqli;
  global $choosingNP;
  global $eModel;
  $jSonRep = "";
  $fullyMarkedStr = $statusArray['previouslyMarked'] == 1? "true":"false";            
  $discardInfo = getDiscardInfo($exptId, $jType, $dayNo, $sessionNo);
  for ($jNo=0; $jNo<$statusArray['iActualCnt']; $jNo++) {
    $igsnNoQry = sprintf("SELECT uid FROM dataSTEP1 WHERE exptId='%s' AND jType='%s' AND jNo='%s' AND dayNo='%s' AND sessionNo='%s'",
        $exptId, $jType, $jNo, $dayNo, $sessionNo);
    $igsnResult = $igrtSqli->query($igsnNoQry);
    $igsnRow = $igsnResult->fetch_object();
    $uidNo = $igsnRow->uid;
    $igsnLoginQry = sprintf("SELECT email FROM igUsers WHERE id='%s'", $uidNo);
    $emailResult = $igrtSqli->query($igsnLoginQry);
    $emailRow = $emailResult->fetch_object();
    $email = $emailRow->email;
    $discardMarker = pow(2, $jNo);
    if ($jNo>0) { $jSonRep.=","; }
    // open judge # questions array
    $jSonRep.=sprintf("{\"jNo\":%s, \"questions\": [",$jNo+1); 
    if ( !$statusArray['hasMarked'] ) {
      $sqlGetData=sprintf("SELECT * FROM dataSTEP1 WHERE exptId='%s' AND jType='%s' AND jNo='%s' AND dayNo='%s' AND sessionNo='%s' ORDER BY qNo ASC;",
                        $exptId, $jType, $jNo, $dayNo, $sessionNo); 
      $discardStr = "\"discardJudge\":false,";
      $reviewedSource = 0;
    }
    else {
      $sqlGetData=sprintf("SELECT * FROM md_dataStep1reviewed WHERE exptId='%s' AND jType='%s' AND jNo='%s' AND dayNo='%s' AND sessionNo='%s' ORDER BY qNo ASC;",
                        $exptId, $jType, $jNo, $dayNo, $sessionNo); 
      if ( ($discardInfo & $discardMarker) == $discardMarker) {
        $discardStr = "\"discardJudge\":true,";                
      }
      else {
        $discardStr = "\"discardJudge\":false,";                
      }
      $reviewedSource = 1;
    }
    $jResult = $igrtSqli->query($sqlGetData);
    if ($jResult) {
      $i=0;
      while ($row = $jResult->fetch_object()) {
        if (($row->q=="FINAL") && ($row->npr=="FINAL") && ($row->pr=="FINAL")) {
          if ($i>0) { $jSonRep.= ","; }
          $finalTurnStr = "\"finalTurn\":\"1\",";
          if ($choosingNP == 1) {
            $targetRight = $row->npLeft == 1 ? false : true;
          }
          else {
            $targetRight = $row->npLeft == 1 ? true : false;                    
          }
          if ($targetRight == true) {
            $correctChoiceStr = ($row->choice == 1) ? 1 : 0;
          }
          else {
            $correctChoiceStr = ($row->choice == 0) ? 1 : 0;                     
          }
	        $jNoQNoStr = sprintf("\"jqNo\":\"%s_%s\",",$jNo, $i );
          $iIntentionStr = sprintf("\"iIntention\":%s,", JSONparse("unset"));
          $pAlignmentValueStr = sprintf("\"pAlignmentValue\":%s,", -1);
          $npAlignmentValueStr = sprintf("\"npAlignmentValue\":%s,", -1);
          $categoryAlignmentValueStr = sprintf("\"categoryAlignmentValue\":%s,", -1);
          $pAlignmentStr = sprintf("\"pAlignmentStr\":%s,", JSONparse("unset"));
          $npAlignmentStr = sprintf("\"npAlignmentStr\":%s,", JSONparse("unset"));
          $categoryAlignmentStr = sprintf("\"categoryAlignmentStr\":%s,", JSONparse("unset")) ;
          $ratingStr = sprintf("\"rating\":\"%s\",",$row->rating);
          $reasonStr = sprintf("\"reason\":%s,", JSONparse($row->reason));
          $choiceStr = sprintf("\"choice\":\"%s\",", $row->choice);
          $npLeftStr = sprintf("\"npLeft\":\"%s\",", $row->npLeft);
          $correctStr = sprintf("\"correct\":\"%s\",", $correctChoiceStr);
        }
        else {
          if ($i>0) { $jSonRep.=","; } // only 1st Q doesn't need comma prepended
          $finalTurnStr = "\"finalTurn\":\"0\",";
          $jNo = $row->jNo;
          //$qNo = $row->qNo;
          if ($choosingNP == 1) {
            $targetRight = $row->npLeft == 1 ? false : true;
          }
          else {
            $targetRight = $row->npLeft == 1 ? true : false;                    
          }
          if ($targetRight == true) {
            $correctChoiceStr = ($row->choice == 1) ? 1 : 0;
          }
          else {
            $correctChoiceStr = ($row->choice == 0) ? 1 : 0;                     
          }
          $iIntention = JSONparse($row->iIntention);
          if ($reviewedSource == 0) {
            // need to derive p and npAlignment from original data
            $r1Alignment = $row->r1Alignment;
            $r2Alignment = $row->r2Alignment;
            $pAlignment = $row->npLeft == 1 ? $r2Alignment : $r1Alignment;
            $npAlignment = $row->npLeft == 1 ? $r1Alignment : $r2Alignment;
            $pAlignmentValue = substr($pAlignment, -1);
            $npAlignmentValue = substr($npAlignment, -1);
            if (!$pAlignmentValue) { $pAlignmentValue = -1; }
            if (!$npAlignmentValue) { $npAlignmentValue = -1; }
            $pAlignmentValue = isset($pAlignmentValue) ? $pAlignmentValue : -1;
            $npAlignmentValue = isset($npAlignmentValue) ? $npAlignmentValue : -1;
            $pAlignmentValueStr = sprintf("\"pAlignmentValue\":%s,", $pAlignmentValue);
            $npAlignmentValueStr = sprintf("\"npAlignmentValue\":%s,", $npAlignmentValue);
            switch ($pAlignmentValue) {
              case 1: { $pAlignmentLabel = $eModel->s1AlignmentNonLabel; break; }
              case 2: { $pAlignmentLabel = $eModel->s1AlignmentPartlyLabel; break; }
              case 3: { $pAlignmentLabel = $eModel->s1AlignmentMostlyLabel; break; }
              case 4: { $pAlignmentLabel = $eModel->s1AlignmentCompletelyLabel; break; }
            }
            switch ($npAlignmentValue) {
              case 1: { $npAlignmentLabel = $eModel->s1AlignmentNonLabel; break; }
              case 2: { $npAlignmentLabel = $eModel->s1AlignmentPartlyLabel; break; }
              case 3: { $npAlignmentLabel = $eModel->s1AlignmentMostlyLabel; break; }
              case 4: { $npAlignmentLabel = $eModel->s1AlignmentCompletelyLabel; break; }
            }
            $pAlignmentStr = sprintf("\"pAlignmentStr\":%s,", JSONparse($pAlignmentLabel));
            $npAlignmentStr = sprintf("\"npAlignmentStr\":%s,", JSONparse($npAlignmentLabel));
            $categoryAlignment = $row->categoryAlignment;
            if (!$categoryAlignment) { $categoryAlignment = -1; }
            $categoryAlignmentValue = substr($categoryAlignment, -1);
            $categoryAlignmentValueStr = sprintf("\"categoryAlignmentValue\":%s,", $categoryAlignmentValue);
            $categoryAlignmentStr = sprintf("\"categoryAlignmentStr\":%s,", JSONparse($eModel->s1AlignmentCategoryLabels[$categoryAlignmentValue-1]));
          }
          else {
            // pAlignment, npAlignment and categoryAlignment previously derived
            $pAlignmentValueStr = sprintf("\"pAlignmentValue\":%s,", $row->pAlignmentValue);
            $npAlignmentValueStr = sprintf("\"npAlignmentValue\":%s,", $row->npAlignmentValue);
            $pAlignmentStr = sprintf("\"pAlignmentStr\":%s,", JSONparse($row->pAlignmentStr));
            $npAlignmentStr = sprintf("\"npAlignmentStr\":%s,", JSONparse($row->npAlignmentStr));
            $categoryAlignmentValueStr = sprintf("\"categoryAlignmentValue\":%s,", $row->categoryAlignmentValue);
            $categoryAlignmentStr = sprintf("\"categoryAlignmentStr\":%s,", JSONparse($row->categoryAlignmentStr));
          }
	        $jNoQNoStr = sprintf("\"jqNo\":\"%s_%s\",",$jNo, $i );
	        $iIntentionStr = sprintf("\"iIntention\":%s,", $iIntention);
          $choiceStr = sprintf("\"choice\":\"%s\",", $row->choice);
          $npLeftStr = sprintf("\"npLeft\":\"%s\",", $row->npLeft);
          $correctStr = sprintf("\"correct\":\"%s\",", $correctChoiceStr);
          $ratingStr = sprintf("\"rating\":\"%s\",",$row->rating);
          $reasonStr = sprintf("\"reason\":%s,", JSONparse($row->reason));
          ++$i;
        }
        $jSonRep.=sprintf("{\"index\":\"%s\",",$i);
	      $jSonRep.=$jNoQNoStr;
	      $jSonRep.=$finalTurnStr;
        $jSonRep.=$iIntentionStr;
        $jSonRep.=$pAlignmentStr;
        $jSonRep.=$npAlignmentStr;
        $jSonRep.=$categoryAlignmentStr;
        $jSonRep.=$pAlignmentValueStr;
        $jSonRep.=$npAlignmentValueStr;
        $jSonRep.=$categoryAlignmentValueStr;
        $jSonRep.=sprintf("\"jQ\":%s,", JSONparse($row->q));
        $jSonRep.=sprintf("\"npR\":%s,", JSONparse($row->npr));
        $jSonRep.=sprintf("\"pR\":%s,", JSONparse($row->pr));
        $jSonRep.=sprintf("\"selecting\":%s,", JSONparse($eModel->labelChoice));
        $jSonRep.=$npLeftStr;
        $jSonRep.=$correctStr;
        $jSonRep.=$choiceStr;
        $jSonRep.=$ratingStr;
        $jSonRep.=$reasonStr;
        if (!$statusArray['hasMarked']) {
          $jSonRep.="\"useQ\": \"use\" }"; 
          $rStr = "True";
        }
        else {
          switch ($row->canUse) {
            case 0: { $cuStr=" {}"; break; }
            case 1: { $cuStr="\"use\""; break; }
            case 2: { $cuStr="\"discard\""; break; }
          }
          $jSonRep.="\"useQ\": ".$cuStr." }";  
          $rStr = $row->reviewed == 1? "True":"False"; 
        }
      }
    }
    $jSonRep.="],";  // close judge questions array
    $jSonRep.="\"reviewed\": \"".$rStr."\",";
    $jSonRep.= "\"igsnNo\": \"".$email."\",";
    $jSonRep.= $discardStr;
    $jSonRep.= sprintf("\"evenJudge\": %s}", ($jType == 0) ? 1 : 0);    // jType = 0 in queries, but need evenJudge = 1 if even for KO JS                
  }
  $interrogatorInfo = [
    'json'=> $jSonRep,
    'fullyMarkedStr'=>$fullyMarkedStr
  ];
  return $interrogatorInfo;
}

// allow use of web-service
if ($permissions >= 128) {
  $eModel = new experimentModel($exptId);
  $oddS1Label = $eModel->oddS1Label;
  $evenS1Label = $eModel->evenS1Label;
  $choosingNP = $eModel->choosingNP;
  $fullyMarkedStr = "";
  $statusArray = getDataStatus($exptId, $jType, $dayNo, $sessionNo);
  $jSonRep='{';                                                                 //  opening wrapper
  $jSonRep.='"days":[';                                                         //  open days array
    $jSonRep.='{';                                                              //  open day 
      $jSonRep.='"sessions":[';                                                 //  open sessions array                     
        $jSonRep.=sprintf("{\"id\":%s,",$sessionNo); 
          $jSonRep.="\"judges\":[";
          if ($statusArray['hasData']) {
            $interrogatorInfo = getInterrogatorInfo($statusArray, $exptId, $jType, $dayNo, $sessionNo);
            $jSonRep.= $interrogatorInfo['json'];
            $fullyMarkedStr = $interrogatorInfo['fullyMarkedStr'];
          }
          else {
            //$jSonRep.= print_r($statusArray, true);
          }
          $jSonRep.="]";
        $jSonRep.="}";                                                          //  close the session
      $jSonRep.="],";                                                           //  terminate sessions array
      $jSonRep.="\"summary\":{";                                                //  add summary
        $jSonRep.= sprintf("\"dataCode\": %s,", $jType == 0 ? JSONparse($evenS1Label." judges") : JSONparse($oddS1Label." judges"));
        $jSonRep.=sprintf("\"dayNo\": %s,", $dayNo);
        $jSonRep.=sprintf("\"sessionNo\": %s,", $sessionNo);
        $jSonRep.=sprintf("\"exptId\": %s,", $exptId);
        $jSonRep.=sprintf("\"jType\": %s,", $jType);
        $jSonRep.="\"totalJudges\": {},";
        $jSonRep.="\"totalQuestions\": {},";
        $jSonRep.="\"totalReviewed\": {},";
        $jSonRep.="\"allReviewed\": ".$fullyMarkedStr."";
      $jSonRep.="}";  // close day summary
    $jSonRep.="}";                                                              //  close the day
  $jSonRep.="]";                                                                //  terminate days
  $jSonRep.="}";                                                                //  close JSON
  echo $jSonRep;
} 
else {
  echo '{"days:"[]}';
}
