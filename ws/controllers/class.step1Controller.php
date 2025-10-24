<?php
include_once($root_path.'/helpers/html/class.htmlBuilder.php');
include_once($root_path.'/ws/models/class.step1Model.php');
include_once($root_path.'/ws/models/class.step1AllocationModel.php');
include_once($root_path.'/helpers/models/class.experimentModel.php');
//include_once($root_path.'/ws/models/class.contentModel.php');

/**
 * controller to surface data to socket-controller  
 * and to pass operations to model
 * only constructed when a session is initialised by an admin
 *
 * @author MartinHall
 */
class Step1Controller {
  public $valid;
  public $s1model;
  public $evenJudgesConnected;
  public $oddJudgesConnected;
  public $notStarted;
  public $configClientIdList = array();    // keep note of which config clients to send back (could be multiples). espcically for reconnection
  public $connectedToAdminClient;
  public $connectedAdminClientId;
      
  public $dummy;
  
  private $logger;

  public $exptId;
  public $dayNo;
  public $sessionNo;
  private $allocationGeneration;  // this indicates which of 2 fixed-allocation paradigms used - for ultimate seating plan or repated groups
  private $Server;
  private $htmlBuilder;
  private $eModel;
  //private $ecController;
  public $showNoMoreQuestions = 0;   // when toggled from the admin page, judge ratings show noMoreB rather than ask another question
	
  // <editor-fold defaultstate="collapsed" desc=" player number mappings and turn counts">

  function getPlayerNo($jType, $jNo) {
    if ($jType == 0) {
      return ($jNo + 1) * 2;
    }
    else {
      return ($jNo *2 ) + 1;
    }
  }
  
  function getJudgeDetails($playerNo) {
    if ( $playerNo % 2 == 0) {
      return array('jType'=>0, 'jNo'=> round(($playerNo - 1) / 2, 0, PHP_ROUND_HALF_DOWN));
    }
    else {
      return array('jType'=>1, 'jNo'=> round($playerNo / 2, 0, PHP_ROUND_HALF_DOWN));      
    }
  }
  
  function getTurnCount($judgeNo) {
    $jDetails = $this->getJudgeDetails($judgeNo);
//    $debug = print_r($jDetails, true);
//    echo $debug;
    $jType = $jDetails['jType'];
    $jNo = $jDetails['jNo'];
    if ($jType == 0) {
      return $this->s1model->evenJudges[$jNo]["qNo"];  
    }
    else {
      return $this->s1model->oddJudges[$jNo]["qNo"];      
    }
  }
  
  //</editor-fold >

  // <editor-fold defaultstate="collapsed" desc=" logging and debugging">

  function logInfo($module, $msg) {
    global $igrtSqli;
    $sql = sprintf("INSERT INTO sysdiags_debug (chrono, module, msg) VALUES(NOW(), '%s', '%s')", $module, $igrtSqli->real_escape_string($msg));
    $igrtSqli->query($sql);
    return $sql;
  }

  function logPlayerMessage($jPlayerNo, $respPlayerNo, $msgType, $contents, $intention = '') {
    switch ($msgType) {
      case "JQ" : {
        $turnNo = $this->getTurnCount($jPlayerNo);
        $msg = "<JQ><:>".$jPlayerNo."<:>".$turnNo."<:>".$contents."<:>.$intention";
        break;
      }
      case "NPA" : {
        $turnNo = $this->getTurnCount($jPlayerNo);
        $msg = "<NPA><:>".$respPlayerNo."<:>".$jPlayerNo."<:>".$turnNo."<:>".$contents;
        break;
      }
      case "PA" : {
        $turnNo = $this->getTurnCount($jPlayerNo);
        $msg = "<PA><:>".$respPlayerNo."<:>".$jPlayerNo."<:>".$turnNo."<:>".$contents;
        break;
      }
      case "JR" : {
        $turnNo = $this->getTurnCount($jPlayerNo);
        $msg = "<JR><:>".$jPlayerNo."<:>".$turnNo."<:>".$contents[0]."<:>".$contents[1]."<:>".$contents[2]."<:>".$contents[3]."<:>".$contents[4]."<:>".$contents[5]."<:>".$contents[6];
        break;
      }
      case "JfinalR" : {
        $turnNo = $this->getTurnCount($jPlayerNo);
        $msg = "<JfinalR><:>".$jPlayerNo."<:>".$turnNo."<:>".$contents[0]."<:>".$contents[1]."<:>".$contents[2]."<:>".$contents[3];
        break;
      }
			case "debugFinalMemoryMap" : {
				$msg = "<debugFinalMemoryMap><:>".$contents;
				break;
			}

    }
    $this->Server->log("msg", $msg);             
  }
  
  function logJudgeState($jType, $jNo, $module) {
    global $igrtSqli;
    if ($jType == 0) {
      $debug = print_r($this->s1model->evenJudges[$jNo], true);     
    }
    else {
      $debug = print_r($this->s1model->oddJudges[$jNo], true);           
    }
    $this->logInfo($module, $debug);
  }

  function storeRecovery($jType, $jNo, $jQ, $npA, $pA) {
    global $igrtSqli;
    $uid=$this->getUIDFromJParams($jType, $jNo);
    $npuid=$this->getNPUIDFromJParams($jType, $jNo);
    $puid=$this->getPUIDFromJParams($jType, $jNo); 
    $e_jQ = $igrtSqli->real_escape_string($jQ);
    $e_npA = $igrtSqli->real_escape_string($npA);
    $e_pA = $igrtSqli->real_escape_string($pA);
    $jSql=sprintf("INSERT INTO sysdiags_STEP1Recovery (messageType,ownUid,jUid,npUid,pUid,chrono,jQ,npA,pA,jType,jNo) VALUES(\"QAturn\",\"%s\",\"%s\",\"%s\",\"%s\",NOW(),\"%s\",\"%s\",\"%s\",\"%s\",\"%s\")",
                    $uid,$uid,$npuid,$puid,$e_jQ,$e_npA,$e_pA,$jType,$jNo);
    $igrtSqli->query($jSql);
  }

  //</editor-fold >

  // <editor-fold defaultstate="collapsed" desc=" allocations, content and start section">

  function attachJEmails($roles) {
    global $igrtSqli;
    // TODO, optimise as JOIN
    for ($i=0; $i < count($roles->evenJudges); $i++) {
      $evenQry = sprintf("SELECT uid FROM igActiveStep1Users WHERE exptId='%s' AND day='%s' AND session='%s' AND jType=0 AND jNo='%s'",
                          $this->exptId, $this->dayNo, $this->sessionNo, $i);
      $evenResult = $igrtSqli->query($evenQry);
      $evenRow = $evenResult->fetch_object();
      $e_uid = $evenRow->uid;
      $evenQry = sprintf("SELECT email FROM igUsers WHERE id='%s'", $e_uid);
      $evenResult = $igrtSqli->query($evenQry);
      $evenRow = $evenResult->fetch_object();
      $e_email = $evenRow->email;
      $roles->evenJudges[$i]['email'] = $e_email;
      $oddQry = sprintf("SELECT uid FROM igActiveStep1Users WHERE exptId='%s' AND day='%s' AND session='%s' AND jType=1 AND jNo='%s'",
                          $this->exptId, $this->dayNo, $this->sessionNo, $i);
      $oddResult = $igrtSqli->query($oddQry);
      $oddRow = $oddResult->fetch_object();
      $o_uid = $oddRow->uid;
      $oddQry = sprintf("SELECT email FROM igUsers WHERE id='%s'", $o_uid);
      $oddResult = $igrtSqli->query($oddQry);
      $oddRow = $oddResult->fetch_object();
      $o_email = $oddRow->email;
      $roles->oddJudges[$i]['email'] = $o_email;
    }
    //echo print_r($roles->evenJudges, true);
  }
        
  function createAllocations($iCnt, $allocationGeneration) {
    $roles = new roleAllocations($iCnt, $allocationGeneration);
    if ($roles->generateFixed()) {
      // attach expected email to all interoggators so that pre-start admin screen shows expected emails
      $this->attachJEmails($roles);
      $this->s1model->evenJudges = $roles->evenJudges;  // arrays have been created and initialised in roleAllocations
      $this->s1model->oddJudges = $roles->oddJudges;
      return true;
    }
    else {
      return false;
    }
  }
    
  function getAllocationInfoXml($exptId) {
//    $Evencontent = $this->buildCurrentEvenConnections($this->s1model->jCnt);
//    $Oddcontent = $this->buildCurrentOddConnections($this->s1model->jCnt);
    $exptTitle = $this->getExptTitle($exptId);
    $xml =  sprintf("<message><messageType>allocSuccess</messageType><title>%s</title></message>", $exptTitle);            
//    $xml =  sprintf("<message><messageType>allocSuccess</messageType><title>%s</title><evenJudges><![CDATA[%s]]></evenJudges><oddJudges><![CDATA[%s]]></oddJudges></message>",$exptTitle,$Xcontent,$Ycontent);            
    return $xml;
  }
  
  function reSendContent($clientId) {
    $contentXml = $this->s1model->eModel->reSendContentXML($this->s1model->eModel->randomiseSideS1);
    $this->Server->wsSend($clientId, $contentXml);
    $this->Server->log("resend content ",$clientId);    
  }
  
  function storeAllocations() {
    global $igrtSqli;
    $delSql = sprintf("DELETE FROM wt_Step1Allocations WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s'", $this->exptId, $this->dayNo, $this->sessionNo);
    $igrtSqli->query($delSql);
    for ($i=0; $i<$this->s1model->jCnt; $i++) {
      $evenSql = sprintf("INSERT INTO wt_Step1Allocations (exptId, dayNo, sessionNo, oddJ, jPtr, email) VALUES('%s', '%s', '%s', '0', '%s', '%s')", $this->exptId, $this->dayNo, $this->sessionNo, $i, $this->s1model->evenJudges[$i]["email"] );
      $igrtSqli->query($evenSql);
      $oddSql = sprintf("INSERT INTO wt_Step1Allocations (exptId, dayNo, sessionNo, oddJ, jPtr, email) VALUES('%s', '%s', '%s', '1', '%s', '%s')", $this->exptId, $this->dayNo, $this->sessionNo, $i, $this->s1model->oddJudges[$i]["email"] );
      $igrtSqli->query($oddSql);      
    }
  }
    
  function sendContent() {
    $contentXml = $this->s1model->eModel->sendContentXML($this->s1model->eModel->randomiseSideS1);
    for ($i=0;$i<$this->s1model->jCnt;$i++) {
      if ($this->s1model->evenJudges[$i]["jclientId"]!=-1) {
        $this->Server->wsSend($this->s1model->evenJudges[$i]["jclientId"], $contentXml);
        //$this->Server->log("ContentDef",sprintf(" to: %s",$this->s1model->evenJudges[$i]["jclientId"]));
      }
      if ($this->s1model->oddJudges[$i]["jclientId"]!=-1) {
        $this->Server->wsSend($this->s1model->oddJudges[$i]["jclientId"], $contentXml);
        //$this->Server->log("ContentDef",sprintf(" to: %s",$this->s1model->oddJudges[$i]["jclientId"]));
      }
    }
    
  }
    
