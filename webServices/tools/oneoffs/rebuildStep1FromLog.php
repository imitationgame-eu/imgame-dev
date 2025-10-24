<?php
  ini_set('display_errors', 'On');
  error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
  include_once $root_path.'/domainSpecific/mySqlObject.php';       
  include_once $root_path.'/ws/models/class.step1AllocationModel.php';

  $playerCount = 0;     // will be derived from log
  $exptId = 0;          // will be derived from log
  $dayNo = 0;           // will be derived from log
  $sessionNo = 0;       // will be derived from log

  // arrays of Step1 components
  $mapClientIdToPlayer;
  $playerAllocations;
  $Ratings;
  $finalRatings;
  $jQCnt = array();
  $NPCnt = array();
  $PCnt = array();
  $JRCnt = array();
  $JudgeArray = array();
  $sessionDetails = array();

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// functions

  function getSessionDetails($datalines) {
    $sdArray = array();
    foreach ($datalines as $dl) {
      if ($dl["count"] > 0) {
        $source = $dl["data"];  
        $details = explode(':', $source);
        $msgType = $details[0];
        if ($msgType == "initStep1") {
          $clientId = $details[3];
          $msg = urldecode($details[5]);
          $step1Details = explode('--', $msg);
          $exptId = substr($step1Details[1], 1);
          $dayNo = substr($step1Details[2], 1);
          $sessionNo = substr($step1Details[3], 1);
          $judgeCount = substr($step1Details[5], 1);
          $tempItem = array (
            'exptId' => $exptId,
            'dayNo' => $dayNo,
            'sessionNo' => $sessionNo,
            'judgeCount' => $judgeCount * 2,
          );
          array_push($sdArray, $tempItem);       
        }
      }
    }    
    return $sdArray;
  }
  
  function getMinMaxLogin() {
    global $igrtSqli;
    global $sessionDetails;
    $mmArray = array();
    $uidQry = sprintf("SELECT MIN(uid) AS uid FROM igActiveStep1Users WHERE exptId='%s' AND day='%s' AND session='%s'",
        $sessionDetails['exptId'], $sessionDetails['dayNo'], $sessionDetails['sessionNo'] );
    $uidResult = $igrtSqli->query($uidQry);
    $uidRow = $uidResult->fetch_object();
    $minUid = $uidRow->uid;
    $maxUid = $minUid + $sessionDetails['judgeCount'] - 1;
    $mmArray['minUid'] = $minUid;
    $mmArray['maxUid'] = $maxUid;
    return $mmArray;
  }

  function getLoginJoins($datalines) {
    $minmaxLoginUids = getMinMaxLogin();
    $ljArray = array();
    foreach ($datalines as $dl) {
      if ($dl["count"] > 0) {
        $source = $dl["data"];  
        $details = explode(':', $source);
        $msgType = $details[0];
        if ($msgType == "loginJoin") {
          $clientId = $details[3];
          $msg = urldecode($details[5]);
          $loginDetails = explode('--', $msg);
          $uid = substr($loginDetails[1], 1);
          $exptId = substr($loginDetails[2], 1);
          $jType = substr($loginDetails[3], 1);
          $jNo = substr($loginDetails[4], 1);
          $dayNo = substr($loginDetails[5], 1);
          $sessionNo = substr($loginDetails[6], 1);
          if (($uid >= $minmaxLoginUids['minUid']) && ($uid <= $minmaxLoginUids['maxUid'])) {
            $tempItem = array (
              'clientId' => $clientId,
              'msg' => $msg,
              'uid' => $uid,
              'exptId' => $exptId,
              'jType' => $jType,
              'jNo' => $jNo,
              'dayNo' => $dayNo,
              'sessionNo' => $sessionNo,
            );
            array_push($ljArray, $tempItem); 
          }
        }
      }
    }
    return $ljArray;
  }
  
  function populateJQ($datalines) {
    global $jQCnt;
    global $JudgeArray;
    foreach ($datalines as $dl) {
      if ($dl["count"] > 0) {
        $source = $dl["data"];  
        $details = explode(':', $source);
        $msgType = $details[0];
        if ($msgType == "JQ") {
          $clientId = $details[3];
          $playerNo = getPlayerNoFromClientID($clientId);
          $msg = urldecode($details[5]);
          $content = substr($msg, 6);
          $turnPtr = ++$jQCnt[$playerNo];
          $JudgeArray[$playerNo][$turnPtr]['jcid'] = $clientId; 
          $JudgeArray[$playerNo][$turnPtr]['npcid'] = -1; 
          $JudgeArray[$playerNo][$turnPtr]['pcid'] = -1; 
          $JudgeArray[$playerNo][$turnPtr]['q'] = $content; 
          $JudgeArray[$playerNo][$turnPtr]['npr'] = '';
          $JudgeArray[$playerNo][$turnPtr]['pr'] = '';
          $JudgeArray[$playerNo][$turnPtr]['choice'] = -1;
          $JudgeArray[$playerNo][$turnPtr]['confidence'] = '';
          $JudgeArray[$playerNo][$turnPtr]['reason'] = '';
          $JudgeArray[$playerNo][$turnPtr]['isFinal'] = -1;
        }
      }
    }
  }
  
  function populateNPA($datalines) {
    global $NPCnt;
    global $JudgeArray;
    foreach ($datalines as $dl) {
      if ($dl["count"] > 0) {
        $source = $dl["data"];  
        $details = explode(':', $source);
        $msgType = $details[0];
        if ($msgType == "NPA") {
          $clientId = $details[3];
          $playerNo = getPlayerNoFromClientID($clientId);
          $npNo = getPlayerNoForNPAFromClientID($clientId);
          //echo $playerNo." is NPA for ".$npNo."<br />";
          $msg = urldecode($details[5]);
          $content = substr($msg, 6);
          $turnPtr = ++$NPCnt[$npNo];
          $JudgeArray[$npNo][$turnPtr]['npr'] = $content;
          $JudgeArray[$npNo][$turnPtr]['npcid'] = $clientId; 
        }
      }
    }
  }

  function populatePA($datalines) {
    global $PCnt;
    global $JudgeArray;
    foreach ($datalines as $dl) {
      if ($dl["count"] > 0) {
        $source = $dl["data"];  
        $details = explode(':', $source);
        $msgType = $details[0];
        if ($msgType == "PA") {
          $clientId = $details[3];
          $playerNo = getPlayerNoFromClientID($clientId);
          $pNo = getPlayerNoForPAFromClientID($clientId);
          //echo $playerNo." is PA for ".$pNo."<br />";
          $msg = urldecode($details[5]);
          $content = substr($msg, 6);
          $turnPtr = ++$PCnt[$pNo];
          $JudgeArray[$pNo][$turnPtr]['pr'] = $content;
          $JudgeArray[$pNo][$turnPtr]['pcid'] = $clientId; 
        }
      }
    }
  }

  function populateJR($datalines) {
    global $JRCnt;
    global $JudgeArray;
    foreach ($datalines as $dl) {
      if ($dl["count"] > 0) {
        $source = $dl["data"];  
        $details = explode(':', $source);
        $msgType = $details[0];
        if (($msgType == "JR") || ($msgType == "JlastR")) {
          $clientId = $details[3];
          $playerNo = getPlayerNoFromClientID($clientId);
          $rating = $details[5];
          $ratingDetails = explode('--', $rating);
          $reason = $ratingDetails[2];
          $reason = urldecode($reason);
          $content = substr($reason, 6);
          $choice = substr($ratingDetails[1], 1);
          $confidence = substr($ratingDetails[3], 1);
          if ($confidence > '') {
            // handle spurious double JR messages in log after reconnect
            $turnPtr = ++$JRCnt[$playerNo];
            $JudgeArray[$playerNo][$turnPtr]['choice'] = $choice;
            $JudgeArray[$playerNo][$turnPtr]['confidence'] = $confidence;
            $JudgeArray[$playerNo][$turnPtr]['reason'] = $reason;
          }
        }
      }
    }
  }
  
  function populateJfinalR($datalines) {
    global $JRCnt;
    global $JudgeArray;
    foreach ($datalines as $dl) {
      if ($dl["count"] > 0) {
        $source = $dl["data"];  
        $details = explode(':', $source);
        $msgType = $details[0];
        if ($msgType == "JfinalR") {
          $clientId = $details[3];
          $playerNo = getPlayerNoFromClientID($clientId);
          $rating = $details[5];
          $ratingDetails = explode('--', $rating);
          $reason = $ratingDetails[2];
          $reason = urldecode($reason);
          $content = substr($reason, 6);
          $choice = substr($ratingDetails[1], 1);
          $confidence = substr($ratingDetails[3], 1);
          $turnPtr = ++$JRCnt[$playerNo];
          $JudgeArray[$playerNo][$turnPtr]['q'] = 'FINAL'; 
          $JudgeArray[$playerNo][$turnPtr]['npr'] = 'FINAL';
          $JudgeArray[$playerNo][$turnPtr]['pr'] = 'FINAL';
          $JudgeArray[$playerNo][$turnPtr]['choice'] = $choice;
          $JudgeArray[$playerNo][$turnPtr]['confidence'] = $confidence;
          $JudgeArray[$playerNo][$turnPtr]['reason'] = $reason;
          $JudgeArray[$playerNo][$turnPtr]['isFinal'] = 1;
        }
      }
    }
  }

  function isUniqueMapping($mappings, $lj) {
    foreach ($mappings as $map) {
      if ($map['uid'] == $lj['uid']) { return false; }
    }
    return true;
  }
  
  function makeMapping($lj) {
    global $playerCount;
    global $exptId;
    global $dayNo;
    global $sessionNo;
    global $igrtSqli;
    if ($exptId == 0) {
      // get session parameters
      $activeUserQry = sprintf("SELECT * FROM igActiveStep1Users WHERE uid='%s'", $lj['uid']);
      $activeUserResult = $igrtSqli->query($activeUserQry);
      $activeUserRow = $activeUserResult->fetch_object();
      $exptId = $activeUserRow->exptId;
      $dayNo = $activeUserRow->day;
      $sessionNo = $activeUserRow->session;
    }
    // get this player's parameters
    $playerDetailsQry = sprintf("SELECT * FROM igActiveStep1Users WHERE uid='%s'", $lj['uid']);
    $playerDetailsResult = $igrtSqli->query($playerDetailsQry);
    $playerDetailsRow = $playerDetailsResult->fetch_object();
    $jType = $playerDetailsRow->jType;
    $jNo = $playerDetailsRow->jNo;
    $playerNo = ($jType == 1) ? $jNo*2+1 : ($jNo+1)*2;
    ++$playerCount;
    return array('clientId' => $lj['clientId'], 'uid'=> $lj['uid'], 'playerNo'=>$playerNo, 'jType'=> $jType, 'jNo' => $jNo);    
  }
  
  function getMappings($loginJoins) {
    $mappings = array();
    foreach ($loginJoins as $lj) {
      if (isUniqueMapping($mappings, $lj)) {
        array_push($mappings, makeMapping($lj));
      }
    }
    return $mappings;
  }
  
  function getPlayerNoFromID($uid) {
    global $mapClientIdToPlayer;  
    foreach ($mapClientIdToPlayer as $map) {
      if ($map['uid'] == $uid) { return $map['playerNo']; }
    }
    return -1;
  } 
  
  function getPlayerNoFromClientID($cid) {
    global $mapClientIdToPlayer;  
    foreach ($mapClientIdToPlayer as $map) {
      if ($map['clientId'] == $cid) { return $map['playerNo']; }
    }
    echo 'houston with client# '.$cid;
    return -1;
  } 

  function getPlayerNoForNPAFromClientID($cid) {
    global $mapClientIdToPlayer;
    $playerNo = -1;
    foreach ($mapClientIdToPlayer as $map) {
      if ($map['clientId'] == $cid) { $playerNo = $map['playerNo']; }
    }
    return getNPAFromPlayer($playerNo);
  } 

  function getPlayerNoForPAFromClientID($cid) {
    global $mapClientIdToPlayer;
    $playerNo = -1;
    foreach ($mapClientIdToPlayer as $map) {
      if ($map['clientId'] == $cid) { $playerNo = $map['playerNo']; }
    }
    return getPAFromPlayer($playerNo);
  } 

  function getNPAFromPlayer($playerNo) {
    global $playerAllocations;
    $jDetails = getNoTypeFromPlayerNo($playerNo);
    $jType = $jDetails['jType'];
    $jNo = $jDetails['jNo'];
    //echo "NP Player $playerNo jType $jType jNo $jNo<br />";
    if ($jType == 0) {
      // find even judge this player is NP to
      for ($ptr = 0; $ptr<count($playerAllocations->evenJudges); $ptr++) {
        if ($playerAllocations->evenJudges[$ptr]['otherNPs'][0] == $jNo) { $pNo = $ptr; }
      }    
      $jPlayer = ($pNo + 1) * 2;
    } 
    else {
      // find odd judge this player is NP to
      for ($ptr = 0; $ptr<count($playerAllocations->oddJudges); $ptr++) {
        if ($playerAllocations->oddJudges[$ptr]['otherNPs'][0] == $jNo) { $pNo = $ptr; }
      }         
      $jPlayer = ($pNo  * 2) + 1;
    } 
    return $jPlayer;
  }
  
  function getPAFromPlayer($playerNo) {
    global $playerAllocations;
    $jDetails = getNoTypeFromPlayerNo($playerNo);
    $jType = $jDetails['jType'];
    $jNo = $jDetails['jNo'];
    //echo "P Player $playerNo jType $jType jNo $jNo<br />";
    if ($jType == 0) {
      // find odd judge this player is P to
      for ($ptr = 0; $ptr<count($playerAllocations->oddJudges); $ptr++) {
        if ($playerAllocations->oddJudges[$ptr]['otherPs'][0] == $jNo) { $pNo = $ptr; }
      }    
      $jPlayer = ($pNo  * 2) + 1;
    } 
    else {
      // find even judge this player is P to
      for ($ptr = 0; $ptr<count($playerAllocations->evenJudges); $ptr++) {
        if ($playerAllocations->evenJudges[$ptr]['otherPs'][0] == $jNo) { $pNo = $ptr; }
      }         
      $jPlayer = ($pNo + 1) * 2;
    } 
    return $jPlayer;
  }
  
  function getNoTypeFromPlayerNo($playerNo) {
    $jType = ($playerNo % 2 == 0) ? 0 : 1;
    if ($jType == 0) {
      $jNo = round( ($playerNo  / 2) - 1, PHP_ROUND_HALF_DOWN);
    }
    else {
      $jNo = round( ($playerNo - 1) / 2, PHP_ROUND_HALF_DOWN);
    }
    return array('jType' => $jType, 'jNo' => $jNo);
  }

  function getUIDFromPlayerNo($pn) {
    global $mapClientIdToPlayer;
    foreach ($mapClientIdToPlayer as $map) {
      if ($map['playerNo'] == $pn) { return $map['uid']; }
    }
  } 
  
  function putStep1Data() {
    global $igrtSqli;
    global $JudgeArray;
    global $sessionDetails;
    global $playerCount;
    $exptId = $sessionDetails['exptId'];
    $dayNo = $sessionDetails['dayNo'];
    $sessionNo = $sessionDetails['sessionNo'];
    for ($playerNo=1; $playerNo<=$playerCount; $playerNo++) {
      $jDetails = getNoTypeFromPlayerNo($playerNo);
      $jNo = $jDetails['jNo'];
      $jType = $jDetails['jType'];
      $uid = getUIDFromPlayerNo($playerNo);
      $npLeft = ($jType == 0) ? 1 : 0;
      $turnCnt = count($JudgeArray[$playerNo]);
      echo $playerNo.' : '.$turnCnt.'<br />';
      if ($turnCnt > 0) {
        for ($j=1; $j<=$turnCnt; $j++) {
          if ( (!isset($JudgeArray[$playerNo][$j]['q'])) ||
              (!isset($JudgeArray[$playerNo][$j]['npr'])) ||
              (!isset($JudgeArray[$playerNo][$j]['q'])) ) {
            echo 'error on turn '.$j.'<br />';
          }
          $sql = sprintf("INSERT INTO dataSTEP1 (uid, exptId, jType, JNo, sessionNo, dayNo, npLeft, qNo, q, npr, pr, choice, rating, reason) "
              . "VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
              $uid, $exptId, $jType, $jNo, $sessionNo, $dayNo, $npLeft, $j, 
              $igrtSqli->real_escape_string($JudgeArray[$playerNo][$j]['q']),
              $igrtSqli->real_escape_string($JudgeArray[$playerNo][$j]['npr']),
              $igrtSqli->real_escape_string($JudgeArray[$playerNo][$j]['pr']),
              $JudgeArray[$playerNo][$j]['choice'],
              $igrtSqli->real_escape_string($JudgeArray[$playerNo][$j]['confidence']),
              $igrtSqli->real_escape_string($JudgeArray[$playerNo][$j]['reason'])
              );
          //echo $sql."<br />";
          //$igrtSqli->query($sql);
        }
      }
    }
    
  }
  
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

