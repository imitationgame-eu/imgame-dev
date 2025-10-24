<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/domainSpecific/mySqlObject.php');
$permissions = $_GET['permissions'];
$uid = $_GET['uid'];
$messageType = $_GET['messageType'];
$content = $_GET['content'];

  function processMessage($_messageType, $_content) {
    global $igrtSqli;
    $sql = "";
    switch ($_messageType) {
      case 'inActive' : {
        $sql = sprintf("UPDATE igExperiments SET isInactive=%s WHERE exptId='%s'", $_content[1] == 1 ? 0 : 1, $_content[0]);
        break;
      }
    }
    $igrtSqli->query($sql);
  }

//ensure admin
if ($permissions >= 128) {
  processMessage($messageType, $content);
}