  function signalStart($clientId) {
    $this->notStarted=false;
    //send start signal to all connected expt clients
    for ($i=0;$i<$this->s1model->jCnt;$i++) {
      $startXML='<message><messageType>startExpt</messageType><useS1Intention>'.$this->eModel->useS1Intention.'</useS1Intention></message>';
      $this->s1model->evenJudges[$i]["jAction"]="active";
      $this->s1model->evenJudges[$i]["npAction"]="waiting";
      $this->s1model->evenJudges[$i]["pAction"]="waiting";
      $this->s1model->evenJudges[$i]["ownnpAction"]="waiting";
      $this->s1model->evenJudges[$i]["ownpAction"]="waiting";
      $this->s1model->evenJudges[$i]["timestamp"] = microtime(true);
      $this->s1model->oddJudges[$i]["jAction"]="active";
      $this->s1model->oddJudges[$i]["npAction"]="waiting";
      $this->s1model->oddJudges[$i]["pAction"]="waiting";
      $this->s1model->oddJudges[$i]["ownnpAction"]="waiting";
      $this->s1model->oddJudges[$i]["ownpAction"]="waiting";
      $this->s1model->oddJudges[$i]["timestamp"] = microtime(true);
      if ($this->s1model->evenJudges[$i]["jclientId"]!=-1) {
        $this->Server->wsSend($this->s1model->evenJudges[$i]["jclientId"], $startXML);
        $this->Server->log("StartSignal",sprintf("StartSignal to: %s",$this->s1model->evenJudges[$i]["jclientId"]));
      }
      if ($this->s1model->oddJudges[$i]["jclientId"]!=-1) {
        $this->Server->wsSend($this->s1model->oddJudges[$i]["jclientId"], $startXML);
        $this->Server->log("StartSignal",sprintf("StartSignal to: %s",$this->s1model->oddJudges[$i]["jclientId"]));
      }
    }
    //send controlStart back to admin client
    $controlXML='<message><messageType>controlStart</messageType><content></content></message>';
    $this->Server->wsSend($clientId, $controlXML);
    //send status data to admin client
    $this->updateStatusPage(0);
   }
  
  function confirmLogin($jType, $jNo) {
    if ($jType == 0) {
      $this->s1model->evenJudges[$jNo]['jclientConfirmed'] = true;     
    }
    else {
      $this->s1model->oddJudges[$jNo]['jclientConfirmed'] = true;           
    }
  }
          
  function connectExperimentClient($clientId, $jNo, $jType, $uid, $exptId, $dayNo, $sessionNo, $email, $tabTitle, $useMacro) {
    global $Server;
    $Server->removeClientFromSuspendedList($clientId);  // special case for new connection using clientId prior to same judge reconnecting.
    if ($jType == 0) {
      //$this->Server->log("evenjudge"," c:$clientId");     
      $this->s1model->evenJudges[$jNo]["jclientId"]=$clientId;      //  -1 means unconnected yet, clientId gives actual channel
      $this->s1model->evenJudges[$jNo]["uid"]=$uid;
      $this->s1model->evenJudges[$jNo]["email"]=$email;
      $this->s1model->evenJudges[$jNo]['jNo']=$jNo;
      // find which other X judge this X will be NP to, and give it this clientId
      $i=0;
      $found=false;
      while ($i<$this->s1model->jCnt && !$found) {
        if ($this->s1model->evenJudges[$i]["otherNPs"][0]==$jNo) {
          $found=true;
          $this->s1model->evenJudges[$i]["npclientId"]=$clientId;  
          $this->s1model->evenJudges[$i]["npUid"]=$uid;  
        }
        ++$i;
      }
      // now find which Y judge this X will be P to, and give it this clientId
      $i=0;
      $found=false;
      while ($i<$this->s1model->jCnt && !$found) {
        if ($this->s1model->oddJudges[$i]["otherPs"][0]==$jNo) {
          $found=true;
          $this->s1model->oddJudges[$i]["pclientId"]=$clientId;
          $this->s1model->oddJudges[$i]["pUid"]=$uid;
        }
        ++$i;
      }
      ++$this->evenJudgesConnected;
    }
    else {
      //$this->Server->log("oddjudge"," c:$clientId");
      $this->s1model->oddJudges[$jNo]["jclientId"]=$clientId;      // clientId or -1 means unconnected yet
      $this->s1model->oddJudges[$jNo]["uid"]=$uid;
      $this->s1model->oddJudges[$jNo]["email"]=$email;
      $this->s1model->oddJudges[$jNo]['jNo']=$jNo;
      // find which other Y judge this Y will be NP to, and give it this clientId
      $i=0;
      $found=false;
      while ($i<$this->s1model->jCnt && !$found) {
        if ($this->s1model->oddJudges[$i]["otherNPs"][0]==$jNo) {
          $found=true;
          $this->s1model->oddJudges[$i]["npclientId"]=$clientId;
          $this->s1model->oddJudges[$i]["npUid"]=$uid;
        }
        ++$i;
      }
      // now find which X judge this Y will be P to, and give it this clientId
      $i=0;
      $found=false;
      while ($i<$this->s1model->jCnt && !$found) {
        if ($this->s1model->evenJudges[$i]["otherPs"][0]==$jNo) {
          $found=true;
          $this->s1model->evenJudges[$i]["pclientId"]=$clientId;
          $this->s1model->evenJudges[$i]["pUid"]=$uid;
        }
        ++$i;
      }
      ++$this->oddJudgesConnected; 
    }
    $this->confirmLogin($jType, $jNo);
    // did send $jType.($connectionNo+1)) , but now send email
    $Initxml=sprintf("<message><messageType>Initialise</messageType><content>%s</content><useMacro>%s</useMacro></message>", $tabTitle, $useMacro );
    //echo $Initxml;
    //$this->Server->log("initialisation signal to:"," $clientId");
    //echo "sending init to client $Initxml";
    $this->Server->wsSend($clientId, $Initxml);
    $this->checkCanStart();
  }
  
  function checkCanStart() {
    // enables start button on admin screen if all ppts connected
    if ( ($this->evenJudgesConnected == $this->s1model->jCnt) && ($this->oddJudgesConnected == $this->s1model->jCnt) ) {
      // all connected - enable startB
      $enableBXml = "<message><messageType>enableStartB</messageType><content></content></message>";
      $this->Server->wsSend($this->connectedAdminClientId, $enableBXml);
    }
  }
          
  function updateIActualCnt($exptId, $dayNo, $sessionNo, $iActualCnt) {
    global $igrtSqli;
    $sqlCmd_jn=sprintf("UPDATE edSessions SET iActualCnt='%s' WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s'", $iActualCnt, $exptId, $dayNo, $sessionNo);
    $igrtSqli->query($sqlCmd_jn);            
  }

  //</editor-fold >

  // <editor-fold defaultstate="collapsed" desc=" Recovery and re-login">
	
    
  function getqNo($jType, $jNo) {
    if ($jType==0) {
      return $this->s1model->evenJudges[$jNo]['qNo'];
    }
    else {
      return $this->s1model->oddJudges[$jNo]['qNo'];    
    }
  }

	function getJaction($jType, $jNo) {
    if ($jType==0) {
      return $this->s1model->evenJudges[$jNo]['jAction'];
    }
    else {
      return $this->s1model->oddJudges[$jNo]['jAction'];    
    }
  }
    
  function getNPaction($jType, $jNo) {
    //dereference from the other judge connected
    if ($jType == 0) {
      $xJ=$this->s1model->evenJudges[$jNo]['ownNPs'][0];
      return $this->s1model->evenJudges[$xJ]['npAction'];
    }
    else {
      $yJ=$this->s1model->oddJudges[$jNo]['ownNPs'][0];
      return $this->s1model->oddJudges[$yJ]['npAction'];    
    }        
  }
    
  function getPaction($jType, $jNo) {
    if ($jType == 0) {
      $yJ=$this->s1model->evenJudges[$jNo]['ownPs'][0];
      return $this->s1model->oddJudges[$yJ]['pAction'];
    }
    else {
      $xJ=$this->s1model->oddJudges[$jNo]['ownPs'][0];
      return $this->s1model->evenJudges[$xJ]['pAction'];    
    }        
  }
    
  function getNPJQ($jType, $jNo) {
    // gets the Q from the J this NP is acting to 
    if ($jType==0) {
      $connectedJNo=$this->s1model->evenJudges[$jNo]['ownNPs'][0];
      return $this->s1model->evenJudges[$connectedJNo]['jQ'];
    }
    else {
      $connectedJNo=$this->s1model->oddJudges[$jNo]['ownNPs'][0];
      return $this->s1model->oddJudges[$connectedJNo]['jQ'];     
    }        
  }

  function getNPA($jType, $jNo) {
    // gets the most recent NP answer to  the J this NP is acting to 
    if ($jType==0) {
      $connectedJNo=$this->s1model->evenJudges[$jNo]['ownNPs'][0];
      return $this->s1model->evenJudges[$connectedJNo]['npR'];
    }
    else {
      $connectedJNo=$this->s1model->oddJudges[$jNo]['ownNPs'][0];
      return $this->s1model->oddJudges[$connectedJNo]['npR'];     
    }        
  }

  function getPJQ($jType, $jNo) {
    // gets the Q from the J this P is acting to 
    if ($jType==0) {
      $connectedJNo=$this->s1model->evenJudges[$jNo]['ownPs'][0];
      return $this->s1model->oddJudges[$connectedJNo]['jQ'];
    }
    else {
      $connectedJNo=$this->s1model->oddJudges[$jNo]['ownPs'][0];
      return $this->s1model->evenJudges[$connectedJNo]['jQ'];     
    }        
  }

  function getPA($jType, $jNo) {
    // gets the most recent P answer to  the J this NP is acting to 
    if ($jType == 0) {
      $connectedJNo=$this->s1model->evenJudges[$jNo]['ownPs'][0];
      return $this->s1model->oddJudges[$connectedJNo]['pR'];
    }
    else {
      $connectedJNo=$this->s1model->oddJudges[$jNo]['ownPs'][0];
      return $this->s1model->evenJudges[$connectedJNo]['pR'];     
    }        
  }
    
  function getRecentJQ($jType, $jNo) {
    if ($jType == 0) {
      return $this->s1model->evenJudges[$jNo]['jQ'];
    }
    else {
      return $this->s1model->oddJudges[$jNo]['jQ'];            
    }
  }
    
  function getResponses($jType, $jNo, &$r1, &$r2) {
    // display counter-balancing
    if ($jNo%2 == 0) {
      if ($jType == 0) {
        $r1=$this->s1model->evenJudges[$jNo]['npR'];
        $r2=$this->s1model->evenJudges[$jNo]['pR'];                
      }
      else {
        $r1=$this->s1model->oddJudges[$jNo]['npR'];
        $r2=$this->s1model->oddJudges[$jNo]['pR'];                                
      }
    }
    else {
      if ($jType == 0) {
        $r1=$this->s1model->evenJudges[$jNo]['pR'];
        $r2=$this->s1model->evenJudges[$jNo]['npR'];                
      }
      else {
        $r1=$this->s1model->oddJudges[$jNo]['pR'];
        $r2=$this->s1model->oddJudges[$jNo]['npR'];                                
      }          
    }
  }

