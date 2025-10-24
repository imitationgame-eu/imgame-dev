<?php
// prevent the server from timing out
set_time_limit(0);

$experimentConfigController = null;
$dbHelper = null;
$Server = null;
$ecHelper = null;
$step1HtmlBuilder = null;
$full_ws_path = realpath(dirname(__FILE__));
$root_path = substr($full_ws_path, 0, strlen($full_ws_path)-3);

require 'class.PHPWebSocket.php';
include_once $root_path.'/domainSpecific/cliEnvironment.php';
include_once $root_path.'/domainSpecific/mySqlObject.php';
include_once $root_path.'/ws/controllers/class.step1Controller.php';
include_once $root_path.'/helpers/class.dbHelpers.php';
include_once($root_path.'/helpers/admin/class.experimentConfigurator.php');
include_once($root_path.'/helpers/html/class.htmlBuilder.php');

$step1GlobalClientPool = array();                 // map of all clientId <--> connected user, config & participant
$step1ParticipantClients = array();               // list of client connections who are participants
$disconnectedStep1ParticipantClients = array();   // pool of step1 clients accidently disconnected, needing recovery
$putativeStep1Participants = array();             // pool of logged-in participants not yet attached to a step1 controller 
$step1SessionControllers = array();               // list of role/status combos currently operational
$disconnectedS1Controllers = array();             // list of step1 controllers without an admin client
$step1MonitorClients = array();                   // list of monitor clients

//echo dns_get_mx();

global $connectionOK;
global $igrtSqli;
global $cliDomain;

// <editor-fold defaultstate="collapsed" desc=" step1 process">

function addToStep1GlobalClientPool($clientId, $uid, $fName, $sName, $permissions, $email) {
  global $step1GlobalClientPool;
  $step1PoolDef = array(
    'uid' => $uid,
    'clientId' => $clientId,
    'fName' => $fName,
    'sName' => $sName,
    'permissions' => $permissions,
    'email' => $email,
  );
  array_push($step1GlobalClientPool, $step1PoolDef);    // put into global step1 client list        
}

function attachPutativeClients($s1SCPtr, $exptId, $dayNo, $sessionNo) {
  global $putativeStep1Participants,$step1ParticipantClients,$step1SessionControllers;
  $pList=array();
  $i=0;
  foreach ($putativeStep1Participants as $pp) {
    if (($pp['exptId']==$exptId) && ($pp['dayNo']==$dayNo) && ($pp['sessionNo']==$sessionNo)) {
      $pp['controllerPtr']=$s1SCPtr;
      // connect to controller
      $clientId=$pp['clientId'];
      $jType=$pp['jType'];
      $uid=$pp['uid'];
      $email=$pp['email'];
      $tabTitle = $pp['tabTitle'];
      $jNo = $pp['jNo'];
      $useMacro = $step1SessionControllers[$s1SCPtr]['useMacro']; 
      //echo $jNo;
      $step1SessionControllers[$s1SCPtr]['controller']->connectExperimentClient($clientId, $jNo, $jType,$uid,$exptId,$dayNo,$sessionNo,$email, $tabTitle, $useMacro);
      // need to do equivalent of initconfirm to show correct logins when controller initialised
      $step1SessionControllers[$s1SCPtr]['controller']->confirmLogin($jType, $jNo);
      $step1SessionControllers[$s1SCPtr]['controller']->updateStatusPage(0);
      // now push from putative to participant
      array_push($step1ParticipantClients,$pp);
      $pListDef=array('index'=>$i);
      array_push($pList,$pListDef);
      ++$i;
    }
  }
  foreach ($pList as $pL) {
    unset ($putativeStep1Participants[$pL['index']]);
  }
  $putativeStep1Participants=array_values($putativeStep1Participants);
}

function processStep1Participant($clientId, $uid, $exptId, $dayNo, $sessionNo, $jType, $jNo, $email) {
  global $putativeStep1Participants, $step1ParticipantClients, $step1SessionControllers;
  //echo "processing Step1 ppt";
  // add to global connections (TODO - use fName/sName/permissions as per config management
  addToStep1GlobalClientPool($clientId, $uid, '', '', 0, $email);
  // new one, create and connect to session
  $step1P = array(
    'uid' => $uid,
    'clientId' => $clientId,
    'exptId' => $exptId,
    'dayNo' => $dayNo,
    'sessionNo' => $sessionNo,
    'jType' => $jType,
    'jNo' => $jNo,
    'email' => $email,
    'loggedIn' => true,
    'pendingKeepAlive' => false,
    'tabTitle' => $email,
    'controllerPtr' => null,
    'disconnectedControllerPtr' => null
  );
  $s1SCPtr = getStep1ControlIndexFromParameters($exptId, $dayNo, $sessionNo);
  if ($s1SCPtr > -1) {
    // controller exists, so connect
    $useMacro = $step1SessionControllers[$s1SCPtr]['useMacro'];
    $step1P['controllerPtr'] = $s1SCPtr;
    $tabTitle = $email;
    $step1P['tabTitle'] = $email;
    array_push($step1ParticipantClients, $step1P);
    $step1SessionControllers[$s1SCPtr]['controller']->connectExperimentClient($clientId, $jNo, $jType, $uid, $exptId, $dayNo, $sessionNo, $email, $tabTitle, $useMacro);
    $step1SessionControllers[$s1SCPtr]['controller']->updateStatusPage(0);
    return "activeStep1";
//    $debug = print_r($step1ParticipantClients, true);
//    echo $debug;
  }
  else {
    // put into putative clients
    array_push($putativeStep1Participants, $step1P);
    return "waitingStep1";
    //echo "adding to putative clients";
  }
  //debugControllerStatus('participant');
}

function confirmLogin($tabTitle) {
  global $step1ParticipantClients,$step1SessionControllers;
  $s1pPtr = getStep1ParticipantFromTitle($tabTitle);
  if ($s1pPtr > -1) {
    $step1ParticipantClients[$s1pPtr]['confirmed']=true;
    $s1cPtr = $step1ParticipantClients[$s1pPtr]['controllerPtr'];
    $jType = $step1ParticipantClients[$s1pPtr]['jType'];
    $jNo = $step1ParticipantClients[$s1pPtr]['jNo'];
    $step1SessionControllers[$s1cPtr]['controller']->confirmLogin($jType, $jNo);
    $step1SessionControllers[$s1cPtr]['controller']->updateStatusPage(0);
    //debugControllerStatus('ppt');
  }
  else {
    // houston
  }
}

// </editor-fold>
 
// <editor-fold defaultstate="collapsed" desc=" logout/ disconnection & reconnection">

