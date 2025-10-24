<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';      
include_once $root_path.'/helpers/parseJSON.php';              // parse and escape JSON elements
include_once $root_path.'/helpers/admin/class.metadataConverter.php';
include_once $root_path.'/helpers/models/class.experimentModel.php';
include_once $root_path.'/helpers/step3/class.shuffleController.php';

// <editor-fold defaultstate="collapsed" desc=" functions">

  function updateTable($exptId, $tblName, $tblFieldName, $value) {
    global $igrtSqli;
    $sql = sprintf("UPDATE %s SET %s='%s' WHERE exptId='%s'", $tblName, $tblFieldName, $value, $exptId);
    $igrtSqli->query($sql);
    return $sql;
  }

  function updateTableSubItem($exptId, $tblName, $tblFieldName, $value, $dim1Name, $dim2Name, $dim1Value, $dim2Value) {
    global $igrtSqli;
    if ($dim2Name == 'unset') {
      $sql = sprintf("UPDATE %s SET %s='%s' WHERE "
          . "exptId='%s' AND %s='%s'", 
          $tblName, $tblFieldName, $value, $exptId, $dim1Name, $dim1Value);
      
    }
    else {
      $sql = sprintf("UPDATE %s SET %s='%s' WHERE "
          . "exptId='%s' AND %s='%s' AND %s='%s'", 
          $tblName, $tblFieldName, $value, $exptId, $dim1Name, $dim1Value, $dim2Name, $dim2Value);      
    }
    $igrtSqli->query($sql);
    return $sql;
  }

  function processSessionChange($exptId, $dayNo, $sessionsChange, $oldSessionsValue) {
    global $igrtSqli;
    if ($sessionsChange < 0) {
      // delete
      $noChanges = abs($sessionsChange);
      for ($i=0; $i<$noChanges; $i++) {
        $sessionNo = $oldSessionsValue - $i;
        $sql = sprintf("DELETE FROM edSessions WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s'",
            $exptId, $dayNo, $sessionNo);
        //echo $sql;
        $igrtSqli->query($sql);
      }
    }
    else {
      // insert
      for ($i=1; $i<=$sessionsChange; $i++) {
        $sessionNo = $oldSessionsValue + $i;
        $sql = sprintf("INSERT INTO edSessions (exptId, dayNo, sessionNo, time) "
            . "VALUES('%s','%s','%s','')",
          $exptId, $dayNo, $sessionNo);
        //echo $sql;
        $igrtSqli->query($sql);
      }
    }  
  }

  function processDayChange($exptId, $daysChange, $oldDaysValue, $sessionsChange, $oldSessionsValue ) {
    global $igrtSqli;
    // firstly add or delete days with existing sessionsNo
    if ($daysChange < 0) {
      // delete days
      $noChanges = abs($daysChange);
      $newNoDays = $oldDaysValue - $noChanges; 
      for ($i=0; $i<$noChanges; $i++) {
        $dayNo = $oldDaysValue - $i;
        $sql = sprintf("DELETE FROM edSessions WHERE exptId='%s' AND dayNo='%s'", $exptId, $dayNo);
        //echo $sql;
        $igrtSqli->query($sql);
      }
    }
    else {
      // insert days
      $newNoDays = $oldDaysValue + $daysChange;
      for ($i=1; $i<=$daysChange; $i++) {
        $dayNo = $oldDaysValue + $i;
        for ($j=1; $j<=$oldSessionsValue; $j++) {
          $sql = sprintf("INSERT INTO edSessions (exptId, dayNo, sessionNo, time) VALUES('%s','%s','%s','')", $exptId, $dayNo, $j);
          //echo $sql;
          $igrtSqli->query($sql);        
        }
      }    
    }
    if ($sessionsChange != 0) {
      for ($i=1; $i<=$newNoDays; $i++) {
        processSessionChange($exptId, $i, $sessionsChange, $oldSessionsValue);
      } 
    }
  }

  function createPassword($length) {
    $chars = "abcdefghjkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ";
    $size = strlen( $chars );
    $str = '';
    for( $i = 0; $i < $length; $i++ ) {
      $str.= $chars[ rand(0, $size - 1) ];
    } 
    return $str;
  }

  function doGenerate($isClassic, $exptId, $newJudgesValue, $newDaysValue, $newSessionsValue) {
    global $igrtSqli;
    $delUsersQry = sprintf("DELETE FROM igUsers WHERE exptId='%s'", $exptId);
    $igrtSqli->query($delUsersQry);
    $delActiveUsers = sprintf("DELETE FROM igActiveStep1Users WHERE exptId='%s'", $exptId);
    $igrtSqli->query($delActiveUsers);
    $delClassicUsers = sprintf("DELETE FROM igActiveClassicUsers WHERE exptId='%s'", $exptId);
    $igrtSqli->query($delClassicUsers);
    $delS1Users = sprintf("DELETE FROM igActiveStep1UsersPW WHERE exptId='%s'", $exptId);
    $igrtSqli->query($delS1Users);
    $delS1Users = sprintf("DELETE FROM igActiveClassicUsersPW WHERE exptId='%s'", $exptId);
    $igrtSqli->query($delS1Users);
    $suNoQry = "SELECT * FROM wt_Step1UsersID";
    $suNoResult = $igrtSqli->query($suNoQry);
    if ($suNoResult) {
      $suNoRow = $suNoResult->fetch_object();
      $oldsuNoPtr = $suNoRow->prevValue;
    }

    $domain = $isClassic ? "@classic.com" : "@s1.com";
    $seedNo = $oldsuNoPtr + 1; 
    // ensure that user names always start with an ODD (changed March 2014) name, so that 
    // judge numbering for static allocations works consistently
    $seedNo = ($seedNo % 2 == 0) ? $seedNo + 1 : $seedNo; 
    for ($i=1; $i<=$newDaysValue; $i++) {
      for ($j=1; $j<=$newSessionsValue; $j++) {
        for ($k=0; $k<$newJudgesValue*2; $k++) {        
          if ($isClassic == 1) {
            //echo 'creating classic judges';
            // 3 roles
            $uname = $seedNo.$domain;
            $pw = createPassword(5);
            $hash_str = hash('sha1', $pw);           
            $createUserSql = "INSERT INTO igUsers (permissions, activated, registrationDT, email, password, exptId) ";
            $createUserSql.= sprintf("VALUES ('0', '1', NOW(), '%s', '%s', '%s')", $uname, $hash_str, $exptId);
            $igrtSqli->query($createUserSql);
            $uid = $igrtSqli->insert_id;
            $createS1userSql = sprintf("INSERT INTO igActiveClassicUsers (uid, exptId, dayNo, sessionNo, role, jState, respState, groupNo) "
                . "VALUES('%s', '%s', '%s', '%s', 'J', '0', '0', '%s')", $uid, $exptId, $i, $j, $k);
            $igrtSqli->query($createS1userSql);             
            $createS1userPWSql = sprintf("INSERT INTO igActiveClassicUsersPW (uid, plainText, exptId) VALUES('%s','%s','%s')", $uid, $pw, $exptId);
            $igrtSqli->query($createS1userPWSql);
            //echo $createS1userPWSql;
            ++$seedNo;
            
            $uname = $seedNo.$domain;
            $pw = createPassword(5);
            $hash_str = hash('sha1', $pw);           
            $createUserSql = "INSERT INTO igUsers (permissions, activated, registrationDT, email, password, exptId) ";
            $createUserSql.= sprintf("VALUES ('0', '1', NOW(), '%s', '%s', '%s')", $uname, $hash_str, $exptId);
            $igrtSqli->query($createUserSql);
            $uid = $igrtSqli->insert_id;
            $createS1userSql = sprintf("INSERT INTO igActiveClassicUsers (uid, exptId, dayNo, sessionNo, role, jState, respState, groupNo) "
                . "VALUES('%s', '%s', '%s', '%s', 'NP', '0', '0', '%s')", $uid, $exptId, $i, $j, $k);
            $igrtSqli->query($createS1userSql);             
            $createS1userPWSql = sprintf("INSERT INTO igActiveClassicUsersPW (uid, plainText, exptId) VALUES('%s','%s','%s')", $uid, $pw, $exptId);
            $igrtSqli->query($createS1userPWSql);
            //echo $createS1userPWSql;
            ++$seedNo;

            $uname = $seedNo.$domain;
            $pw = createPassword(5);
            $hash_str = hash('sha1', $pw);           
            $createUserSql = "INSERT INTO igUsers (permissions, activated, registrationDT, email, password, exptId) ";
            $createUserSql.= sprintf("VALUES ('0', '1', NOW(), '%s', '%s', '%s')", $uname, $hash_str, $exptId);
            $igrtSqli->query($createUserSql);
            $uid = $igrtSqli->insert_id;
            $createS1userSql = sprintf("INSERT INTO igActiveClassicUsers (uid, exptId, dayNo, sessionNo, role, jState, respState, groupNo) "
                . "VALUES('%s', '%s', '%s', '%s', 'P', '0', '0', '%s')", $uid, $exptId, $i, $j, $k);
            $igrtSqli->query($createS1userSql);             
            $createS1userPWSql = sprintf("INSERT INTO igActiveClassicUsersPW (uid, plainText, exptId) VALUES('%s','%s','%s')", $uid, $pw, $exptId);
            $igrtSqli->query($createS1userPWSql);
            //echo $createS1userPWSql;
            ++$seedNo;
            
          }
          else {
            //echo 'creating non classic users';
            $uname = $seedNo.$domain;
            $jType = $seedNo % 2;  // 0= even, 1= odd;
            $jNo =  round($k/2, 0, PHP_ROUND_HALF_DOWN); // jNo=0 for 0&1, 1 for 2&3 etc
            $pw = createPassword(5);
            $hash_str = hash('sha1', $pw);           
            $createUserSql = "INSERT INTO igUsers (permissions, activated, registrationDT, email, password, exptId) ";
            $createUserSql.= sprintf("VALUES ('0', '1', NOW(), '%s', '%s', '%s')", $uname, $hash_str, $exptId);
            $igrtSqli->query($createUserSql);
            //echo $createUserSql;
            // get uid
            $uid = $igrtSqli->insert_id;
            $createS1userSql = sprintf("INSERT INTO igActiveStep1Users (uid, exptId, day, session, jType, jNo) "
                . "VALUES('%s', '%s', '%s', '%s', '%s', '%s')", $uid, $exptId, $i, $j, $jType, $jNo);
            $igrtSqli->query($createS1userSql);             
            //echo $createS1userSql;
            $createS1userPWSql = sprintf("INSERT INTO igActiveStep1UsersPW (uid, plainText, exptId) VALUES('%s','%s','%s')", $uid, $pw, $exptId);
            $igrtSqli->query($createS1userPWSql);
            //echo $createS1userPWSql;
            ++$seedNo;
          }
        } 
      }         
    }
    $updateS1noQry = sprintf("UPDATE wt_Step1UsersID SET prevValue='%s'", $seedNo);
    $igrtSqli->query($updateS1noQry);
    $showS1setSql = sprintf("UPDATE edExptStatic_refactor SET s1usersSet=1 WHERE exptId='%s'", $exptId);
    $igrtSqli->query($showS1setSql);
  }

  function processCategoriesChange($exptId, $step, $oldCategoriesValue, $newCategoriesValue) {
    global $igrtSqli;
    if ($oldCategoriesValue < $newCategoriesValue) {
      $diff = $newCategoriesValue - $oldCategoriesValue;
      for ($i=0; $i<$diff; $i++) {
        $categoryValue = $oldCategoriesValue + $i +1;
        $insertQry = sprintf("INSERT INTO edAlignmentControlLabels (exptId, step, displayOrder, label) "
            . "VALUES('%s','%s','%s','%s')",
            $exptId, $step, $categoryValue, "category".$categoryValue);
        $igrtSqli->query($insertQry);
      }
    }
    else {
      $diff = $oldCategoriesValue - $newCategoriesValue;
      for ($i=0; $i<$diff; $i++) {
        $deleteQry = sprintf("DELETE FROM edAlignmentControlLabels "
            . "WHERE exptId='%s' AND step='%s' AND displayOrder='%s'",
            $exptId, $step, ($oldCategoriesValue - $i));
        echo $deleteQry;
        $igrtSqli->query($deleteQry);
      }
    }
  }

  function processLikertLabelsChange($exptId, $wLikert, $oldLikertValue, $newLikertValue) {
    global $igrtSqli;
    if ($oldLikertValue < $newLikertValue) {
      $diff = $newLikertValue - $oldLikertValue;
      for ($i=0; $i<$diff; $i++) {
        $confidenceValue = $oldLikertValue + $i +1;
        $insertQry = sprintf("INSERT INTO edLabels (exptId, whichLikert, confidenceValue, label) "
            . "VALUES('%s','%s','%s','%s')",
            $exptId, $wLikert, $confidenceValue, "confidence".$confidenceValue);
        echo $insertQry;
        $igrtSqli->query($insertQry);
      }
    }
    else {
      $diff = $oldLikertValue - $newLikertValue;
      for ($i=0; $i<$diff; $i++) {
        $deleteQry = sprintf("DELETE FROM edLabels "
            . "WHERE exptId='%s' AND whichLikert='%s' AND confidenceValue='%s'",
            $exptId, $wLikert, ($oldLikertValue - $i));
        echo $deleteQry;
        $igrtSqli->query($deleteQry);
      }
    }
  }
  
  function setupBalancer($exptId, $jType, $respNo) {
    global $igrtSqli;
    global $eModel;
    $flagName = ($jType == 0) ? "step2EvenConfigured" : "step2OddConfigured";
    $clearQry = sprintf("DELETE FROM wt_Step2Balancer WHERE exptId='%s' AND jType='%s'", $exptId, $jType);
    $igrtSqli->query($clearQry);
    // find # of datasets for the expt and jType and create appropriate entries
    $step2dsPtr = 0;
    for ($dayNo = 1; $dayNo <= $eModel->noDays; $dayNo++) { 
      for ($sessionNo = 1; $sessionNo <= $eModel->noSessions; $sessionNo++) {
        $hasDataSql = sprintf("SELECT * FROM md_dataStep1reviewed WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND jType='%s'", $exptId, $dayNo, $sessionNo, $jType);
        $hdr = $igrtSqli->query($hasDataSql);
        if ($hdr) {
          $maxSql = sprintf("SELECT MAX(jNo) as maxjNo FROM md_dataStep1reviewed WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND jType='%s'", $exptId, $dayNo, $sessionNo, $jType);
          $meResult = $igrtSqli->query($maxSql);
          $meRow = $meResult->fetch_object();
          $maxJNo = $meRow->maxjNo;
          // get relevant discard info
          $jDiscard = 0;
          $discardSql = sprintf("SELECT * FROM wt_Step1Discards WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s'", $exptId, $dayNo, $sessionNo);
          $discardResult = $igrtSqli->query($discardSql);
          if ($discardResult) {
            $discardRow = $discardResult->fetch_object();
            if ($jType == 0) {
              $jDiscard = $discardRow->evenDiscards;         
            }
            else {
              $jDiscard = $discardRow->oddDiscards;          
            }       
          }
          for ($i=0; $i<=$maxJNo; $i++) {
            // check whether this judge discarded and don't insert in balancer if discarded
            $dMarker = pow(2, $i);
            $discardValue = (($jDiscard & $dMarker) == $dMarker) ? 1 : 0;
            if (($jDiscard & $dMarker) != $dMarker) {
              ++$step2dsPtr;
              $label = sprintf("s2_%s_%s_%s", $exptId, $jType, $step2dsPtr);
              $insSql = sprintf("INSERT INTO wt_Step2Balancer (exptId, jType, jNo, respCount, respMax, actualJNo, dayNo, sessionNo, label) "
                  . "VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                  $exptId, $jType, $i, 0, $respNo, $step2dsPtr, $dayNo, $sessionNo, $label);
              $igrtSqli->query($insSql);
            }
          }          
        }
      }
    }
    $updateQry = sprintf("UPDATE igExperiments SET %s=1 WHERE exptId='%s'", $flagName, $exptId);
    $igrtSqli->query($updateQry);
  }
  
  function setupInvertedBalancer($exptId, $jType, $respNo) {
    global $igrtSqli;
    global $eModel;
    $flagName = ($jType == 0) ? "step2InvertedEvenConfigured" : "step2InvertedOddConfigured";
    $clearQry = sprintf("DELETE FROM wt_Step2BalancerInverted WHERE exptId='%s' AND jType='%s'", $exptId, $jType);
    $igrtSqli->query($clearQry);
    // find # of datasets for the expt and jType and create appropriate entries 
    $step2dsPtr = 0;
    for ($dayNo = 1; $dayNo <= $eModel->noDays; $dayNo++) { 
      for ($sessionNo = 1; $sessionNo <= $eModel->noSessions; $sessionNo++) {
        $hasDataSql = sprintf("SELECT * FROM md_dataStep1reviewed WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND jType='%s'", $exptId, $dayNo, $sessionNo, $jType);
        $hdr = $igrtSqli->query($hasDataSql);
        if ($hdr) {
          $maxSql = sprintf("SELECT MAX(jNo) as maxjNo FROM md_dataStep1reviewed WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND jType='%s'", $exptId, $dayNo, $sessionNo, $jType);
          $meResult = $igrtSqli->query($maxSql);
          $meRow = $meResult->fetch_object();
          $maxJNo = $meRow->maxjNo;
          // get relevant discard info
          $jDiscard = 0;
          $discardSql = sprintf("SELECT * FROM wt_Step1Discards WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s'", $exptId, $dayNo, $sessionNo);
          $discardResult = $igrtSqli->query($discardSql);
          if ($discardResult) {
            $discardRow = $discardResult->fetch_object();
            if ($jType == 0) {
              $jDiscard = $discardRow->evenDiscards;         
            }
            else {
              $jDiscard = $discardRow->oddDiscards;          
            }       
          }
          for ($i=0; $i<=$maxJNo; $i++) {
            // check whether this judge discarded and don't insert in balancer if discarded
            $dMarker = pow(2, $i);
            $discardValue = (($jDiscard & $dMarker) == $dMarker) ? 1 : 0;
            if (($jDiscard & $dMarker) != $dMarker) {
              ++$step2dsPtr;
              $label = sprintf("s2_%s_%s_%s", $exptId, $jType, $step2dsPtr);
              $insSql = sprintf("INSERT INTO wt_Step2BalancerInverted (exptId, jType, jNo, respCount, respMax, actualJNo, dayNo, sessionNo, label) "
                  . "VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                  $exptId, $jType, $i, 0, $respNo, $step2dsPtr, $dayNo, $sessionNo, $label);
              $igrtSqli->query($insSql);
            }
          }          
        }
      }
    }
    $updateQry = sprintf("UPDATE igExperiments SET %s=1 WHERE exptId='%s'", $flagName, $exptId);
    $igrtSqli->query($updateQry);
  }
  
  function processBalancerValues($exptId, $step, $oddRespMax, $evenRespMax) {
    global $igrtSqli;
    // just in case normal updateTable didn't work as rrow does not yet exist
    $existsSql = sprintf("SELECT * FROM wt_Step2BalancerRespMax WHERE exptId='%s'", $exptId);
    $existsResult = $igrtSqli->query($existsSql);
    if ($existsResult->num_rows == 0) {
      $createSql = sprintf("INSERT INTO wt_Step2BalancerRespMax "
          . "(exptId, oddRespMax, evenRespMax, invertedOddRespMax, invertedEvenRespMax) "
          . "VALUES ('%s','20','20','20','20')", $exptId);
      $igrtSqli->query($createSql);
      if ($step == "s2Balancer") {
        $updateSql = sprintf("UPDATE wt_Step2BalancerRespMax SET "
            . "oddRespMax='%s', evenRespMax='%s' WHERE exptId='%s'",
            $oddRespMax, $evenRespMax, $exptId);
      }
      else {
        $updateSql = sprintf("UPDATE wt_Step2BalancerRespMax SET "
            . "invertedOddRespMax='%s', invertedEvenRespMax='%s' WHERE exptId='%s'",
            $oddRespMax, $evenRespMax, $exptId);
      } 
      $igrtSqli->query($updateSql);
    }
    // now actually do the balancer values
    if ($step == "s2Balancer") {
      setupBalancer($exptId, 0, $evenRespMax);
      setupBalancer($exptId, 1, $oddRespMax);      
    }
    else {
      setupInvertedBalancer($exptId, 0, $evenRespMax);
      setupInvertedBalancer($exptId, 1, $oddRespMax);            
    }
  }
  
  function processShuffles($exptId, $oddS4JudgeCount, $evenS4JudgeCount) {
    global $igrtSqli;
    // create new entries for judge counts if they don't exist - and thus won't have successfully updated in normal processing
    $sql = sprintf("SELECT * FROM wt_Step4JudgeCounts WHERE exptId='%s'", $exptId);
    $r = $igrtSqli->query($sql);
    if ($r->num_rows == 0) {
      $sql = sprintf("INSERT INTO wt_Step4JudgeCounts (exptId, evenS4JudgeCount, oddS4JudgeCount) VALUES('%s', '%s', '%s')",
          $exptId, $evenS4JudgeCount, $oddS4JudgeCount);
      $igrtSqli->query($sql);
    }
    $shuffler = new shuffleControllerClass($igrtSqli, $exptId,  $evenS4JudgeCount, $oddS4JudgeCount);
    $oddJudgeList = $shuffler->doShuffle(1, $oddS4JudgeCount);
    $evenJudgeList = $shuffler->doShuffle(0, $evenS4JudgeCount);
    $shuffler->storeJSON($oddJudgeList, $evenJudgeList);  // store in json table and get in shuffle status page.
  }

  function processSnowShuffles($exptId, $oddS4JudgeCount, $evenS4JudgeCount) {
    global $igrtSqli;
    // create new entries for judge counts if they don't exist - and thus won't have successfully updated in normal processing
    $sql = sprintf("SELECT * FROM wt_Step4JudgeCounts WHERE exptId='%s'", $exptId);
    $r = $igrtSqli->query($sql);
    if ($r->num_rows == 0) {
      $sql = sprintf("INSERT INTO wt_Step4JudgeCounts (exptId, evenS4JudgeCount, oddS4JudgeCount) VALUES('%s', '%s', '%s')",
          $exptId, $evenS4JudgeCount, $oddS4JudgeCount);
      $igrtSqli->query($sql);
    }
    $shuffler = new shuffleControllerClass($igrtSqli, $exptId,  $evenS4JudgeCount, $oddS4JudgeCount);
    $oddJudgeList = []; //$shuffler->doSnowShuffle(1, $oddS4JudgeCount);
    $evenJudgeList = $shuffler->doSnowShuffle(0, $evenS4JudgeCount);
    $shuffler->storeSnowShuffleJSON($oddJudgeList, $evenJudgeList);  // store in json table and get in shuffle status page.
  }

  function processLEShuffles($exptId, $oddS4JudgeCount, $evenS4JudgeCount) {
    global $igrtSqli;
    // create new entries for judge counts if they don't exist - and thus won't have successfully updated in normal processing
//    $sql = sprintf("SELECT * FROM wt_Step4JudgeCounts WHERE exptId='%s'", $exptId);
//    $r = $igrtSqli->query($sql);
//    if ($r->num_rows == 0) {
//      $sql = sprintf("INSERT INTO wt_Step4JudgeCounts (exptId, evenS4JudgeCount, oddS4JudgeCount) VALUES('%s', '%s', '%s')",
//          $exptId, $evenS4JudgeCount, $oddS4JudgeCount);
//      $igrtSqli->query($sql);
//    }
    $shuffler = new shuffleControllerClass($igrtSqli, $exptId,  $evenS4JudgeCount, $oddS4JudgeCount);
    $oddJudgeList = []; //$shuffler->doSnowShuffle(1, $oddS4JudgeCount);
    $evenJudgeJSON = $shuffler->doLEShuffle(0, $evenS4JudgeCount);
    $shuffler->storeLEShuffleJSON($evenJudgeJSON);  // store in json table and get in shuffle status page.
  }

  function processTBTShuffles($exptId, $oddS4JudgeCount, $evenS4JudgeCount) {
    global $igrtSqli;
    $shuffler = new shuffleControllerClass($igrtSqli, $exptId,  $evenS4JudgeCount, $oddS4JudgeCount);
    $oddJudgeList = []; //$shuffler->doSnowShuffle(1, $oddS4JudgeCount);
    $evenJudgeList = $shuffler->doTBTShuffle(0, $evenS4JudgeCount);
    $shuffler->storeTBTShuffleJSON($evenJudgeList);  // store in json table and get in shuffle status page.
  }
  
// </editor-fold>

$rawBody = file_get_contents('php://input');
$jsonObject = json_decode($rawBody, true);
$formFields = $jsonObject['formFields'];
$sectionName = $jsonObject['sectionName'];
$exptId = $jsonObject['exptId'];
$eModel = new experimentModel($exptId);
$metadataConverter = new metadataConverter();
foreach ($formFields as $ff) {
  $tblName = $ff['tblName'];
  $tblFieldName = $ff['tblFieldName'];
  switch ($ff['controlType']) {
    case 'text' : {
      $value = $igrtSqli->real_escape_string($ff['itemValue']);
      if ($ff['isSubItem']) {
        // get subField values and update table accordingly (e.g days and sessions)
        $dim1Name = $ff['dimension1Name'];
        $dim2Name = $ff['dimension2Name'];
        $dim1Value = $ff['dimension1Value'];
        $dim2Value = $ff['dimension2Value'];
        updateTableSubItem($exptId, $tblName, $tblFieldName, $value, $dim1Name, $dim2Name, $dim1Value, $dim2Value);                
      }
      else {
        updateTable($exptId, $tblName, $tblFieldName, $value);        
      }
      break;
    }
    case 'select': {
      $value = $metadataConverter->getIdFromLabel($ff['legend'], $ff['selectedItem']);
      updateTable($exptId, $tblName, $tblFieldName, $value);      
      break;
    }
    case 'checkbox' : {
      // booleanValue is submitted as 1, '1', 'True' , true etc if checked (on in flipswitches) and null if not, how irritating!!!!
	    switch ($ff['booleanValue']) {
		    case 1:
		    case '1':
		    case true:
		    case 'True':
		    case 'true':
			    {
			    	$value = 1;
			    	break;
			    }
		    default:
		    	$value = 0;
	    }
      updateTable($exptId, $tblName, $tblFieldName, $value);
      break;
    }
  }
}

// NOTE - get eModel AFTER any changes above IF it's required

// for most sections above is standard, but any dynamic sections (e.g. days & sessions)
// or registrationViews that have an operation (balancer, shuffle) etc
// need to have values written back first, with any structural changes then imposed over
switch ($sectionName) {
  case "s1sessionsusers" : {
    foreach ($formFields as $ff) {
      if ($ff['tblFieldName'] == "noDays") {
        $oldDaysValue = intval($ff['prevSelectedItem']);
        $newDaysValue = intval($ff['selectedItem']);
      }
      if ($ff['tblFieldName'] == "noSessions") {
        $oldSessionsValue = intval($ff['prevSelectedItem']);
        $newSessionsValue = intval($ff['selectedItem']);
      }
      if ($ff['tblFieldName'] == "noJudges") {
        $oldJudgesValue = intval($ff['prevSelectedItem']);
        $newJudgesValue = intval($ff['selectedItem']);
      }
      if ($ff['tblFieldName'] == "isClassic") {
        $oldClassicValue = $ff['RawBooleanValue'];
        $newClassicValue = $ff['booleanValue'];
      }
    }
    $daysChange = $newDaysValue - $oldDaysValue;
    $sessionsChange = $newSessionsValue - $oldSessionsValue;
    $judgesChange = $newJudgesValue - $oldJudgesValue;
    $classicChange = ($oldClassicValue == $newClassicValue) ? false : true;
    if ($daysChange == 0) {
      if ($sessionsChange != 0) {
        for ($i=1; $i<=$oldDaysValue; $i++) {
          //echo "processSessionChange($exptId, $i, $sessionsChange, $oldSessionsValue)";
          processSessionChange($exptId, $i, $sessionsChange, $oldSessionsValue); // sessionChange can't be zero        
        }     
      }
    }
    else {
      //echo "processDayChange($exptId, $daysChange, $oldDaysValue, $sessionsChange, $oldSessionsValue )";
      processDayChange($exptId, $daysChange, $oldDaysValue, $sessionsChange, $oldSessionsValue );
    }
    $generateStep1Users = $daysChange == 0 ? false : true;
    $generateStep1Users = $sessionsChange == 0 ? $generateStep1Users : true;
    $generateStep1Users = $judgesChange == 0 ? $generateStep1Users : true;
    $generateStep1Users = $classicChange ? true : $generateStep1Users;
		$eModel = new experimentModel($exptId);
		doGenerate($eModel->isClassic, $exptId, $newJudgesValue, $newDaysValue, $newSessionsValue);
//    if ($generateStep1Users) {
//      doGenerate($eModel->isClassic, $exptId, $newJudgesValue, $newDaysValue, $newSessionsValue);
//    }
    break;
  }
  case "s1interrogatorOptions" : {
    foreach ($formFields as $ff) {
      if ($ff['tblFieldName'] == "noLikert") {
        $oldCategoriesValue = intval($ff['prevSelectedItem']);
        $newCategoriesValue = intval($ff['selectedItem']);
        if ($oldCategoriesValue != $newCategoriesValue) {
          processLikertLabelsChange($exptId, 0, $oldCategoriesValue, $newCategoriesValue);
        }
      }
    }
    break;
  }
  case "s1interrogatorFinalOptions" : {
    foreach ($formFields as $ff) {
      if ($ff['tblFieldName'] == "noFinalLikert") {
        $oldCategoriesValue = intval($ff['prevSelectedItem']);
        $newCategoriesValue = intval($ff['selectedItem']);
        if ($oldCategoriesValue != $newCategoriesValue) {
          processLikertLabelsChange($exptId, 2, $oldCategoriesValue, $newCategoriesValue);
        }
      }
    }
    break;
  }
  case "s1interrogatorAlignment" : {
    foreach ($formFields as $ff) {
      if ($ff['tblFieldName'] == "s1NoCategories") {
        $oldCategoriesValue = intval($ff['prevSelectedItem']);
        $newCategoriesValue = intval($ff['selectedItem']);
        if ($oldCategoriesValue != $newCategoriesValue) {
          processCategoriesChange($exptId, 1, $oldCategoriesValue, $newCategoriesValue);
        }
      }
    }
    break;
  }
  case "s2Balancer" : {
    foreach ($formFields as $ff) {
      if ($ff['tblFieldName'] == "oddRespMax") { $oddRespMax = $ff['selectedItem']; }
      if ($ff['tblFieldName'] == "evenRespMax") { $evenRespMax = $ff['selectedItem']; }
    }
    processBalancerValues($exptId, "s2Balancer", $oddRespMax, $evenRespMax);
    break;
  }
  case "iS2Balancer" : {
    foreach ($formFields as $ff) {
      if ($ff['tblFieldName'] == "invertedOddRespMax") { $oddRespMax = $ff['selectedItem']; }
      if ($ff['tblFieldName'] == "invertedEvenRespMax") { $evenRespMax = $ff['selectedItem']; }
    }
    processBalancerValues($exptId, "iS2Balancer", $oddRespMax, $evenRespMax);
    break;
  }
  case "s3Shuffle" : {
    foreach ($formFields as $ff) {
      if ($ff['tblFieldName'] == "oddS4JudgeCount") { $oddS4JudgeCount = $ff['selectedItem']; }
      if ($ff['tblFieldName'] == "evenS4JudgeCount") { $evenS4JudgeCount = $ff['selectedItem']; }
    }
    processShuffles($exptId, $oddS4JudgeCount, $evenS4JudgeCount);
    break;
  }
  case "snowShuffle" : {
    foreach ($formFields as $ff) {
      if ($ff['tblFieldName'] == "oddS4JudgeCount") { $oddS4JudgeCount = $ff['selectedItem']; }
      if ($ff['tblFieldName'] == "evenS4JudgeCount") { $evenS4JudgeCount = $ff['selectedItem']; }
    }
    processSnowShuffles($exptId, $oddS4JudgeCount, $evenS4JudgeCount);
    break;
  }
  // the LE and TVT shuffles don't really require the judge counts, as they are pretty much
  // hard-wired depending on the actual number of IGs across the linked experiments
  case "leShuffle" : {
    foreach ($formFields as $ff) {
      if ($ff['tblFieldName'] == "oddS4JudgeCount") { $oddS4JudgeCount = $ff['selectedItem']; }
      if ($ff['tblFieldName'] == "evenS4JudgeCount") { $evenS4JudgeCount = $ff['selectedItem']; }
    }
    processLEShuffles($exptId, $oddS4JudgeCount, $evenS4JudgeCount);
    break;
  }
  case "tbtShuffle" : {
    foreach ($formFields as $ff) {
      if ($ff['tblFieldName'] == "oddS4JudgeCount") { $oddS4JudgeCount = $ff['selectedItem']; }
      if ($ff['tblFieldName'] == "evenS4JudgeCount") { $evenS4JudgeCount = $ff['selectedItem']; }
    }
    processTBTShuffles($exptId, $oddS4JudgeCount, $evenS4JudgeCount);
    break;
  }
}
