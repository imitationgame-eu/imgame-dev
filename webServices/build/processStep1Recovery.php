<?php
// -----------------------------------------------------------------------------
// 
// web service to process turn data from STEP1Recovery for into STEP1DATA
// for manual back-filling of ratings & reasons
// 
// -----------------------------------------------------------------------------

$ExperimentConfigurator;
$ExperimentViewModel;
$Step1Runner;
$Server;
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
$permissions=$_GET['permissions'];
$uid=$_GET['uid'];
$uidArray = array();
$jHtml='';

    
function inUidArray($jUid) {
    global $uidArray;
    foreach ($uidArray as $uidA) {
        if ($uidA['jUid']==$jUid) { return true;}
    }
    return false;
}

if (($uid==28) && ($permissions==1024)) {
    //echo 'data extract';
    include_once $root_path.'/domainSpecific/mySqlObject.php';      
    // firstly find each unique uid in jUid field
    $uidSql="SELECT * FROM sysdiags_STEP1Recovery WHERE chrono>'2013-12'";
    $uidResults = $igrtSqli->query($uidSql);
    if ($uidResults) {
      while ($row = $uidResults->fetch_object()) {
        if (!inUidArray($row->jUid)) {
          $uidDef = array('jUid'=>$row->jUid, 'jType'=>$row->jType, 'jNo'=>$row->jNo);
          array_push($uidArray, $uidDef);
        }
      }
    }
    echo print_r($uidArray,true);
    // now build transcript for each jUid
    $totQ = 0;
    foreach ($uidArray as $uidA) {
      $qSql = sprintf("SELECT * FROM sysdiags_STEP1Recovery WHERE jUid='%s' AND chrono>'2013-12' ORDER BY chrono ASC", $uidA['jUid']);
      $qResult = $igrtSqli->query($qSql);
      //echo $qSql;
      if ($qResult) {
        $qCnt = 0;
        //echo 'building qry';
        $npLeft = ($uidA['jNo'] % 2 == 0) ? 1 : 0;
        while ($row = $qResult->fetch_object()) {
          $insQry = sprintf("INSERT INTO dataSTEP1 (uid, exptId, jType, jNo, sessionNo, dayNo, npLeft, qNo, q, npr, pr) 
                              VALUES('%s','205','%s','%s','1','1','%s','%s','%s','%s','%s')",
                              $uidA['jUid'], $uidA['jType'], $uidA['jNo'], $npLeft, $row->qNo, 
                              $igrtSqli->real_escape_string($row->jQ), 
                              $igrtSqli->real_escape_string($row->npA),
                              $igrtSqli->real_escape_String($row->pA));
          //echo $insQry;
          $igrtSqli->query($insQry);
          ++$qCnt;
        }
        //echo $insQry;
        $insQry = sprintf("INSERT INTO dataSTEP1 (uid, exptId, jType, jNo, sessionNo, dayNo, npLeft, qNo, q, npr, pr) 
                           VALUES('%s','205','%s','%s','1','1','%s','%s','%s','%s','%s')",
                           $uidA['jUid'], $uidA['jType'], $uidA['jNo'], ($uidA['jNo'] % 2 == 0) ? 1 : 0, $qCnt, 'FINAL', 'FINAL', 'FINAL');
        $igrtSqli->query($insQry);
        $totQ += $qCnt;
      }
    }
    echo $totQ;
}
else {
    echo 'not authorised';
}

