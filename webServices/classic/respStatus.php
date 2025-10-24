<?php
  ini_set('display_errors', 'On');
  error_reporting(E_ALL); 
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
  include_once $root_path.'/domainSpecific/mySqlObject.php';        
  include($root_path.'/domainSpecific/domainInfo.php');
  include_once $root_path.'/helpers/models/class.experimentModel.php';       
  $uid=$_POST['userid'];
  $exptId=$_POST['experimentID'];
  $groupNo = $_POST['groupNo'];
  $eModel = new experimentModel($exptId);
  $respData=array(
    'uid'=>$uid,
    'exptId'=>$exptId,
    'groupNo'=> $groupNo,
    'day'=>'',
    'session'=>'',
    'history'=>'.',
    'recentQ'=>'.',
    'recentA'=>'.',
    'respState'=>'.',
    'respStateText'=>'.',
    'role'=>'',
  );
  // get all current values
  $sql=sprintf("SELECT * FROM igActiveClassicUsers WHERE uid='%s'",$uid);
  $result=$igrtSqli->query($sql);
  $row=$result->fetch_object(); // 
  $respData['day']=$row->dayNo;
  $respData['session']=$row->sessionNo;
  $respData['respState']=$row->respState;
  $respData['role']=$row->role;    
  // see whether any recent Q & A exist        
  $sqlData=sprintf("SELECT * FROM dataClassic WHERE "
      . "exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND groupNo='%s' "
      . "ORDER BY insertDT DESC",
    $respData['exptId'],
    $respData['day'],
    $respData['session'],
    $respData['groupNo']
  );
  $dataResult=$igrtSqli->query($sqlData);
  //echo $sqlData;
  if ($dataResult) {
    $dataRow=$dataResult->fetch_object();
    $respData['recentQ']=$dataRow->jQ;
    if ($respData['role']=="NP") {
      if ($dataRow->npA > '') {$respData['recentA']=$dataRow->npA;}
    }
    else {
      if ($dataRow->pA > '') {$respData['recentA']=$dataRow->pA;}
    }                
  }
  switch($respData['respState']) {
    case 0: {
      $respData['respStateText']='waitingForAction';
      break;
    }
    case 1: {
      $respData['respStateText']='answerQuestion';
      break;
    }
    case 2: {
      $respData['respStateText']='waiting';
      break;
    }
    case 3: {
      $respData['respStateText']='done';
      break;
    }
  }
  // build history if it exists
  $resp_html='<div></div>';
  $reverseHistory=array();
  // get exptId, day & session
  $sqlHistory=sprintf("SELECT * FROM dataClassic WHERE exptId='%s' "
      . "AND dayNo='%s' AND sessionNo='%s' AND groupNo='%s' "
      . "ORDER BY insertDT DESC",
    $respData['exptId'],
    $respData['day'],
    $respData['session'],
    $respData['groupNo']
  );
  $historyResult=$igrtSqli->query($sqlHistory);
  if ($historyResult) {
    while ($row=$historyResult->fetch_object()) {
      if ($respData['role']=="NP") {
        $det = array('resp'=>$row->npA,'jQ'=>$row->jQ);                    
      }
      else {
        $det = array('resp'=>$row->pA,'jQ'=>$row->jQ);                                        
      }
      array_push($reverseHistory, $det);
    }
  }
  $lastNumber=count($reverseHistory);
  foreach ($reverseHistory as $v) {
    $resp_html.='<div class="previousQuestion">';
    $resp_html.="<p> - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - </p>";
    $resp_html.=sprintf("<p>%s<span> %s: </span>%s</p>",$eModel->rCurrentQ, $lastNumber, $v["jQ"]);
    $resp_html.=sprintf("<div class=\"response\"><p><span>%s: </span>%s</p></div>",$eModel->rYourAnswer, $v["resp"]); 
    $resp_html.='</div>';
    --$lastNumber;
  }
  $respData['history']=$resp_html;
  if ($respData['role']=='NP') {
    $mt='npUpdate';
  }
  else {
    $mt='pUpdate';
  }
  $xml=sprintf('<message><messageType>%s</messageType>
      <respState>%s</respState>
      <historyHtml><![CDATA[%s]]></historyHtml>
      <recentA><![CDATA[%s]]></recentA>
      <recentQ><![CDATA[%s]]></recentQ>
      </message>',
      $mt,
      $respData['respStateText'],
      $respData['history'],
      $respData['recentA'],
      $respData['recentQ']
  );
  echo $xml;