for ($k=2; $k<=2; $k++) {
  echo "session ".$k. "<br />";
  $playerCount = 0;     
  $exptId = 0;          
  $dayNo = 0;           
  $sessionNo = 0;       
  $fn = "berlin".$k.".txt";
  $lines = file($fn, FILE_IGNORE_NEW_LINES);
  $lineCnt = count($lines);
  $datalines = array();
  for ($i=0; $i<$lineCnt; $i++) {
    if (isset($temp)) { unset($temp); }
    $temp = array("count" => strlen($lines[$i]), "data"=>$lines[$i]);
    array_push($datalines, $temp);
  }
  $sessionDetailsList = getSessionDetails($datalines);
  $sessionDetails = $sessionDetailsList[count($sessionDetailsList) - 1];  // assume last always
  $loginJoins = getLoginJoins($datalines);
//  $status = print_r($loginJoins, true)."<br />";
//  echo $status;
  $mapClientIdToPlayer = getMappings($loginJoins);
//  $status = print_r($mapClientIdToPlayer, true)."<br />";
//  echo $status;
  $playerCount = count($mapClientIdToPlayer); // could check this as query against edSessions
  $judgeCount = $playerCount / 2;
  echo $playerCount.' '.$judgeCount."<br />";
  $playerAllocations = new roleAllocations($judgeCount, 1);
  $playerAllocations->generateFixed();
  $status = print_r($playerAllocations->mappings, true)."<br />";
  echo $status;
  $turnArray = array();
  for ($i=1; $i<=$playerCount; $i++) {
    $jQCnt[$i] = 0;
    $NPCnt[$i] = 0;
    $PCnt[$i] = 0;
    $JRCnt[$i] = 0;
    array_push($JudgeArray, $turnArray);
  } 
  populateJQ($datalines);
//  echo "post populate JQ <br /> ";
//  $status = print_r($JudgeArray[2], true)."<br />";
//  echo $status;
  populateNPA($datalines);
  populatePA($datalines);
  populateJR($datalines);
  echo "post populate rating <br /> ";
  $status = print_r($JudgeArray[2], true)."<br />";
  echo $status;
  populateJfinalR($datalines);
  putStep1Data();
  unset($mapClientIdToPlayer);
  unset($playerAllocations);
  unset($datalines);
  unset($lines);
  unset($loginJoins);
  unset($JQ);
}

echo 'done';
//$debug = print_r($JudgeArray);
//echo $debug;


