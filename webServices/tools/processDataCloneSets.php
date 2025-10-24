<?php
// -----------------------------------------------------------------------------
// web service process JSON representation of data clone targets
// -----------------------------------------------------------------------------
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }

include_once $root_path.'/domainSpecific/mySqlObject.php';  

  function cloneS1($srcExptId, $exptId, $dayNo, $sessionNo) {
    global $igrtSqli;
    $callQry = sprintf("CALL xinjectS1(%s,%s,%s,%s,@aRows)", $srcExptId, $exptId, $dayNo, $sessionNo);
    $igrtSqli->query($callQry);
    echo $callQry;
    $arr = $igrtSqli->query("SELECT @aRows as arc");
    if ($arr) {
      $row = $arr->fetch_object();
      $nAffectedRows = $row->arc;
    }
    // update injected status
    $updateQry = sprintf("UPDATE igExperiments SET injectedFlag=1, injectedS1Flag=1, s1srcExptId='%s' WHERE exptId='%s'", $srcExptId, $exptId);
    $igrtSqli->query($updateQry); 
    echo $updateQry;
  }

  function cloneS2($srcExptId, $exptId, $dayNo, $sessionNo) {
    global $igrtSqli;
    $callQry = sprintf("CALL xinjectS2(%s,%s,%s,%s,@aRows)", $srcExptId, $exptId, $dayNo, $sessionNo);
    $igrtSqli->query($callQry);
    echo $callQry;
    $arr = $igrtSqli->query("SELECT @aRows as arc");
    if ($arr) {
      $row = $arr->fetch_object();
      $nAffectedRows = $row->arc;
    }
    // update injected status
    $updateQry = sprintf("UPDATE igExperiments SET injectedFlag=1, injectedS2Flag=1, s2srcExptId='%s' WHERE exptId='%s'", $srcExptId, $exptId);
    $igrtSqli->query($updateQry);  
  }

  function cloneS2injected($srcExptId, $exptId, $dayNo, $sessionNo) {
    global $igrtSqli;
    $callQry = sprintf("CALL xinjectS2injected(%s,%s,%s,%s,@aRows)", $srcExptId, $exptId, $dayNo, $sessionNo);
    echo $callQry;
    $igrtSqli->query($callQry);
    $arr = $igrtSqli->query("SELECT @aRows as arc");
    if ($arr) {
      $row = $arr->fetch_object();
      $nAffectedRows = $row->arc;
    }
    // update injected status
    $updateQry = sprintf("UPDATE igExperiments SET injectedFlag=1, injectedInvertedS2Flag=1, s2invertedsrcExptId='%s' WHERE exptId='%s'", $srcExptId, $exptId);
    $igrtSqli->query($updateQry);  
  }
  
$rawBody = file_get_contents('php://input');
$jSonArray = json_decode($rawBody, true);
$srcExptId = $jSonArray['srcExptId'];
$experiments = $jSonArray['experiments'];
for ($i=0; $i<count($experiments); $i++) {
  $injected = $experiments[$i]['injected'];
  $manuallyInjected = $experiments[$i]['manuallyInjected'];
  $exptId = $experiments[$i]['exptId'];
  for ($j=0; $j<count($experiments[$i]['daySessions']); $j++) {
    $dayNo = $experiments[$i]['daySessions'][$j]['dayNo'];
    $sessionNo = $experiments[$i]['daySessions'][$j]['sessionNo'];
    if ($injected == 0) {
      if ($experiments[$i]['daySessions'][$j]['hasS1']) { cloneS1($srcExptId, $exptId, $dayNo, $sessionNo); }
      if ($experiments[$i]['daySessions'][$j]['hasS2']) { cloneS1($srcExptId, $exptId, $dayNo, $sessionNo); }
      if ($experiments[$i]['daySessions'][$j]['hasS2inverted']) { cloneS1($srcExptId, $exptId, $dayNo, $sessionNo); }      
    }
    else {
      if ($experiments[$i]['daySessions'][$j]['overrideS1']) { cloneS1($srcExptId, $exptId, $dayNo, $sessionNo); }
      if ($experiments[$i]['daySessions'][$j]['overrideS2']) { cloneS1($srcExptId, $exptId, $dayNo, $sessionNo); }
      if ($experiments[$i]['daySessions'][$j]['overrideS2inverted']) { cloneS1($srcExptId, $exptId, $dayNo, $sessionNo); }      
    }
  }
}
echo 'done';