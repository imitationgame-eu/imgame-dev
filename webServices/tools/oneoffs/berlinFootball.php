<?php
// -----------------------------------------------------------------------------
// 
//    
// -----------------------------------------------------------------------------
ini_set('display_errors', 'On');
error_reporting(E_ALL);

if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';      

$getDataQry = "SELECT * FROM sysdiag_socketsLog WHERE messageType='msg' AND chrono<'2014-11-15 13:30' ORDER BY chrono ASC";
$getDataResult = $igrtSqli->query($getDataQry);
if ($getDataResult) {
  while ($dataRow = $getDataResult->fetch_object()) { 
    $outStr = str_replace('<','::',$dataRow->message);
    $outStr = str_replace('>','::', $outStr);
    echo $outStr.'<br/>';
  }
}
