<?php
// -----------------------------------------------------------------------------
// 
// web service to validate user-responses for Q in step2
// 
// -----------------------------------------------------------------------------
$full_ws_path=realpath(dirname(__FILE__));
$root_path=substr($full_ws_path, 0, strlen($full_ws_path)-18);  // 19 /webServices/step2
require_once($root_path.'/helpers/html/class.htmlBuilder.php');
require_once($root_path.'/helpers/forms/class.formBuilder.php');
include_once $root_path.'/domainSpecific/mySqlObject.php';      // gives us $igrtSqli for all db operations in the CLI scripts

// ------------------------------------------------------------    function defs

  function isValidated($userForm) {
    foreach ($userForm as $uf) {
      if ($uf['needsValidation'] == 1) { return false; }
    }
    return true;
  }

// ------------------------------------------------------   end of function defs


$postValues = array();
$formControlTypes = array();
foreach ($_GET as $key=>$value) {
  if (($key == 'exptId') || ($key == 'uid') || ($key == 'dayNo') || ($key == 'sessionNo') || ($key == 'permissions')) {
    if ($key == 'exptId') { $exptId = $value; }
    if ($key == 'uid') { $uid = $value; }
    if ($key == 'dayNo') { $dayNo = $value; }
    if ($key == 'sessionNo') { $sessionNo = $value; }
  }
  else {
    $formData = array("key" => $key, "value" => $value);
    array_push($postValues, $formData);
  }
}

// firstly get structure of form, to keep user-state of previous responses and 
// controls requiring validation due to no or invalid user-response
  
