<?php
  ini_set('display_errors', 'On');
  error_reporting(E_ALL); 
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
  include_once $root_path.'/domainSpecific/mySqlObject.php';       
  include_once $root_path.'/helpers/models/class.experimentModel.php';       
  $uid=$_POST['userid'];
  $exptId=$_POST['experimentID'];
  $groupNo = $_POST['groupNo'];
  $qNo = $_POST['qNo'];
  $respA=$igrtSqli->real_escape_string($_POST['respA']);
  $dayNo='';
  $sessionNo='';
  $role='.';
  $recentA='.';
  $recentQ='.';


  // get day and session info from igActiveClassicUsers
  $sqlDS=sprintf("SELECT * FROM igActiveClassicUsers WHERE uid='%s' AND exptId='%s'",$uid,$exptId);
  $DSresult=$igrtSqli->query($sqlDS);
  if ($DSresult) {
    $rowDS=$DSresult->fetch_object();
    $dayNo=$rowDS->dayNo;
    $sessionNo=$rowDS->sessionNo;
    $role=$rowDS->role;
  } 
  // get id of current question
  $sqlQ=sprintf("SELECT * FROM dataClassic WHERE "
      . "exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND groupNo='%s' "
      . "ORDER by insertDT DESC",
      $exptId, $dayNo, $sessionNo, $groupNo);
  $qResult=$igrtSqli->query($sqlQ);
  $row = $qResult->fetch_object();
  $qID = $row->id;
  // store answer
  if ($role=='NP') {
    $sqlNPU=sprintf("UPDATE dataClassic SET npA='%s' WHERE id='%s'", $respA, $qID);        
    $igrtSqli->query($sqlNPU);
    $sql=sprintf("UPDATE igActiveClassicUsers SET respState='2' "
        . "WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND groupNo='%s' AND role='NP'",
        $exptId, $dayNo, $sessionNo, $groupNo);
    $igrtSqli->query($sql);
  }
  else {
    $sqlPU=sprintf("UPDATE dataClassic SET pA='%s' WHERE id='%s'",$respA,$qID); 
    $igrtSqli->query($sqlPU);
    $sql=sprintf("UPDATE igActiveClassicUsers SET respState='2' "
        . "WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND groupNo='%s' AND role='P'",
        $exptId, $dayNo, $sessionNo, $groupNo);
    $igrtSqli->query($sql);
  }
  // get currentA and Q
  $sqlData=sprintf("SELECT * FROM dataClassic WHERE "
      . "exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND groupNo='%s' "
      . "ORDER BY insertDT DESC",
      $exptId, $dayNo, $sessionNo, $groupNo);
  $dataResult=$igrtSqli->query($sqlData);
  if ($dataResult) {
    $dataRow=$dataResult->fetch_object();
    $recentQ=$dataRow->jQ;
    if ($role=="NP") {
      if ($dataRow->npA > '') {$recentA=$dataRow->npA;}
    }
    else {
      if ($dataRow->pA > '') {$recentA=$dataRow->pA;}
    }                
    // check for both, and if so set J to rating 
    // 07/11 - change check for both to look at status of both respondents (should both be 2)
//        $sqlBothCheck=sprintf("SELECT * FROM igActiveClassicUsers WHERE exptId='%s' AND day='%s' AND session='%s' AND respState='2'",$exptId,$day,$session);
//        $bcResult=$igrtSqli->query($sqlBothCheck);
    //if ($igrtSqli->affected_rows == 2) {
    if (($dataRow->npA>'') && ($dataRow->pA>'')) {
      $sqlBoth=sprintf("UPDATE igActiveClassicUsers SET jState='2' WHERE "
          . "exptId='%s' AND dayNo='%s' AND sessionNo='%s' ANG groupNo='%s' AND role='J'",
          $exptId, $dayNo, $sessionNo, $groupNo);
      $igrtSqli->query($sqlBoth);
    }
  }

  // build history if it exists
  $resp_html='<div></div>';
  $reverseHistory=array();
  // get exptId, day & session
  $sqlHistory=sprintf("SELECT * FROM dataClassic WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s' ORDER BY insertDT DESC",$exptId,$dayNo,$sessionNo);
  $historyResult=$igrtSqli->query($sqlHistory);
  if ($historyResult) {
    while ($row=$historyResult->fetch_object()) {
      if ($role=="NP") {
        $det=array('resp'=>$row->npA,'jQ'=>$row->jQ);                    
      }
      else {
        $det=array('resp'=>$row->pA,'jQ'=>$row->jQ);                                        
      }
      array_push($reverseHistory,$det);
    }
  }
  $lastNumber=count($reverseHistory);
  foreach ($reverseHistory as $v) {
    $resp_html.='<div class="previousQuestion">';
    $resp_html.=sprintf("<p><span>Question %s</span>%s</p>",$lastNumber,$v["jQ"]);
    $resp_html.=sprintf("<div class=\"response\"><p><span>Your answer: </span>%s</p></div>",$v["resp"]); 
    $resp_html.='</div>';
    --$lastNumber;
  }
  $xml=sprintf('<message><messageType>respStateInfo</messageType>
    <respState>%s</respState>
    <historyHtml><![CDATA[%s]]></historyHtml>
    <recentQ><![CDATA[%s]]></recentQ>
    <recentA><![CDATA[%s]]></recentA>
    <role>%s</role>
    </message>','waiting',$resp_html,$recentQ,$recentA,$role);
  echo $xml;
 