function cleanUp($clientId) {
  global $step1GlobalClientPool, $step1ParticipantClients,
    $disconnectedStep1ParticipantClients, $disconnectedS1Controllers,
    $putativeStep1Participants, $step1SessionControllers,
    $Server;
  // this might be due to an accidental disconnection or proper use    
  // is this clientId a genuine one, or an accidental proxy connection?
  $s1GCPtr = getGlobalClientPoolIndex($clientId);
  $clientType = '';
  if ($s1GCPtr > -1) {
    $s1CPoolPtr = getStep1ControlIndex($clientId);
    //echo " disconnect $clientId should see scptr $s1CPoolPtr \n ";
    if ($s1CPoolPtr > -1) {
      if ($step1SessionControllers[$s1CPoolPtr]['controller']->connectedToAdminClient) {
          if ($step1SessionControllers[$s1CPoolPtr]['controller']->connectedAdminClientId == $clientId) {
            $discController = array(
              'uid' => $step1SessionControllers[$s1CPoolPtr]['uid'], 
              's1cPtr' => $s1CPoolPtr,
              'exptId' => $step1SessionControllers[$s1CPoolPtr]['exptId'],
              'sessionNo' => $step1SessionControllers[$s1CPoolPtr]['sessionNo'],           
              'dayNo' => $step1SessionControllers[$s1CPoolPtr]['dayNo'],
            );
            array_push($disconnectedS1Controllers, $discController);
            $step1SessionControllers[$s1CPoolPtr]['controller']->removeMonitor($clientId);
            $step1SessionControllers[$s1CPoolPtr]['controller']->connectedToAdminClient = false;
            $step1SessionControllers[$s1CPoolPtr]['controller']->connectedAdminClientId = -1;
            $step1SessionControllers[$s1CPoolPtr]['controller']->clientId = -1;
            $step1SessionControllers[$s1CPoolPtr]['clientId'] = -1;
          }
        }
      $clientType = 'admin';
    }
    $s1Step1PPtr = getStep1ParticipantIndex($clientId);
    if ($s1Step1PPtr > -1) {
      // it's a participant, so need to move to disconnected participant pool
      $cPtr = $step1ParticipantClients[$s1Step1PPtr]['controllerPtr'];        
      $jType = $step1ParticipantClients[$s1Step1PPtr]['jType'];
      $jNo = $step1ParticipantClients[$s1Step1PPtr]['jNo'];
      $uid = $step1ParticipantClients[$s1Step1PPtr]['uid'];
      $step1ParticipantClients[$s1Step1PPtr]['confirmed'] = false; // used by admin status screen
      array_push($disconnectedStep1ParticipantClients, $step1ParticipantClients[$s1Step1PPtr]);
      unset($step1ParticipantClients[$s1Step1PPtr]);
      $step1ParticipantClients = array_values($step1ParticipantClients);
      $Server->addClientToSuspendedList($clientId,null,null,null,null,$uid);
      $step1SessionControllers[$cPtr]['controller']->setJudgeDisconnected($jType, $jNo);
      // show updated login or progress status to admin client if connected
      $step1SessionControllers[$cPtr]['controller']->updateStatusPage(0);  

      $clientType = 'participant';
    }
    $step1PutativePPtr = getStep1PutativeParticipantIndex($clientId);
    if ($step1PutativePPtr > -1) {
      unset($putativeStep1Participants[$step1PutativePPtr]);
      $putativeStep1Participants = array_values($putativeStep1Participants);
    }
    unset($step1GlobalClientPool[$s1GCPtr]);
    $step1GlobalClientPool = array_values($step1GlobalClientPool);
    //debugControllerStatus($clientType);      
  }
}

function reConnect($clientId, $uid, $exptId, $jType, $jNo, $dayNo, $sessionNo, $email, $s1SCPtr) {
  global $Server, $step1SessionControllers;
  // if the clientId has been suspended, it will be restored
  $Server->removeClientFromSuspendedList($clientId);

  // ensure instance of old clientId are new clientId (it may be the same!) in s1model ready for rebuild and resend
  $step1SessionControllers[$s1SCPtr]['controller']->reconnectClientId($jType, $jNo, $clientId);
  // has this step1 started?
  if ($step1SessionControllers[$s1SCPtr]['controller']->notStarted) {
    // signal reconnection to login status/control page 
    $step1SessionControllers[$s1SCPtr]['controller']->updateStatusPage(0);   
    $useMacro = $step1SessionControllers[$s1SCPtr]['useMacro']; 
    //echo("reconnect jNo: ".$jNo+1);
    $Initxml=sprintf("<message><messageType>Initialise</messageType><content>%s</content><useMacro>%s</useMacro></message>", $email, $useMacro);
    //$Server->log("init"," $clientId : $Initxml");
    $Server->wsSend($clientId, $Initxml);
  }
  else {
    // send content to client
    $step1SessionControllers[$s1SCPtr]['controller']->reSendContent($clientId); // only to this client
    // set UI of reconnected client
    $jAction = $step1SessionControllers[$s1SCPtr]['controller']->getJaction($jType, $jNo);
    $qNo = $step1SessionControllers[$s1SCPtr]['controller']->getQno($jType, $jNo);
    $npAction = $step1SessionControllers[$s1SCPtr]['controller']->getNPaction($jType, $jNo);
    $pAction = $step1SessionControllers[$s1SCPtr]['controller']->getPaction($jType, $jNo);
    // judge
    $jHistoryHTML=$step1SessionControllers[$s1SCPtr]['controller']->buildJHistory($jType, $jNo);
    $r1 = ''; $r2 = '';
    $step1SessionControllers[$s1SCPtr]['controller']->getResponses($jType, $jNo, $r1, $r2);
    if ($r1=='') {$r1='.';}
    if ($r2=='') {$r2='.';}
    $jQ=$step1SessionControllers[$s1SCPtr]['controller']->getRecentJQ($jType, $jNo);
    if ($jQ=='') {$jQ='.';}
    $jRatingHtml=$step1SessionControllers[$s1SCPtr]['controller']->getJudgeRatingHtml($jType);
    if ($step1SessionControllers[$s1SCPtr]['controller']->s1model->eModel->randomiseSideS1 == 1) {
      $npSide = mt_rand(0,1);
    }
    else {
      $npSide = $jNo % 2 == 0 ? 1 : 0;
    }
    $jFinalRatingHtml=$step1SessionControllers[$s1SCPtr]['controller']->getJudgeFinalRatingHtml($jType, $jNo, $npSide);
    $useLikert=$step1SessionControllers[$s1SCPtr]['controller']->s1model->eModel->useLikert;           
    $useReasons=$step1SessionControllers[$s1SCPtr]['controller']->s1model->eModel->useReasons; 
    $useFinalReasons=$step1SessionControllers[$s1SCPtr]['controller']->s1model->eModel->useReasonFinalRating;
    $useFinalLikert = $step1SessionControllers[$s1SCPtr]['controller']->s1model->eModel->useFinalLikert;
    $showNoMoreQuestions = $step1SessionControllers[$s1SCPtr]['controller']->showNoMoreQuestions;
    $s1barbilliardControl = $step1SessionControllers[$s1SCPtr]['controller']->s1model->eModel->s1barbilliardControl;
    $s1QuestionCountAlternative = $step1SessionControllers[$s1SCPtr]['controller']->s1model->eModel->s1QuestionCountAlternative;
    $xml=sprintf("<message><messageType>Title</messageType><content>%s</content></message>", $email);
    //$Server->log('resend title',"$clientId : $xml");
    $Server->wsSend($clientId,$xml);
    $xml = sprintf("<message><messageType>rebuildJui</messageType>
      <currentstate>%s</currentstate>
      <jQ><![CDATA[%s]]></jQ>
      <r1><![CDATA[%s]]></r1>
      <r2><![CDATA[%s]]></r2>
      <jH><![CDATA[%s]]></jH>
      <jrHtml><![CDATA[%s]]></jrHtml>
      <jfinalrHtml><![CDATA[%s]]></jfinalrHtml>
      <useLikert>%s</useLikert>
      <useReasons>%s</useReasons>
      <useFinalReason>%s</useFinalReason>
      <useFinalLikert>%s</useFinalLikert>
      <intentionMinValue>%s</intentionMinValue>
      <reasonMinValue>%s</reasonMinValue>
      <finalReasonMinValue>%s</finalReasonMinValue>
      <randomiseSideS1>%s</randomiseSideS1>
      <useS1AlignmentControl>%s</useS1AlignmentControl>
      <useS1QCategoryControl>%s</useS1QCategoryControl>
      <useS1IntentionMin>%s</useS1IntentionMin>
      <useS1Intention>%s</useS1Intention>
      <useBarbilliardsControl>%s</useBarbilliardsControl>
			<noMandatoryQuestions>%s</noMandatoryQuestions>
			<qNo>%s</qNo>
			<finalQ>%s</finalQ>
      <npSide>%s</npSide>
      </message>",
      $jAction, $jQ, $r1, $r2, $jHistoryHTML, $jRatingHtml, $jFinalRatingHtml,
      $useLikert, $useReasons, $useFinalReasons, $useFinalLikert, 
      $step1SessionControllers[$s1SCPtr]['controller']->s1model->eModel->s1IntentionMin,
      $step1SessionControllers[$s1SCPtr]['controller']->s1model->eModel->reasonCharacterLimitValue,
      $step1SessionControllers[$s1SCPtr]['controller']->s1model->eModel->reasonCharacterLimitValueF,        
      $step1SessionControllers[$s1SCPtr]['controller']->s1model->eModel->randomiseSideS1,
      $step1SessionControllers[$s1SCPtr]['controller']->s1model->eModel->useS1AlignmentControl,
      $step1SessionControllers[$s1SCPtr]['controller']->s1model->eModel->useS1QCategoryControl,
      $step1SessionControllers[$s1SCPtr]['controller']->s1model->eModel->useS1IntentionMin,        
      $step1SessionControllers[$s1SCPtr]['controller']->s1model->eModel->useS1Intention,        
      $s1barbilliardControl, $s1QuestionCountAlternative, $qNo, $showNoMoreQuestions, $npSide
    );
    //$Server->log('rebuildJui',"$clientId : $xml ");
    $Server->wsSend($clientId,$xml);
    // np
    $npHistoryHTML=$step1SessionControllers[$s1SCPtr]['controller']->buildOtherNPHistory($jType, $jNo);
    $npJQ=$step1SessionControllers[$s1SCPtr]['controller']->getNPJQ($jType, $jNo);
    if ($npJQ=='') {$npJQ='.';}
    $npA=$step1SessionControllers[$s1SCPtr]['controller']->getNPA($jType, $jNo);
    if ($npA=='') {$npA='.';}
    $xml=sprintf("<message><messageType>rebuildNPui</messageType><state>%s</state><rQ><![CDATA[%s]]></rQ><rA><![CDATA[%s]]></rA><npH><![CDATA[%s]]></npH></message>",$npAction,$npJQ,$npA,$npHistoryHTML);            
    //$Server->log('rebuildNPui',"$clientId : $xml");
    $Server->wsSend($clientId,$xml);
    // p
    $pHistoryHTML=$step1SessionControllers[$s1SCPtr]['controller']->buildOtherPHistory($jType, $jNo);
    $pJQ=$step1SessionControllers[$s1SCPtr]['controller']->getPJQ($jType, $jNo);
    if ($pJQ=='') {$pJQ='.';}
    $pA=$step1SessionControllers[$s1SCPtr]['controller']->getPA($jType, $jNo);
    if ($pA=='') {$pA='.';}
    $xml=sprintf("<message><messageType>rebuildPui</messageType><state>%s</state><rQ><![CDATA[%s]]></rQ><rA><![CDATA[%s]]></rA><pH><![CDATA[%s]]></pH></message>",$pAction,$pJQ,$pA,$pHistoryHTML);            
    //$Server->log('rebuildPui',"$clientId : $xml");
    $Server->wsSend($clientId,$xml);
    // don't update status page until loginConfirm received
    $step1SessionControllers[$s1SCPtr]['controller']->updateStatusPage(0);
  }
  //debugControllerStatus('client');
}

