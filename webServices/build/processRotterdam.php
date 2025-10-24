<?php
// -----------------------------------------------------------------------------
// 
// web service to recreate Step1 data from downloaded files
// 
// NOTE: ACTUAL ->query() is disabled in case of accidental use
// 
// -----------------------------------------------------------------------------

$ExperimentConfigurator;
$ExperimentViewModel;
$Step1Runner;
$Server;
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
$permissions = $_GET['permissions'];
$uid = $_GET['uid'];
$exptId = 263;

    
function inUidArray($jUid) {
    global $uidArray;
    foreach ($uidArray as $uidA) {
        if ($uidA['jUid']==$jUid) { return true;}
    }
    return false;
}

if (($uid==28) && ($permissions==1024)) {
  include_once $root_path.'/domainSpecific/mySqlObject.php';
  $lines = file("RotterdamSession1Turns.txt", FILE_IGNORE_NEW_LINES);
  $igrArray = array();
  $currentIGR = 0;
  foreach ($lines as $line) {
    if (substr($line, 0, 3) == "Day") {
      // next IG
      $igrParams = explode(',', $line);
      $dayNo = substr($igrParams[0], -1);
      $sessionNo = substr($igrParams[1], -1);
      $jType = (substr($igrParams[2], -1) == 'X') ? 0 : 1;
      $jNo = intval(substr($igrParams[3], -1)) - 1;
      $uidParams = explode('=', $igrParams[4]);
      $uid = intval($uidParams[1]) - 1;
      $sideParams = explode(':', $igrParams[5]);
      $npLeft = $sideParams[1] == " NPR on left" ? 1 : 0; // NB literal space at front is important
      if (isset($igrItem)) { 
        if (isset($currentTurn)) {
          array_push($igrItem['turns'], $currentTurn);
          unset($currentTurn);                      
        }
        array_push($igrArray, $igrItem);
        unset($igrItem);
      }
      $igrItem = array (
        'dayNo' => $dayNo,
        'sessionNo' => $sessionNo,
        'jType' => $jType,
        'jNo' => $jNo,
        'uid' => $uid,
        'npLeft' => $npLeft,
        'turns' => array()
      );
      $currentTurnPtr = 0;
    }
    else {
      $lineType = substr($line, 0, 1);
      switch ($lineType) {
        case 'Q' : {
          $qParams = explode(':', $line);
          $q = '';
          for ($i=1; $i<count($qParams); $i++) {
            $q.= $qParams[$i];
          }
          ++$currentTurnPtr;
          if (isset($currentTurn)) { 
            array_push($igrItem['turns'], $currentTurn);
            unset($currentTurn);            
          }
          $currentTurn = array(
            'qNo' => $currentTurnPtr,
            'q' => $q,
            'npr' => '',
            'pr' => '',
            'choice' => -1,
            'rating' => '',
            'reason' => '',
          );
          break;
        }
        case 'N' : {
          $npParams = explode(':', $line);
          $np = '';
          for ($i=1; $i<count($npParams); $i++) {
            $np.= $npParams[$i];
          }
          $currentTurn['npr'] = $np;
          break;
        }
        case 'P' : {
          $pParams = explode(':', $line);
          $p = '';
          for ($i=1; $i<count($pParams); $i++) {
            $p.= $pParams[$i];
          }
          $currentTurn['pr'] = $p;
          break;
        }
        case 'c' : {
          $cParams = explode(':', $line);
          $currentTurn['choice'] = ((($cParams[1] == 'NPR') && ($npLeft == 0)) || (($cParams[1] == 'PR') && ($npLeft == 1))) ? 0 : 1 ;
          break;
        }
        case 'r' : {
          $rParams = explode(':', $line);
          if (substr($line, 0, 2) == "ra") {
            $currentTurn['rating'] = $rParams[1];
          }
          else {
            $reason = "";
            for ($i=1; $i<count($rParams); $i++) {
              $reason.= $rParams[$i];
            }
            $currentTurn['reason'] = $reason;
          }
          break;
        }
        case 'F' : {
          ++$currentTurnPtr;
          if (isset($currentTurn)) { 
            array_push($igrItem['turns'], $currentTurn);
            unset($currentTurn);            
          }
          $currentTurn = array(
            'qNo' => $currentTurnPtr,
            'q' => 'FINAL',
            'npr' => 'FINAL',
            'pr' => 'FINAL',
            'choice' => -1,
            'rating' => '',
            'reason' => '',
          );
          break;
        }
      }
    }
  }
  foreach ($igrArray as $igr) {
    $npLeft = $igr['npLeft'];
    $uid = $igr['uid'];
    $dayNo = $igr['dayNo'];
    $sessionNo = $igr['sessionNo'];
    $jType = $igr['jType'];
    $jNo = $igr['jNo'];
    foreach ($igr['turns'] as $turn) {
      $insertQry = sprintf("INSERT INTO dataSTEP1 (exptId, jType, jNo, sessionNo, dayNo, npLeft, qNo, q, npr, pr, choice, rating, reason) "
          . "VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
          $exptId,
          $jType,
          $jNo,
          $sessionNo,
          $dayNo,
          $npLeft,
          $turn['qNo'],
          $igrtSqli->real_escape_string($turn['q']),
          $igrtSqli->real_escape_string($turn['npr']),
          $igrtSqli->real_escape_string($turn['pr']),
          $turn['choice'],
          $turn['rating'],
          $igrtSqli->real_escape_string($turn['reason'])
       );
      //$igrtSqli->query($insertQry);
    }
  }
  echo 'done';
}

