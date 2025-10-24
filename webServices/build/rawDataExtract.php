<?php
// -----------------------------------------------------------------------------
// 
// web service to retrieve raw data from debug log for uncompleted sessions
// 
// -----------------------------------------------------------------------------

$ExperimentConfigurator;
$ExperimentViewModel;
$Step1Runner;
$Server;
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
$permissions=$_GET['permissions'];
$uid=$_GET['uid'];


if (($uid==28) && ($permissions=1024)) {
    //echo 'data extract';
    include_once $root_path.'/domainSpecific/mySqlObject.php';      
    $jHtml='';
    echo "<h3>Judge Questions</h3>";
    $jQry="SELECT * FROM sysdiag_socketsLog WHERE messageType='JQ' ORDER BY chrono ASC";
    $jResult=$igrtSqli->query($jQry);
    if ($jResult) {
        while ($row=$jResult->fetch_object()) {
            $jHtml.=sprintf("%s : %s<br />",$row->chrono,$row->message);
        }
    }
    echo $jHtml;

    $npHtml='';
    echo "<h3>NP Answers</h3>";
    $npQry="SELECT * FROM sysdiag_socketsLog WHERE messageType='NPA' ORDER BY chrono ASC";
    $npResult=$igrtSqli->query($npQry);
    if ($npResult) {
        while ($row=$npResult->fetch_object()) {
            $npHtml.=sprintf("%s : %s<br />",$row->chrono,$row->message);
        }
    }
    echo $npHtml;

    $pHtml='';
    echo "<h3>P Answers</h3>";
    $pQry="SELECT * FROM sysdiag_socketsLog WHERE messageType='PA' ORDER BY chrono ASC";
    $pResult=$igrtSqli->query($pQry);
    if ($pResult) {
        while ($row=$pResult->fetch_object()) {
            $pHtml.=sprintf("%s : %s<br />",$row->chrono,$row->message);
        }
    }
    echo $pHtml;
}
else {
    echo 'not authorised';
}

