<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/domainSpecific/mySqlObject.php');
$permissions = $_GET['permissions'];
$uid = $_GET['uid'];
$exptId = $_GET['exptId'];
$messageType = $_GET['messageType'];
$content = $_GET['content'];

  function processMessage($_uid, $_exptId, $_content) {
    global $igrtSqli;
    $details = explode('_', $_content);
    $controlName = $details[0];
    $value = $details[1];
    $sql = sprintf("UPDATE ui_experimentControlHeaders SET %s='%s' WHERE exptId='%s' AND uid='%s'", $controlName, $value, $_exptId, $_uid);
    echo $sql;
    $igrtSqli->query($sql);
  }

//ensure admin
if ($permissions >= 128) {
  processMessage($uid, $exptId, $content);
}
