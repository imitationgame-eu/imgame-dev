<?php
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';     
include_once $root_path.'/helpers/parseJSON.php';              

$rawBody = file_get_contents('php://input');
$jSonArray = json_decode($rawBody, true);
//$formName = $jSonArray['formName'];
$formTypeStr = $jSonArray['formType'];
$formType = str_replace('"', '', $formTypeStr);
$exptIdStr = $jSonArray['exptId'];
$exptId = str_replace('"', '', $exptIdStr);
$jTypeStr = $jSonArray['jType'];
$jType = str_replace('"', '', $jTypeStr);

$jsonTblName = sprintf("zz_json_%s", $exptId);
$tblExistsQry = sprintf("show tables like '%s'", $jsonTblName);
//echo $tblExistsQry;
$tr = $igrtSqli->query($tblExistsQry);
if ($tr) {
  $createTblQry = sprintf("CREATE TABLE `%s` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `chrono` datetime DEFAULT NULL,
    `formType` int(4),
    `isJQM` int(4) DEFAULT 0,
    `restartUID` int(11) DEFAULT -1,
    `json` BLOB NOT NULL,
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8", $jsonTblName);
  $igrtSqli->query($createTblQry);
}
else {
  // check that the table has the newer column to indicate whether the JSON
  // is from older ko-js form or newer jqm form
  $checkColQry = sprintf("SHOW COLUMNS FROM `%s` LIKE 'isJQM'", $jsonTblName);
  $checkColResult = $igrtSqli->query($checkColQry);
  if ($checkColResult) {
    $alterQry = sprintf("ALTER TABLE `%s` ADD COLUMN `isJQM` INT(4) NULL DEFAULT 0 AFTER `formType`", $jsonTblName);
    $igrtSqli->query($alterQry);
  }
  // check that the table has the newer column to for restartUID to make it easier to pull out data later
  $checkColQry = sprintf("SHOW COLUMNS FROM `%s` LIKE 'restartUID'", $jsonTblName);
  $checkColResult = $igrtSqli->query($checkColQry);
  if ($checkColResult) {
    $alterQry = sprintf("ALTER TABLE `%s` ADD COLUMN `restartUID` INT(11) NULL DEFAULT -1 AFTER `isJQM`", $jsonTblName);
    $igrtSqli->query($alterQry);
  }
}


// get participant uid before saving if it's a pre-Form - this then gets used in actual Step and in any post-Form
$isPreForm = ($formType == 2) || ($formType == 6) || ($formType == 12) || ($formType == 10) ? true : false;
$isPostForm = ($formType == 3) || ($formType == 7) || ($formType == 13) || ($formType == 11) ? true : false;
switch ($formType) {   
    case 0:
    case 1:
    case 2:
    case 3: {
        $tblName = "wt_Step1FormUIDs";
        $dataTblName = "sdStep1SurveyData";
        break;
    }
    case 4:
    case 5:
    case 6:
    case 7: {
        $tblName = "wt_Step2FormUIDs";
        $dataTblName = "sdStep2SurveyData";
        break;
    }
    case 12:  
    case 13: {
        $tblName = "wt_Step2InvertedFormUIDs";
        $dataTblName = "sdStep2InvertedSurveyData";
        break;
    }
    case 8:
    case 9:
    case 10:
    case 11: {
        $tblName = "wt_Step4FormUIDs";
        $dataTblName = "sdStep4SurveyData";
        break;
    }
        
}
if ($isPreForm) {
  $getUID = sprintf("INSERT INTO %s (exptId,formType) VALUES('%s','%s')", $tblName, $exptId, $formType);
  $igrtSqli->query($getUID);
  //echo $getUID;
  $uid = $igrtSqli->insert_id;
  $retXML = sprintf("<message><messageType>step2Parameters</messageType><restartUID>%s</restartUID><userCode>%s</userCode><jType>%s</jType></message>",
      $uid, $attachUID ? $suppliedCode : "na", $jType);
  $restartUID = $attachUID;
}
if ($isPostForm) {
  $retXML = "<message><messageType>postStepDone</messageType><restartUID>na</restartUID></message>";
  $restartUID = str_replace('"', '', $jSonArray['restartUID']);
}
// store raw in appropriate table with appropriate indicator for whether a jqm form or not
$isJQMStr = isset($jSonArray['jsonType']) ? $jSonArray['jsonType'] : "";
$isJQM = ($isJQMStr == "\"jqm\"") ? 1 : 0;
//echo $isJQMStr. ' '. $isJQM;
$insertSql = sprintf("INSERT INTO %s (chrono, formType, isJQM, restartUID, json) VALUES(NOW(), '%s', '%s', '%s', '%s')", $jsonTblName, $formType, $isJQM, $restartUID, $igrtSqli->real_escape_string($rawBody) );
$igrtSqli->query($insertSql);

// finally send actual parameter message
echo $retXML;  



// <editor-fold defaultstate="collapsed" desc="empty">

// </editor-fold>

