<?php
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';     

$rawBody = file_get_contents('php://input');
$jSonArray = json_decode($rawBody, true);
for ($i=0; $i<count($jSonArray['experiments']); $i++) {
  $exptId = $jSonArray['experiments'][$i]['exptId'];
  $cloneFormsAccordionOpen = $jSonArray['experiments'][$i]['cloneFormsAccordionOpen'];
  $updateQry = sprintf("UPDATE igExperiments SET cloneFormsAccordionOpen='%s' WHERE exptId='%s'", $cloneFormsAccordionOpen, $exptId);
  $igrtSqli->query($updateQry);
}
