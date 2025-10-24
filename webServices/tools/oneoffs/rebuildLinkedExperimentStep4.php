<?php
  ini_set('display_errors', 'On');
  error_reporting(E_ALL);
  if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
  include_once $root_path.'/domainSpecific/mySqlObject.php';       

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// functions

  
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
$fn = "access_log";
$lines = file($fn, FILE_IGNORE_NEW_LINES);
$lineCnt = count($lines);
$datalines = array();
for ($i=0; $i<$lineCnt; $i++) {
  if (isset($temp)) { unset($temp); }
  $temp = array("count" => strlen($lines[$i]), "data"=>$lines[$i]);
  array_push($datalines, $temp);
}
for ($i=0; $i<count($datalines); $i++) {
  if (strpos($datalines[$i]['data'], 'step4storeRating') !==false ) {
    $s4jNoSplit = explode('&s4jNo=', $datalines[$i]['data']);
    $s4jNoSplit_2 = explode('&', $s4jNoSplit[1]);
    $s4jNo = $s4jNoSplit_2[0];
    $split1 = explode('step4storeRating', $datalines[$i]['data']);
    $split2 = explode('&content%5B2%5D=', $split1[1]);
    $choiceSplit = explode('&', $split2[1]);
    $choice = $choiceSplit[0];
    $intervalSplit = explode('content%5B3%5D=', $choiceSplit[1]);
    $interval = $intervalSplit[1];
    $reasonSplit = explode('content%5B4%5D=', $choiceSplit[2]);
    $reason = $igrtSqli->real_escape_string(urldecode($reasonSplit[1]));    
    $pRightSplit = explode('content%5B5%5D=', $choiceSplit[3]);
    $pRight = $pRightSplit[1];
    $jNoSplit = explode('content%5B7%5D=', $choiceSplit[4]);
    $jNo = $jNoSplit[1];
    $exptIdSplit = explode('content%5B8%5D=', $choiceSplit[5]);
    $exptIdSplit_2 = explode(' ', $exptIdSplit[1]);
    $exptId = $exptIdSplit_2[0];
    if ($pRight == 0) {
      $correct = $choice == 1 ? 1 : 0;
    }
    else {
      $correct = $choice == 0 ? 1 : 0;
    }
    //echo $s4jNo.' - '.$exptId.' - '.$jNo.' - '.$reason.' - '.$interval.' - '.$pRight.' - '.$choice.'<br />'; 
    $update = sprintf("UPDATE dataLinkedSTEP4 SET pretenderRight='%s', correct='%s', choice='%s' "
      . "WHERE "
      . "s4jNo='%s' AND exptId='%s' AND igNo='%s'",
      $pRight, $correct, $choice, $s4jNo, $exptId, $jNo
      );
    //echo $update.'<br />';
    $igrtSqli->query($update);
  }
}


echo 'done';
//$debug = print_r($JudgeArray);
//echo $debug;