  function reconnectClientId($jType, $jNo, $clientId) {
    if ($jType == 0) {
      $this->s1model->evenJudges[$jNo]["disconnected"]=false;
      $this->s1model->evenJudges[$jNo]['jclientId']=$clientId;
      $xNPj=$this->s1model->evenJudges[$jNo]['ownNPs'][0];
      $this->s1model->evenJudges[$xNPj]['npclientId']=$clientId;
      $this->s1model->evenJudges[$xNPj]['npDisconnected']=false;
      $yPj=$this->s1model->evenJudges[$jNo]['ownPs'][0];
      $this->s1model->oddJudges[$yPj]['pclientId']=$clientId;
      $this->s1model->oddJudges[$yPj]['pDisconnected']=false;
    }
    else {
      $this->s1model->oddJudges[$jNo]["disconnected"]=false;
      $this->s1model->oddJudges[$jNo]['jclientId']=$clientId;
      $yNPj=$this->s1model->oddJudges[$jNo]['ownNPs'][0];
      $this->s1model->oddJudges[$yNPj]['npclientId']=$clientId;
      $this->s1model->oddJudges[$yNPj]['npDisconnected']=false;
      $xPj=$this->s1model->oddJudges[$jNo]['ownPs'][0];
      $this->s1model->evenJudges[$xPj]['pclientId']=$clientId;    
      $this->s1model->evenJudges[$xPj]['pDisconnected']=false;    
    }
  }
      
  //</editor-fold >

  // <editor-fold defaultstate="collapsed" desc=" Game-play processors">
  
  function setNPStatus($jType, $jNo, $status) {
    if ($jType==0) {
      $this->s1model->evenJudges[$jNo]["npAction"]="$status";
    }
    else {
      $this->s1model->oddJudges[$jNo]["npAction"]="$status";                
    }
    $this->updateStatusPage(0);
  }
 
  function setOwnNPStatus($jType, $jNo, $status) {
    if ($jType==0) {
      $this->s1model->evenJudges[$jNo]["ownnpAction"]="$status";
    }
    else {
      $this->s1model->oddJudges[$jNo]["ownnpAction"]="$status";                
    }
    $this->updateStatusPage(0);
  }

  function setOwnPStatus($jType, $jNo, $status) {
    if ($jType == 0) {
      $this->s1model->evenJudges[$jNo]["ownpAction"]="$status";        
    }
    else {
      $this->s1model->oddJudges[$jNo]["ownpAction"]="$status";                
    }
    $this->updateStatusPage(0);
  }

  function setPStatus($jType, $jNo, $status) {
    if ($jType == 0) {
      $this->s1model->evenJudges[$jNo]["pAction"]="$status";        
    }
    else {
      $this->s1model->oddJudges[$jNo]["pAction"]="$status";                
    }
    $this->updateStatusPage(0);
  }

  function getFormattedTime($ts) {
    $elapsedSeconds = microtime(true) - $ts;
    $mins = round($elapsedSeconds / 60, 0, PHP_ROUND_HALF_DOWN);
    $remainingSeconds = round($elapsedSeconds - ( $mins * 60 ), 0, PHP_ROUND_HALF_DOWN);
    return $mins.":".$remainingSeconds;
  }

  function sendQ($jType, $jNo, $content, $intention) {
    // log player# type info for automated rebuild if necessary
     
    // $content is decoded so needs encoding for XML transmission
    $codedContent = rawurlencode($content);
    $NPxml=sprintf("<message><messageType>NPQ</messageType><content>%s</content></message>", $codedContent);
    $Pxml=sprintf("<message><messageType>PQ</messageType><content>%s</content></message>", $codedContent);
    if ($jType == 0) {
      // SET TIME OF INTERACTION (php function?)
      $this->s1model->evenJudges[$jNo]["time"] = $this->getFormattedTime($this->s1model->evenJudges[$jNo]["timestamp"]);            
      $this->s1model->evenJudges[$jNo]["timestamp"] = microtime(true);
      //set status of all members of the cohort
      $this->s1model->evenJudges[$jNo]["jAction"]="waiting";
      $this->s1model->evenJudges[$jNo]["npAction"]="active";
      $this->s1model->evenJudges[$jNo]["pAction"]="active";
      $np = $this->s1model->evenJudges[$jNo]["otherNPs"][0];
      $p = $this->s1model->evenJudges[$jNo]["otherPs"][0];      
      $this->s1model->evenJudges[$np]["ownnpAction"] = "active";
      $this->s1model->oddJudges[$p]["ownpAction"] = "active";
      $this->s1model->evenJudges[$jNo]["jQ"]=$content;
      $this->s1model->evenJudges[$jNo]["jIntention"] = $intention;
      //send message to appropriate NP & P
      if ($this->s1model->evenJudges[$jNo]["npclientId"] != -1) {
        $this->Server->wsSend($this->s1model->evenJudges[$jNo]["npclientId"], $NPxml);
        //$this->Server->log("sendQ",sprintf("from: $jNo to: c%s, xnp%s, %s",$this->s1model->evenJudges[$jNo]["npclientId"],$this->s1model->evenJudges[$jNo]["otherNPs"][0],$jNo,$NPxml));             
      }
      if ($this->s1model->evenJudges[$jNo]["pclientId"] != -1) {
        $this->Server->wsSend($this->s1model->evenJudges[$jNo]["pclientId"], $Pxml);                    
        //$this->Server->log("sendQ",sprintf("from: X$jNo to: c%s, yp%s, %s",$this->s1model->evenJudges[$jNo]["pclientId"],$this->s1model->evenJudges[$jNo]["otherPs"][0],$Pxml));          
      }
    }
    else {
      // SET TIME OF INTERACTION (php function?)
      $this->s1model->oddJudges[$jNo]["time"]=$this->getFormattedTime($this->s1model->oddJudges[$jNo]["timestamp"]);
      $this->s1model->oddJudges[$jNo]["timestamp"] = microtime(true);
      //set status of all members of the cohort
      $this->s1model->oddJudges[$jNo]["jAction"]="waiting";
      $this->s1model->oddJudges[$jNo]["npAction"]="active";
      $this->s1model->oddJudges[$jNo]["pAction"]="active";
      $np = $this->s1model->oddJudges[$jNo]["otherNPs"][0];
      $p = $this->s1model->oddJudges[$jNo]["otherPs"][0];      
      $this->s1model->oddJudges[$np]["ownnpAction"] = "active";
      $this->s1model->evenJudges[$p]["ownpAction"] = "active";
      $this->s1model->oddJudges[$jNo]["jQ"]=$content;
      $this->s1model->oddJudges[$jNo]["jIntention"] = $intention;
      //send message to appropriate NP & P
      if ($this->s1model->oddJudges[$jNo]["npclientId"] !=-1) {
       $this->Server->wsSend($this->s1model->oddJudges[$jNo]["npclientId"], $NPxml);
       //$this->Server->log("sendQ",sprintf("from: Y$jNo to: c%s, ynp%s, %s",$this->s1model->oddJudges[$jNo]["npclientId"],$this->s1model->oddJudges[$jNo]["otherNPs"][0],$jNo,$NPxml));            
      }
      if ($this->s1model->oddJudges[$jNo]["pclientId"] !=-1) {
        $this->Server->wsSend($this->s1model->oddJudges[$jNo]["pclientId"], $Pxml);                    
        //$this->Server->log("sendQ",sprintf("from: Y$jNo to: c%s, xp%s, %s",$this->s1model->oddJudges[$jNo]["pclientId"],$this->s1model->oddJudges[$jNo]["otherPs"][0],$Pxml));             
      }
   }
  }
    
  function sendFinalRatingToJudge($jclientId, $jType, $jNo) {
    if ($this->s1model->eModel->useFinalRating == 1) {
      if ($this->s1model->eModel->randomiseSideS1 == 1) {
        $npSide = mt_rand(0,1);
      }
      else {
        $npSide = $jNo % 2 == 0 ? 0 : 1;
      }
      $jfrHtml = $this->getJudgeFinalRatingHtml($jType, $jNo, $npSide);
      if ($jType == 0) {
        $this->s1model->evenJudges[$jNo]['jAction']="finalRating";
      }
      else {
        $this->s1model->oddJudges[$jNo]['jAction']="finalRating";            
      }
      $xml=sprintf("<message><messageType>fRating</messageType>"
          . "<content><![CDATA[%s]]></content>"
          . "<useFinalReason>%s</useFinalReason>"
          . "<useFinalLikert>%s</useFinalLikert>"
          . "<finalReasonMinValue>%s</finalReasonMinValue>"
          . "<npSide>%s</npSide></message>",
          $jfrHtml, 
          $this->s1model->eModel->useReasonFinalRating ? 1 : 0,
          $this->s1model->eModel->useFinalLikert ? 1 : 0,
          $this->s1model->eModel->reasonCharacterLimitValueF,
          $npSide);
      $this->Server->wsSend($jclientId, $xml);                            
    }
    else {
      // no final rating, so go to final feedback if any
      if ($this->s1model->eModel->s1giveFeedbackFinal == 1) {
        $winPercentage = $this->getWinPercentage($jType, $jNo);
        if ($winPercentage >= $this->s1model->eModel->s1PercentForWinFeedbackFinal) {
          $xml=sprintf("<message><messageType>finalFeedback</messageType>"
              . "<winLoseMessage><![CDATA[%s]]></winLoseMessage>",
              $this->s1model->eModel->s1WinFeedbackLabel);          
        }
        else {
          $xml=sprintf("<message><messageType>finalFeedback</messageType>"
              . "<winLoseMessage><![CDATA[%s]]></winLoseMessage>",
              $this->s1model->eModel->s1LoseFeedbackLabel);          
        }
          $this->Server->wsSend($jclientId, $xml);                            
      }
      $this->processFinalRating($jclientId, $jType, $jNo, [0,0,"dummy entry as no final rating when feedback used AND no final-rating chosen (never used yet!)",-1]);
    }
  }
  
  function getWinPercentage($jType, $jNo) {
    if ($jType == 0) {
      $qNo=$this->s1model->evenJudges[$jNo]["qNo"];
      $correctTotal = 0;
      for ($i=1; $i<=$qNo; $i++) {
        if ($this->s1model->evenJudges[$jNo]["ratings"][$i]["correct"] == 1) { ++$correctTotal; }
      }
      //echo $correctTotal.' - '.$qNo.' - '.(100*$correctTotal)/$qNo.PHP_EOL;
      return (100 * $correctTotal)/$qNo;
    }
    else {
      $qNo=$this->s1model->oddJudges[$jNo]["qNo"];
      $correctTotal = 0;
      for ($i=1; $i<=$qNo; $i++) {
        if ($this->s1model->oddJudges[$jNo]["ratings"][$i]["correct"] == 1) { ++$correctTotal; }
      }
      //echo print_r($this->s1model->oddJudges[$jNo], true).PHP_EOL;
      //echo $correctTotal.' - '.$qNo.' - '.(100*$correctTotal)/$qNo.PHP_EOL;
      return (100 * $correctTotal)/$qNo;
    }    
  }
  
