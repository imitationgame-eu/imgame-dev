<?php
// -----------------------------------------------------------------------------
// 
// web service to return step form status for all experiments as JSON object for 
// rendering by KO-JS
// 
// -----------------------------------------------------------------------------
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
$permissions = $_GET['permissions'];
require_once($root_path.'/domainSpecific/mySqlObject.php');

// -- functions ----------------------------------------------------------------
  function getFormTypes() {
    global $igrtSqli;
    $getTypeSql = "SELECT * FROM fdStepFormsNames ORDER BY formType ASC";
    $getTypeResult = $igrtSqli->query($getTypeSql);
    $retArray = array();
    while ($getTypeRow = $getTypeResult->fetch_object()) {
      array_push($retArray, $getTypeRow);
    }
    return $retArray;
  }     

//ensure admin
if ($permissions >= 128) {
  $exptList = array();
  $formTypes = getFormTypes();
  $getExperimentsQry = "SELECT * FROM igExperiments ORDER BY exptId DESC";
  $exptResult = $igrtSqli->query($getExperimentsQry);
  while ($exptRow = $exptResult->fetch_object()) {
    $exptId = $exptRow->exptId;
    $exptTitle = $exptRow->title;
    $cloneFormsAccordionOpen = $exptRow->cloneFormsAccordionOpen;
    $formsList = array();
    for ($i=0; $i<count($formTypes); $i++) {
      $tempValue = array('partPopulated' => 0, 'definitionComplete'=>0, 'formName'=>$formTypes[$i]->formName, 'formType'=>$formTypes[$i]->formName);
      $getStatusQry = sprintf("SELECT * FROM fdStepForms WHERE exptId='%s' AND formType='%s'", $exptId, $i);
      $getStatusResult = $igrtSqli->query($getStatusQry);
      if ($getStatusResult) {
        $tempValue['partPopulated'] = 1;
        $getStatusRow = $getStatusResult->fetch_object();
        $tempValue['definitionComplete'] = $getStatusRow->definitionComplete;
      }
      array_push($formsList, $tempValue);
    }
    $tempExpt = array(
      'exptId'=>$exptId,
      'exptTitle'=>$exptTitle,
      'cloneFormsAccordionOpen'=>$cloneFormsAccordionOpen, 
      'formsList'=>$formsList);
    array_push($exptList, $tempExpt);
  }
  $jSonRep = "{";
  $jSonRep.= "\"info\" : \"dummy holder \",";
	$jSonRep.= "\"formTypes\": [";
  foreach($formTypes as $i => $formType) {
  	if ($i > 0) { $jSonRep.=","; }
	  $jSonRep.= "{\"formType\":\"".$formType->formType."\",\"formName\":\"".$formType->formName."\"}";
  }
	$jSonRep.= "],";
  $jSonRep.= "\"experiments\":[";
  for ($i=0; $i<count($exptList); $i++) {
    if ($i > 0) { $jSonRep.=","; }  // prepend any experiment after the first
    $jSonRep.= "{";
      $jSonRep.= "\"exptId\":\"" . $exptList[$i]['exptId'] . "\",";
      $jSonRep.= "\"exptTitle\":\"" . $exptList[$i]['exptTitle'] . "\",";
      $jSonRep.= "\"cloneFormsAccordionOpen\":\"" . $exptList[$i]['cloneFormsAccordionOpen'] . "\",";
      $jSonRep.= "\"forms\": [";
      for ($j=0; $j<count($exptList[$i]['formsList']); $j++) {      
        if ($j > 0) { $jSonRep.= ","; } // prepend any form after the first
        $jSonRep.= "{";
        $jSonRep.= "\"partPopulated\":\"" . $exptList[$i]['formsList'][$j]['partPopulated'] . "\",";
        $jSonRep.= "\"definitionComplete\":\"" . $exptList[$i]['formsList'][$j]['definitionComplete'] . "\",";
	      $jSonRep.= "\"dataOnText\":\"clone here\",";
	      $jSonRep.= "\"dataOffText\":\"don't clone\",";
	      $jSonRep.= "\"formName\":\"" . $exptList[$i]['formsList'][$j]['formName'] . "\",";
        $jSonRep.= "\"formType\":\"" . $exptList[$i]['formsList'][$j]['formType'] . "\",";
        $jSonRep.= "\"cloneToHere\":false";  // get checkbox value when clone action initiated 
        $jSonRep.= "}";
      }
      $jSonRep.= "]";
    $jSonRep.= "}";
  }
  $jSonRep.= "]";
  $jSonRep.= "}";
  echo $jSonRep;    
}
