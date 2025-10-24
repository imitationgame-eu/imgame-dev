<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
//echo $root_path;
include_once $root_path.'/domainSpecific/mySqlObject.php';       
include_once $root_path.'/ws/models/class.step1AllocationModel.php';

$playerCount = 0;     // will be derived from log
$exptId = -1;         

// arrays of Step1 components
$PlayerAllocations;
$Ratings;
$finalRatings;
$judgeArray = [];

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// functions


  function getPlayerNo($jType, $jNo) {
    if ($jType == 0) {
      return ($jNo + 1) * 2;
    }
    else {
      return ($jNo *2 ) + 1;
    }
  }
  
  function playerArray($src) {
    $ret = [];
    for ($i=0; $i<count($src); $i++) {
      array_push($ret, []);
    }
    for ($i=0; $i<count($src); $i++) {
      $ret[$src[$i]['playerNo']] = $src[$i];
    }
    return $ret;
  }

  function getLoginJoins($datalines) {
    $ljArray = [];
    foreach ($datalines as $dl) {
      if ($dl["count"] > 0) {
        $source = $dl["data"];  
        $details = explode('<:>', $source);
        $msgType = $details[0];
        if ($msgType == "<loginJoin>") {
          $cid = $details[1];
          $uid = $details[2];
          $exptId = $details[3];
          $dayNo = $details[4];
          $sessionNo = $details[5];
          $jType = $details[6];          
          $jNo = $details[7];
          $tempItem = [
            'uid' => $uid,
            'cid'=> $cid,
            'exptId' => $exptId,
            'jType' => $jType,
            'jNo' => $jNo,
            'dayNo' => $dayNo,
            'sessionNo' => $sessionNo,
            'playerNo'=> getPlayerNo($jType, $jNo),
            'turns' => []
          ];
          array_push($ljArray, $tempItem);       
        }
      }
    }
    // make array ordered by player no, from this one ordered by connection order
    
    return playerArray($ljArray);
  }
  
  function populateJQ($datalines) {
    global $judgeArray;
    foreach ($datalines as $dl) {
      if ($dl["count"] > 0) {
        $source = $dl["data"];  
        $details = explode('<:>', $source);
        $msgType = $details[0];
        if ($msgType == "<JQ>") {
          $Q = urldecode($details[3]);
          $playerNo = $details[1];
          $turnPtr = $details[2];
          $judgeArray[$playerNo]['turns'][$turnPtr]['q'] = $Q; 
          $judgeArray[$playerNo]['turns'][$turnPtr]['np'] = '';
          $judgeArray[$playerNo]['turns'][$turnPtr]['p'] = '';
          $judgeArray[$playerNo]['turns'][$turnPtr]['choice'] = -1;
          $judgeArray[$playerNo]['turns'][$turnPtr]['confidence'] = '';
          $judgeArray[$playerNo]['turns'][$turnPtr]['reason'] = '';
          $judgeArray[$playerNo]['turns'][$turnPtr]['isFinal'] = false;
          $judgeArray[$playerNo]['turns'][$turnPtr]['npSide'] = -1;
        }
      }
    }
  }
  
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -


