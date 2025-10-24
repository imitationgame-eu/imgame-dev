<?php
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';     
include_once $root_path.'/helpers/parseJSON.php';              


$getJSONItems = sprintf("SELECT * FROM jsonStepFormsData");
$getJSONResult = $igrtSqli->query($getJSONItems);
if ($getJSONResult) {
  while ($jsonRow = $getJSONResult->fetch_object()) {
    $chrono = $jsonRow->chrono;
    $rawBody = $jsonRow->json;
    $jSonArray = json_decode($rawBody, true);
    switch (json_last_error()) {
      case JSON_ERROR_NONE:
          echo ' - No errors';
      break;
      case JSON_ERROR_DEPTH:
          echo ' - Maximum stack depth exceeded';
      break;
      case JSON_ERROR_STATE_MISMATCH:
          echo ' - Underflow or the modes mismatch';
      break;
      case JSON_ERROR_CTRL_CHAR:
          echo ' - Unexpected control character found';
      break;
      case JSON_ERROR_SYNTAX:
          echo ' - Syntax error, malformed JSON';
      break;
      case JSON_ERROR_UTF8:
          echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
      break;
      default:
          echo ' - Unknown error';
      break;
    }
    $formType = $jSonArray['formType'];
    $exptId = $jSonArray['exptId'];
//    echo strlen($exptId).'- ';
//    if (strlen($exptId) == 0 ) {
//      echo $rawBody;
//    }
    $jsonTblName = "zz_json_" . $exptId;
    $tblExistsQry = sprintf("show tables like '%s'", $jsonTblName);
    //echo $tblExistsQry;
    $ter = $igrtSqli->query($tblExistsQry);
    if ($ter->num_rows == 0) {
      $createTblQry = sprintf("CREATE TABLE `%s` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `chrono` datetime DEFAULT NULL,
        `formType` int(4),
        `json` BLOB NOT NULL,
        PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8", $jsonTblName);
      $igrtSqli->query($createTblQry);
    }
    // store raw in appropriate table
    $insertSql = sprintf("INSERT INTO %s (chrono, formType, json) VALUES('%s', '%s', '%s')", $jsonTblName, $chrono, $formType, $rawBody );
    $igrtSqli->query($insertSql);
    //echo $insertSql;
  }
}

