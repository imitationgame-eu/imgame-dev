<?php
// -----------------------------------------------------------------------------
// web service to give JSON list of survey 2 datasets that are ready for 
// injection into survey viewer
// exptType indicates which category to return
// 0 = active-standard 
// 1 = active-injected 
// 2 = inactive-standard
// 3 = inactive-injected 
// -----------------------------------------------------------------------------
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
$permissions=$_GET['permissions'];
$uid=$_GET['uid'];
$exptType = $_GET['exptType'];  
include_once $root_path.'/domainSpecific/mySqlObject.php';
include_once $root_path.'/helpers/parseJSON.php';

  function buildJSON($exptType, $dsList, $prefix) {

    $jSonRep = "{";
    $jSonRep.= "\"exptType\":" . $exptType . ","; 
    $jSonRep.= "\"exptCount\":" . count($dsList) . ","; 
    $jSonRep.= "\"".$prefix."Experiments\":[";
    for ($i=0; $i<count($dsList); $i++) {
      if ($i>0) { $jSonRep.= ","; }
      $jSonRep.= "{";
      $jSonRep.= "\"exptId\":".$dsList[$i]['exptId'].",";
      $jSonRep.= "\"exptTitle\":". JSONparse($dsList[$i]['title']) .",";
      $jSonRep.= "\"s2preNo\":".$dsList[$i]['s2preNo'].",";
      $jSonRep.= "\"s2postNo\":".$dsList[$i]['s2postNo'].",";
      $jSonRep.= "\"inverteds2preNo\":".$dsList[$i]['inverteds2preNo'].",";
      $jSonRep.= "\"inverteds2postNo\":".$dsList[$i]['inverteds2postNo'].",";
      $jSonRep.= "\"s4preNo\":".$dsList[$i]['s4preNo'].",";
      $jSonRep.= "\"s4postNo\":".$dsList[$i]['s4postNo'];
      $jSonRep.= "}";
    }
    $jSonRep.= "]";
    $jSonRep.= "}";
    return $jSonRep;    
  }

if ($permissions>=128) {
  $datasetList = array();
  switch ($exptType) {
    case 0: {
      $inActive = 0; $injectedFlag = 0; $prefix = "as"; break;
    }
    case 1: {
      $inActive = 0; $injectedFlag = 1; $prefix = "ai"; break;
    }
    case 2: {
      $inActive = 1; $injectedFlag = 0; $prefix = "is"; break;
    }
    case 3: {
      $inActive = 1; $injectedFlag = 1; $prefix = "ii"; break;
    }
  }
  $exptListQry = sprintf("SELECT * FROM igExperiments WHERE inActive='%s' AND injectedFlag='%s' ORDER BY exptId DESC", $inActive, $injectedFlag );
  $exptListResult = $igrtSqli->query($exptListQry);
  if ($exptListResult) {
    while ($exptListRow = $exptListResult->fetch_object()) {
      $dsItem = array(
        'exptId' => $exptListRow->exptId,
        'title' => $exptListRow->title,
        's2preNo' => 0,
        's2postNo' => 0,
        'inverteds2preNo' => 0,
        'inverteds2postNo' => 0,
        's4preNo' => 0,
        's4postNo' => 0
      );
      $tblName = "zz_json_".$exptListRow->exptId;
      $hasData = false;
      $getJsonQry = sprintf("SELECT formType FROM %s", $tblName);
      $jsonResult = $igrtSqli->query($getJsonQry);
      if ($jsonResult) {
        $hasData = true;
        while ($jsonRow = $jsonResult->fetch_object()) {
          $formType = $jsonRow->formType;
          //$jSonArray = json_decode($rawBody, true);
          switch ($formType) {
            case 6 : { ++$dsItem['s2preNo']; break; }
            case 7 : { ++$dsItem['s2postNo']; break; }
            case 12 : { ++$dsItem['inverteds2preNo']; break; }
            case 13 : { ++$dsItem['inverteds2preNo']; break; }
            case 10 : { ++$dsItem['s4preNo']; break; }
            case 11 : { ++$dsItem['s4preNo']; break; }
          }
        }
        array_push($datasetList, $dsItem);
      }      
    }
    echo buildJSON($exptType, $datasetList, $prefix);
  }
}