  function storeIntoHistory($jType, $jNo, $ans, $aType) {
    if ($jType == "X") {
      $jQ = $this->s1model->evenJudges[$jNo]["jQ"];
      $jIntention = $this->s1model->evenJudges[$jNo]["jIntention"];
      $hCount = count($this->s1model->evenJudges[$jNo]["history"]);
      if ($hCount == 0) {
        $array_row = ($aType == "NPA") ? array('jQuestion'=>$jQ, 'npReply'=>$ans, 'pReply'=>'', 'jIntention'=>$jIntention) : array('jQuestion'=>$jQ, 'npReply'=>'', 'pReply'=>$ans, 'jIntention'=>$jIntention);
        array_push($this->s1model->evenJudges[$jNo]["history"], $array_row);
      }
      else {
        $hcIndex = $hCount - 1;
        if ($aType == "NPA") {
          if ($this->s1model->evenJudges[$jNo]["history"][$hcIndex]['npReply'] > '') {
            // needs next
            $array_row = array('jQuestion'=>$jQ, 'npReply'=>$ans, 'pReply'=>'', 'jIntention'=>$jIntention);
            array_push($this->s1model->evenJudges[$jNo]["history"], $array_row);
          }
          else {
            // update current
            $this->s1model->evenJudges[$jNo]["history"][$hcIndex]['npReply'] = $ans;
          }
        }
        else {
          if ($this->s1model->evenJudges[$jNo]["history"][$hcIndex]['pReply'] > '') {
            // needs next
            $array_row = array('jQuestion'=>$jQ, 'npReply'=>'', 'pReply'=>$ans, 'jIntention'=>$jIntention);
            array_push($this->s1model->evenJudges[$jNo]["history"], $array_row);
          }
          else {
            // update current
            $this->s1model->evenJudges[$jNo]["history"][$hcIndex]['pReply'] = $ans;
          }
        }
      }
      //echo "store : ".print_r($this->s1model->evenJudges[$jNo]["history"], true);
    }
    else {
      $hCount = count($this->s1model->oddJudges[$jNo]["history"]);
      $jQ = $this->s1model->oddJudges[$jNo]["jQ"];
      $jIntention = $this->s1model->oddJudges[$jNo]["jIntention"];
      if ($hCount == 0) {
        $array_row = ($aType == "NPA") ? array('jQuestion'=>$jQ, 'npReply'=>$ans, 'pReply'=>'', 'jIntention'=>$jIntention) : array('jQuestion'=>$jQ, 'npReply'=>'', 'pReply'=>$ans, 'jIntention'=>$jIntention);
        array_push($this->s1model->oddJudges[$jNo]["history"], $array_row);
      }
      else {
        $hcIndex = $hCount - 1;
        if ($aType == "NPA") {
          if ($this->s1model->oddJudges[$jNo]["history"][$hcIndex]['npReply'] > '') {
            // needs next
            $array_row = array('jQuestion'=>$jQ, 'npReply'=>$ans, 'pReply'=>'', 'jIntention'=>$jIntention);
            array_push($this->s1model->oddJudges[$jNo]["history"], $array_row);
          }
          else {
            // update current
            $this->s1model->oddJudges[$jNo]["history"][$hcIndex]['npReply'] = $ans;
          }
        }
        else {
          if ($this->s1model->oddJudges[$jNo]["history"][$hcIndex]['pReply'] > '') {
            // needs next
            $array_row = array('jQuestion'=>$jQ, 'npReply'=>'', 'pReply'=>$ans, 'jIntention'=>$jIntention);
            array_push($this->s1model->oddJudges[$jNo]["history"], $array_row);
          }
          else {
            // update current
            $this->s1model->oddJudges[$jNo]["history"][$hcIndex]['pReply'] = $ans;
          }
        }
      }
      //echo "store : ".print_r($this->s1model->oddJudges[$jNo]["history"], true);
    }
  }

  function sendRepliestoJudge($jType, $jNo, $jclientId, $mType, $np, $p) {
    // $np and $p are decoded content - need to recode for transmission in XML
    $jrHtml = $this->getJudgeRatingHtml($jType);
    $npclientId=-1;
    $pclientId=-1;
    $jQ = ($jType == 0) ? $this->s1model->evenJudges[$jNo]["jQ"] : $this->s1model->oddJudges[$jNo]["jQ"];
    $this->storeRecovery($jType, $jNo, $jQ, $np ,$p);
    if ($jType == 0) {
      $npclientId=$this->s1model->evenJudges[$jNo]["npclientId"];
      $pclientId=$this->s1model->evenJudges[$jNo]["pclientId"];
      ++$this->s1model->evenJudges[$jNo]["qNo"];
      $this->s1model->evenJudges[$jNo]["jAction"]="rating";
      $qNo = $this->s1model->evenJudges[$jNo]["qNo"];
    }
    else {
      $npclientId=$this->s1model->oddJudges[$jNo]["npclientId"];
      $pclientId=$this->s1model->oddJudges[$jNo]["pclientId"];
      ++$this->s1model->oddJudges[$jNo]["qNo"];
      $this->s1model->oddJudges[$jNo]["jAction"]="rating";
      $qNo = $this->s1model->oddJudges[$jNo]["qNo"];
    }  
    // send Judge rating html (based on db entries) to judge client
    // set values for optional rating parameters required for client-side use and validation
    $uLikert = ($this->s1model->eModel->useLikert) ? 1 : 0;
    $uReasons = ($this->s1model->eModel->useReasons) ? 1 : 0;
    $codedNPA = rawurlencode($np);
    $codedPA = rawurlencode($p);
    // do counter-balancing of P and NP here for current turn (build J history includes counter-balancing)
    // if experiment is set to randomiseSideS1, then randomise and don't send history as unnecessary
    if ($this->s1model->eModel->randomiseSideS1 == 1) {
      $side = mt_rand(0,1);
    }
    else {
      $side = $jNo%2 == 0 ? 0 : 1;
    }
    if ($side == 0) {
      $lContent = $codedNPA;
      $rContent = $codedPA;      
    }
    else {
      $rContent = $codedNPA;
      $lContent = $codedPA;      
    }
    $xml = sprintf("<message><messageType>%s</messageType>"
      . "<lContent>%s</lContent>"
        . "<rContent>%s</rContent>"
      . "<jrHtml><![CDATA[%s]]></jrHtml>"
        . "<finalQ>%s</finalQ>"
      . "<useBarbilliardsControl>%s</useBarbilliardsControl>"
      . "<noMandatoryQuestions>%s</noMandatoryQuestions>"
      . "<qNo>%s</qNo>"
      . "<useLikert>%s</useLikert>"
        . "<useReasons>%s</useReasons>"
      . "<intentionMinValue>%s</intentionMinValue>"
        . "<reasonMinValue>%s</reasonMinValue>"
      . "<useS1AlignmentControl>%s</useS1AlignmentControl>"
        . "<useS1QCategoryControl>%s</useS1QCategoryControl>"
      . "<useS1IntentionMin>%s</useS1IntentionMin>"
      . "<randomiseSideS1>%s</randomiseSideS1>"
      . "<useS1Intention>%s</useS1Intention>"
        . "<npSide>%s</npSide>"
      . "</message>",
      $mType, $lContent, $rContent, $jrHtml, 
      $this->showNoMoreQuestions, 
      $this->s1model->eModel->s1barbilliardControl,
      $this->s1model->eModel->s1QuestionCountAlternative,
      $qNo,$uLikert, $uReasons,
      $this->s1model->eModel->s1IntentionMin,
      $this->s1model->eModel->reasonCharacterLimitValue,
      $this->s1model->eModel->useS1AlignmentControl,
      $this->s1model->eModel->useS1QCategoryControl,
      $this->s1model->eModel->useS1IntentionMin,
      $this->s1model->eModel->randomiseSideS1,      
      $this->s1model->eModel->useS1Intention,
      $side
    );        

    $jNoText = $jNo+1;
    // deal with special case where judge has diconnected in meantime
    if ($jclientId == -1) {
      // will pick up rating status when judge reconnects
    }
    else {
      $this->Server->wsSend($jclientId, $xml);
      if ($this->s1model->eModel->randomiseSideS1 == 0) {
        $judgeHistoryHhtml = $this->buildJHistory($jType, $jNo);
        $jXML = sprintf("<message><messageType>jHistory</messageType><content><![CDATA[%s]]></content></message>", $judgeHistoryHhtml);
        $this->Server->wsSend($jclientId, $jXML);        
      }
    }
  }

  function updateNPHistory($jType, $jNo) {
    $npclientId = ($jType == 0) ? $this->s1model->evenJudges[$jNo]["npclientId"] : $this->s1model->oddJudges[$jNo]["npclientId"];
    $NPHhtml = $this->buildNPHistory($jType, $jNo);
    $NPHxml = sprintf("<message><messageType>%s</messageType><content><![CDATA[%s]]></content></message>", 'npHistory', $NPHhtml);
    //$this->Server->log("npHistory","to: $npclientId, npHistory\r\n");
    //echo "$npclientId :: $NPHxml";
    $this->Server->wsSend($npclientId, $NPHxml);           
  }

  function updatePHistory($jType,$jNo) {
    $pclientId = ($jType == 0) ? $this->s1model->evenJudges[$jNo]["pclientId"] : $this->s1model->oddJudges[$jNo]["pclientId"]; 
    $PHhtml = $this->buildPHistory($jType, $jNo);
    $PHxml = sprintf("<message><messageType>%s</messageType><content><![CDATA[%s]]></content></message>", 'pHistory', $PHhtml);
    //$this->Server->log("pHistory","to: $pclientId, pHistory\r\n");
    //echo "$pclientId :: $PHxml";
    $this->Server->wsSend($pclientId, $PHxml);        
  }
  
