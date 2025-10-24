<?php
// -----------------------------------------------------------------------------
// web service to export all Step2 respondents to each Step1 QS for use in nVivo
// -----------------------------------------------------------------------------
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/parseJSON.php');
require_once($root_path.'/helpers/class.dataHandler.php');
require_once($root_path.'/helpers/models/class.experimentModel.php');
include_once $root_path.'/domainSpecific/mySqlObject.php';      
$permissions=$_GET['permissions'];
$uid = $_GET['uid'];
$exptId = $_GET['exptId'];
$jType = $_GET['jType'];


if ($permissions>=128) {
  $eModel = new experimentModel($exptId);
  $exptTitle = $eModel->title;
  $jTypeLabel = $jType == 0 ? $eModel->evenS1Label : $eModel->oddS1Label;
//  $exptArray = $dbHelper->getExptDaySessionCounts($exptId);
//  if ($exptArray["status"] == "ok") {
//    $dayCnt = $exptArray["dayCnt"];
//    $sessionCnt = $exptArray["sessionCnt"];        
//  }
//  else {
//    $dayCnt = 0;
//    $sessionCnt = 0;
//  }
  $qsList = array();
  $qsQry = sprintf("SELECT * FROM wt_Step2Balancer WHERE exptId='%s' AND jType='%s' ORDER BY actualJNo ASC", $exptId, $jType);
  $qsResult = $igrtSqli->query($qsQry);
  if ($qsResult->num_rows > 0) {
    while ($qsRow = $qsResult->fetch_object()) {
      $actualJNo = $qsRow->actualJNo;
      $dayNo = $qsRow->dayNo;
      $sessionNo = $qsRow->sessionNo;
      $jNo = $qsRow->jNo;
      $qsSet = array(
        'actualJNo'=> $actualJNo,
        'dayNo'=> $dayNo,
        'sessionNo'=> $sessionNo,
        'jNo'=> $jNo,
        'questions'=> array()
      );
      $iQuestionQry = sprintf("SELECT * FROM md_dataStep1reviewed WHERE exptId='%s' AND jType='%s' AND dayNo='%s' AND "
          . "sessionNo='%s' AND jNo='%s' AND canUse=1 ORDER BY qNo ASC", 
          $exptId, $jType, $dayNo, $sessionNo, $jNo);
      $iQuestionResult = $igrtSqli->query($iQuestionQry);
      if ($iQuestionResult->num_rows > 0) {
        while ($iQuestionRow = $iQuestionResult->fetch_object()) {
          $iQuestion = array(
            'q'=> $iQuestionRow->q,
            'qNo'=> $iQuestionRow->qNo,
            'ppts'=> array()
          );
          $s2Qry = sprintf("SELECT * FROM dataSTEP2 WHERE exptId='%s'AND jType='%s' AND dayNo='%s' AND "
            . "sessionNo='%s' AND jNo='%s' AND qNo='%s' ORDER BY pptNo ASC",
            $exptId, $jType, $dayNo, $sessionNo, $jNo, $iQuestionRow->qNo);
          $s2Result = $igrtSqli->query($s2Qry);
          if ($s2Result->num_rows > 0) {
            while ($s2Row = $s2Result->fetch_object()) {
              $s2 = array(
                'pptNo'=> $s2Row->pptNo,
                'reply'=> $s2Row->reply                
              );
              array_push($iQuestion['ppts'], $s2);
            }
          }
          array_push($qsSet['questions'], $iQuestion);
        }
      }
      array_push($qsList, $qsSet);
    }
  }
  $json = "{";
    $json.= "\"exptTitle\":". JSONparse($exptTitle) . ",";
    $json.= "\"jTypeLabel\":". JSONparse($jTypeLabel) . ",";
    $json.= "\"exptId\":". $exptId . ",";
    $json.= "\"jType\":". $jType . ",";
    $json.= "\"S1QS\":[";
    for ($i=0; $i<count($qsList); $i++) {
      if ($i > 0) { $json.= ","; }
      $json.= "{";
        $json.= "\"actualJNo\":". $qsList[$i]['actualJNo'] . ",";
        $json.= "\"dayNo\":". $qsList[$i]['dayNo'] . ",";
        $json.= "\"sessionNo\":". $qsList[$i]['sessionNo'] . ",";
        $json.= "\"jNo\":". $qsList[$i]['jNo'] . ",";
        $json.= "\"questions\":[";
          for ($j=0; $j<count($qsList[$i]['questions']); $j++) {
            if ($j>0) { $json.= ","; }
            $json.= "{";
              $json.="\"qNo\":" . $qsList[$i]['questions'][$j]['qNo'] . ",";
              $json.="\"q\":" . JSONparse($qsList[$i]['questions'][$j]['q']) . ",";
              $json.= "\"s2ppts\":[";
                for ($k=0; $k<count($qsList[$i]['questions'][$j]['ppts']); $k++) {
                  if ($k>0) { $json.=","; }
                  $json.= "{";
                    $json.= "\"pptNo\":". $qsList[$i]['questions'][$j]['ppts'][$k]['pptNo'] . ",";
                    $json.= "\"reply\":". JSONparse($qsList[$i]['questions'][$j]['ppts'][$k]['reply']);                    
                  $json.= "}";
                }
              $json.= "]";
            $json.= "}";
          }
        $json.= "]";
      $json.= "}";
    }
    $json.= "]";
  $json.= "}";
  echo $json;
}
