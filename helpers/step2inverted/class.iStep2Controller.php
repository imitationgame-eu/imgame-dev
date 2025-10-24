<?php
/**
 * Step2 Manager
 * top-level controller to configure, view & run sessions
 * @author MartinHall 
 */
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';
include_once $root_path.'/helpers/models/class.experimentModel.php';

class iStep2Controller {
  private $exptId;
  private $eModel;
  private $tabIndex = 1;
  private $focusId;
  public $logger;
  
  
  function chooseIGR($jType, $restartUID, $userCode) {
    global $igrtSqli;
    $jChosen = -1;
    $targetSql = sprintf("SELECT MIN(respCount) AS respCount FROM wt_Step2BalancerInverted WHERE exptId='%s' AND jType='%s' AND closed=0", $this->exptId, $jType);
    $targetResult = $igrtSqli->query($targetSql);
    $targetRow = $targetResult->fetch_object(); // get first of all possible ones
    $targetRespCount = $targetRow->respCount;


    $getCountQry = sprintf("SELECT * FROM wt_Step2BalancerInverted WHERE exptId='%s' AND jType='%s' AND closed=0", $this->exptId, $jType);
    $countResult = $igrtSqli->query($getCountQry);
    $countNotClosed = 0;
    $respUsedCount = 0;
    while ($notClosedRow = $countResult->fetch_object()) { 
      ++$countNotClosed; 
      if ($notClosedRow->respCount == $targetRespCount) { ++$respUsedCount; }
    }
    if ($respUsedCount == $countNotClosed) { 
      $getTargetSql = sprintf("SELECT * FROM wt_Step2BalancerInverted WHERE exptId='%s' AND jType='%s' AND closed=0",
          $this->exptId, $jType);        
    }
    else {
      $getTargetSql = sprintf("SELECT * FROM wt_Step2BalancerInverted WHERE exptId='%s' AND jType='%s' AND closed=0 AND respCount='%s'",
          $this->exptId, $jType, $targetRespCount);
    }
    $getTargetResult = $igrtSqli->query($getTargetSql);
    $getTargetRow = $getTargetResult->fetch_object();
    $targetId = $getTargetRow->id;
    $jChosen = $getTargetRow->actualJNo;
    $currentRespCount = $getTargetRow->respCount;
    $currentRespMax = $getTargetRow->respMax;
    ++$currentRespCount;
    $closedStatus = ($currentRespCount == $currentRespMax) ? 1 : 0;
    $upDateQry = sprintf("UPDATE wt_Step2BalancerInverted SET respCount='%s', closed='%s' WHERE id='%s'",
        $currentRespCount, $closedStatus, $targetId);
    $igrtSqli->query($upDateQry);
//    $attachQry = sprintf("UPDATE wt_Step2InvertedFormUIDs SET recruitmentCode='%s' WHERE id='%s'", $igrtSqli->real_escape_string($userCode), $restartUID);
//    $igrtSqli->query($attachQry);
    return $jChosen;
  }
  
  function attachLabeltoRespondent($newRespId, $finishLabel) {
    global $igrtSqli;
    $sql = sprintf("UPDATE wt_Step2pptStatusInverted SET userCode='%s' WHERE id='%s'", $finishLabel, $newRespId);
    $igrtSqli->query($sql);
  }
  
  function setPptFinished($respId) {
    global $igrtSqli;
    $sql = sprintf("UPDATE wt_Step2pptStatusInverted SET finished='1' WHERE id='%s'", $respId);
    $igrtSqli->query($sql);    
  }
  
    
  //--------------------------------------------------------------------------
  // helpers/builders
  //--------------------------------------------------------------------------
  
  function getIStep2Settings() {
    // remember - only bool and int settings are settings are sent to front=end
    // any text is injected by the viewBuilder
    $msg = sprintf("<message><messageType>istep2Settings</messageType>
      <useIS2CharacterLimit>%s</useIS2CharacterLimit>
      <iS2CharacterLimitValue>%s</iS2CharacterLimitValue>
      <useIS2NPAlignment>%s</useIS2NPAlignment>
      </message>",
        $this->eModel->useIS2CharacterLimit,
        $this->eModel->iS2CharacterLimitValue,
        $this->eModel->useIS2NPAlignment
    );
    return $msg;
  } 
  