  function processFinalRating($jclientId, $jType, $jNo, $ratings) {
    $choice = $ratings[0];        // always choice, but others may not have a value depending on currentExpt
    $reason = urldecode($ratings[1]);
    $likert = $ratings[2];
    $npSide = $ratings[3];
    if ($jType == 0) {
      $qNo=$this->s1model->evenJudges[$jNo]["qNo"];
      $this->s1model->evenJudges[$jNo]["ratings"][$qNo+1]["jChoice"]= $choice;
      $this->s1model->evenJudges[$jNo]["ratings"][$qNo+1]["jReason"]= $reason;
      $this->s1model->evenJudges[$jNo]["ratings"][$qNo+1]["jConfidence"] = $likert;
      $this->s1model->evenJudges[$jNo]["ratings"][$qNo+1]["npSide"] = $npSide;
      $this->s1model->evenJudges[$jNo]["jAction"]="done";
      $this->s1model->evenJudges[$jNo]["npAction"]="done";
      $this->s1model->evenJudges[$jNo]["pAction"]="done";
      $this->storeData($jType, $jNo, $this->s1model->evenJudges[$jNo]);
//      $this->s1model->evenJudges[$jNo]["dataSaved"] = 0;  // initial -1 means not saved yet
      $npclientId = $this->s1model->evenJudges[$jNo]["npclientId"];
      $pclientId = $this->s1model->evenJudges[$jNo]["pclientId"];
      $otherNP = $this->s1model->evenJudges[$jNo]["otherNPs"][0];
      $otherP = $this->s1model->evenJudges[$jNo]["otherPs"][0];
      $this->s1model->evenJudges[$jNo]["jDone"]="done";
      $this->s1model->evenJudges[$otherNP]["ownnpAction"]="done";
      $this->s1model->oddJudges[$otherP]["ownpAction"]="done";
      $npDoneXML="<message><messageType>jDone</messageType><content>NP</content></message>";
      $pDoneXML="<message><messageType>jDone</messageType><content>P</content></message>";
      $this->Server->wsSend($npclientId, $npDoneXML);        
      $this->Server->wsSend($pclientId, $pDoneXML);
      //$this->Server->log("done signals sent to"," np$np p$p");
    }
    else {
      $qNo=$this->s1model->oddJudges[$jNo]["qNo"];
      $this->s1model->oddJudges[$jNo]["ratings"][$qNo+1]["jChoice"]=$choice;
      $this->s1model->oddJudges[$jNo]["ratings"][$qNo+1]["jReason"]=$reason;
      $this->s1model->oddJudges[$jNo]["ratings"][$qNo+1]["jConfidence"] = $likert;
      $this->s1model->oddJudges[$jNo]["ratings"][$qNo+1]["npSide"] = $npSide;
      $this->s1model->oddJudges[$jNo]["jAction"]="done";
      $this->s1model->oddJudges[$jNo]["npAction"]="done";
      $this->s1model->oddJudges[$jNo]["pAction"]="done";
      $this->storeData($jType, $jNo, $this->s1model->oddJudges[$jNo]);
//      $this->s1model->oddJudges[$jNo]["dataSaved"] = 0;  // initial -1 means not saved yet
      $npclientId = $this->s1model->oddJudges[$jNo]["npclientId"];
      $pclientId = $this->s1model->oddJudges[$jNo]["pclientId"];
      $otherNP = $this->s1model->oddJudges[$jNo]["otherNPs"][0];
      $otherP = $this->s1model->oddJudges[$jNo]["otherPs"][0];
      $this->s1model->oddJudges[$jNo]["jDone"] = "done";
      $this->s1model->oddJudges[$otherNP]["ownnpAction"] = "done";
      $this->s1model->evenJudges[$otherP]["ownpAction"] = "done";
      //echo $otherNP.' '.$otherP.' ';
      //echo print_r($this->s1model->evenJudges[$otherP], true);
      $npDoneXML="<message><messageType>jDone</messageType><content>NP</content></message>";
      $pDoneXML="<message><messageType>jDone</messageType><content>P</content></message>";
      $this->Server->wsSend($npclientId, $npDoneXML);        
      $this->Server->wsSend($pclientId, $pDoneXML);        
      //$this->Server->log("done signals sent to ","np$np p$p");
    }
    if ($this->s1model->eModel->s1giveFeedbackFinal == 1) {
      $winPercentage = $this->getWinPercentage($jType, $jNo);
      if ($winPercentage >= $this->s1model->eModel->s1PercentForWinFeedbackFinal) {
        $xml=sprintf("<message><messageType>finalFeedback</messageType>"
            . "<winLoseMessage><![CDATA[%s]]></winLoseMessage>",
            $this->s1model->eModel->s1WinFeedbackLabel);          
      }
      else {
        $xml=sprintf("<message><messageType>finalFeedback</messageType>"
            . "<winLoseMessage><![CDATA[%s]]></winLoseMessage>",
            $this->s1model->eModel->s1LoseFeedbackLabel);          
      }
        $this->Server->wsSend($jclientId, $xml);                            
    }   
    $this->checkForComplete();
  } 

  function processRating($jclientId, $jType, $jNo, $ratings) {
    $choice = $ratings[0];        // always choice, but others may not have a vlaue depending on currentExpt
    $reason = urldecode($ratings[1]);
    $confidence = $ratings[2];
    $npSide = $ratings[3];
    $pSide = $npSide == 0 ? 1 : 0;
    $targetSide = $this->eModel->choosingNP == 1 ? $npSide : $pSide; 
    $correct = $choice == $targetSide ? 1 : 0;
    $feedbackMessage = $correct == 1 ? $this->eModel->s1correctFB : $this->eModel->s1incorrectFB;
    if ($jType == 0) {
      $qNo=$this->s1model->evenJudges[$jNo]["qNo"];
      $this->s1model->evenJudges[$jNo]["ratings"][$qNo]["jChoice"]=$choice;
      $this->s1model->evenJudges[$jNo]["ratings"][$qNo]["jReason"]=$reason;
      $this->s1model->evenJudges[$jNo]["ratings"][$qNo]["jConfidence"]=$confidence;
      $this->s1model->evenJudges[$jNo]["ratings"][$qNo]["npSide"] = $npSide; 
      $this->s1model->evenJudges[$jNo]["ratings"][$qNo]["correct"] = $correct; 
      if ($correct == 1) {++$this->s1model->evenJudges[$jNo]["correctCnt"];} 
      $correctCnt = $this->s1model->evenJudges[$jNo]["correctCnt"];
      if ($this->eModel->useS1AlignmentControl) {
        $this->s1model->evenJudges[$jNo]["ratings"][$qNo]["r1Alignment"] = $ratings[4];         
        $this->s1model->evenJudges[$jNo]["ratings"][$qNo]["r2Alignment"] = $ratings[5];         
      }
      else {
        $this->s1model->evenJudges[$jNo]["ratings"][$qNo]["r1Alignment"] = -1;                 
        $this->s1model->evenJudges[$jNo]["ratings"][$qNo]["r2Alignment"] = -1;         
      }
      if ($this->eModel->useS1QCategoryControl) {
        $this->s1model->evenJudges[$jNo]["ratings"][$qNo]["categoryAlignment"] = $ratings[6];         
      }
      else {
        $this->s1model->evenJudges[$jNo]["ratings"][$qNo]["categoryAlignment"] = -1;                 
      }      
      $this->s1model->evenJudges[$jNo]["jAction"]="active";
    }
    else {
      $qNo=$this->s1model->oddJudges[$jNo]["qNo"];
      $this->s1model->oddJudges[$jNo]["ratings"][$qNo]["jChoice"]=$choice;
      $this->s1model->oddJudges[$jNo]["ratings"][$qNo]["jReason"]=$reason;
      $this->s1model->oddJudges[$jNo]["ratings"][$qNo]["jConfidence"]=$confidence;      
      $this->s1model->oddJudges[$jNo]["ratings"][$qNo]["npSide"] = $npSide;      
      $this->s1model->oddJudges[$jNo]["ratings"][$qNo]["correct"] = $correct; 
      if ($correct == 1) {++$this->s1model->oddJudges[$jNo]["correctCnt"];} 
      $correctCnt = $this->s1model->oddJudges[$jNo]["correctCnt"];
      $this->s1model->oddJudges[$jNo]["jAction"]="active";
      if ($this->eModel->useS1AlignmentControl) {
        $this->s1model->oddJudges[$jNo]["ratings"][$qNo]["r1Alignment"] = $ratings[4];         
        $this->s1model->oddJudges[$jNo]["ratings"][$qNo]["r2Alignment"] = $ratings[5];         
      }
      else {
        $this->s1model->oddJudges[$jNo]["ratings"][$qNo]["r1Alignment"] = -1;                 
        $this->s1model->oddJudges[$jNo]["ratings"][$qNo]["r2Alignment"] = -1;         
      }
      if ($this->eModel->useS1QCategoryControl) {
        $this->s1model->oddJudges[$jNo]["ratings"][$qNo]["categoryAlignment"] = $ratings[6];         
      }
      else {
        $this->s1model->oddJudges[$jNo]["ratings"][$qNo]["categoryAlignment"] = -1;                 
      }      
    }
    
    if ($this->eModel->s1giveFeedback == 1) {
      if ($this->eModel->s1runningScore == 1) {
        $runningScoreMessage = $this->eModel->s1runningScoreLabel." ".$correctCnt."/".$qNo." ".$this->eModel->s1runningScoreDividerLabel;
      }
      else {
        $runningScoreMessage = '';
      }
      $xml=sprintf("<message><messageType>feedback</messageType>"
          . "<runningScore><![CDATA[%s]]></runningScore>"
          . "<feedbackMessage>%s</feedbackMessage>"
          . "</message>",
          $runningScoreMessage, 
          $feedbackMessage);
      $this->Server->wsSend($jclientId, $xml);                
    }
  }

  //</editor-fold >

  // <editor-fold defaultstate="collapsed" desc=" ctor - session end & data storage">