function switchDisconnectedStep1CtoActive($clientId, $dccPtr) {
  global $step1SessionControllers, $disconnectedS1Controllers, $Server, $dbHelper;
  $s1SCPtr = $disconnectedS1Controllers[$dccPtr]['s1cPtr'];
  unset($disconnectedS1Controllers[$dccPtr]);
  $disconnectedS1Controllers = array_values($disconnectedS1Controllers);
  $step1SessionControllers[$s1SCPtr]['controller']->addMonitor($clientId);
  $step1SessionControllers[$s1SCPtr]['clientId'] = $clientId;
  $step1SessionControllers[$s1SCPtr]['controller']->clientId = $clientId;
  $step1SessionControllers[$s1SCPtr]['controller']->connectedToAdminClient = true;
  $step1SessionControllers[$s1SCPtr]['controller']->connectedAdminClientId = $clientId;
  $exptId = $step1SessionControllers[$s1SCPtr]['exptId'];
  $exptTitle = $dbHelper->getExptTitleFromId($exptId);
  $paramsXml = sprintf("<message><messageType>reconnectAdmin</messageType><title>%s</title><sn>%s</sn><dn>%s</dn></message>",
                        $exptTitle,
                        $step1SessionControllers[$s1SCPtr]['controller']->sessionNo,
                        $step1SessionControllers[$s1SCPtr]['controller']->dayNo);
  $Server->wsSend($clientId, $paramsXml);  // set title and allocation info, although latter is quickly overwritten with live connection info
  if ( !$step1SessionControllers[$s1SCPtr]['controller']->notStarted ) {
    //send controlStart back to admin client
    $controlXML='<message><messageType>controlStart</messageType><content></content></message>';
    $Server->wsSend($clientId, $controlXML); 
    // check to see 'last Q' button should be hidden
    if ($step1SessionControllers[$s1SCPtr]['controller']->showNoMoreQuestions == 1) {
      $controlXML='<message><messageType>hideNoMoreQ</messageType><content></content></message>';
      $Server->wsSend($clientId, $controlXML);    
    }
  }
  $step1SessionControllers[$s1SCPtr]['controller']->checkCanStart();
  $step1SessionControllers[$s1SCPtr]['controller']->updateStatusPage(0);
  //debugControllerStatus('admin');
}

function switchFromDisconnectedToConnectedPool($clientId, $dpPtr) {
  global $disconnectedStep1ParticipantClients, $step1ParticipantClients;
  $tempC = $disconnectedStep1ParticipantClients[$dpPtr];
  $tempC['clientId'] = $clientId;
  array_push($step1ParticipantClients, $tempC);
  unset($disconnectedStep1ParticipantClients[$dpPtr]);
  $disconnectedStep1ParticipantClients = array_values($disconnectedStep1ParticipantClients);
}

function processReconnection($clientId, $uid, $exptId, $jType, $jNo, $dayNo, $sessionNo, $email, $s1SCPtr, $ds1pPtr) {
  global $disconnectedStep1ParticipantClients;
  global $Server;
  $tabTitle = $disconnectedStep1ParticipantClients[$ds1pPtr]['tabTitle'];
  reConnect($clientId, $uid, $exptId, $jType, $jNo, $dayNo, $sessionNo, $email, $s1SCPtr);
  switchFromDisconnectedToConnectedPool($clientId, $ds1pPtr);
  addToStep1GlobalClientPool($clientId, $uid, '', '', 0, $email);      // so that subsequent reconnections can be handled
  if ($tabTitle == '') {$tabTitle = $email ;}
  $ackxml=sprintf("<message><messageType>exptUI</messageType><content>%s</content></message>", $tabTitle);
  $Server->log("reconnected (logged-in) signal to:"," $clientId");
  $Server->wsSend($clientId, $ackxml);                     
}

function processDuplicateReconnection($clientId, $uid, $exptId, $jType, $jNo, $dayNo, $sessionNo, $email, $s1SCPtr, $ds1pPtr) {
  global $disconnectedStep1ParticipantClients;
  global $Server;
  $tabTitle = $email;
  reConnect($clientId, $uid, $exptId, $jType, $jNo, $dayNo, $sessionNo, $email, $s1SCPtr);
  switchFromDisconnectedToConnectedPool($clientId, $ds1pPtr);
  addToStep1GlobalClientPool($clientId, $uid, '', '', 0, $email);      // so that subsequent reconnections can be handled
  $ackxml=sprintf("<message><messageType>exptUI</messageType><content>%s</content></message>", $tabTitle);
  $Server->log("reconnected (logged-in) signal to:"," $clientId");
  $Server->wsSend($clientId, $ackxml);                     
}