$playerCount = 0;     
$exptId = 0;          
$fn = "madridSession1.txt";
$lines = file($fn, FILE_IGNORE_NEW_LINES);
$lineCnt = count($lines);
$datalines = [];
for ($i=0; $i<$lineCnt; $i++) {
  if (isset($temp)) { unset($temp); }
  $temp = array("count" => strlen($lines[$i]), "data"=>$lines[$i]);
  array_push($datalines, $temp);
}
$loginJoins = getLoginJoins($datalines);
//$status = print_r($loginJoins, true)."<br />";
//echo $status;
$judgeCount = count($loginJoins) / 2;
$playerCount = count($loginJoins);
//echo $judgeCount."<br />";
//$PlayerAllocations = new roleAllocations($judgeCount, 1);
//$PlayerAllocations->generateFixed();
//$status = print_r($PlayerAllocations->mappings, true)."<br />";
//echo $status;
$judgeArray = $loginJoins;
populateJQ($datalines);
$dlCnt = count($datalines);
for ($i=0; $i<$dlCnt; $i++) {
  // process each line
  if ($datalines[$i]["count"] > 0) {
    $source = $datalines[$i]["data"];
    $details = explode('<:>', $source);
    $msgType = $details[0];
    switch ($msgType) {
      case '<NPA>' : {
        $playerNo = $details[2];  // NB - this is the game# - not the NP player#
        $msg = urldecode($details[4]);
        $turnPtr = $details[3];
        $judgeArray[$playerNo]['turns'][$turnPtr]['np'] = $msg;
        break;
      }
      case '<PA>' : {
        $playerNo = $details[2];  // NB - this is the game# - not the P player#
        $msg = urldecode($details[4]);
        $turnPtr = $details[3];
        $judgeArray[$playerNo]['turns'][$turnPtr]['p'] = $msg;
        break;
      }
      case '<JR>' : {
        $playerNo = $details[1];  // NB - this is the J game# 
        $turnPtr = $details[2] - 1;
        $choice = $details[3];
        $reason = urldecode($details[4]);
        $confidence = $details[5];
        $judgeArray[$playerNo]['turns'][$turnPtr]['choice'] = $choice;
        $judgeArray[$playerNo]['turns'][$turnPtr]['confidence'] = $confidence;
        $judgeArray[$playerNo]['turns'][$turnPtr]['reason'] = $reason;
        $judgeArray[$playerNo]['turns'][$turnPtr]['isFinal'] = false;
        $judgeArray[$playerNo]['turns'][$turnPtr]['npSide'] = -1;
        break;
      }
      case '<JfinalR>' : {
        $playerNo = $details[1];  // NB - this is the J game# 
        $turnPtr = $details[2];
        $choice = $details[3];
        $reason = urldecode($details[4]);
        $confidence = $details[5];
        $npSide = $details[6];
        $judgeArray[$playerNo]['turns'][$turnPtr]['choice'] = $choice;
        $judgeArray[$playerNo]['turns'][$turnPtr]['confidence'] = $confidence;
        $judgeArray[$playerNo]['turns'][$turnPtr]['reason'] = $reason;
        $judgeArray[$playerNo]['turns'][$turnPtr]['isFinal'] = true;
        $judgeArray[$playerNo]['turns'][$turnPtr]['npSide'] = $npSide;
        break;
      }
    }
  }
}
//$status = print_r($judgeArray, true)."<br />";
//echo $status;

// get rating info



$delSql = "DELETE FROM dataSTEP1 WHERE exptId=301";
$igrtSqli->query($delSql);

$firstID = 25291;
for ($i=1; $i<=$playerCount; $i++) {
  $jNo = $judgeArray[$i]['jNo'];          //round($i / 2,0,PHP_ROUND_HALF_DOWN);
  $jType = $judgeArray[$i]['jType'];      //($i%2 == 0) ? 0 : 1;
  $uid = $judgeArray[$i]['uid'];          //$firstID + $i - 1;
  $exptId= $judgeArray[$i]['exptId'];
  $qCount = count($judgeArray[$i]['turns']);
  if ($qCount > 0) {
    for ($j=0; $j<$qCount; $j++) {
      if ($judgeArray[$i]['turns'][$j]['npSide'] > -1) {
        $npLeft = $judgeArray[$i]['turns'][$j]['npSide'] == 0? 1 : 0;         
      }
      else {
        $npLeft = $judgeArray[$i]['turns'][$j]['npSide'];        
      }
      if ($judgeArray[$i]['turns'][$j]['isFinal']) {
            $sql = sprintf("INSERT INTO dataSTEP1 "
                . "(uid, exptId, jType, JNo, sessionNo, dayNo, npLeft, qNo, q, npr, pr, choice, rating, reason) "
                . "VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
                $uid, $exptId, $jType, $jNo, 
                $judgeArray[$i]['sessionNo'], 
                $judgeArray[$i]['dayNo'], 
                $npLeft, 
                $j+1, 
                'FINAL',
                'FINAL',
                'FINAL',
                $judgeArray[$i]['turns'][$j]['choice'],         
                $judgeArray[$i]['turns'][$j]['confidence'],         
                $igrtSqli->real_escape_string($judgeArray[$i]['turns'][$j]['reason'])        
            );       
      }
      else {
            $sql = sprintf("INSERT INTO dataSTEP1 "
                . "(uid, exptId, jType, JNo, sessionNo, dayNo, npLeft, qNo, q, npr, pr, choice, rating, reason) "
                . "VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
                $uid, $exptId, $jType, $jNo, 
                $judgeArray[$i]['sessionNo'], 
                $judgeArray[$i]['dayNo'], 
                $npLeft, 
                $j+1, 
                $igrtSqli->real_escape_string($judgeArray[$i]['turns'][$j]['q']),
                $igrtSqli->real_escape_string($judgeArray[$i]['turns'][$j]['np']),
                $igrtSqli->real_escape_string($judgeArray[$i]['turns'][$j]['p']),
                $judgeArray[$i]['turns'][$j]['choice'],         
                $judgeArray[$i]['turns'][$j]['confidence'],         
                $igrtSqli->real_escape_string($judgeArray[$i]['turns'][$j]['reason'])        
            );        
      }
      echo $sql."<br />";
      $igrtSqli->query($sql);
    }
  }
}
echo 'done';


