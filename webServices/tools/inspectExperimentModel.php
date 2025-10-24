<?php
// -----------------------------------------------------------------------------
// 
// debug an experimentModel
// 
// -----------------------------------------------------------------------------
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once $root_path.'/domainSpecific/mySqlObject.php';
require_once $root_path.'/helpers/models/class.experimentModel.php';
require_once $root_path.'/helpers/debug/class.prettifier.php';
$exptId = $_GET['exptId'];
$eModel = new experimentModel($exptId);
$dump = print_r($eModel, true);
$prettifier = new prettifier($dump);
$prettyDump = $prettifier->getHTML();
echo $prettyDump;
