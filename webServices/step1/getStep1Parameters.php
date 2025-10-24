<?php
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
  include_once $root_path.'/domainSpecific/mySqlObject.php';      
  $uid = $_POST["post_uid"];
  $jSonOut=array (
    'success'=>'unconnected',
    'email'=>$email,
    'uid'=>'',
    'permissions' => 0,
    'isActive'=>'0',
    'exptId'=>'',
    'dayNo'=>'',
    'sessionNo'=>'',
    'exptType'=>'',
    'exptStage'=>'',
    'role'=>'.',
    'jType'=>'.',
    'jNo' => '.',
    'finishedProbe' => 0,
  );
  $jSonOut['success']='logged-in!';
  // see if in active session, and set parameters accordingly
  $sqlQry_Session=sprintf("SELECT * FROM igActiveStep1Users WHERE uid='%s'", $uid);
  $activeResult=$igrtSqli->query($sqlQry_Session);
  if ($activeResult) {
    $sessionRow=$activeResult->fetch_object();
    $jSonOut['uid']=$sessionRow->uid;
    $jSonOut['exptId']=$sessionRow->exptId;
    $jSonOut['dayNo']=$sessionRow->day;
    $jSonOut['sessionNo']=$sessionRow->session; 
    $jSonOut['jType']=$sessionRow->jType;
    $jSonOut['jNo'] = $sessionRow->jNo;
    $jSonOut['isActive']='1';
    $jSonOut['exptStage']='1';
    $jSonOut['exptType']='multi';
    $jSonOut['finishedProbe']=$sessionRow->finishedProbe;
  } 
  $xml=sprintf('<message><messageType>loginResults</messageType><success>%s</success>
                <email>%s</email><uid>%s</uid><permissions>%s</permissions>
                <isActive>%s</isActive><exptId>%s</exptId><dayNo>%s</dayNo>
                <sessionNo>%s</sessionNo><jType>%s</jType><jNo>%s</jNo><exptType>%s</exptType>
                <exptStage>%s</exptStage><role>%s</role><finishedProbe>%s</finishedProbe></message>',
      $jSonOut['success'],
      $jSonOut['email'],
      $jSonOut['uid'],
      $jSonOut['permissions'],
      $jSonOut['isActive'],
      $jSonOut['exptId'],
      $jSonOut['dayNo'],
      $jSonOut['sessionNo'],
      $jSonOut['jType'],
      $jSonOut['jNo'],
      $jSonOut['exptType'],
      $jSonOut['exptStage'],
      $jSonOut['role'],
      $jSonOut['finishedProbe']
  );
  echo $xml;

