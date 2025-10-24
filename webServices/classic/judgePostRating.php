<?php
  ini_set('display_errors', 'On');
  error_reporting(E_ALL); 
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
  include_once $root_path.'/domainSpecific/mySqlObject.php';       
  include_once $root_path.'/helpers/models/class.experimentModel.php';       

  $uid=$_POST['userid'];
  $exptId=$_POST['experimentID'];
  $stage=$_POST['stage'];
  $choice=$_POST['uChoice'];
  $confidence=$_POST['uConfidence'];
$alignmentChoice1 = $_POST['alignmentChoice1'];
$alignmentChoice2 = $_POST['alignmentChoice2'];
  $qNo = $_POST['qNo'];
  $groupNo = $_POST['groupNo'];
  $npLeft = $_POST['npLeft'];
  $reason = $igrtSqli->real_escape_string($_POST['uReason']); 
  
  $eModel = new experimentModel($exptId);
  
  // get day and session info from igActiveClassicUsers
  $sqlDS=sprintf("SELECT * FROM igActiveClassicUsers WHERE uid='%s' AND exptId='%s'",$uid,$exptId);
  $DSresult=$igrtSqli->query($sqlDS);
  if ($DSresult) {
    $rowDS=$DSresult->fetch_object();
    $dayNo=$rowDS->dayNo;
    $sessionNo=$rowDS->sessionNo;
  }
  // normal rating
  // get id of latest question
  $sqlQ=sprintf("SELECT * FROM dataClassic WHERE owner='%s' AND exptId='%s' ORDER BY insertDT DESC",$uid,$exptId);
  $qResult=$igrtSqli->query($sqlQ);
  // most recent on top
  $row=$qResult->fetch_object();
  $qID=$row->id;

  $sqlUQ=sprintf("UPDATE dataClassic SET choice='%s', confidence='%s', reason='%s', npLeft='%s', r1Alignment='%s', r2Alignment = '%s' "
      . "WHERE id='%s'",
      $choice, $confidence, $reason, $npLeft, $alignmentChoice1, $alignmentChoice2, $qID);
  $igrtSqli->query($sqlUQ);
  if ($stage==0) {
    $sqlUpdate = sprintf("UPDATE igActiveClassicUsers SET jState='0' WHERE uid='%s' AND exptId='%s' AND dayNo='%s' AND sessionNo='%s'",$uid,$exptId,$dayNo,$sessionNo);        
  }
  else {
    $sqlUpdate = sprintf("UPDATE igActiveClassicUsers SET jState='3' WHERE uid='%s' AND exptId='%s' AND dayNo='%s' AND sessionNo='%s'",$uid,$exptId,$dayNo,$sessionNo);
    //echo $sqlUpdate;
  }
  $igrtSqli->query($sqlUpdate);
  // get history
  $j_html='<div></div>';
  $reverseHistory=array();
  $sql=sprintf("SELECT * FROM dataClassic WHERE owner='%s' ORDER BY insertDT DESC", $uid);
  //echo $sql;
  $result=$igrtSqli->query($sql);
  if ($result) {
    while ($row=$result->fetch_object()) {
      $det=array(
        'jQuestion'=>$row->jQ,
        'npReply'=>$row->npA,
        'pReply'=>$row->pA,
      );
      array_push($reverseHistory,$det);
    }               
  }
  $lastNumber=count($reverseHistory);
  if ($npLeft == 1) {
    foreach ($reverseHistory as $v) {
      // counter-balance left/right responses
      $j_html.='<div class="previousQuestion"><p>.....................................................................................................................................................................</p>';
      $j_html.=sprintf("<p><span>%s %s</span>%s</p>", $eModel->jRatingQ, $lastNumber, $v["jQuestion"]);
      $j_html.=sprintf("<div class=\"responseOne\"><h3>%s </h3><p>%s</p></div>",$eModel->jRatingR1,$v["npReply"]);
      $j_html.=sprintf("<div class=\"responseTwo\"><h3>%s </h3><p>%s</p></div>",$eModel->jRatingR2,$v["pReply"]);
      $j_html.='</div>';
      --$lastNumber;
    }
  }
  else {
    foreach ($reverseHistory as $v) {
      $j_html.='<div class="previousQuestion"><p>.....................................................................................................................................................................</p>';
      $j_html.=sprintf("<p><span>%s %s</span>%s</p>", $eModel->jRatingQ, $lastNumber, $v["jQuestion"]);
      $j_html.=sprintf("<div class=\"responseOne\"><h3>%s </h3><p>%s</p></div>",$eModel->jRatingR1,$v["pReply"]);
      $j_html.=sprintf("<div class=\"responseTwo\"><h3>%s </h3><p>%s</p></div>",$eModel->jRatingR2,$v["npReply"]);
      $j_html.='</div>';
      --$lastNumber;
    }
  }
  if ($stage==0) {
    // go to next question
    $xml = sprintf('<message><messageType>jStateInfo</messageType>
        <jState>%s</jState>
        <historyHtml><![CDATA[%s]]></historyHtml>
        </message>','active',$j_html);
  }
  else {
    // go to final rating
    $xml = sprintf('<message><messageType>jStateInfo</messageType>
        <jState>%s</jState>
        <historyHtml><![CDATA[%s]]></historyHtml>
        </message>','finalRating',$j_html);
  }
  echo $xml;
 