  function getStep2PresentType($exptId) {
    global $igrtSqli;
    $getSQL = "SELECT * FROM edExptStatic_refactor WHERE exptId=$exptId";
    $getResult = $igrtSqli->query($getSQL);
    if ($getResult) {
      $row = $getResult->fetch_object();
      return $row->step2Sequential;
    }
    return 0;
  }

  function getUsePost() {
    return $this->eModel->step2PostInvert;
  }

  function getJ($exptId, $jType) {
    global $igrtSqli;
    $jList = array();
    $dayQry = "SELECT DISTINCT(dayNo) AS dayNo FROM md_dataStep1reviewed WHERE exptId=$exptId AND jType=$jType ORDER BY dayNo ASC";
    //$retDebug = $dayQry; 
    $dayResult = $igrtSqli->query($dayQry);
    if ($dayResult) {
      while ($dayRow = $dayResult->fetch_object()) {
        $sessQry = "SELECT DISTINCT(sessionNo) as sessionNo FROM md_dataStep1reviewed WHERE exptId=$exptId AND jType=$jType AND dayNo=$dayRow->dayNo ORDER BY sessionNo ASC ";
        //$retDebug .= ' : '.$sessQry;
        $sessResult = $igrtSqli->query($sessQry);
        if ($sessResult) {
          while ($sessRow = $sessResult->fetch_object()) {
            $xQry = "SELECT DISTINCT(jNo) AS jNo FROM md_dataStep1reviewed WHERE exptId=$exptId AND jType=$jType AND reviewed=1 AND canUse=1 AND sessionNo=$sessRow->sessionNo AND dayNo=$dayRow->dayNo ORDER BY jNo ASC";
            //$retDebug .= ' : '.$xQry;
            $xResult = $igrtSqli->query($xQry);
            if ($xResult) {
              $Discards = $this->getDiscards($exptId, $dayRow->dayNo, $sessRow->sessionNo, $jType);
              while ($jNoRow = $xResult->fetch_object()) {
                $jNo = $jNoRow->jNo;
                $discardMarker = pow(2, $jNo);
                if ( ($Discards & $discardMarker) == $discardMarker) {
                  //ignore this judge
                }
                else {
                  $qQry = "SELECT * FROM md_dataStep1reviewed WHERE exptId=$exptId AND jType=$jType AND reviewed=1 AND canUse=1 AND jNo=$jNo AND sessionNo=$sessRow->sessionNo AND dayNo=$dayRow->dayNo ORDER BY qNO ASC";
                  //$retDebug .= ' : '.$qQry;
                  $qResult = $igrtSqli->query($qQry);
                  if ($qResult) {
                    // questions array to attach to judge
                    $qArray = array();
                    while ($qRow = $qResult->fetch_object()) {
                      $qDef = array('qNo' => $qRow->qNo, 'jQ' => $qRow->q);
                      array_push($qArray, $qDef);
                    }
                                        // create judge entity
                    $judge = array(
                      'exptId' => $exptId,
                      'dayNo' => $dayRow->dayNo,
                      'sessionNo' => $sessRow->sessionNo,
                      'jType' => $jType,
                      'jNo' => $jNo,
                      'questions' => $qArray,                      
                    );
                    array_push($jList, $judge);
                  }
                }                
              }
            }            
          }
        }
      }
    }
    //return $retDebug;
    return $jList;
  }
  
  function getQHtml($questions) {
    $html = '';
    $rowCnt = 0;
    foreach ($questions as $q) {
      $tint = ($rowCnt % 2 == 0) ? "dark" : "light";
      $qText = $q['jQ'];
      $html .= "<div class=\"formRow $tint\">Qno (order):$rowCnt text: $qText</div>";
      ++$rowCnt;
    }
    return $html;
  }
  
