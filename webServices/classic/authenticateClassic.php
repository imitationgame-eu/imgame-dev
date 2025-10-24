<?php
  ini_set('display_errors', 'On');
  error_reporting(E_ALL); 
  if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
  include_once $root_path.'/domainSpecific/mySqlObject.php';     
  include_once $root_path.'/helpers/models/class.experimentModel.php';
  $exptId = -1;
  $email=$_POST["username"];
  $pw=$_POST["password"];
  $hash_str=hash('sha1',$pw);
  $userDef=[
    'success'=>'unconnected',
    'email'=>$email,
    'uid'=>'',
    'permissions'=>'',
    'isActive'=>'0',
    'exptId'=>'',
    'dayNo'=>'',
    'groupNo'=> '',
    'qNo'=> '0',
    'sessionNo'=>'',
    'exptType'=>'',
    'jState'=>'',
    'respState'=>'',
    'role'=>'.',
    'jType'=>'.',
  ];
  $sqlQry_Login=sprintf("SELECT * FROM igUsers WHERE email='%s' AND password='%s'",$email,$hash_str);
  $loginResult=$igrtSqli->query($sqlQry_Login);
  if ($loginResult) {
    $row=$loginResult->fetch_object();
    $userDef['uid']=$row->id;
    $userDef['permissions'] = $row->permissions;
    $userDef['success']='logged-in!';
    // see if in active classic session, and set parameters accordingly
    $sqlQry_Session=sprintf("SELECT * FROM igActiveClassicUsers WHERE uid='%s'",$row->id);
    $activeResult=$igrtSqli->query($sqlQry_Session);
    if ($activeResult) {
      $sessionRow=$activeResult->fetch_object();
      $exptId = $sessionRow->exptId;
      $userDef['exptId']=$sessionRow->exptId;
      $userDef['dayNo']=$sessionRow->dayNo;
      $userDef['sessionNo']=$sessionRow->sessionNo; 
      $userDef['role'] = $sessionRow->role;
      $userDef['groupNo'] = $sessionRow->groupNo;
			$userDef['qNo'] = $sessionRow->qNo;
			$userDef['isActive']='1';
			$userDef['jState'] = $sessionRow->jState;
			$userDef['respState'] = $sessionRow->respState;
			$userDef['exptType']='classic';
      if ($userDef['role'] == "J") {
      	// check whether the qNo needs incrementing if the turn is finished
				$turnFinished = true;
				$groupQry = sprintf("SELECT * FROM igActiveClassicUsers WHERE groupNo='%s' AND dayNo='%s' AND sessionNo='%s'",$userDef['groupNo'],$userDef['dayNo'],$userDef['sessionNo'] );
				$groupResult=$igrtSqli->query($groupQry);
				if ($groupResult) {
					while ($groupRow = $activeResult->fetch_object()) {
						if ($groupRow->qNo != $userDef['qNo']) { $turnFinished = false; }
					}
				}
				if ($turnFinished) { ++$userDef['qNo']; }
			}
    }
  }
  $eModel = new experimentModel($exptId);
  $xml=sprintf("<message><messageType>loginResults</messageType>"
      . "<success>%s</success>"
      . "<email>%s</email>"
      . "<uid>%s</uid>"
      . "<permissions>%s</permissions>"
      . "<isActive>%s</isActive>"
      . "<exptId>%s</exptId>"
      . "<dayNo>%s</dayNo>"
      . "<sessionNo>%s</sessionNo>"
      . "<jType>%s</jType>"
      . "<exptType>%s</exptType>"
      . "<role>%s</role>"
      . "<groupNo>%s</groupNo>"
      . "<qNo>%s</qNo>"
      . "<jState>%s</jState>"
      . "<respState>%s</respState>"
      . "<rFinalMsg>%s</rFinalMsg>"
      . "<rYourAnswer>%s</rYourAnswer>"
      . "<rHistoryTitle>%s</rHistoryTitle>"
      . "<npTab>%s</npTab>"
      . "<pTab>%s</pTab>"
      . "<rTabActive>%s</rTabActive>"
      . "<rTabInactive>%s</rTabInactive>"
      . "<rTabWaiting>%s</rTabWaiting>"
      . "<rWaitNext>%s</rWaitNext>"
      . "<rWaitFirst>%s</rWaitFirst>"
      . "<rCurrentQ>%s</rCurrentQ>"
      . "<rInstruction>%s</rInstruction>"
      . "<rSendB>%s</rSendB>"
      . "<rGuidanceHeader>%s</rGuidanceHeader>"
      . "<npGuidance>%s</npGuidance>"
      . "<pGuidance>%s</pGuidance>"
      . "<rTabDone>%s</rTabDone>"
      . "<jTab>%s</jTab>"
      . "<jTabUnconnected>%s</jTabUnconnected>"
      . "<jTabWaiting>%s</jTabWaiting>"
      . "<jTabActive>%s</jTabActive>"
      . "<jTabRating>%s</jTabRating>"
      . "<jTabDone>%s</jTabDone>"
      . "<jWaitingToStart>%s</jWaitingToStart>"
      . "<jPleaseAsk>%s</jPleaseAsk>"
      . "<jAskButton>%s</jAskButton>"
      . "<jWaitingForReplies>%s</jWaitingForReplies>"
      . "<jHistoryTitle>%s</jHistoryTitle>"
      . "<jRatingTitle>%s</jRatingTitle>"
      . "<jFinalRatingTitle>%s</jFinalRatingTitle>"
      . "<jRatingYourQuestion>%s</jRatingYourQuestion>"
      . "<jRatingQ>%s</jRatingQ>"
      . "<jRatingR1>%s</jRatingR1>"
      . "<jRatingR2>%s</jRatingR2>"
      . "<jAskAnotherB>%s</jAskAnotherB>"
      . "<jNoMoreB>%s</jNoMoreB>"
      . "<jSaveFinalB>%s</jSaveFinalB>"
      . "<jFinalMsg>%s</jFinalMsg>"
      . "<jConfirmHead>%s</jConfirmHead>"
      . "<jConfirmBody>%s</jConfirmBody>"
      . "<jConfirmOK>%s</jConfirmOK>"
		. "<jConfirmCancel>%s</jConfirmCancel>"
		. "<randomiseSideS1>%s</randomiseSideS1>"
		. "<s1IntentionLabel>%s</s1IntentionLabel>"
		. "<useS1Intention>%s</useS1Intention>"
		. "<useS1IntentionMin>%s</useS1IntentionMin>"
		. "<s1IntentionMin>%s</s1IntentionMin>"
		. "<useS1AlignmentControl>%s</useS1AlignmentControl>"
		. "<useS1MinQuestionLimit>%s</useS1MinQuestionLimit>"
		. "<s1MinQuestionLimit>%s</s1MinQuestionLimit>"
	  . "<s1IntentionMinLabel>%s</s1IntentionMinLabel>"
	  . "<s1QuestionMinLabel>%s</s1QuestionMinLabel>"
	  . "<useS1MinQuestionCount>%s</useS1MinQuestionCount>"
	  . "<s1MinQuestionCount>%s</s1MinQuestionCount>"
      . "</message>",
      $userDef['success'],
      $userDef['email'],
      $userDef['uid'],
      $userDef['permissions'],
      $userDef['isActive'],
      $userDef['exptId'],
      $userDef['dayNo'],
      $userDef['sessionNo'],
      $userDef['jType'],
      $userDef['exptType'],
      $userDef['role'],
      $userDef['groupNo'],
      $userDef['qNo'],
      $userDef['jState'],
      $userDef['respState'],
      $eModel->rFinalMsg > '' ? $eModel->rFinalMsg : ".",
		$eModel->rYourAnswer > '' ? $eModel->rYourAnswer : ".",
      $eModel->rHistoryTitle > '' ? $eModel->rHistoryTitle : ".",
      $eModel->npTab > '' ? $eModel->npTab : ".",
      $eModel->pTab > '' ? $eModel->pTab : ".",
      $eModel->rTabActive > '' ? $eModel->rTabActive : ".",
      $eModel->rTabInactive > '' ? $eModel->rTabInactive : ".",
      $eModel->rTabWaiting > '' ? $eModel->rTabWaiting : ".",
      $eModel->rWaitNext > '' ? $eModel->rWaitNext : ".",
      $eModel->rWaitFirst > '' ? $eModel->rWaitFirst : ".",
      $eModel->rCurrentQ > '' ? $eModel->rCurrentQ : ".",
      $eModel->rInstruction > '' ? $eModel->rInstruction : ".",
      $eModel->rSendB > '' ? $eModel->rSendB : ".",
      $eModel->rGuidanceHeader > '' ? $eModel->rGuidanceHeader : ".",
      $eModel->npGuidance > '' ? $eModel->npGuidance : ".",
      $eModel->pGuidance > '' ? $eModel->pGuidance : ".",
      $eModel->rTabDone > '' ? $eModel->rTabDone : ".",
      $eModel->jTab > '' ? $eModel->jTab : ".",
      $eModel->jTabUnconnected > '' ? $eModel->jTabUnconnected : ".",
      $eModel->jTabWaiting > '' ? $eModel->jTabWaiting : ".",
      $eModel->jTabActive > '' ? $eModel->jTabActive : ".",
      $eModel->jTabRating > '' ? $eModel->jTabRating : ".",
      $eModel->jTabDone > '' ? $eModel->jTabDone : ".",
      $eModel->jWaitingToStart > '' ? $eModel->jWaitingToStart : ".",
      $eModel->jPleaseAsk > '' ? $eModel->jPleaseAsk : ".",
      $eModel->jAskButton > '' ? $eModel->jAskButton : ".",
      $eModel->jWaitingForReplies > '' ? $eModel->jWaitingForReplies : ".",
      $eModel->jHistoryTitle > '' ? $eModel->jHistoryTitle : ".",
      $eModel->jRatingTitle > '' ? $eModel->jRatingTitle : ".",
      $eModel->jFinalRatingTitle > '' ? $eModel->jFinalRatingTitle : ".",
      $eModel->jRatingYourQuestion > '' ? $eModel->jRatingYourQuestion : ".",
      $eModel->jRatingQ > '' ? $eModel->jRatingQ : ".",
      $eModel->jRatingR1 > '' ? $eModel->jRatingR1 : ".",
      $eModel->jRatingR2 > '' ? $eModel->jRatingR2 : ".",
      $eModel->jAskAnotherB > '' ? $eModel->jAskAnotherB : ".",
      $eModel->jNoMoreB > '' ? $eModel->jNoMoreB : ".",
      $eModel->jSaveFinalB > '' ? $eModel->jSaveFinalB : ".",
		$eModel->jFinalMsg > '' ? $eModel->jFinalMsg : ".",
		$eModel->jConfirmHead > '' ? $eModel->jConfirmHead : ".",
		$eModel->jConfirmBody > '' ? $eModel->jConfirmBody : ".",
		$eModel->jConfirmOK > '' ? $eModel->jConfirmOK : ".",
		$eModel->jConfirmCancel > '' ? $eModel->jConfirmCancel : ".",
		$eModel->randomiseSideS1,
		$eModel->s1IntentionLabel > '' ? $eModel->s1IntentionLabel : ".",
		$eModel->useS1Intention,
		$eModel->useS1IntentionMin,
		$eModel->s1IntentionMin,
		$eModel->useS1AlignmentControl,
		$eModel->useS1MinQuestionLimit,
		$eModel->s1MinQuestionLimit,
	  $eModel->s1IntentionMinLabel > '' ? $eModel->s1IntentionMinLabel : ".",
	  $eModel->s1QuestionMinLabel > '' ? $eModel->s1QuestionMinLabel : ".",
		$eModel->s1barbilliardControl > '' ? $eModel->s1barbilliardControl : ".",
		$eModel->s1QuestionCountAlternative > '' ? $eModel->s1QuestionCountAlternative : "."
  );
  echo $xml;