function closeOldConnection($dupPtr) {
  global $Server;
  global $step1ParticipantClients;
  $oldClientId = $step1ParticipantClients[$dupPtr]['clientId'];
  $closeXml = "<message><messageType>closeLogin</messageType><content></content></message>"; 
  $Server->wsSend($oldClientId, $closeXml);
  $Server->log("msg", $oldClientId."<:>".$closeXml);
  return $oldClientId;
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" internal helpers">

function getStep1ParticipantFromTitle($tabTitle) {
  global $step1ParticipantClients;
  $ptr = 0;
  foreach ($step1ParticipantClients as $c) {
    if ($c['tabTitle'] == $tabTitle) { return $ptr; }
    ++$ptr;
  }
  return -1;
}

function getDuplicateLoginIndex($uid, $exptId, $dayNo, $sessionNo) {
  global $step1ParticipantClients;
  $ptr = 0;
  foreach ($step1ParticipantClients as $c) {
    if ( ($c['uid'] == $uid ) && ($c['exptId'] == $exptId) ) {
      return $ptr;
    }
    ++$ptr;
  }
  return -1;
}

function getJNoFromSuspendedStep1ParticipantPool($uid) {
  global $disconnectedStep1ParticipantClients;
  foreach ($disconnectedStep1ParticipantClients as $c) {
    if ($c['uid'] == $uid) { return $c['jNo']; }
  }
  return -1;  //houston
}

function getS1SCPtrFromClientId($clientId) {
  global $step1SessionControllers;
  $ptr=0;
  foreach ($step1SessionControllers as $s1sc) {
    if ($s1sc['clientId'] == $clientId) { return $ptr; }
    ++$ptr;
  }
  return -1;  // indicates problem
}

function getPutativeClientIndex($clientId) {
    global $putativeStep1Participants;
    $ptr=0;
    foreach ($putativeStep1Participants as $pp)
    {
        if ($pp['clientId'] == $clientId) { return $ptr; }
        ++$ptr;
    }
    return -1;
}

function getGlobalClientPoolIndex($clientId) {
  global $step1GlobalClientPool;
  $ptr = 0;
  foreach ($step1GlobalClientPool as $s1cp)
  {
    if ($s1cp['clientId'] == $clientId) { return $ptr; }
    ++$ptr;
  }
  return -1;
}

function getStep1ControlIndex($clientId) {
  global $step1SessionControllers;
  $ptr = 0;
  foreach ($step1SessionControllers as $dsc) {
    if ($dsc['clientId'] == $clientId) { return $ptr; }
    ++$ptr;
  }
  return -1;
}

function getStep1ParticipantIndex($clientId) {
  global $step1ParticipantClients;
  $ptr=0;
  foreach ($step1ParticipantClients as $s1pc) {
    if ($s1pc['clientId'] == $clientId) { return $ptr; }
    ++$ptr;
  }
  return -1;
}

function getStep1PutativeParticipantIndex($clientId) {
  global $putativeStep1Participants;
  $ptr = 0;
  foreach ($putativeStep1Participants as $c) {
    if ($c['clientId'] == $clientId) { return $ptr; }
    ++$ptr;
  }
  return -1;
}

function getDisconnectedStep1ParticipantIndex($uid) {
  global $disconnectedStep1ParticipantClients;
  $ptr = 0;
  foreach ($disconnectedStep1ParticipantClients as $c) {
    if ($c['uid'] == $uid) { return $ptr; }
    ++$ptr;
  }
  return -1;
}

function getDisconnectedStep1ControllerIndex($uid) {
  global $disconnectedS1Controllers;
  $ptr=0;
  foreach ($disconnectedS1Controllers as $ds1c) {
    if ($ds1c['uid'] == $uid) { return $ptr; }
    ++$ptr;      
  }
  return -1;
}

function getUIDFromGlobalPool($clientId) {
  global $step1GlobalClientPool;
  foreach ($step1GlobalClientPool as $c) {
    if ($c['clientId'] == $clientId) { return $c['uid']; }
  }
  return -1;  // houston
}

function getStep1MonitorIndex($clientId) {
  global $step1MonitorClients;
  $s1cCnt = count($step1MonitorClients);
  for ($ptr = 0; $ptr<$s1cCnt; $ptr++) {
    if ($step1MonitorClients[$ptr]['clientId'] == $clientId) { return $ptr; }
  }
  return -1;
}

function getOldClientId($uid) {
  global $Server;
  foreach ($Server->suspendedClients as $sc) {
    if ($sc['uid'] == $uid) {return $sc['clientId'];}
  }
  return -1;  //houston
}

function getStep1ControlIndexFromParameters($exptId, $dayNo, $sessionNo) {
  global $step1SessionControllers;
  $ptr = 0;
  foreach ($step1SessionControllers as $s1sc) {
    if (  ($s1sc['exptId'] == $exptId) &&
          ($s1sc['dayNo'] == $dayNo) &&
          ($s1sc['sessionNo'] == $sessionNo)  ) { return $ptr; }
    ++$ptr;
  }
  return -1;
}

function getDisconnectedStep1ControlIndexFromParameters($exptId, $dayNo, $sessionNo) {
  global $disconnectedStep1SessionControllers;
  $ptr = 0;
  foreach ($disconnectedStep1SessionControllers as $c) {
    if (  ($c['exptId'] == $exptId) &&
          ($c['dayNo'] == $dayNo) &&
          ($c['sessionNo'] == $sessionNo)  ) { return $ptr; }
    ++$ptr;
  }
  return -1;
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" admin screen functions">

function updateConfigClient($clientId, $exptId, $dayNo, $sessionNo, $s1SCPtr) {
  global $configClients;
  $ccPtr = getConfigPoolIndex($clientId);
  $configClients[$ccPtr]['dayNo']=$dayNo;
  $configClients[$ccPtr]['sessionNo']=$sessionNo;
  $configClients[$ccPtr]['s1SCPtr']=$s1SCPtr;    
}

function getS1FromClientId($clientId) {
  global $step1SessionControllers;
  for ($i=0; $i<count($step1SessionControllers); $i++) {
    if ($step1SessionControllers[$i]['clientId'] == $clientId) { return $i; }
  }
  return -1;
}

function addToStep1MonitorPool($clientId) {
  global $step1MonitorClients;
  $mPtr = getStep1MonitorIndex($clientId);
  if ($mPtr > -1) {
    // ignore reconnection to Monitor client
  }
  else {
    $temp = array('clientId'=>$clientId, 'controller'=>null);
    array_push($step1MonitorClients, $temp);    
  }
}

function initialiseStep1Controller($clientId, $exptId, $dayNo, $sessionNo, $iActualCnt, $useMacro, $allocationGeneration) {
  global $step1SessionControllers, $Server, $step1GlobalClientPool;
  $gPtr = getGlobalClientPoolIndex($clientId);
  $step1Instance = new step1Controller($clientId, $Server, $exptId, $dayNo, $sessionNo, $iActualCnt, $allocationGeneration);
  $allocXml = $step1Instance->getAllocationInfoXml($exptId);
  $Server->wsSend($clientId, $allocXml);  // set title and allocation info, although latter is quickly overwritten with live connection info
  $exptModel = new experimentModel($exptId);
  $step1SC = array(
    'clientId' => $clientId,
    'adminConnected' => true,
    'uid' => $step1GlobalClientPool[$gPtr]['uid'],
    'fName' => $step1GlobalClientPool[$gPtr]['fName'],
    'sName' => $step1GlobalClientPool[$gPtr]['sName'],
    'exptId' => $exptId,
    'dayNo' => $dayNo,
    'sessionNo' => $sessionNo,
    'controller' => $step1Instance,
    'exptModel' => $exptModel,
    'useMacro' => $useMacro,
    'iActualCnt' => $iActualCnt,
    'allocationGeneration' => $allocationGeneration,
  );
  array_push($step1SessionControllers, $step1SC);
  $s1SCPtr = count($step1SessionControllers) - 1;
  $step1Instance->updateStatusPage(0);
  $Server->log("allocation parameters ", "initSession<:>$clientId<:>$exptId<:>$dayNo<:>$sessionNo<:>$iActualCnt");
  $step1Instance->updateIActualCnt($exptId, $dayNo, $sessionNo, $iActualCnt);
  // check putative client list to see any that are already logged in ready for this session
  attachPutativeClients($s1SCPtr, $exptId, $dayNo, $sessionNo);
}

function getActiveStep1Sessions($clientId) {
  global $Server, $step1SessionControllers, $dbHelper, $step1HtmlBuilder;
  $html = "";
  if (count($step1SessionControllers) > 0) {
    $html.= "<div class=\"currentExperiments active\">";
    $html.= "<h2>current active Step1 sessions</h2>";
    $controllerNo = 0;
    $html.= "<table><tr><th>expt title</th><th>day</th><th>session</th><th>judges per role</th><th>started by</th><th>attach</th></tr>";
    foreach ($step1SessionControllers as $s1sc) {
      $exptTitle = $dbHelper->getExptTitleFromId( $s1sc['exptId'] );
      $startedBy = $dbHelper->getEmailFromUid( $s1sc['uid']);
      $html.= sprintf("<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>", $exptTitle, $s1sc['dayNo'], $s1sc['sessionNo'], $s1sc['iActualCnt'], $startedBy );
      $buttonId = sprintf("monitorB_%s", $controllerNo);
      $html.= "<td>".$step1HtmlBuilder->makeButton($buttonId, "monitor", "button", '', $controllerNo)."</td></tr>";      
      ++$controllerNo;
    }
    $html.= "</table>";      
    $html.= "</div>";
  }
  else {
    $html.= "<h2>no active Step1 sessions</h2>";
  }
  $xml = sprintf("<message><messageType>step1ActiveSessionList</messageType><content><![CDATA[%s]]></content></message>", $html);
  //$Server->log('step1ActiveSessionList', $xml);
  $Server->wsSend($clientId, $xml);  
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" helpers and debug tools">

function logRawMessage($message) {
  global $igrtSqli;
  $logCmd=sprintf("INSERT INTO sysdiag_socketsLog (chrono,message) VALUES(NOW(),\"%s\");",$message);  
  $igrtSqli->query($logCmd);
}

function debugGlobalClients() {
    global $step1GlobalClientPool;
    echo("\n all clients (step1GlobalClientPool)\n");
    $i=0;
    foreach ($step1GlobalClientPool as $s1cp)
    {
        echo $i.' : ';
        echo $s1cp['clientId'].' : ';
        echo $s1cp['uid'].' : ';
        //echo $s1cp['jType'].' : '.$s1cp['jNo'];
        echo "\n";
        ++$i;
    }
}

function debugstep1ParticipantClients() {
    global $step1ParticipantClients;
    echo("\n step1ParticipantClients\n");
    $i=0;
    foreach($step1ParticipantClients as $pc)
    {
        echo $i.' : ';
        echo $pc['clientId'].' : ';
        echo $pc['uid'].' : ';
        echo $pc['jType'].' : '.$pc['jNo'].' : '.$pc['controllerPtr'];
        echo "\n";
        ++$i;
    }
}

function debugdisconnectedStep1ParticipantClients() {
    global $disconnectedStep1ParticipantClients;
    echo("\n disconnected step 1 Participant Clients\n");
    $i=0;
    foreach($disconnectedStep1ParticipantClients as $dpc)
    {
        echo $i.' : ';
        echo $dpc['clientId'].' : ';
        echo $dpc['uid'].' : ';
        echo $dpc['jType'].' : '.$dpc['jNo'].' : '.$dpc['controllerPtr'];
        echo "\n";
        ++$i;
    }
}

function debugPutativeClients() {
    global $putativeStep1Participants;
    echo("\n putative clients \n");
    $i=0;
    foreach($putativeStep1Participants as $pc)
    {
        echo $i.' : ';
        echo $pc['clientId'].' : ';
        echo $pc['uid'].' : ';
        echo $pc['jType'].' : '.$pc['jNo'].' : '.$pc['controllerPtr'];
        echo "\n";
        ++$i;        
    }
}

function debugdisconnectedStep1SessionControllers() {
  global $disconnectedS1Controllers;
  echo("\n disconnected Step1 Controllers\n");
  $i = 0;
  foreach($disconnectedS1Controllers as $ds1c) {
    echo $i.' : uid : ';
    echo $ds1c['uid'].' : exptId ';
    echo $ds1c['exptId'].' : ';
    echo $ds1c['dayNo'].' : '.$ds1c['sessionNo'];
    echo ' ptr: '.$ds1c['s1cPtr'];
    echo "\n";
    ++$i;
  }  
}

function debugStep1SessionControllers() {
  global $step1SessionControllers;
  echo("\n step1 session controllers\n");
  $i = 0;
  foreach ($step1SessionControllers as $s1c) {
    echo $i.' : uid : ';
    echo $s1c['uid'].' : name ';
    echo $s1c['fName'].' '.$s1c['sName'].' : ';
    echo $s1c['clientId'].' : ';
    echo $s1c['exptId'].' : ';
    echo $s1c['dayNo'].' : '.$s1c['sessionNo'].' : controller exptId '.$s1c['controller']->exptId;
    echo '\n connected to admin: '.$s1c['controller']->connectedToAdminClient;
    echo '\n connected admin clientId: '.$s1c['controller']->connectedAdminClientId;
    echo "\n";
    ++$i;
  }  
}

function debugControllerStatus($clientType) {
  if ($clientType == 'admin') {
    debugStep1SessionControllers();
    debugdisconnectedStep1SessionControllers();
  }
  else {
    debugstep1ParticipantClients();
    debugdisconnectedStep1ParticipantClients();
    debugPutativeClients();
  }
  debugGlobalClients();
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" message processor">

function processMessage($clientId, $messageType, $content) {
  global $Server;
  global $step1SessionControllers;
  global $step1ParticipantClients;
  global $disconnectedStep1ParticipantClients;
  global $dbHelper;
  global $ecHelper;
  switch ($messageType) {
    case "JQ": {
      $s1PPtr = getStep1ParticipantIndex($clientId);
      $s1SCPtr = $step1ParticipantClients[$s1PPtr]['controllerPtr'];
      $jType = $step1SessionControllers[$s1SCPtr]['controller']->getJTypeFromClientId($clientId);      //$Step1Runner->getJTypeFromClientId($clientId);
      $jNo = $step1SessionControllers[$s1SCPtr]['controller']->getJNoFromClientId($clientId);          //getJNoFromClientId($clientId);
      $q = urldecode($content[0]);
      $intention = urldecode($content[1]);
      $jPlayerNo = $step1SessionControllers[$s1SCPtr]['controller']->getPlayerNo($jType, $jNo);
      $step1SessionControllers[$s1SCPtr]['controller']->logPlayerMessage($jPlayerNo, null, "JQ", $content[0], $content[1]);  // use urlencoded value to avoid linebreaks
      $step1SessionControllers[$s1SCPtr]['controller']->sendQ($jType, $jNo, $q, $intention);
      $step1SessionControllers[$s1SCPtr]['controller']->updateStatusPage(0); // send update to admin client, not expt client
      //echo "JQ $clientId";
      break;
    }
    case "JR": {
      $s1PPtr = getStep1ParticipantIndex($clientId);
      $s1SCPtr = $step1ParticipantClients[$s1PPtr]['controllerPtr'];
      $jType = $step1SessionControllers[$s1SCPtr]['controller']->getJTypeFromClientId($clientId);
      $jNo = $step1SessionControllers[$s1SCPtr]['controller']->getJNoFromClientId($clientId);
      $jPlayerNo = $step1SessionControllers[$s1SCPtr]['controller']->getPlayerNo($jType, $jNo);
      $step1SessionControllers[$s1SCPtr]['controller']->logPlayerMessage($jPlayerNo, null, "JR", $content);
      $step1SessionControllers[$s1SCPtr]['controller']->processRating($clientId, $jType, $jNo, $content);     // length of $content may vary depending on expt configuration
      $step1SessionControllers[$s1SCPtr]['controller']->updateStatusPage(0);
      break;
    }
    case "JlastR": {
      $s1PPtr = getStep1ParticipantIndex($clientId);
      $s1SCPtr = $step1ParticipantClients[$s1PPtr]['controllerPtr'];
      $jType = $step1SessionControllers[$s1SCPtr]['controller']->getJTypeFromClientId($clientId);
      $jNo = $step1SessionControllers[$s1SCPtr]['controller']->getJNoFromClientId($clientId);
      $jPlayerNo = $step1SessionControllers[$s1SCPtr]['controller']->getPlayerNo($jType, $jNo);
      $step1SessionControllers[$s1SCPtr]['controller']->logPlayerMessage($jPlayerNo, null, "JR", $content);
//			$debugContent = print_r($step1SessionControllers[$s1SCPtr]['controller']->s1model->evenJudges, true);
//			$step1SessionControllers[$s1SCPtr]['controller']->logPlayerMessage(null, null, "debugFinalMemoryMap", $debugContent);
//			$debugContent = print_r($step1SessionControllers[$s1SCPtr]['controller']->s1model->oddJudges, true);
//			$step1SessionControllers[$s1SCPtr]['controller']->logPlayerMessage(null, null, "debugFinalMemoryMap", $debugContent);
      $step1SessionControllers[$s1SCPtr]['controller']->processRating($clientId, $jType, $jNo, $content);
      $step1SessionControllers[$s1SCPtr]['controller']->sendFinalRatingToJudge($clientId, $jType, $jNo);            
      $step1SessionControllers[$s1SCPtr]['controller']->updateStatusPage(0);
      break;
    }
    case "JfinalR": {
      $s1PPtr = getStep1ParticipantIndex($clientId);
      $s1SCPtr = $step1ParticipantClients[$s1PPtr]['controllerPtr'];
      $jType = $step1SessionControllers[$s1SCPtr]['controller']->getJTypeFromClientId($clientId);
      $jNo = $step1SessionControllers[$s1SCPtr]['controller']->getJNoFromClientId($clientId);
      $jPlayerNo = $step1SessionControllers[$s1SCPtr]['controller']->getPlayerNo($jType, $jNo);
      $step1SessionControllers[$s1SCPtr]['controller']->logPlayerMessage($jPlayerNo, null, "JfinalR", $content);
//      if ($step1SessionControllers[$s1SCPtr]['controller']->canProcessFinalRating($jType, $jNo)) {
//        $step1SessionControllers[$s1SCPtr]['controller']->processFinalRating($jType, $jNo, $content);
//      }
      $step1SessionControllers[$s1SCPtr]['controller']->processFinalRating($clientId, $jType, $jNo, $content);
      $step1SessionControllers[$s1SCPtr]['controller']->updateStatusPage(0); 
      break;
    }
    case "NPA": {
      $s1PPtr = getStep1ParticipantIndex($clientId);
      $s1SCPtr = $step1ParticipantClients[$s1PPtr]['controllerPtr'];
      $jType = $step1SessionControllers[$s1SCPtr]['controller']->getJTypeForNP($clientId);
      $jNo = $step1SessionControllers[$s1SCPtr]['controller']->getJNoForNP($clientId);
      // get jPlayerNo for who is judge to this person 
      $jPlayerNo = $step1SessionControllers[$s1SCPtr]['controller']->getPlayerNo($jType, $jNo);
      // get npPlayerNo for who this person is
      $npjType = $step1SessionControllers[$s1SCPtr]['controller']->getJTypeFromClientId($clientId);
      $npjNo = $step1SessionControllers[$s1SCPtr]['controller']->getJNoFromClientId($clientId);
      $npPlayerNo = $step1SessionControllers[$s1SCPtr]['controller']->getPlayerNo($npjType, $npjNo);      
      $npa = urldecode($content[0]);
      $step1SessionControllers[$s1SCPtr]['controller']->logPlayerMessage($jPlayerNo, $npPlayerNo, "NPA", $content[0]);  // use urlencoded value to avoid linebreaks
      $step1SessionControllers[$s1SCPtr]['controller']->storeIntoHistory($jType, $jNo, $npa, "NPA");
      if ($jType == 0) {
        $step1SessionControllers[$s1SCPtr]['controller']->s1model->evenJudges[$jNo]["npR"]=$npa;
        ++$step1SessionControllers[$s1SCPtr]['controller']->s1model->evenJudges[$jNo]["clientReplies"];
        if ($step1SessionControllers[$s1SCPtr]['controller']->s1model->evenJudges[$jNo]["clientReplies"]===2) {
          $step1SessionControllers[$s1SCPtr]['controller']->s1model->evenJudges[$jNo]["clientReplies"]=0; 
          $step1SessionControllers[$s1SCPtr]['controller']->sendRepliestoJudge(
              $jType, $jNo,
              $step1SessionControllers[$s1SCPtr]['controller']->s1model->evenJudges[$jNo]["jclientId"],
              'JAs',
              $step1SessionControllers[$s1SCPtr]['controller']->s1model->evenJudges[$jNo]["npR"],
              $step1SessionControllers[$s1SCPtr]['controller']->s1model->evenJudges[$jNo]["pR"]
          );
        }
      }
      else {
        $step1SessionControllers[$s1SCPtr]['controller']->s1model->oddJudges[$jNo]["npR"]=$npa;
        ++$step1SessionControllers[$s1SCPtr]['controller']->s1model->oddJudges[$jNo]["clientReplies"];
        if ($step1SessionControllers[$s1SCPtr]['controller']->s1model->oddJudges[$jNo]["clientReplies"]===2) {
          $step1SessionControllers[$s1SCPtr]['controller']->s1model->oddJudges[$jNo]["clientReplies"]=0;   
          $step1SessionControllers[$s1SCPtr]['controller']->sendRepliestoJudge($jType,$jNo,$step1SessionControllers[$s1SCPtr]['controller']->s1model->oddJudges[$jNo]["jclientId"],'JAs',$step1SessionControllers[$s1SCPtr]['controller']->s1model->oddJudges[$jNo]["npR"],$step1SessionControllers[$s1SCPtr]['controller']->s1model->oddJudges[$jNo]["pR"]);
        }    
      }
      $step1SessionControllers[$s1SCPtr]['controller']->updateNPHistory($jType, $jNo);
      $step1SessionControllers[$s1SCPtr]['controller']->setNPStatus($jType, $jNo, "waiting");
      $ownJNo = $step1SessionControllers[$s1SCPtr]['controller']->getJNoForJ($clientId);
      $ownJType = $step1SessionControllers[$s1SCPtr]['controller']->getJTypeForJ($clientId);
      $step1SessionControllers[$s1SCPtr]['controller']->setOwnNPStatus($ownJType, $ownJNo, "waiting");
      $step1SessionControllers[$s1SCPtr]['controller']->updateStatusPage(0);
      break;
    }
    case "PA": {
      $s1PPtr = getStep1ParticipantIndex($clientId);
      $s1SCPtr = $step1ParticipantClients[$s1PPtr]['controllerPtr'];
      $jType=$step1SessionControllers[$s1SCPtr]['controller']->getJTypeForP($clientId);
      $jNo=$step1SessionControllers[$s1SCPtr]['controller']->getJNoForP($clientId);
      // get jPlayerNo for who is judge to this person 
      $jPlayerNo = $step1SessionControllers[$s1SCPtr]['controller']->getPlayerNo($jType, $jNo);
      // get pPlayerNo for who this person is
      $pjType = $step1SessionControllers[$s1SCPtr]['controller']->getJTypeFromClientId($clientId);
      $pjNo = $step1SessionControllers[$s1SCPtr]['controller']->getJNoFromClientId($clientId);
      $pPlayerNo = $step1SessionControllers[$s1SCPtr]['controller']->getPlayerNo($pjType, $pjNo);      
      $pa = urldecode($content[0]);
      $step1SessionControllers[$s1SCPtr]['controller']->logPlayerMessage($jPlayerNo, $pPlayerNo, "PA", $content[0]);  // use urlencoded value to avoid linebreaks
      $step1SessionControllers[$s1SCPtr]['controller']->storeIntoHistory($jType, $jNo, $pa, "PA");
      if ($jType==0) {
        $step1SessionControllers[$s1SCPtr]['controller']->s1model->evenJudges[$jNo]["pR"]=$pa;
        ++$step1SessionControllers[$s1SCPtr]['controller']->s1model->evenJudges[$jNo]["clientReplies"];
        if ($step1SessionControllers[$s1SCPtr]['controller']->s1model->evenJudges[$jNo]["clientReplies"]===2) {
          $step1SessionControllers[$s1SCPtr]['controller']->s1model->evenJudges[$jNo]["clientReplies"]=0;   
          $step1SessionControllers[$s1SCPtr]['controller']->sendRepliestoJudge($jType,$jNo,$step1SessionControllers[$s1SCPtr]['controller']->s1model->evenJudges[$jNo]["jclientId"],'JAs',$step1SessionControllers[$s1SCPtr]['controller']->s1model->evenJudges[$jNo]["npR"],$step1SessionControllers[$s1SCPtr]['controller']->s1model->evenJudges[$jNo]["pR"]);
        }
      }
      else {
        $step1SessionControllers[$s1SCPtr]['controller']->s1model->oddJudges[$jNo]["pR"]=$pa;
        ++$step1SessionControllers[$s1SCPtr]['controller']->s1model->oddJudges[$jNo]["clientReplies"];
        if ($step1SessionControllers[$s1SCPtr]['controller']->s1model->oddJudges[$jNo]["clientReplies"]===2) {
          $step1SessionControllers[$s1SCPtr]['controller']->s1model->oddJudges[$jNo]["clientReplies"]=0;   
          $step1SessionControllers[$s1SCPtr]['controller']->sendRepliestoJudge($jType,$jNo,$step1SessionControllers[$s1SCPtr]['controller']->s1model->oddJudges[$jNo]["jclientId"],'JAs',$step1SessionControllers[$s1SCPtr]['controller']->s1model->oddJudges[$jNo]["npR"],$step1SessionControllers[$s1SCPtr]['controller']->s1model->oddJudges[$jNo]["pR"]);
        }    
      }
      $step1SessionControllers[$s1SCPtr]['controller']->updatePHistory($jType, $jNo);
      $step1SessionControllers[$s1SCPtr]['controller']->setPStatus($jType,$jNo,"waiting");
      $ownJNo = $step1SessionControllers[$s1SCPtr]['controller']->getJNoForJ($clientId);
      $ownJType = $step1SessionControllers[$s1SCPtr]['controller']->getJTypeForJ($clientId);
      $step1SessionControllers[$s1SCPtr]['controller']->setOwnPStatus($ownJType, $ownJNo, "waiting");
      $step1SessionControllers[$s1SCPtr]['controller']->updateStatusPage(0);
      break;
    }

    // expt-client join messages
    case "loginJoin": {
      $uid = $content[0];
      $exptId = $content[1];
      $jType = $content[2];
      $jNo = $content[3];
      $dayNo = $content[4];
      $sessionNo = $content[5];
      $msg = "<loginJoin><:>".$clientId."<:>".$uid."<:>".$exptId."<:>".$dayNo."<:>".$sessionNo."<:>".$jType."<:>".$jNo."<:>";
      $Server->log("msg", $msg);             
      $email = $dbHelper->getEmailFromUID($uid);
      $ds1pPtr = getDisconnectedStep1ParticipantIndex($uid);
      if ($ds1pPtr > -1) {
        $s1SCPtr = $disconnectedStep1ParticipantClients[$ds1pPtr]['controllerPtr'];
        processReconnection($clientId, $uid, $exptId, $jType, $jNo, $dayNo, $sessionNo, $email, $s1SCPtr, $ds1pPtr);
      }
      else {
        $dupLoginPtr = getDuplicateLoginIndex($uid, $exptId, $dayNo, $sessionNo);  // only using uid and exptId currently
        if ($dupLoginPtr > -1) {
          // instead of showing duplicate login message, force a disconnection
          // and reconnect with new clientId
          $s1SCPtr = $step1ParticipantClients[$dupLoginPtr]['controllerPtr'];
          $oldClientId = closeOldConnection($dupLoginPtr);
          cleanUp($oldClientId);
          $ds1pPtr = getDisconnectedStep1ParticipantIndex($uid);
          processDuplicateReconnection($clientId, $uid, $exptId, $jType, $jNo, $dayNo, $sessionNo, $email, $s1SCPtr, $ds1pPtr);
          $msg = "<duplicateloginJoin><:>".$clientId."<:>".$uid."<:>".$exptId."<:>".$dayNo."<:>".$sessionNo."<:>".$jType."<:>".$jNo."<:>";
          $Server->log("msg", $msg);             
        }
        else {
          //$Server->log("normal step1 login", $clientId);
          // continue normal login
          $pptStatus = processStep1Participant($clientId, $uid, $exptId, $dayNo, $sessionNo, $jType, $jNo, $email);  
          if ($pptStatus == "activeStep1") {
            // no houston
          }
          else {
            // must be putative..
            $ackxml=sprintf("<message><messageType>waitingStep1</messageType><content>%s</content></message>","unassigned as yet");
            //$Server->log("waiting step 1 ppt (logged-in) signal to:"," $clientId");
            $Server->wsSend($clientId, $ackxml);                                
          }      
        }
      }
      // send confirmation signal to client
       break;
    }
    case "initConfirm" : {
      $tabTitle = $content[0];
      confirmLogin($tabTitle);
      break;
    }

    // control and join messages
    case "toggleNextButton" : {
      $s1SCPtr = getS1SCPtrFromClientId($clientId);
      $step1SessionControllers[$s1SCPtr]['controller']->setNextButtonSwitch();
      break;
    } 
    case "dumpStep1" : {
      //echo 'dumping';
      $s1Ptr = getStep1ControlIndex($clientId);
      date_default_timezone_set('GMT');
      $date = date('Ymd-h-i-s', time());
      //$dumpFile;
      $dumpFile = fopen("logs/snapshot".$date.".txt",'wt');
      fwrite($dumpFile, "Even judges \n");
      $dump = print_r($step1SessionControllers[$s1Ptr]['controller']->s1model->evenJudges, true);  
      fwrite($dumpFile, $dump);
      fwrite($dumpFile, "\n");
      fwrite($dumpFile, "Odd judges \n");
      $dump = print_r($step1SessionControllers[$s1Ptr]['controller']->s1model->oddJudges, true);  
      fwrite($dumpFile, $dump);
      fwrite($dumpFile, "\n");
      fclose($dumpFile);
      break;
    }
    case "initStep1" : {
      $exptId=$content[0];
      $dayNo=$content[1];
      $sessionNo=$content[2];
      $allocationGeneration = $content[3];
      $iActualCnt=$content[4];
      $useMacro = $content[5];
      initialiseStep1Controller($clientId, $exptId, $dayNo, $sessionNo, $iActualCnt, $useMacro, $allocationGeneration);            
     break;
    }
    case "startStep1": {
      $s1SCPtr = getS1SCPtrFromClientId($clientId);
      $step1SessionControllers[$s1SCPtr]['controller']->storeAllocations();
      $step1SessionControllers[$s1SCPtr]['controller']->sendContent();  // sends to all connected client
      $step1SessionControllers[$s1SCPtr]['controller']->signalStart($clientId);
      break;
    }
    case "closeSession": {
      $uid = $content[0];
      $permissions = $content[1];
      $spPtr = getS1FromClientId($clientId);
      //$step1SessionControllers[$spPtr]->closeStep1Session();  // saves partial data and signals to clients
      if ($spPtr > -1) {
        echo "killing session" . $spPtr;
        // could send close message to all client browsers here if wanted
        $tempS1C = array();
        for ($i=0; $i<count($step1SessionControllers); $i++) {
          if ($i != $spPtr) {
            array_push($tempS1C, $step1SessionControllers[$i]);
          }
        }
        unset($step1SessionControllers);
        $step1SessionControllers = array_values($tempS1C);
        // get list of step1-ready experiments
        $eListHtml = $ecHelper->getStep1ExperimentListHtml($uid, $permissions, $step1SessionControllers);
        $xml=sprintf("<message><messageType>resetStep1Control</messageType><step1><![CDATA[%s]]></step1></message>", $eListHtml);
        $Server->wsSend($clientId, $xml);
      }
      break;
    }
    case "discardSession": {
      $uid = $content[0];
      $permissions = $content[1];
      $email = $content[4];
      $spPtr = getS1FromClientId($clientId);
      unset ($step1SessionControllers[$spPtr]);
      $step1SessionControllers = array_values($step1SessionControllers);    // re-index!
     // get list of step1-ready experiments
      $eListHtml = $ecHelper->getStep1ExperimentListHtml($uid, $permissions, $step1SessionControllers);
      $xml=sprintf("<message><messageType>step1Control</messageType><step1><![CDATA[%s]]></step1></message>", $eListHtml);
      $Server->wsSend($clientId, $xml);        
      break;
    }
    case "controllerInit": {
      $uid=$content[0];
      $fName=$content[1];
      $sName=$content[2];
      $permissions=$content[3];
      $email = $content[4];
      addToStep1GlobalClientPool($clientId, $uid, $fName, $sName, $permissions, $email);
      // check whether a previous erroneous disconnection as adminclient can be recovered
      $ds1CPtr = getDisconnectedStep1ControllerIndex($uid);
      //echo "disconnected Ptr = $ds1CPtr";
      if ($ds1CPtr > -1) {
        switchDisconnectedStep1CtoActive($clientId, $ds1CPtr);
      }
      else {
        // get list of step1-ready experiments
        $eListHtml = $ecHelper->getStep1ExperimentListHtml($uid, $permissions, $step1SessionControllers);
        $xml=sprintf("<message><messageType>step1Control</messageType><step1><![CDATA[%s]]></step1></message>", $eListHtml);
        $Server->wsSend($clientId, $xml);        
      }
      $Server->addAdminClient($clientId);
      break;
    }

    // remote monitor messages
    case "monitorInit": {
      // get list of active Step1 sessions eligible for monitoring
      $uid=$content[0];
      $fName=$content[1];
      $sName=$content[2];
      $permissions=$content[3];
      $email = $content[4];
      addToStep1GlobalClientPool($clientId, $uid, $fName, $sName, $permissions, $email);
      getActiveStep1Sessions($clientId);
      $Server->addAdminClient($clientId);
      break;
    }
    case "monitorStep1Session": {
      $s1sc = $content[0];
      $xml = sprintf("<message><messageType>SwitchToMonitor</messageType></message>");
      $Server->wsSend($clientId, $xml);
      $step1SessionControllers[$s1sc]['controller']->addMonitor($clientId);
      addToStep1MonitorPool($clientId);
      //$Server->log("remote monitor step1 attached:"," $clientId");
      break;
    }
  }    
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" sockets connection and messaging">

function wsOnMessage($clientId, $message, $messageLength, $binary) {
  global $Server;
  //echo $message;
  //logRawMessage($message);
  //$ip = long2ip($Server->wsClients[$clientId][6]);
  $messageType='';
  $content = array();
  $xml = new DOMDocument();
  $xml->loadXML($message);
  $messageTypes = $xml->getElementsByTagName('messageType');    // should only be 1
  foreach ($messageTypes as $mt) {
    $messageType=$mt->nodeValue;
  }
  $contentNodes=$xml->getElementsByTagName('content');
  $contentCnt=0;
  foreach ($contentNodes as $cn) {
    $content[$contentCnt++]=$cn->nodeValue;
  }
  processMessage($clientId, $messageType, $content);
}

function wsOnOpen($clientId) {
    global $Server;
    $ip = long2ip($Server->wsClients[$clientId][6]);
    echo "connection","$ip ($clientId) has connected.".'\n';
    //$Server->log( "connection","$ip ($clientId) has connected." ); 
    // send listener clientId back to client in case it needs to spawn child windows
    $xml=sprintf("<message><messageType>cid</messageType><content>%s</content></message>",$clientId);
    //$Server->log("cidMessage", $xml);
    $Server->wsSend($clientId, $xml);
}

function wsOnClose($clientId, $status) {
  global $Server;
  $ip = long2ip( $Server->wsClients[$clientId][6] );
  $Server->log( "disconnection","$ip ($clientId)" );
  echo  "disconnection","$ip ($clientId)"."\n";
  cleanUp($clientId);    
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" script startup">

if ($connectionOK) {
  // i.e., has db connection
  $logName = NULL;
  if (isset($argv[1])) { 
    $par = substr($argv[1], 0, 2);
    if ($par == "f:") { 
      $arr = explode(":", $argv[1]);
      $logName = $arr[1];
    }
    if ($par == "d:") {
      $arr = explode(":", $argv[1]);
      $domainName = $arr[1];
    }
  }
  if (isset($argv[2])) { 
    $par = substr($argv[2], 0, 2);
    if ($par == "f:") { 
      $arr = explode(":", $argv[2]);
      $logName = $arr[1];
    }
    if ($par == "d:") {
      $arr = explode(":", $argv[2]);
      $domainName = $arr[1];
    }
  }
  $Server = new PHPWebSocket();
  $dbHelper = new DBHelper($igrtSqli);
  $ecHelper = new experimentConfigurator(28, -1); // use mh's uid 28 to intialise when used by the step1 controller
  $step1HtmlBuilder = new htmlBuilder();
  if ($cliDomain == "ig2.com") {
    $domainName = '192.168.1.134';
  } else {
    $domainName = $cliDomain;
  }
  
  if ($logName != NULL) {
    echo "Running through $domainName and logging to". $logName.".txt";
  }
  else {
    echo "Running through $domainName and logging to timestamped file";
  }
  // create and start the server
  $Server->bind('message', 'wsOnMessage');
  $Server->bind('open', 'wsOnOpen');
  $Server->bind('close', 'wsOnClose');
  $Server->wsStartServer($domainName, 8080, $logName);
}

// </editor-fold>



