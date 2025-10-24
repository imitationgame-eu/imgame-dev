<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';       

$lines = file('session1_ratings_playerNos.txt', FILE_IGNORE_NEW_LINES);
$lineCnt = count($lines);
$datalines = array();
for ($i=0; $i<$lineCnt; $i++) {
  if (isset($temp)) { unset($temp); }
  $temp = array("count" => strlen($lines[$i]), "data"=>$lines[$i]);
  array_push($datalines, $temp);
}

$playerCount = 18;
$exptId = 258;
$dayNo = 1;
$sessionNo = 1;
$firstID = 20951;

$jRCnt = array();
$JudgeArray = array();
$turnArray = array();
for ($i=1; $i<=$playerCount; $i++) {
  $jRCnt[$i]=0;
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
    $msg = $details[5];
    switch ($msgType) {
      case 'JR' : {
        $msgDetails = explode('--', $msg);
        $choice = $msgDetails[1];
        $reason = urldecode($msgDetails[2]);
        $confidence = $msgDetails[3];
        $turnPtr = ++$jRCnt[$jNo];
        $JudgeArray[$jNo][$turnPtr]['choice'] = $choice;
        $JudgeArray[$jNo][$turnPtr]['reason'] = $reason;
        $JudgeArray[$jNo][$turnPtr]['confidence'] = $confidence;
        break;
      }
      case 'JlastR' : {
        $msgDetails = explode('--', $msg);
        $choice = $msgDetails[1];
        $reason = urldecode($msgDetails[2]);
        $confidence = $msgDetails[3];
        $turnPtr = ++$jRCnt[$jNo];
        $JudgeArray[$jNo][$turnPtr]['choice'] = $choice;
        $JudgeArray[$jNo][$turnPtr]['reason'] = $reason;
        $JudgeArray[$jNo][$turnPtr]['confidence'] = 'finalI'.substr($confidence, 2);
        break;
      }
//      case 'NPA' : {
//        $content = substr($msg, 7);
//        $turnPtr = ++$NPCnt[$jNo];
//        $JudgeArray[$jNo][$turnPtr]['np'] = $content;
//        break;
//      }
//      case 'PA' : {
//        $content = substr($msg, 6);
//        $turnPtr = ++$PCnt[$jNo];
//        $JudgeArray[$jNo][$turnPtr]['p'] = $content;
//        break;
//      }
    }
  }
}

for ($i=1; $i<=$playerCount; $i++) {
  $jNo = round(($i-1) / 2, 0, PHP_ROUND_HALF_DOWN);
  $jType = ($i%2 == 0) ? 0 : 1;
  $uid = $firstID + $i - 1;
  $npLeft = ($jType == 0) ? 1 : 0;
  if ($jRCnt[$i] > 0) {
    for ($j=1; $j<=$jRCnt[$i]; $j++) {

      $sql = sprintf("UPDATE dataSTEP1 SET choice='%s', rating='%s', reason='%s' WHERE uid='%s' AND qNo='%s' AND jTYPE='%s' AND jNo='%s'",
          $JudgeArray[$i][$j]['choice'], 
          $JudgeArray[$i][$j]['confidence'], 
          $igrtSqli->real_escape_string($JudgeArray[$i][$j]['reason']), 
          $uid, $j, $jType, $jNo);
      echo $sql."<br />";
      //$igrtSqli->query($sql);
    }
//    $sql = sprintf("INSERT INTO dataSTEP1 (exptId, jType, JNo, sessionNo, dayNo, qNo, q, npr, pr) "
//          . "VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s')",
//          $exptId, $jType, $jNo, $sessionNo, $dayNo, $j, 
//          'FINAL',
//          'FINAL',
//          'FINAL'       
//          );
//    $igrtSqli->query($sql);   
  }
}
echo 'done';
//$debug = print_r($JudgeArray);
//echo $debug;
//

