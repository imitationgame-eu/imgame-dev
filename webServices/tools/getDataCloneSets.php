<?php
// -----------------------------------------------------------------------------
// web service to make JSON list of experiments and whether they have injected
// S1, S2 or S2inverted data
// -----------------------------------------------------------------------------
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
$permissions = $_GET['permissions'];
$uid = $_GET['uid'];
$srcExptId = $_GET['exptId'];
$currentExperimentName = "";

include_once $root_path.'/domainSpecific/mySqlObject.php';      
include_once $root_path.'/helpers/parseJSON.php';

if ($permissions>=128) {
  $eQry = "SELECT * FROM igExperiments WHERE injectedFlag='1' ORDER BY exptId DESC";
  $eResult = $igrtSqli->query($eQry);
  $eList = array();
  $injectedNo = 0;
  $standardNo = -1;
  while ($eRow = $eResult->fetch_object()) {
    if ($eRow->exptId == $srcExptId) { $currentExperimentName = $eRow->title; }
    $thisHasDataQry = sprintf("SELECT DISTINCT(jNo) FROM md_dataStep1reviewed WHERE exptId='%s'", $eRow->exptId);
    $hr = $igrtSqli->query($thisHasDataQry);
    $approxJNo = $hr ? $hr->num_rows : 0;
    $getSessions = sprintf("SELECT * FROM edSessions WHERE exptId='%s' ORDER BY dayNo ASC, sessionNo ASC", $srcExptId);
    $sessionsResult = $igrtSqli->query($getSessions);
    $sessionsArray = array();
    while ($sessionRow = $sessionsResult->fetch_object()) {
      $dayNo = $sessionRow->dayNo;
      $sessionNo = $sessionRow->sessionNo;

      $jNoQry = sprintf("SELECT DISTINCT(jNo) FROM dataSTEP1 WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s'", $srcExptId, $dayNo, $sessionNo);
      $fr = $igrtSqli->query($jNoQry);
      $jCnt = $fr->num_rows;
      if ($jCnt>0) {
        array_push($sessionsArray, array('dayNo'=>$dayNo, 'sessionNo'=>$sessionNo, 'jCnt'=>$jCnt));        
      }
    }
    $eDef = array(
      'exptId' => $eRow->exptId, 
      'title' => $eRow->title,
      'injectedNo' => $injectedNo++,
      'standardNo' => $standardNo,
      'injected' => 1,
      'hasS1' => $eRow->injectedS1Flag,
      'hasS2' => $eRow->injectedS2Flag,
      'hasS2inverted' => $eRow->injectedInvertedS2Flag,
      's1srcExptId' => $eRow->s1srcExptId, 
      's2srcExptId' => $eRow->s2srcExptId, 
      's2invertedsrcExptId' => $eRow->s2invertedsrcExptId,
      'existingS1Judges' => $approxJNo,
      'daySessions' => $sessionsArray
    );
    array_push($eList, $eDef);    
  }
  $eQry = "SELECT * FROM igExperiments WHERE injectedFlag='0' ORDER BY exptId DESC";
  $eResult = $igrtSqli->query($eQry);
  while ($eRow = $eResult->fetch_object()) {
    if ($eRow->exptId == $srcExptId) { $currentExperimentName = $eRow->title; }
    $thisHasDataQry = sprintf("SELECT DISTINCT(jNo) FROM md_dataStep1reviewed WHERE exptId='%s'", $eRow->exptId);
    $hr = $igrtSqli->query($thisHasDataQry);
    $approxJNo = $hr->num_rows;
    $getSessions = sprintf("SELECT * FROM edSessions WHERE exptId='%s' ORDER BY dayNo ASC, sessionNo ASC", $srcExptId);
    $sessionsResult = $igrtSqli->query($getSessions);
    $sessionsArray = array();
    while ($sessionRow = $sessionsResult->fetch_object()) {
      $dayNo = $sessionRow->dayNo;
      $sessionNo = $sessionRow->sessionNo;
      $jNoQry = sprintf("SELECT DISTINCT(jNo) FROM dataSTEP1 WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s'", $srcExptId, $dayNo, $sessionNo);
      $fr = $igrtSqli->query($jNoQry);
      $jCnt = $fr->num_rows;
      if ($jCnt>0) {
        array_push($sessionsArray, array('dayNo'=>$dayNo, 'sessionNo'=>$sessionNo, 'jCnt'=>$jCnt));        
      }
    }
    $eDef = array(
      'exptId' => $eRow->exptId, 
      'title' => $eRow->title,
      'injectedNo' => $injectedNo,
      'standardNo' => $standardNo++,
      'injected' => 0,
      'hasS1' => 0,
      'hasS2' => 0,
      'hasS2inverted' => 0,
      's1srcExptId' => -1, 
      's2srcExptId' => -1, 
      's2invertedsrcExptId' => -1,
      'existingS1Judges' => $approxJNo,
      'daySessions' => $sessionsArray 
    );
    array_push($eList, $eDef);    
  }
  $jSonRep = "{";
  $jSonRep.= "\"currentExperimentName\":" . JSONparse($currentExperimentName). ",";
  $jSonRep.= "\"srcExptId\":" . $srcExptId . ",";
  $jSonRep.= "\"injectedCount\":" . $injectedNo . ",";
  $jSonRep.= "\"standardCount\":" . $standardNo . ",";
  $jSonRep.= "\"experiments\":[";
  for ($i=0; $i<count($eList); $i++) {
    $rowNo = $i+1;
    if ($i>0) { $jSonRep.= ","; }
    $jSonRep.= "{";
      $jSonRep.= "\"exptId\":\"" . $eList[$i]['exptId'] . "\",";
      $jSonRep.= (($eList[$i]['injected']==1) && ($eList[$i]['existingS1Judges']>0) && ($eList[$i]['s1srcExptId'] == -1)) ? "\"manuallyInjected\":true," : "\"manuallyInjected\":false,";
      // manually injected means direct db manipulation by MH, usually from translated QSs
      $jSonRep.= "\"existingS1Judges\":\"" . count($eList[$i]['existingS1Judges']) . "\",";
      $jSonRep.= "\"daySessionsCount\":\"" . count($eList[$i]['daySessions']) . "\",";
      $jSonRep.= "\"daySessions\":[";
        for ($j=0; $j<count($eList[$i]['daySessions']); $j++) {
          if ($j>0) { $jSonRep.= ","; }
          $jSonRep.="{";
          $jSonRep.= "\"dayNo\":". $eList[$i]['daySessions'][$j]['dayNo'] . ","; 
          $jSonRep.= "\"sessionNo\":". $eList[$i]['daySessions'][$j]['sessionNo'] . ",";
          $jSonRep.= "\"jCnt\":". $eList[$i]['daySessions'][$j]['jCnt'] . ",";
          $jSonRep.= $eList[$i]['hasS1'] == 1 ? "\"hasS1\":true," : "\"hasS1\":false,";
          $jSonRep.= $eList[$i]['hasS2'] == 1 ? "\"hasS2\":true," : "\"hasS2\":false,";
          $jSonRep.= $eList[$i]['hasS2inverted'] == 1 ? "\"hasS2inverted\":true," : "\"hasS2inverted\":false,";      
          $jSonRep.= "\"overrideS1\":false,";
          $jSonRep.= "\"overrideS2\":false,";
          $jSonRep.= "\"overrideS2inverted\":false";
          $jSonRep.= "}";
        }
      $jSonRep.= "],";
      $jSonRep.= "\"rowNo\":" . $rowNo . ",";
      $jSonRep.= "\"injected\":" . $eList[$i]['injected'] . ","; 
      $jSonRep.= "\"injectedNo\":" . $eList[$i]['injectedNo'] . ",";
      $jSonRep.= "\"standardNo\":" . $eList[$i]['standardNo'] . ",";
      $jSonRep.= "\"s1srcExptId\":" . $eList[$i]['s1srcExptId'] . ",";
      $jSonRep.= "\"s2srcExptId\":" . $eList[$i]['s2srcExptId'] . ",";
      $jSonRep.= "\"s2invertedsrcExptId\":" . $eList[$i]['s2invertedsrcExptId'] . ",";
      $jSonRep.= "\"title\":". JSONparse($eList[$i]['title']) . "";
    $jSonRep.= "}";    
  }
  $jSonRep.= "]}";
  echo $jSonRep;    
}
