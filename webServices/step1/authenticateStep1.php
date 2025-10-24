<?php
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
  include_once $root_path.'/domainSpecific/mySqlObject.php';      
  $email=$_POST["username"];
  $pw=$_POST["password"];
  $hash_str=hash('sha1',$pw);
  $jSonOut=array (
      'success'=>'unconnected',
      'email'=>$email,
      'uid'=>'',
      'permissions'=>'',
      'isActive'=>'0',
      'exptId'=>'',
      'dayNo'=>'',
      'sessionNo'=>'',
      'exptType'=>'',
      'exptStage'=>'',
      'role'=>'.',
      'jType'=>'.',
  );
  $sqlQry_Login=sprintf("SELECT * FROM igUsers WHERE email='%s' AND password='%s';",$email,$hash_str);
  //$sqlQry_Login=sprintf("SELECT * FROM igUsers WHERE email='%s'",$email);
  $loginResult=$igrtSqli->query($sqlQry_Login);
  if ($loginResult)
  {
      $row=$loginResult->fetch_object();
      $jSonOut['uid']=$row->id;
      $jSonOut['permissions'] = $row->permissions;
      $jSonOut['success']='logged-in!';
      // see if in active session, and set parameters accordingly
      $sqlQry_Session=sprintf("SELECT * FROM igActiveUsers WHERE uid='%s';",$row->id);
      $activeResult=$igrtSqli->query($sqlQry_Session);
      if ($activeResult->num_rows > 0) {
          $sessionRow=$activeResult->fetch_object();
          $jSonOut['exptId']=$sessionRow->exptId;
          $jSonOut['dayNo']=$sessionRow->day;
          $jSonOut['sessionNo']=$sessionRow->session; 
          $jSonOut['jType']=$sessionRow->jType;
          $jSonOut['isActive']='1';
          $jSonOut['exptStage']=$sessionRow->exptStage;
          $jSonOut['exptType']='multi';
      }
  }
  $xml=sprintf('<message><messageType>loginResults</messageType><success>%s</success><email>%s</email><uid>%s</uid><permissions>%s</permissions>
                  <isActive>%s</isActive><exptId>%s</exptId><dayNo>%s</dayNo><sessionNo>%s</sessionNo><jType>%s</jType>
                  <exptType>%s</exptType><exptStage>%s</exptStage><role>%s</role></message>',
              $jSonOut['success'],
              $jSonOut['email'],
              $jSonOut['uid'],
              $jSonOut['permissions'],
              $jSonOut['isActive'],
              $jSonOut['exptId'],
              $jSonOut['dayNo'],
              $jSonOut['sessionNo'],
              $jSonOut['jType'],
              $jSonOut['exptType'],
              $jSonOut['exptStage'],
              $jSonOut['role']
          );
  echo $xml;