// get universal fields
$emailPtr = NULL;
$genderPtr = NULL;
$agePtr = NULL;
$nationalityPtr = NULL;
$firstLanguagePtr = NULL;
$sql_fu = sprintf("SELECT * FROM formsSTEP2general WHERE exptId='%s'", $exptId);
$fuResults = $igrtSqli->query($sql_fu);
if ($igrtSqli->affected_rows > 0) { 
  $row = $fuResults->fetch_object();
  $controlNo = 0;
  if ($row->useEmail == 1) {
    $cv = array('controlNo'=>$controlNo, 'controlType'=>3, 'userResponse'=>array('response'=>NULL), 'needsValidation'=>1);
    array_push($formControlTypes, $cv);
    ++$controlNo;
    $emailPtr = $controlNo;
  }
  if ($row->useGender == 1) {
    $cv = array('controlNo'=>$controlNo, 'controlType'=>5,'userResponse'=>array('response'=>NULL), 'needsValidation'=>1);
    array_push($formControlTypes, $cv);
    ++$controlNo;
    $genderPtr = $controlNo;
  }
  if ($row->useAge == 1) {
    $cv = array('controlNo'=>$controlNo, 'controlType'=>6, 'userResponse'=>array('response'=>NULL), 'needsValidation'=>1);
    array_push($formControlTypes, $cv);
    ++$controlNo;
    $agePtr = $controlNo;
  }
  if ($row->useNationality == 1) {
    $cv = array('controlNo'=>$controlNo, 'controlType'=>6, 'userResponse'=>array('response'=>NULL), 'needsValidation'=>1);
    array_push($formControlTypes, $cv);
    ++$controlNo;
    $nationalityPtr = $controlNo;
  }
  if ($row->useFirstLanguage == 1) {
    $cv = array('controlNo'=>$controlNo, 'controlType'=>6, 'userResponse'=>array('response'=>NULL), 'needsValidation'=>1);
    array_push($formControlTypes, $cv);
    ++$controlNo;
    $firstLanguagePtr = $controlNo;
  }
  $firstBespoke = $controlNo;
  // now loop through any bespoke fields and add types
  $sql_bf = sprintf("SELECT * FROM formsSTEP2options WHERE exptId='%s' ORDER BY qNo ASC", $exptId);
  $bfResults = $igrtSqli->query($sql_bf);
  if ($igrtSqli->affected_rows > 0) {
    while ($row = $bfResults->fetch_object()) {
      $cv = array('controlNo'=>$controlNo, 'controlType'=>$row->controlType, 'userResponse'=>array('response'=>NULL), 'needsValidation'=>1);
      array_push($formControlTypes, $cv);
      ++$controlNo;
    }
  }
  // now create array linking posted values to control types ready for validation and re-display if necessary
  // remember, not encessarily a one-one mapping of post to control, and unselected radios not posted
  // and checkboxes could have multiple posts
  $control_id = array();
  foreach($postValues as $pv) {
    $key = $pv["key"];
    $value = $pv["value"];
    $control_id = explode('_', $key);
    $controlNo = $control_id[1]-1;  // controls numbered from 1 on front-end
    if (count($control_id) == 3) {
      // cb is of form cb_qNo_responseNo
      // and never needs validation as ok to not select any
      //echo print_r($control_id, true);
      $optionNo = $control_id[2];
      $cb_response = array('rNo' => $optionNo, 'response'=>$value);
      array_push($formControlTypes[$controlNo]['userResponse'], $cb_response);
      $formControlTypes[$control_id[1]]['needsValidation'] = 0;              
    }
    else {
      $response = array('response' => $value);
      $formControlTypes[$controlNo]['userResponse'] = $response; 
      switch ($formControlTypes[$controlNo]['controlType']) {
        case 1 :  // single-text
        case 2 :  // multi-text
        case 3 :  // email
        case 4 :  // date
        case 5 :  // radio button
        {
          if (($formControlTypes[$controlNo]['userResponse']['response'] != NULL) && ($formControlTypes[$controlNo]['userResponse']['response']>'')) { $formControlTypes[$controlNo]['needsValidation'] = 0; }
          break;
        }
        case 6: { // select always has a default value of 0 for not selected
          if (($formControlTypes[$controlNo]['userResponse']['response'] != NULL) && ($formControlTypes[$controlNo]['userResponse']['response']>0)) { $formControlTypes[$controlNo]['needsValidation'] = 0; }
          break;
        }
      }      
    }
    ++$controlNo;
  }
  // now check to see whether form is validated
  if (isValidated($formControlTypes)) {
    $bespokeCnt = $controlNo - $firstBespoke;
    // store data in table
    $tableName = sprintf("x_s2_%s", $exptId);
    $insertDataQry = sprintf('INSERT INTO %s (exptId,dayNo,sessionNo,uid', $tableName);
    if ($emailPtr != NULL) { $insertDataQry .= ',email'; }
    if ($genderPtr != NULL) { $insertDataQry .= ',gender'; }
    if ($agePtr != NULL) { $insertDataQry .= ',age'; }
    if ($nationalityPtr != NULL) { $insertDataQry .= ',nationality'; }
    if ($firstLanguagePtr != NULL) { $insertDataQry .= ',firstLanguage'; }
    for ($i=1; $i<=$bespokeCnt; $i++) {
      $insertDataQry .= sprintf(",bq_%s", $i);
    }
    $insertDataQry .= sprintf(") VALUES ('%s','%s','%s','%s'", $exptId, $dayNo, $sessionNo, $uid);
    if ($emailPtr != NULL) { $insertDataQry .= sprintf(",'%s'", $formControlTypes[$emailPtr - 1]['userResponse']['response']); }
    if ($genderPtr != NULL) { $insertDataQry .= sprintf(",'%s'", $formControlTypes[$genderPtr - 1]['userResponse']['response']); }
    if ($agePtr != NULL) { $insertDataQry .= sprintf(",'%s'", $formControlTypes[$agePtr - 1]['userResponse']['response']); }
    if ($nationalityPtr != NULL) { $insertDataQry .= sprintf(",'%s'", $formControlTypes[$nationalityPtr - 1]['userResponse']['response']); }
    if ($firstLanguagePtr != NULL) { $insertDataQry .= sprintf(",'%s'", $formControlTypes[$firstLanguagePtr - 1]['userResponse']['response']); }
    for ($i=0; $i<$bespokeCnt; $i++) {
      $insertDataQry .= sprintf(",'%s'", $formControlTypes[$firstBespoke + $i]['userResponse']['response']);
    }
    $insertDataQry .= ')';
    $igrtSqli->query($insertDataQry);
    // now mark as questionnaire complete
    $markCompleteQry = sprintf("UPDATE igActiveStep2Users SET completeQuestionnaire='1' WHERE uid='%s'", $uid);
    $igrtSqli->query($markCompleteQry);
    $html = 'validated';
    //$html = sprintf("formcontrols %s : firstbespoke %s : bespokeCnt %s : insertDataQry %s : emailPtr %s : genderPtr %s : agePtr %s : nationalityPtr %s : firstLanguagePtr %s", print_r($formControlTypes, true), $firstBespoke, $bespokeCnt, $insertDataQry, $emailPtr , $genderPtr, $agePtr, $nationalityPtr, $firstLanguagePtr );
  }
  else {
    // rebuild form with user-input  
    $htmlBuilder = new htmlBuilder();
    $formBuilder = new formBuilder($uid, $igrtSqli, $htmlBuilder, $exptId);
    $html = $formBuilder->makeStep2Form($formControlTypes);  
  }
}
echo $html;