  function getJHtml($exptId, $judges) {
    $html = '';
    $pNo = 1;
//    $usePre = $this->getUsePre($exptId);
//    if ($usePre) { 
//      $preDef = $this->getQDef($exptId, 0); 
//      $preHtml = $this->getPreFormHtml($exptId, $preDef);
//    }
//    $usePost = $this->getUsePost($exptId);
//    if ($usePost) { 
//      $postDef = $this->getQDef($exptId, 1); 
//      $postHtml = $this->getPostFormHtml($exptId, $postDef);
//    }
    foreach ($judges as $jDef) {
      $dayNo = $jDef['dayNo'];
      $sessionNo = $jDef['sessionNo'];
      $jNo = $jDef['jNo'];
      $jNoLabel = (int) $jNo + 1;
      $jType = $jDef['jType'];
      $questionsHtml = $this->getQHtml($jDef['questions']);
      $tint = ($pNo % 2 == 0) ? "dark" : "light";
      $xy = ($jType == 1) ? "x" : "y";
      $html .= sprintf("<h3>DataSet %s <a href=\"step2_%s_%s_%s_%s_%s\">start session using %s_day%s_session%s_j%s</a> </h3>", 
          $pNo, $exptId, $dayNo, $sessionNo, $jType, $jNo, $xy, $dayNo, $sessionNo, $jNoLabel );
      $html .= "<div class=\"formRow $tint\">";
        $html .= "<div>";
          $html .= $preHtml;
        $html .= "</div>";
        $html .= "<h2>questions</h2>";
        $html .= "<div>";
          $html .= $questionsHtml;
        $html .= "</div>";
        $html .= "<div>";
          $html .= $postHtml;
        $html .= "</div>";
      $html .= "</div>";
      ++$pNo;
    }
    return $html;
  }

  function getDiscards($exptId, $dayNo, $sessionNo, $jType) {
    global $igrtSqli;
    $retValue = 0;
    $getSummary = sprintf("SELECT * FROM wt_Step1Discards WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s'", $exptId, $dayNo, $sessionNo);
    $summaryResults = $igrtSqli->query($getSummary);
    if ($summaryResults) {
      $summaryRow = $summaryResults->fetch_object();
      $retValue = ($jType == 1) ? $summaryRow->evenDiscards : $summaryRow->oddDiscards;
    }
    return $retValue;
  }
     
  //--------------------------------------------------------------------------
  // step2 ppt session control
  //--------------------------------------------------------------------------
  
  function getIStep2Page($exptId, $jType, $respId, $qNo) {
    // remember that qNo may not be 1 for first, as questions can be discarded at step1 review
    $sessionDetails = $this->getSessionDetails($jType, $respId);
    return $this->getQuestion($exptId, $sessionDetails['dayNo'], $sessionDetails['sessionNo'], $jType, $sessionDetails['jNo'], $qNo);
  }
  
  function getIStep2RespParameters($jType, $restartUID) {
    $is2Params = $this->getIStep2StartParameters($jType, $restartUID);
    //$sessionDetails = $this->getSessionDetails($jType, $is2Params['respId']);
    //$finishLabel = $sessionDetails['label']."_".$s2Params['pptNo']."_".$userCode;
    //$this->attachLabeltoRespondent($respId, $finishLabel);
    $msg = sprintf("<message><messageType>respParameters</messageType>
      <pptNo>%s</pptNo>
      <igrChosen>%s</igrChosen>
      <respId>%s</respId>
      </message>",
      $is2Params['pptNo'], $is2Params['igrChosen'], $is2Params['respId']);          
    return $msg;
  }
  
  function getSessionDetails($jType, $respId) {
    global $igrtSqli;
    $sql = sprintf("SELECT * FROM wt_Step2pptStatusInverted WHERE id='%s'", $respId);
    $sdResult = $igrtSqli->query($sql);
    $sdRow = $sdResult->fetch_object();
    $actualJNo = $sdRow->actualJNo;
    $isdSql = sprintf("SELECT * FROM wt_Step2BalancerInverted WHERE exptId='%s' AND jType='%s' AND actualJNo='%s'", $this->exptId, $jType, $actualJNo);
    $isdResult = $igrtSqli->query($isdSql);
    $isdRow = $isdResult->fetch_object();
    $retArray = array('igrNo'=>$actualJNo, 'jNo' => $isdRow->jNo, 'dayNo' => $isdRow->dayNo, 'sessionNo'=> $isdRow->sessionNo, 'label' => $isdRow->label);
    return $retArray;
  }
  