  function storeData($jType, $jNo, $judgeData) {
    global $igrtSqli;
    //*
    // future reference: odd indexing noted here
    // [history] goes  0->qNo-1
    // [ratings] goes 1->qNo+1 !!! // awkward, but to do with the way that numbering happens when changing state - easier to cope with here
    for ($i=0; $i<$judgeData["qNo"]; $i++) {
      $labelStr="intervalId";
      $sourceStr=$judgeData["ratings"][$i+1]['jConfidence'];
      $jLikertValue=substr($sourceStr,  strlen($labelStr));
      $localQ = $igrtSqli->real_escape_string($judgeData["history"][$i]['jQuestion']);
      $localNPR = $igrtSqli->real_escape_string($judgeData["history"][$i]['npReply']);
      $localPR = $igrtSqli->real_escape_string($judgeData["history"][$i]['pReply']);
      $localReason = $igrtSqli->real_escape_string($judgeData["ratings"][$i+1]['jReason']);
      $localIntention = $igrtSqli->real_escape_string($judgeData["history"][$i]['jIntention']);
      $sqlCmd_Insert = "INSERT INTO dataSTEP1 ";
      $sqlCmd_Insert.="(uid, exptId, jType, jNo, sessionNo, dayNo, npLeft, qNo, q, npr, pr, choice, rating, extraRating, reason, insertTime, iIntention, categoryAlignment, r1Alignment, r2Alignment) ";
      $sqlCmd_Insert.="VALUES (";
        $sqlCmd_Insert.=sprintf("'%s',",$judgeData["uid"]);
        $sqlCmd_Insert.=sprintf("'%s',",$this->exptId);
        $sqlCmd_Insert.=sprintf("'%s',",$jType);
        $sqlCmd_Insert.=sprintf("'%s',",$jNo);
        $sqlCmd_Insert.=sprintf("'%s',",$this->sessionNo);
        $sqlCmd_Insert.=sprintf("'%s',",$this->dayNo);
        $sqlCmd_Insert.=sprintf("'%s',", $judgeData["ratings"][$i+1]['npSide'] == 0 ? 1 : 0);
        $sqlCmd_Insert.=sprintf("'%s',",$i+1);
        $sqlCmd_Insert.=sprintf("'%s',",$localQ);
        $sqlCmd_Insert.=sprintf("'%s',",$localNPR);
        $sqlCmd_Insert.=sprintf("'%s',",$localPR);
        $sqlCmd_Insert.=sprintf("'%s',",$judgeData["ratings"][$i+1]['jChoice']);
        $sqlCmd_Insert.=sprintf("'%s',",$judgeData["ratings"][$i+1]['jConfidence']);
        $sqlCmd_Insert.=sprintf("'%s',",-1);
        $sqlCmd_Insert.=sprintf("'%s',",$localReason);
        $sqlCmd_Insert.=" NOW(),";
        $sqlCmd_Insert.=sprintf("'%s',",$localIntention);
        $sqlCmd_Insert.=sprintf("'%s',",$judgeData["ratings"][$i+1]['categoryAlignment']);
        $sqlCmd_Insert.=sprintf("'%s',",$judgeData["ratings"][$i+1]['r1Alignment']);
        $sqlCmd_Insert.=sprintf("'%s'",$judgeData["ratings"][$i+1]['r2Alignment']);
      $sqlCmd_Insert.=")";
      $igrtSqli->query($sqlCmd_Insert); 
      echo $sqlCmd_Insert;
    } 
    if (isset($judgeData["ratings"][$i+1])) {
      // we have a final rating
      $localReason = $igrtSqli->real_escape_string($judgeData["ratings"][$i+1]['jReason']);
      $localChoice = $judgeData["ratings"][$i+1]['jChoice'];
      $localConfidence = $judgeData["ratings"][$i+1]['jConfidence'];
    }
    else {
      // this is partial data, forcibly saved by admin
      $localReason = "PARTIAL";
      $localChoice = -1;
      $localConfidence = "PARTIAL";      
    }
    $sqlCmd_Insert="INSERT INTO dataSTEP1 ";
    $sqlCmd_Insert.="(uid, exptId, jType, jNo, sessionNo, dayNo, npLeft, qNo, q, npr, pr, choice, rating, reason, insertTime) ";
    $sqlCmd_Insert.="VALUES (";
      $sqlCmd_Insert.=sprintf("'%s',",$judgeData["uid"]);
      $sqlCmd_Insert.=sprintf("'%s',",$this->exptId);
      $sqlCmd_Insert.=sprintf("'%s',",$jType);
      $sqlCmd_Insert.=sprintf("'%s',",$jNo);
      $sqlCmd_Insert.=sprintf("'%s',",$this->sessionNo);
      $sqlCmd_Insert.=sprintf("'%s',",$this->dayNo);
      $sqlCmd_Insert.=sprintf("'%s',", $judgeData["ratings"][$i+1]['npSide'] == 0 ? 1 : 0);
      $sqlCmd_Insert.=sprintf("'%s',",$i+1);
      $sqlCmd_Insert.=sprintf("'%s',","FINAL");
      $sqlCmd_Insert.=sprintf("'%s',","FINAL");
      $sqlCmd_Insert.=sprintf("'%s',","FINAL");
      $sqlCmd_Insert.=sprintf("'%s',",$localChoice);
      $sqlCmd_Insert.=sprintf("'%s',",$localConfidence);
      $sqlCmd_Insert.=sprintf("'%s',",$localReason);
      $sqlCmd_Insert.=" NOW()";
    $sqlCmd_Insert.=")";
    $igrtSqli->query($sqlCmd_Insert);
	  echo $sqlCmd_Insert;
  }

  function closeStep1Session() {
    // store data & mark session as complete
    global $igrtSqli;
    $sqlCmd_close = sprintf("UPDATE edSessions SET active='0', step1Complete='1' WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s'",
      $this->exptId, $this->dayNo, $this->sessionNo);
    $igrtSqli->query($sqlCmd_close);
    // signal to ppt clients that all is finished
    $endXml = "<message><messageType>finish</messageType><content>0</content></message>";            
    foreach ($this->s1model->evenJudges as $ej) {      
      $this->Server->wsSend($ej["jclientId"], $endXml);
      //$this->storeData(0, $ej["jNo"], $ej);
    }
    foreach ($this->s1model->oddJudges as $oj) {
      $this->Server->wsSend($oj["jclientId"], $endXml);
      //$this->storeData(0, $oj["jNo"], $oj);
    }
    // signal to admin that session can be closed (this could be auto, but better avoid spooking experimenters)
    foreach ($this->configClientIdList as $cid) {
      $this->Server->wsSend($cid['clientId'], "<message><messageType>step1Complete</messageType><body>na</body></message>");
    }    
  }
  
  function checkForComplete() {
    $complete = 1;
    for ($i=0; $i<count($this->s1model->evenJudges); $i++) {
      if ($this->s1model->evenJudges[$i]["doneSent"] == "notSent") {
        if (($this->s1model->evenJudges[$i]["jDone"] != "done") || 
            ($this->s1model->evenJudges[$i]["npDone"] != "done") || 
            ($this->s1model->evenJudges[$i]["pDone"] != "done")) { 
          $complete = 0; 
        }
      }
    }
    for ($i=0; $i<count($this->s1model->oddJudges); $i++) {
      if ($this->s1model->oddJudges[$i]["doneSent"] == "notSent") {
        if (($this->s1model->oddJudges[$i]["jDone"] != "done") || 
            ($this->s1model->oddJudges[$i]["npDone"] != "done") || 
            ($this->s1model->oddJudges[$i]["pDone"] != "done")) { 
          $complete = 0; 
        }
      }
    }
    if ($complete == 1) {
      $this->closeStep1Session();      
    }
  }

  
  //</editor-fold >

  // <editor-fold defaultstate="collapsed" desc=" helpers">

  function getExptTitle($exptId) {
    global $igrtSqli;
    $sqlQry_title=sprintf("SELECT * FROM igExperiments WHERE exptId='%s'",$exptId);
    $titleResults=$igrtSqli->query($sqlQry_title);
    if ($titleResults) {
      $row=$titleResults->fetch_object();
      return $row->title;
    }
  }

  function getJNoForJ($clientId) {
    $i=0;
    while ($i<$this->s1model->jCnt) {
      if ($this->s1model->evenJudges[$i]["jclientId"]==$clientId) { return $i; }
      if ($this->s1model->oddJudges[$i]["jclientId"]==$clientId) { return $i; }
      ++$i;
    }
  }

  function getJTypeForJ($clientId) {
    $i=0;
    while ($i<$this->s1model->jCnt) {
      if ($this->s1model->evenJudges[$i]["jclientId"] == $clientId) { return 0; }
      if ($this->s1model->oddJudges[$i]["jclientId"] == $clientId) { return 1; }
      ++$i;
    }
  }

  function getJNoForNP($clientId) {
    $i=0;
    while ($i<$this->s1model->jCnt) {
      if ($this->s1model->evenJudges[$i]["npclientId"]==$clientId) { return $i; }
      if ($this->s1model->oddJudges[$i]["npclientId"]==$clientId) { return $i; }
      ++$i;
    }
  }

  function getJTypeForNP($clientId) {
    $i=0;
    while ($i < $this->s1model->jCnt) {
      if ($this->s1model->evenJudges[$i]["npclientId"]==$clientId) { return 0; }
      if ($this->s1model->oddJudges[$i]["npclientId"]==$clientId) { return 1; }
      ++$i;
    }
  }

  function getJNoForP($clientId) {
    $i=0;
    while ($i<$this->s1model->jCnt) {
      if ($this->s1model->evenJudges[$i]["pclientId"] == $clientId) { return $i; }
      if ($this->s1model->oddJudges[$i]["pclientId"] == $clientId) { return $i; }
      ++$i;
    }
  }

  function getJTypeForP($clientId) {
    $i=0;
    while ($i<$this->s1model->jCnt) {
      if ($this->s1model->evenJudges[$i]["pclientId"] == $clientId) { return 0; }
      if ($this->s1model->oddJudges[$i]["pclientId"] == $clientId) { return 1; }
      ++$i;
    }
  }

  function getJTypeFromClientId($clientId) {
    foreach ($this->s1model->evenJudges as $xj) {
      if ($xj['jclientId'] == $clientId) {return 0;}
    }
    foreach ($this->s1model->oddJudges as $yj) {
      if ($yj['jclientId'] == $clientId) {return 1;}
    }
  }

  function getJNoFromClientId($clientId) {
    $i=0;
    while ($i<$this->s1model->jCnt) {
      if ($this->s1model->evenJudges[$i]["jclientId"]==$clientId) {return $i;}
      if ($this->s1model->oddJudges[$i]["jclientId"]==$clientId) {return $i;}
      ++$i;
    }    
  }

  function getUIDFromJParams($jType, $jNo) {
    if ($jType == 0) {
      return $this->s1model->evenJudges[$jNo]["uid"];
    }
    else {
      return $this->s1model->oddJudges[$jNo]["uid"];            
    }
  }    

  function getNPUIDFromJParams($jType, $jNo) {
    if ($jType == 0) {
      return $this->s1model->evenJudges[$jNo]["npUid"];
    }
    else {
      return $this->s1model->oddJudges[$jNo]["npUid"];            
    }
  }    

  function getPUIDFromJParams($jType, $jNo) {
    if ($jType == 0) {
      return $this->s1model->evenJudges[$jNo]["pUid"];
    }
    else {
      return $this->s1model->oddJudges[$jNo]["pUid"];            
    }
  }    
    
  function setJudgeDisconnected($jType, $jNo) {
    if ($jType == 0) {
      $this->s1model->evenJudges[$jNo]["jclientConfirmed"] = false;
      $this->s1model->evenJudges[$jNo]["disconnected"]=true;
      $this->s1model->evenJudges[$jNo]["jclientId"]=-1;
      // disconnect from judges I am NP  & P to
      $xNPjudge=$this->s1model->evenJudges[$jNo]["ownNPs"][0];
      $this->s1model->evenJudges[$xNPjudge]['npclientId']=-1;
      $this->s1model->evenJudges[$xNPjudge]['npDisconnected']=true;

      $yPjudge=$this->s1model->evenJudges[$jNo]["ownPs"][0];
      $this->s1model->oddJudges[$yPjudge]['pclientId']=-1;
      $this->s1model->oddJudges[$yPjudge]['pDisconnected']=true;
    }
    else {
      $this->s1model->oddJudges[$jNo]["jclientConfirmed"] = false;     
      $this->s1model->oddJudges[$jNo]["disconnected"]=true;            
      $this->s1model->oddJudges[$jNo]["jclientId"]=-1;            
      // disconnect from judges I am NP  & P to
      $yNPjudge=$this->s1model->oddJudges[$jNo]["ownNPs"][0];
      $this->s1model->oddJudges[$yNPjudge]['npclientId']=-1;
      $this->s1model->oddJudges[$yNPjudge]['npDisconnected']=true;
      $xPjudge=$this->s1model->oddJudges[$jNo]["ownPs"][0];
      $this->s1model->evenJudges[$xPjudge]['pclientId']=-1;
      $this->s1model->evenJudges[$xPjudge]['pDisconnected']=true;
    }
  }
    
  //</editor-fold >

  // <editor-fold defaultstate="collapsed" desc=" data to HTML helpers">
  
