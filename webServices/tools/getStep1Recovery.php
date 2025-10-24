<?php
// -----------------------------------------------------------------------------
// 
// web service to retrieve raw data from debug log for uncompleted sessions
// 
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }

$ExperimentConfigurator;
$ExperimentViewModel;
$Step1Runner;
$Server;
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
    $uidResults=$igrtSqli->query($uidSql);
    if ($uidResults) {
        while ($row=$uidResults->fetch_object()) {
            if (!inUidArray($row->jUid)) {
                $uidDef=array('jUid'=>$row->jUid, 'jType'=>$row->jType);
                array_push($uidArray,$uidDef);
            }
        }
    }
    //echo print_r($uidArray,true);
    // now build transcript for each jUid
    foreach ($uidArray as $uidA) {
        $qSql=sprintf("SELECT * FROM sysdiags_STEP1Recovery WHERE jUid='%s' AND chrono>'2013-12' ORDER BY chrono ASC", $uidA['jUid']);
        $qResult=$igrtSqli->query($qSql);
        if ($qResult) {
          //print_r($qSql);
           $jHtml.=sprintf("<h3>%s - %s</h3>",$uidA['jUid'],$uidA['jType']);
            while ($row=$qResult->fetch_object()) {
                $jHtml.=sprintf("%s<br />",$row->jQ);
                $jHtml.=sprintf("%s ::: %s<br />",$row->npA,$row->pA);                                
            }
        }
        $jHtml.="<hr />";
    }
    echo $jHtml;
}
else {
    echo 'not authorised';
}

