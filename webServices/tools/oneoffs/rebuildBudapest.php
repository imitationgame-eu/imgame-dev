<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';       

$lines = file('budapestturns.txt', FILE_IGNORE_NEW_LINES);
$lineCnt = count($lines);
$datalines = array();
for ($i=0; $i<$lineCnt; $i++) {
  if (isset($temp)) { unset($temp); }
  $temp = array("count" => strlen($lines[$i]), "data"=>$lines[$i]);
  array_push($datalines, $temp);
}

$jQCnt = array();
$NPCnt = array();
$PCnt =array();
$JudgeArray = array();
$turnArray = array();
for ($i=1; $i<=20; $i++) {
  $jQCnt[$i]=0;
  $NPCnt[$i]=0;
  $PCnt[$i]=0;
  array_push($JudgeArray, $turnArray);
} 
$dlCnt = count($datalines);
for ($i=0; $i<$dlCnt; $i++) {
  // process each line
  if ($datalines[$i]["count"] > 0) {
    $source = $datalines[$i]["data"];  
    $details = explode(':', $source);
    $msgType = $details[0];
    $jNo = $details[3];
    $msg = urldecode($details[5]);
    switch ($msgType) {
      case 'JQ' : {
        $turnPtr = ++$jQCnt[$jNo];
        $JudgeArray[$jNo][$turnPtr]['q'] = $msg;
        break;
      }
      case 'NPA' : {
        $turnPtr = ++$NPCnt[$jNo];
        $JudgeArray[$jNo][$turnPtr]['np'] = $msg;
        break;
      }
      case 'PA' : {
        $turnPtr = ++$PCnt[$jNo];
        $JudgeArray[$jNo][$turnPtr]['p'] = $msg;
        break;
      }
    }
  }
}

for ($i=1; $i<=20; $i++) {
  switch ($i) {
    case 1 : { $jType = 1; $jNo = 0; break; }
    case 2 : { $jType = 0; $jNo = 0; break; }
    case 3 : { $jType = 1; $jNo = 1; break; }
    case 4 : { $jType = 0; $jNo = 1; break; }
    case 5 : { $jType = 1; $jNo = 2; break; }
    case 6 : { $jType = 0; $jNo = 2; break; }
    case 7 : { $jType = 1; $jNo = 3; break; }
    case 8 : { $jType = 0; $jNo = 3; break; }
    case 9 : { $jType = 1; $jNo = 4; break; }
    case 10 : { $jType = 0; $jNo = 4; break; }
    case 11 : { $jType = 1; $jNo = 5; break; }
    case 12 : { $jType = 0; $jNo = 5; break; }
    case 13 : { $jType = 1; $jNo = 6; break; }
    case 14 : { $jType = 0; $jNo = 6; break; }
    case 15 : { $jType = 1; $jNo = 7; break; }
    case 16 : { $jType = 0; $jNo = 7; break; }
    case 17 : { $jType = 1; $jNo = 8; break; }
    case 18 : { $jType = 0; $jNo = 8; break; }
    case 19 : { $jType = 1; $jNo = 9; break; }
    case 20 : { $jType = 0; $jNo = 9; break; }
  }
  for ($j=1; $j<=$jQCnt[$i]; $j++) {
    $sql = sprintf("INSERT INTO dataSTEP1 (exptId, jType, JNo, sessionNo, dayNo, qNo, q, npr, pr) "
        . "VALUES('260','%s','%s','2','1','%s','%s','%s','%s')",
        $jType, $jNo, $j, 
        $igrtSqli->real_escape_string($JudgeArray[$i][$j]['q']),
        $igrtSqli->real_escape_string($JudgeArray[$i][$j]['np']),
        $igrtSqli->real_escape_string($JudgeArray[$i][$j]['p'])       
        );
    //$igrtSqli->query($sql);
  }
}
echo 'done';
//
//
//
$debug = print_r($JudgeArray);
echo $debug;