  function buildNPHistory($jType, $jNo) {
    $np_html='<div></div>';
    $reverseHistory=array();
    $reverseHistory = ($jType == 0) ? array_reverse($this->s1model->evenJudges[$jNo]["history"]) : array_reverse($this->s1model->oddJudges[$jNo]["history"]);
    $lastNumber=count($reverseHistory);
    $yaContent = $this->s1model->eModel->rYourAnswer;
    $yqContent = $this->s1model->eModel->rCurrentQ;
    //echo "Build NP".print_r($reverseHistory, true);
    foreach ($reverseHistory as $v) {
      $np_html.='<div class="previousQuestion">';
      $np_html.=sprintf("<p><span>%s %s</span>%s</p>",$yqContent, $lastNumber, $v["jQuestion"]);
      $np_html.=sprintf("<div class=\"response\"><p><span>%s: </span>%s</p></div>", $yaContent, $v["npReply"]); 
      $np_html.='</div>';
      --$lastNumber;
    }
    return $np_html;
  }

  function buildPHistory($jType, $jNo) {
    $p_html='<div></div>';
    $reverseHistory=array();
    $reverseHistory = ($jType == 0) ? array_reverse($this->s1model->evenJudges[$jNo]["history"]) : array_reverse($this->s1model->oddJudges[$jNo]["history"]); 
    $lastNumber=count($reverseHistory);
    $yaContent = $this->s1model->eModel->rYourAnswer;
    $yqContent = $this->s1model->eModel->rCurrentQ;
    //echo "Build P".print_r($reverseHistory, true);
    foreach ($reverseHistory as $v) {
      $p_html.='<div class="previousQuestion">';
      $p_html.=sprintf("<p><span>%s %s</span>%s</p>",$yqContent, $lastNumber, $v["jQuestion"]);
      $p_html.=sprintf("<div class=\"response\"><p><span>%s: </span>%s</p></div>", $yaContent, $v["pReply"]);        
      $p_html.='</div>';
      --$lastNumber;
    }
    return $p_html;
  }
  
  function buildOtherNPHistory($jType, $jNo) {
    $np_html='<div></div>';
    $reverseHistory=array();
    if ($jType == 0) {
      $otherJNo = $this->s1model->evenJudges[$jNo]['ownNPs'][0];
      $reverseHistory=array_reverse($this->s1model->evenJudges[$otherJNo]["history"]);                
    }
    else {
      $otherJNo = $this->s1model->oddJudges[$jNo]['ownNPs'][0];
      $reverseHistory=array_reverse($this->s1model->oddJudges[$otherJNo]["history"]);                        
    }
    $lastNumber=count($reverseHistory);
    $yaContent = $this->s1model->eModel->rYourAnswer;
    $yqContent = $this->s1model->eModel->rCurrentQ;
    foreach ($reverseHistory as $v) {
      $np_html.='<div class="previousQuestion">';
      $np_html.=sprintf("<p><span>%s %s</span>%s</p>",$yqContent, $lastNumber, $v["jQuestion"]);
      $np_html.=sprintf("<div class=\"response\"><p><span>%s: </span>%s</p></div>", $yaContent, $v["npReply"]); 
      $np_html.='</div>';
      --$lastNumber;
    }
    return $np_html;
  }

  function buildOtherPHistory($jType, $jNo) {
    $p_html='<div></div>';
    $reverseHistory=array();
    if ($jType == 0) {
      $otherJNo = $this->s1model->evenJudges[$jNo]['ownPs'][0];
      $reverseHistory=array_reverse($this->s1model->oddJudges[$otherJNo]["history"]);                
    }
    else {
      $otherJNo = $this->s1model->oddJudges[$jNo]['ownPs'][0];
      $reverseHistory=array_reverse($this->s1model->evenJudges[$otherJNo]["history"]);                        
    }
    $lastNumber=count($reverseHistory);
    $yaContent = $this->s1model->eModel->rYourAnswer;
    $yqContent = $this->s1model->eModel->rCurrentQ;
    foreach ($reverseHistory as $v) {
      $p_html.='<div class="previousQuestion">';
      $p_html.=sprintf("<p><span>%s %s</span>%s</p>",$yqContent, $lastNumber, $v["jQuestion"]);
      $p_html.=sprintf("<div class=\"response\"><p><span>%s: </span>%s</p></div>", $yaContent, $v["pReply"]);        
      $p_html.='</div>';
      --$lastNumber;
    }
    return $p_html;
  }

  function buildJHistory($jType, $jNo) {
    $j_html='<div></div>';
    $reverseHistory=array();
    if ($jType == 0) {
      $reverseHistory=array_reverse($this->s1model->evenJudges[$jNo]["history"]);        
    }
    else {
      $reverseHistory=array_reverse($this->s1model->oddJudges[$jNo]["history"]);                
    }  
    $lastNumber=count($reverseHistory);
    $r1Content = $this->s1model->eModel->jRatingR1;
    $r2Content = $this->s1model->eModel->jRatingR2;
    $yqContent = $this->s1model->eModel->jRatingQ;
    // counter-balance left/right responses
    if ($jNo%2==0) {
      foreach ($reverseHistory as $v) {
        $j_html.='<div class="previousQuestion">';
          $j_html.=sprintf("<p><span>%s %s</span>%s</p>",$yqContent, $lastNumber, $v["jQuestion"]);
        $j_html .= '</div>';
        $j_html.=sprintf("<div class=\"responseOne\"><h3>%s: </h3><p>%s</p></div>", $r1Content, $v["npReply"]);
        $j_html.=sprintf("<div class=\"responseTwo\"><h3>%s: </h3><p>%s</p></div>", $r2Content, $v["pReply"]);
        $j_html.='<div style="clear: both"></div>';  //<div style="clear: both"></div>
        --$lastNumber;
      }
    }
    else {
      foreach ($reverseHistory as $v) {
        $j_html.='<div class="previousQuestion">';
          $j_html.=sprintf("<p><span>%s %s</span>%s</p>",$yqContent, $lastNumber, $v["jQuestion"]);
          $j_html .= '</div>';
        $j_html.=sprintf("<div class=\"responseOne\"><h3>%s: </h3><p>%s</p></div>", $r1Content, $v["pReply"]);
        $j_html.=sprintf("<div class=\"responseTwo\"><h3>%s: </h3><p>%s</p></div>", $r2Content, $v["npReply"]);
        $j_html.='<div style="clear: both"></div>';  //<div style="clear: both"></div>
        --$lastNumber;
      }
    }
    return $j_html;
  }

  function getFinalTranscript($jType, $jNo, $npSide) {
    $history=array();
    $j_html = "";
    if ($jType == 0) {
      $history=array_values($this->s1model->evenJudges[$jNo]["history"]);        
    }
    else {
      $history=array_values($this->s1model->oddJudges[$jNo]["history"]);                
    }  
    $currentNumber=1;
    $r1Content = $this->s1model->eModel->jRatingR1;
    $r2Content = $this->s1model->eModel->jRatingR2;
    $yqContent = $this->s1model->eModel->jRatingQ;
    // counter-balance left/right responses
    if ($npSide == 0) {
      foreach ($history as $v) {
        $j_html.='<div><div class="latestQuestion">';
        $j_html .= "<hr />"; 
        $j_html.=sprintf("<p><span>%s %s</span>%s</p>",$yqContent, $currentNumber, $v["jQuestion"]);
        $j_html.=sprintf("<div class=\"responseOne\"><h2>%s</h2><p>%s</p></div>", $r1Content, $v["npReply"]);
        $j_html.=sprintf("<div class=\"responseTwo\"><h2>%s</h2><p>%s</p></div>", $r2Content, $v["pReply"]);
        $j_html.='<div style="clear: both"></div></div></div>';
        ++$currentNumber;
      }
    }
    else {
      foreach ($history as $v) {
        $j_html.='<div><div class="latestQuestion">';
        $j_html .= "<hr />"; 
        $j_html.=sprintf("<p><span>%s %s</span>%s</p>",$yqContent, $currentNumber, $v["jQuestion"]);
        $j_html.=sprintf("<div class=\"responseOne\"><h2>%s</h2><p>%s</p></div>", $r1Content, $v["pReply"]);
        $j_html.=sprintf("<div class=\"responseTwo\"><h2>%s</h2><p>%s</p></div>", $r2Content, $v["npReply"]);
        $j_html.='<div style="clear: both"></div></div></div>';
        ++$currentNumber;
      }
    }
    return $j_html;
  }

  function getJudgeFinalRatingHtml($jType, $jNo, $npSide) {
    $html = '<h2>' . $this->s1model->eModel->labelFinalRating . '</h2>';
    // need to build whole transcript here 
    $html .= $this->getFinalTranscript($jType, $jNo, $npSide);
    $html .= $this->htmlBuilder->makeJudgeFinalChoice("judgesFinalChoice",$this->s1model->eModel->labelChoiceFinalRating,"finalJudgement");
    $html .= "<hr/>";
    if ($this->s1model->eModel->useReasonFinalRating) {
      $html.=$this->htmlBuilder->makeJudgeFinalReason("judgesMainReason",$this->s1model->eModel->labelReasonFinalRating);
      if ($this->s1model->eModel->useReasonCharacterLimitF) {
        $html.= '<p class="finalJudgesReason">'.$this->s1model->eModel->reasonGuidanceF.'</p>';
      }
    }
    if ($this->s1model->eModel->useFinalLikert) {
      $html.=$this->htmlBuilder->makeFinalJudgeLikert($this->s1model->eModel->instFinalLikert,$this->s1model->eModel->labelFinalLikert);      
    }
    return $html;
  }

  function getJudgeRatingHtml($jType) {
    $html = '';
    if ($this->s1model->eModel->useS1AlignmentControl == 1) {
      $html.= $this->htmlBuilder->makeJudgeAlignmentOptions($this->s1model->eModel, $jType);     
    }
    $html.=$this->htmlBuilder->makeJudgeChoice("jRating",$this->s1model->eModel->labelChoice,"judgement");
    $html.="<hr/>";
    if ($this->s1model->eModel->useReasons) {
      $html.=$this->htmlBuilder->makeJudgeReason("jReason", $this->s1model->eModel->labelReasons, $this->s1model->eModel->reasonGuidance);           
    }
    if ($this->s1model->eModel->useLikert) {
      $html.=$this->htmlBuilder->makeJudgeLikert($this->s1model->eModel->instLikert,$this->s1model->eModel->labelLikert);            
    }    
    if ($this->s1model->eModel->useS1QCategoryControl == 1) {
      $html.= $this->htmlBuilder->makeS1AlignmentCategoryLikert($this->s1model->eModel);
    }
    return $html;
  } 
  
  //</editor-fold >

  // <editor-fold defaultstate="collapsed" desc=" admin/monitor screen">

  function getMonPtr($clientId) {
    $ptr =0;
    foreach($this->configClientIdList as $c) {
      if ($c['clientId'] == $clientId) { return $ptr; }
      ++$ptr;
    }
    return -1;
  }
  
