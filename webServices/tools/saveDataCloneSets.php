<?php
// -----------------------------------------------------------------------------
// web service process JSON representation of data clone targets
// -----------------------------------------------------------------------------
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }

include_once $root_path.'/domainSpecific/mySqlObject.php';     

$rawBody = file_get_contents('php://input');
$jSonArray = json_decode($rawBody, true);
$experiments = $jSonArray['experiments'];
for ($i=0; $i<count($experiments); $i++) {
  $useOddInvertedS2 = $experiments[$i]['useOddInvertedS2'] ? 1 : 0;
  $useEvenInvertedS2 = $experiments[$i]['useEvenInvertedS2'] ? 1 : 0;
  $exptId = $experiments[$i]['exptId'];
  $update = sprintf("UPDATE igExperiments SET useOddInvertedS2='%s', useEvenInvertedS2='%s' WHERE exptId='%s'", $useOddInvertedS2, $useEvenInvertedS2, $exptId);
  $igrtSqli->query($update);
}
echo 'done';