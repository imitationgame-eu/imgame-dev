<?php
// -----------------------------------------------------------------------------
// 
// web service to update missing confidence levels from tmpNNNIntervals
// where NNN is the experiment number 
// 
// -----------------------------------------------------------------------------

$ExperimentConfigurator;
$ExperimentViewModel;
$Step1Runner;
$Server;
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
$permissions = $_GET['permissions'];
$uid = $_GET['uid'];
$exptId = $_GET['exptId'];

    
function inUidArray($jUid) {
    global $uidArray;
    foreach ($uidArray as $uidA) {
        if ($uidA['jUid']==$jUid) { return true;}
    }
    return false;
}

if (($uid==28) && ($permissions==1024)) {
  include_once $root_path.'/domainSpecific/mySqlObject.php';
  $tblName = sprintf("tmp%sIntervals", $exptId);
  $qry = sprintf("SELECT * FROM %s ORDER BY id ASC", $tblName);
  $ratingResult = $igrtSqli->query($qry);
  while ($ratingRow = $ratingResult->fetch_object()) {
    $id = $ratingRow->id;
    $rating = $ratingRow->rating;
    $updateQry = sprintf("UPDATE dataSTEP1 SET rating='%s' WHERE id='%s'", $rating, $id);
    $igrtSqli->query($updateQry);
  }
  echo 'done';
}
else {
}