  function getQuestion($exptId, $dayNo, $sessionNo, $jType, $jNo, $qNo) {
    global $igrtSqli;
    // get  #real# questions - dataQNo may not equal logical qNo due to discards
    $qQry = "SELECT * FROM md_dataStep1reviewed WHERE exptId=$exptId AND reviewed=1 AND canUse=1 AND dayNo=$dayNo AND sessionNo=$sessionNo AND jType=$jType AND jNo=$jNo ORDER BY qNo ASC" ;
    //echo $qQry;
    $qResult = $igrtSqli->query($qQry);
		$qList = [];
		$qPtr = 1;
    if ($qResult) {
			while ($qRow = $qResult->fetch_object()) {
				$qDef = [
					'jQ' => $qRow->q,
					'qNo' => $qPtr,
					'dataQNo' => $qRow->qNo
				];
				++$qPtr;
				array_push($qList, $qDef);
			}
    }
		if ($qNo == count($qList)) {
			return ['s2turn'=>false];
		}
		else {
		$currentQ = $qList[$qNo];
    $formattedQ = '';
    $paraJ = explode('\n', $currentQ['jQ']);
    foreach ($paraJ as $jp) {
      $formattedQ.= '<p>'.$jp.'</p>';
    }
			return [
				's2turn' => true, 
				's2q' => $formattedQ,
				'dataQNo'=> $currentQ['dataQNo'],
				'qNo' => $qNo
			];			
		}
  }
  
  function getIStep2StartParameters($jType, $restartUID) {
    global $igrtSqli;
    $html = '';
    $pptNo = 0;
    $igrChosen = $this->chooseIGR($jType, $restartUID);
    // not needed as incremented in ->chooseIGR
//    $incSql = sprintf("UPDATE wt_Step2BalancerInverted SET respCount=respCount+1 WHERE exptId='%s' AND jType='%s' AND actualJNo='%s'",
//              $this->exptId, $jType, $igrChosen);
//    $igrtSqli->query($incSql);
    $maxSql = sprintf("SELECT MAX(respNo) AS pptPtr FROM wt_Step2pptStatusInverted WHERE exptId='%s' AND jType='%s' AND actualJNo='%s'", $exptId, $jType, $igrChosen);
    $maxResult = $igrtSqli->query($maxSql);
    if ($maxResult) {
      $maxRow = $maxResult->fetch_object();
      $pptNo = $maxRow->pptPtr;
      ++$pptNo;
    }
    $insSql = sprintf("INSERT INTO wt_Step2pptStatusInverted (exptId, jType, actualJNo, respNo, finished, chrono, userCode, restartUID) "
        . "VALUES('%s', '%s', '%s', '%s', '0', NOW(),'%s' ,'%s')",
        $this->exptId, $jType, $igrChosen, $pptNo, $userCode, $restartUID);
    $igrtSqli->query($insSql);
    $respId = $igrtSqli->insert_id;
    $s2params = array('pptNo'=> $pptNo, 'respId'=>$respId, 'igrChosen'=>$igrChosen);
    return $s2params;
  }
 
  function storeIStep2Reply($exptId, $jType, $pptNo, $respId, $qNo, $pReply, $alignment, $isAligned, $correctedReply) {
    global $igrtSqli;
    $reply = urldecode($pReply);
    $db_reply = $igrtSqli->real_escape_string($reply);
    $i_correctedReply = urldecode($correctedReply);
    $db_correctedReply = $igrtSqli->real_escape_string($i_correctedReply);
    $sessionDetails = $this->getSessionDetails($jType, $respId);
    $dayNo = $sessionDetails['dayNo'];
    $sessionNo = $sessionDetails['sessionNo'];
    $jNo = $sessionDetails['jNo'];
    $insertQry = "INSERT INTO dataSTEP2inverted (uid, exptId, dayNo, sessionNo, 
                  chrono, jType, jNo, qNo, reply, pptNo, hasAlignmentData, isAligned, correctedReply)
                  VALUES ($respId, $exptId, $dayNo, $sessionNo, NOW(), $jType, $jNo, "
        . "$qNo, '$db_reply', $pptNo, $alignment, $isAligned, '$db_correctedReply')";
    $igrtSqli->query($insertQry);
    //echo $insertQry;
    ++$qNo;
    $s2Array = $this->getQuestion($exptId, $dayNo, $sessionNo, $jType, $jNo, $qNo);
    if (!$s2Array["s2turn"]) {
      $this->setPptFinished($respId);      
    }
    return $s2Array;
  }

  //--------------------------------------------------------------------------
  // constructor and initialisation
  //--------------------------------------------------------------------------   
    
  function __construct($exptId) {
    global $igrtSqli;
    $this->exptId = $exptId;
    $this->eModel = new experimentModel($exptId);
    $this->tabIndex = 1;   // 
  }
}