  function addMonitor($clientId) {
    $monPtr = $this->getMonPtr($clientId);
    //echo $monPtr;
    if ($monPtr == -1) {
      $temp = array('clientId' => $clientId);
      array_push($this->configClientIdList, $temp);
    }
//    $debug = print_r($this->configClientIdList, true);
//    echo $debug;
    $this->updateStatusPage(0);
  }
  
  function removeMonitor($clientId) {
    $monPtr = $this->getMonPtr($clientId);
    if ($monPtr > -1) {
      unset($this->configClientIdList[$monPtr]);
      $this->configClientIdList = array_values($this->configClientIdList);
    }    
  }
  
  function setNextButtonSwitch() {
    $this->showNoMoreQuestions = 1;
  }

  function updateStatusPage($debug) {
    // send message back to admin client that a game or login status has changed....
    if ($this->notStarted) {
      $oddStatus = $this->buildCurrentOddConnections($this->s1model->jCnt);
      $evenStatus = $this->buildCurrentEvenConnections($this->s1model->jCnt);
    }
    else {
      // send message back to admin client that another judge status has changed, or show initial
      $oddStatus = $this->buildCurrentOddStatus($this->s1model->jCnt);
      $evenStatus = $this->buildCurrentEvenStatus($this->s1model->jCnt);
    }
    $oddXml = sprintf("<message><messageType>oddStatusUpdate</messageType><content><![CDATA[%s]]></content></message>", $oddStatus);
    $evenXml = sprintf("<message><messageType>evenStatusUpdate</messageType><content><![CDATA[%s]]></content></message>", $evenStatus);
    foreach ($this->configClientIdList as $cid) {
      $this->Server->wsSend($cid['clientId'], $oddXml);
      $this->Server->wsSend($cid['clientId'], $evenXml);
    }      
  }

  function buildCurrentEvenConnections($jCnt) {
    $html='<ul class="statusPods">';
    for ($i=0;$i<$jCnt;$i++) {
      $html.='<li>';
      if ($this->s1model->evenJudges[$i]['jclientConfirmed']) {
        $Jactive="active";
        $email=$this->s1model->evenJudges[$i]["email"];                
      }
      else {
        $Jactive="";
        if ($this->s1model->evenJudges[$i]["disconnected"]) { $email = "gone!"; } else { $email = $this->s1model->evenJudges[$i]["email"]; }
      }
      // ($i * 2) + 1
      $html.=sprintf("<div class=\"statusJudge %s\">%s<br/>%s </div>", $Jactive, ($i+1)*2, $email);
      $NPactive= ($this->s1model->evenJudges[$i]["npclientId"]!=-1) ? ("active") : ("inactive");
      $html.=sprintf("<div class=\"statusNonPretender %s\">%s c:%s</div>", $NPactive, ($this->s1model->evenJudges[$i]["otherNPs"][0]+1)*2, $this->s1model->evenJudges[$i]["npclientId"]+1);
      $Pactive= ($this->s1model->evenJudges[$i]["pclientId"]!=-1) ? ("active") : ("inactive");
      $html.=sprintf("<div class=\"statusPretender %s\">%s c:%s</div>", $Pactive, ($this->s1model->evenJudges[$i]["otherPs"][0]*2)+1, $this->s1model->evenJudges[$i]["pclientId"]+1);
      $html.='</li>';
    }
    $html.='</ul>';
    return $html;
  }

  function buildCurrentOddConnections($jCnt) {
    $html='<ul class="statusPods">';
    for ($i=0;$i<$jCnt;$i++) {
      $html.='<li>';
      if ($this->s1model->oddJudges[$i]['jclientConfirmed']) {
        $Jactive="active";
        $email=$this->s1model->oddJudges[$i]["email"];                
      }
      else {
        $Jactive="";
        if ($this->s1model->oddJudges[$i]["disconnected"]) { $email = "gone!"; } else { $email = $this->s1model->oddJudges[$i]["email"]; }
      }
      // ($i + 1) * 2
      $html.=sprintf("<div class=\"statusJudge %s\">%s<br/>%s</div>",$Jactive, ($i*2)+1,$email);
      $NPactive= ($this->s1model->oddJudges[$i]["npclientId"]!=-1) ? ("active") : ("");
      $html.=sprintf("<div class=\"statusNonPretender %s\">%s c:%s</div>",$NPactive, ($this->s1model->oddJudges[$i]["otherNPs"][0]*2)+1,$this->s1model->oddJudges[$i]["npclientId"]+1);
      $Pactive= ($this->s1model->oddJudges[$i]["pclientId"]!=-1) ? ("active") : ("");
      $html.=sprintf("<div class=\"statusPretender %s\">%s c:%s</div>",$Pactive, ($this->s1model->oddJudges[$i]["otherPs"][0]+1)*2,$this->s1model->oddJudges[$i]["pclientId"]+1);
      $html.='</li>';
    }
    $html.='</ul>';
    return $html;
  }

  function buildCurrentEvenStatus($jCnt) {
    $html='<ul class="statusPods">';
    for ($i=0;$i<$jCnt;$i++) {
      $html.='<li>';
      if ($this->s1model->evenJudges[$i]['npDisconnected']==true) {
        $html.=sprintf("<div class=\"statusNonPretender disconnected\">np:%s </div>",
            ($this->s1model->evenJudges[$i]["otherNPs"][0]+1)*2);
      }
      else {
        $html.=sprintf("<div class=\"statusNonPretender %s\">np:%s</div>",
            $this->s1model->evenJudges[$i]["npAction"],
            ($this->s1model->evenJudges[$i]["otherNPs"][0]+1)*2);
      }
      if ($this->s1model->evenJudges[$i]['pDisconnected']==true) {
        $html.=sprintf("<div class=\"statusPretender disconnected\">p:%s</div>",
            ($this->s1model->evenJudges[$i]["otherPs"][0]*2)+1);
      }
      else {
        $html.=sprintf("<div class=\"statusPretender %s\">p:%s</div>",
            $this->s1model->evenJudges[$i]["pAction"],
            ($this->s1model->evenJudges[$i]["otherPs"][0]*2)+1);
      }
      if ($this->s1model->evenJudges[$i]["disconnected"]==true) {
        $html.=sprintf("<div class=\"statusJudge disconnected\">%s (%s)<br />Q%s %s</div>",
            ($i+1)*2,
            $this->s1model->evenJudges[$i]["email"],
            $this->s1model->evenJudges[$i]["qNo"],
            $this->s1model->evenJudges[$i]["time"]);                
      }
      else {
         $html.=sprintf("<div class=\"statusJudge %s\">%s (%s)<br />Q%s %s</div>",
             $this->s1model->evenJudges[$i]["jAction"],
             ($i+1)*2,
             $this->s1model->evenJudges[$i]["email"],
             $this->s1model->evenJudges[$i]["qNo"],
             $this->s1model->evenJudges[$i]["time"]);
      }
      $ownNP = $this->s1model->evenJudges[$i]["ownNPs"][0];
      $html.=sprintf("<div class=\"statusOwnNP %s\">np:%s</div>",
        $this->s1model->evenJudges[$i]["ownnpAction"],
        ($ownNP+1)*2);
      $ownP = $this->s1model->evenJudges[$i]["ownPs"][0];
      $html.=sprintf("<div class=\"statusOwnP %s\">p:%s</div>",
        $this->s1model->evenJudges[$i]["ownpAction"],
        ($ownP*2)+1);
      $html.='</li>';
    }
    $html.='</ul>';
    return $html;
  }

  function buildCurrentOddStatus($jCnt) {
    $html='<ul class="statusPods">';
    for ($i=0;$i<$jCnt;$i++) {
      $html.='<li>';
      if ($this->s1model->oddJudges[$i]['npDisconnected']==true) {
        $html.=sprintf("<div class=\"statusNonPretender disconnected\">np:%s </div>",
            ($this->s1model->oddJudges[$i]["otherNPs"][0]*2)+1);
      }
      else {
        $html.=sprintf("<div class=\"statusNonPretender %s\">np:%s </div>",
            $this->s1model->oddJudges[$i]["npAction"],
            ($this->s1model->oddJudges[$i]["otherNPs"][0]*2)+1);
      }
      if ($this->s1model->oddJudges[$i]['pDisconnected']==true) {
          $html.=sprintf("<div class=\"statusPretender disconnected\">p:%s </div>",
              ($this->s1model->oddJudges[$i]["otherPs"][0]+1)*2);
      }
      else {
          $html.=sprintf("<div class=\"statusPretender %s\">p:%s </div>",
              $this->s1model->oddJudges[$i]["pAction"],
              ($this->s1model->oddJudges[$i]["otherPs"][0]+1)*2);
      }
      if ($this->s1model->oddJudges[$i]["disconnected"]==true) {
        $html.=sprintf("<div class=\"statusJudge disconnected\">%s (%s) <br />Q%s %s</div>",
            ($i*2)+1,
            $this->s1model->oddJudges[$i]["email"],
            $this->s1model->oddJudges[$i]["qNo"],
            $this->s1model->oddJudges[$i]["time"]);               
      }
      else {
        $html.=sprintf("<div class=\"statusJudge %s\">%s (%s)<br />Q%s %s</div>",
            $this->s1model->oddJudges[$i]["jAction"],
            ($i*2)+1,
            $this->s1model->oddJudges[$i]["email"],
            $this->s1model->oddJudges[$i]["qNo"],
            $this->s1model->oddJudges[$i]["time"]);
      }
      $ownNP = $this->s1model->oddJudges[$i]["ownNPs"][0];
      $html.=sprintf("<div class=\"statusOwnNP %s\">np:%s </div>",
        $this->s1model->oddJudges[$i]["ownnpAction"],
        ($ownNP*2)+1);
      $ownP = $this->s1model->oddJudges[$i]["ownPs"][0];
      $html.=sprintf("<div class=\"statusOwnP %s\">p:%s </div>",
        $this->s1model->oddJudges[$i]["ownpAction"],
        ($ownP+1)*2);
      $html.='</li>';
    }
    $html.='</ul>';
    return $html;
  }

  //</editor-fold >

  // <editor-fold defaultstate="collapsed" desc=" ctor - intitialises a step1 session in main controller">

  function __construct($configclientId, $gServer, $exptId, $dayNo, $sessionNo, $iCnt, $allocationGeneration) {
    $this->Server=$gServer;
    $temp = array('clientId' => $configclientId);        
    array_push($this->configClientIdList, $temp);
    $this->exptId=$exptId;
    $this->dayNo=$dayNo;
    $this->sessionNo=$sessionNo;
    $this->allocationGeneration = $allocationGeneration;
    // get the experiment/content models & inject accordingly
    $this->eModel = new experimentModel($exptId);
    $this->s1model=new step1Model($exptId, $dayNo, $sessionNo, $iCnt, $this->eModel);
    $this->valid=false;
    $this->evenJudgesConnected=0;
    $this->oddJudgesConnected=0;
    if ($this->createAllocations($iCnt, $allocationGeneration)) {
      $this->valid=true;
      $this->notStarted=true;
      $this->htmlBuilder=new htmlBuilder();
      $this->connectedAdminClientId = $configclientId;
      $this->connectedToAdminClient = true;
    }
//    $this->ecController = new experimentConfigurator();
  }

  //</editor-fold >

}