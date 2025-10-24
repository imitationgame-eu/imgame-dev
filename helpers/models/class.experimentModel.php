<?php
/**
 * experiment model presents all experiment settings and content 
 * @author mh
 */

class experimentModel {
  // <editor-fold defaultstate="collapsed" desc=" global experiment settings">
  public $ownerId;                
  public $exptId;                 
  public $clientId;               
  public $title;
  public $isInactive;
  public $s1srcExptId;
  public $isInjected;
  public $injectedS1Flag;
  public $isClassic;
  public $useAutoLogins;
  public $exptSubject;
  public $language;
  public $location;
  public $country;
  public $evenS1Label;
  public $oddS1Label;
  public $description;
  public $canClone;
  // </editor-fold>

  // <editor-fold defaultstate="collapsed" desc=" S1 settings from edExptStatic_refactor">
  public $useS1Intention;
  public $useS1IntentionMin;
  public $s1IntentionMin;
	public $useS1AlignmentControl;
	public $useS1MinQuestionLimit;
	public $s1MinQuestionLimit;
  public $useS1QCategoryControl;
  public $s1NoCategories;
  public $s1usersSet;
  public $randomiseSideS1;
	public $s1barbilliardControl;
	public $s1QuestionCountAlternative;  
  public $noJudges;
  public $extraJ;
  public $extraNP;
  public $extraP;
  public $useRating;              // does the judge need to make ratings?
  public $useLikert;              // use a rating?
  public $noLikert;               // # of points
  public $useReasons;             // use a reason textarea?
  public $useFinalRating;
  public $useReasonFinalRating;
  public $useReasonCharacterLimit;
  public $reasonCharacterLimitValue;
  public $useReasonCharacterLimitF;
  public $reasonCharacterLimitValueF;
  public $useFinalLikert;
  public $noFinalLikert;
  public $noDays;
  public $noSessions;             
  public $choosingNP;
  public $appendITypetoS1AlignmentNoneLabel;
  public $appendITypetoS1AlignmentPartlyLabel;
  public $appendITypetoS1AlignmentMostlyLabel;
  public $appendITypetoS1AlignmentCompletelyLabel;
  public $appendITypetoS1AlignmentExtraLabel;
  public $s1giveFeedback;
  public $s1giveFeedbackFinal;
  public $s1feedbackTime;
  public $s1runningScore;
  public $useS1AlignmentNoneLabel;
  public $useS1AlignmentPartlyLabel;
  public $useS1AlignmentMostlyLabel;
  public $useS1AlignmentCompletelyLabel;
  public $useS1AlignmentExtraLabel;
  public $useS1AlignmentAsRB ;
  public $s1PercentForWinFeedbackFinal;
  
  // </editor-fold>

  // <editor-fold defaultstate="collapsed" desc=" Step2 settings from edExptStatic_refactor">
  public $step2EvenConfigured;
  public $step2OddConfigured;
  public $step2InvertedEvenConfigured;
  public $step2InvertedOddConfigured;
  public $s2srcExptId;
  public $s2invertedsrcExptId;
  public $useStep2ReplyCalculation;
  public $useOddInvertedS2;
  public $useEvenInvertedS2;  
  public $useS2CharacterLimit;
  public $s2CharacterLimitValue;
  public $useIS2CharacterLimit;
  public $iS2CharacterLimitValue;
  public $useS2PAlignment;
  public $useIS2NPAlignment;
  public $step2Sequential;

  // </editor-fold>

  // <editor-fold defaultstate="collapsed" desc=" Step4 settings from edExptStatic_refactor">
  public $useS4CharacterLimit;
  public $s4CharacterLimitValue;  
  public $s4RandomiseSide;
  public $useS4IndividualTurn;
  public $useS4Intention;
  public $useS4IntentionMin;
  public $s4IntentionMin;
  public $useS4AlignmentControl;
  public $useS4QCategoryControl;
  public $s4NoCategories;
  public $s4_reasonCharacterLimitValue;
  public $step4Sequential;

  // </editor-fold>
  
  // <editor-fold defaultstate="collapsed" desc=" survey/form settings">
  public $step1ConsentForm;
  public $step1RecruitForm;
  public $step1PreForm;
  public $step1PostForm;
  public $step2ConsentForm;
  public $step2RecruitForm;
  public $step2PreForm;
  public $step2PostForm;
  public $step2PreInvert;       
  public $step2PostInvert;
  public $step4ConsentForm;
  public $step4RecruitForm;
  public $step4PreForm;
  public $step4PostForm;  
  // </editor-fold>
  
  // <editor-fold defaultstate="collapsed" desc=" S1 text and labels from edContentDefs_refactor">

  public $labelChoice;
  public $instLikert;
  public $labelReasons;
  public $instExtraLikert;
  public $labelFinalRating;
  public $labelChoiceFinalRating;
  public $labelReasonFinalRating;
  public $instFinalLikert;
  public $reasonGuidance;
  public $reasonGuidanceF;
  public $labelRating;
  public $s1IntentionLabel;
  public $s1IntentionMinLabel;
	public $s1QuestionMinLabel;
  public $s1AlignmentNoneLabel;
  public $s1AlignmentPartlyLabel;
  public $s1AlignmentMostlyLabel;
  public $s1AlignmentCompletelyLabel;
  public $s1AlignmentExtraLabel;
  public $s1AlignmentLabel;
  public $s1CategoryLabel;
  public $s1correctFB;
  public $s1incorrectFB;
  public $s1runningScoreDividerLabel;
  public $s1runningScoreLabel;
  public $s1WinFeedbackLabel;
  public $s1LoseFeedbackLabel;
  
  // </editor-fold>
    
  // <editor-fold defaultstate="collapsed" desc=" S1 Interrogator & Respondent labels, used in other Steps">
  public $jTab; 
  public $jTabUnconnected; 
  public $jTabWaiting;
  public $jTabActive; 
  public $jTabRating;  
  public $jTabDone;  
  public $jWaitingToStart; 
  public $jPleaseAsk;  
  public $jAskButton;  
  public $jWaitingForReplies;  
  public $jHistoryTitle; 
  public $jRatingTitle;  
  public $jFinalRatingTitle;
  public $jRatingYourQuestion;
  public $jRatingQ;
  public $jRatingR1;
  public $jRatingR2;
  public $jAskAnotherB;
  public $jNoMoreB;
  public $jSaveFinalB;
  public $jFinalMsg;
  public $jConfirmHead;
  public $jConfirmBody;
  public $jConfirmOK;
  public $jConfirmCancel;
  public $npTab;
  public $pTab;
  public $rTabInactive;
  public $rTabActive;
  public $rTabWaiting;
  public $rTabDone;
  public $rWaitFirst;
  public $rWaitNext;
  public $rHistoryTitle;
  public $rCurrentQ;
  public $rInstruction;
  public $rSendB;
  public $rGuidanceHeader;
  public $rYourAnswer;
  public $rFinalMsg;
  public $npGuidance;
  public $pGuidance;
  // </editor-fold>

  // <editor-fold defaultstate="collapsed" desc=" S2 messages & labels">
  
  public $step2_startMsg;
  public $step2_startBLabel;
  public $step2_finalMsg;
  public $step2_closedMsg;
  public $step2_replyMsg;
  public $step2_endBLabel;
  public $step2_invertedStartMsg;
  public $step2_invertedStartBLabel;
  public $step2_invertedFinalMsg;
  public $step2_invertedClosedMsg;
  public $step2_invertedReplyMsg;
  public $step2_invertedEndBLabel;
  public $s2PAlignmentLabel;
  public $s2PYesLabel;
  public $s2PNoLabel;
  public $s2ContinueLabel;
  public $s2CorrectedAnswerLabel;
  public $iS2NPAlignmentLabel;
  public $iS2NPYesLabel;
  public $iS2NPNoLabel;
  public $iS2ContinueLabel;
  public $iS2CorrectedAnswerLabel;
  public $step2_sendBLabel;
  public $step2_invertedSendBLabel;
  public $step2_ReplyLimitGuidance;
  public $istep2_ReplyLimitGuidance;
  
  // </editor-fold>
  
  // <editor-fold defaultstate="collapsed" desc=" S4 messages & labels">
  public $step4_judgeNumberMsg;
  public $step4_startMsg;
  public $step4_startBLabel;
  public $step4_closedMsg;
  public $step4_finalMsg;
  public $step4_nextBLabel;
  public $step4_reasonMsg;
  public $step4_reasonGuidance;
  public $step4_endBLabel;
  public $s4QCategoryLabel;
  public $step4IntentionLimitGuidance;
  public $s4IntentionLabel;
  public $s4AlignmentLabel;
  public $s4AlignmentNoneLabel;
  public $s4AlignmentPartlyLabel;
  public $s4AlignmentMostlyLabel;
  public $s4AlignmentCompletelyLabel;
  // </editor-fold>
  
  // <editor-fold defaultstate="collapsed" desc="formReady, arrays of labels etc">

  public $formsCollection;
  public $step1ConsentFormReady;
  public $step1RecruitFormReady;
  public $step1PreFormReady;
  public $step1PostFormReady;
  public $step2ConsentFormReady;
  public $step2RecruitFormReady;
  public $step2PreFormReady;
  public $step2PostFormReady;
  public $step4ConsentFormReady;
  public $step4RecruitFormReady;
  public $step4PreFormReady;
  public $step4PostFormReady;
  public $step2PreInvertReady;
  public $step2PostInvertReady;
  public $labelLikert = [];
  public $labelFinalLikert = [];
  public $days = [];
  
  public $s1AlignmentCategoryLabels = [];
  public $s2AlignmentCategoryLabels = [];
  public $is2AlignmentCategoryLabels = [];
  public $s4AlignmentCategoryLabels = [];
  // </editor-fold>
 
  // <editor-fold defaultstate="collapsed" desc=" step1 content/message XML">

  function buildContentXML($mt, $randomiseSideS1) {
    $xml=sprintf("<message><messageType>%s</messageType>",$mt);
      $xml.=sprintf("<jTab><![CDATA[%s]]></jTab> ",$this->jTab );
      $xml.=sprintf("<jTabUnconnected><![CDATA[%s]]></jTabUnconnected> ",$this->jTabUnconnected );
      $xml.=sprintf("<jTabWaiting><![CDATA[%s]]></jTabWaiting> ",$this->jTabWaiting );
      $xml.=sprintf("<jTabActive><![CDATA[%s]]></jTabActive> ",$this->jTabActive );
      $xml.=sprintf("<jTabRating><![CDATA[%s]]></jTabRating> ",$this->jTabRating );
      $xml.=sprintf("<jTabDone><![CDATA[%s]]></jTabDone> ",$this->jTabDone );
      $xml.=sprintf("<jWaitingToStart><![CDATA[%s]]></jWaitingToStart> ",$this->jWaitingToStart );
      $xml.=sprintf("<jPleaseAsk><![CDATA[%s]]></jPleaseAsk> ",$this->jPleaseAsk );
      $xml.=sprintf("<jAskButton><![CDATA[%s]]></jAskButton> ",$this->jAskButton);
      $xml.=sprintf("<jWaitingForReplies><![CDATA[%s]]></jWaitingForReplies> ",$this->jWaitingForReplies );
      $xml.=sprintf("<jHistoryTitle><![CDATA[%s]]></jHistoryTitle> ",$this-> jHistoryTitle);
      $xml.=sprintf("<jRatingTitle><![CDATA[%s]]></jRatingTitle> ",$this->jRatingTitle );
      $xml.=sprintf("<jFinalRatingTitle><![CDATA[%s]]></jFinalRatingTitle> ",$this->jFinalRatingTitle );
      $xml.=sprintf("<jRatingYourQuestion><![CDATA[%s]]></jRatingYourQuestion> ",$this->jRatingYourQuestion );
      $xml.=sprintf("<jRatingQ><![CDATA[%s]]></jRatingQ> ", $this->jRatingQ );
      $xml.=sprintf("<jRatingR1><![CDATA[%s]]></jRatingR1> ",$this->jRatingR1 );
      $xml.=sprintf("<jRatingR2><![CDATA[%s]]></jRatingR2> ",$this->jRatingR2);
      $xml.=sprintf("<jAskAnotherB><![CDATA[%s]]></jAskAnotherB> ",$this->jAskAnotherB );
      $xml.=sprintf("<jNoMoreB><![CDATA[%s]]></jNoMoreB> ",$this->jNoMoreB );
      $xml.=sprintf("<jSaveFinalB><![CDATA[%s]]></jSaveFinalB> ", $this->jSaveFinalB );
      $xml.=sprintf("<jFinalMsg><![CDATA[%s]]></jFinalMsg>", $this->jFinalMsg );
      $xml.=sprintf("<jConfirmHead><![CDATA[%s]]></jConfirmHead> ", $this->jConfirmHead );
      $xml.=sprintf("<jConfirmBody><![CDATA[%s]]></jConfirmBody>", $this->jConfirmBody );
      $xml.=sprintf("<jConfirmOK><![CDATA[%s]]></jConfirmOK> ", $this->jConfirmOK );
      $xml.=sprintf("<jConfirmCancel><![CDATA[%s]]></jConfirmCancel>", $this->jConfirmCancel );
      $xml.=sprintf("<npTab><![CDATA[%s]]></npTab> ",$this->npTab );
      $xml.=sprintf("<pTab><![CDATA[%s]]></pTab> ",$this->pTab);
      $xml.=sprintf("<rTabInactive><![CDATA[%s]]></rTabInactive> ",$this->rTabInactive);
      $xml.=sprintf("<rTabActive><![CDATA[%s]]></rTabActive> ",$this->rTabActive);
      $xml.=sprintf("<rTabWaiting><![CDATA[%s]]></rTabWaiting> ",$this->rTabWaiting );
      $xml.=sprintf("<rTabDone><![CDATA[%s]]></rTabDone> ",$this->rTabDone);
      $xml.=sprintf("<rWaitFirst><![CDATA[%s]]></rWaitFirst> ",$this->rWaitFirst);
      $xml.=sprintf("<rWaitNext><![CDATA[%s]]></rWaitNext> ",$this->rWaitNext);
      $xml.=sprintf("<rHistoryTitle><![CDATA[%s]]></rHistoryTitle> ",$this->rHistoryTitle );
      $xml.=sprintf("<rCurrentQ><![CDATA[%s]]></rCurrentQ> ",$this->rCurrentQ );
      $xml.=sprintf("<rYourAnswer><![CDATA[%s]]></rYourAnswer> ",$this->rYourAnswer );
      $xml.=sprintf("<rInstruction><![CDATA[%s]]></rInstruction> ",$this->rInstruction);
      $xml.=sprintf("<rSendB><![CDATA[%s]]></rSendB> ",$this-> rSendB );
      $xml.=sprintf("<rGuidanceHeader><![CDATA[%s]]></rGuidanceHeader> ",$this->rGuidanceHeader);
      $xml.=sprintf("<npGuidance><![CDATA[%s]]></npGuidance> ",$this->npGuidance);
      $xml.=sprintf("<pGuidance><![CDATA[%s]]></pGuidance> ",$this->pGuidance);
      $xml.=sprintf("<rFinalMsg><![CDATA[%s]]></rFinalMsg>",$this->rFinalMsg );
      $xml.=sprintf("<randomiseSideS1>%s</randomiseSideS1>", $randomiseSideS1 );
      $xml.=sprintf("<intentionLabel><![CDATA[%s]]></intentionLabel>",$this->s1IntentionLabel );
		$xml.=sprintf("<intentionMinValue><![CDATA[%s]]></intentionMinValue>",$this->s1IntentionMin );
		$xml.=sprintf("<intentionGuidance><![CDATA[%s]]></intentionGuidance>",$this->s1IntentionMin );
    $xml.='</message>';
    return $xml;
}

  function sendContentXML($randomiseSideS1) {
    return $this->buildContentXML('contentDef', $randomiseSideS1);
  }

  function reSendContentXML($randomiseSideS1) {
    return $this->buildContentXML('contentUpdate', $randomiseSideS1);
  }

  // </editor-fold>
  
  // <editor-fold defaultstate="collapsed" desc=" internal functions">
  
  function getSaveGlobalsQry() {
//      languageId = '%s',
//      ownerId='%s',
//      title='%s',
//      inActive='%s',
//      s1srcExptId='%s',
//      injectedFlag='%s',
//      injectedS1Flag='%s',
//      isClassic='%s',
//      useAutoLogins='%s',
//      exptSubject='%s',
//      location='%s',
//      country='%s',
//      evenS1Label='%s',
//      oddS1Label='%s',
//      description='%s',
//      canClone='%s',
//      step2EvenConfigured = '%s',
//      step2OddConfigured = '%s',
//      step2InvertedEvenConfigured = '%s',
//      step2InvertedOddConfigured = '%s',
//      s2srcExptId = '%s',
//      s2invertedsrcExptId = '%s',
//      $this->ownerId,
//      $this->isClassic,
//      $this->useAutoLogins,
//      $this->s1usersSet,
//      $this->exptSubject,
//      $this->location,
//      $this->country,
//      $this->noJudges,
//      $this->extraJ,
//      $this->extraNP,
//      $this->extraP,
//      $this->canClone,
    
  }
  
  function getSaveSettingsQry() {
    $qry = sprintf("UPDATE edExptStatic_refactor 
      SET      
      useS1Intention='%s',
      useS1IntentionMin='%s',
      s1IntentionMin='%s',
      useS1AlignmentControl='%s',
      useS1QCategoryControl='%s',
      s1NoCategories='%s',
      s1usersSet = '%s',
      randomiseSideS1='%s',
      s1barbilliardControl='%s',
      s1QuestionCountAlternative='%s'
      noJudges='%s',
      extraJ='%s',
      extraNP='%s',
      extraP='%s',
      useRating='%s',
      useLikert='%s',
      noLikert='%s',
      useReasons='%s',
      useFinalRating='%s',
      useReasonFinalRating='%s',
      useReasonCharacterLimit='%s',
      reasonCharacterLimitValue='%s',
      useReasonCharacterLimitF='%s',
      reasonCharacterLimitValueF='%s',
      useFinalLikert='%s',
      noFinalLikert='%s',
      noDays='%s',
      noSessions='%s',
      choosingNP = '%s'

      useStep2ReplyCalculation='%s',
      useOddInvertedS2='%s',
      useEvenInvertedS2='%s',
      useS2CharacterLimit = '%s',
      s2CharacterLimitValue = '%s',
      useIS2ChracterLimit = '%s',
      iS2CharacterLimitValue = '%s',
      useS2PAlignment='%s',
      useIS2NPAlignment='%s',
      step2Sequential = '%s',

      useS4CharacterLimit = '%s',
      s4CharacterLimitValue = '%s',
      s4RandomiseSide='%s',
      useS4IndividualTurn='%s',
      useS4Intention='%s',
      useS4IntentionMin='%s',
      s4IntentionMin='%s',
      useS4AlignmentControl='%s',
      useS4QCategoryControl='%s',
      s4NoCategories='%s',
      s4_reasonCharacterLimitValue = '%s',
      step4Sequential = '%s',
      
      step1ConsentForm='%s',
      step1RecruitForm='%s',
      step1PreForm='%s',
      step1PostForm='%s',
      step2ConsentForm='%s',
      step2RecruitForm='%s',
      step2PreForm='%s',
      step2PostForm='%s',
      step2PreInvert='%s',
      step2PostInvert='%s',
      step4ConsentForm='%s',
      step4RecruitForm='%s',
      step4PreForm='%s',
      step4PostForm='%s',

      WHERE exptId='%s'",
      $this->useS1Intention,
      $this->useS1IntentionMin,
      $this->s1IntentionMin,
      $this->useS1AlignmentControl,
      $this->useS1QCategoryControl,
      $this->s1NoCategories,
      $this->s1usersSet,
      $this->randomiseSideS1,
      $this->s1barbilliardControl,
      $this->s1QuestionCountAlternative,
      $this->noJudges,
      $this->extraJ,
      $this->extraNP,
      $this->extraP,
      $this->useRating,
      $this->useLikert,
      $this->noLikert,
      $this->useReasons,
      $this->useFinalRating,
      $this->useReasonFinalRating,
      $this->useReasonCharacterLimit,
      $this->reasonCharacterLimitValue,
      $this->useReasonCharacterLimitF,
      $this->reasonCharacterLimitValueF,
      $this->useFinalLikert,
      $this->noFinalLikert,        
      $this->noDays,
      $this->noSessions,
      $this->choosingNP,
      $this->useStep2ReplyCalculation,
      $this->useOddInvertedS2,
      $this->useEvenInvertedS2,
      $this->useS2CharacterLimit,
      $this->s2CharacterLimitValue,
      $this->useIS2CharacterLimit,
      $this->iS2CharacterLimitValue,
      $this->useS2PAlignment,
      $this->useIS2NPAlignment,
      $this->step2Sequential,

        $this->useS4CharacterLimit,
        $this->s4CharacterLimitValue,
        $this->s4RandomiseSide,
        $this->useS4IndividualTurn,
        $this->useS4Intention,
        $this->useS4IntentionMin,
        $this->s4IntentionMin,
        $this->useS4AlignmentControl,
        $this->useS4QCategoryControl,
        $this->s4NoCategories,
        $this->s4_reasonCharacterLimitValue,
      $this->step4Sequential,

      $this->step1ConsentForm,
      $this->step1RecruitForm,
      $this->step1PreForm,
      $this->step1PostForm,
      $this->step2ConsentForm,
      $this->step2RecruitForm,
      $this->step2PreForm,
      $this->step2PostForm,
      $this->step2PreInvert,
      $this->step2PostInvert,
      $this->step4ConsentForm,
      $this->step4RecruitForm,
      $this->step4PreForm,
      $this->step4PostForm,
      $this->step2PreInvert,
      $this->step2PostInvert,
      $this->exptId);
    return $qry;
  }
  
  function getSaveContentQry() {
    $qry = sprintf("UPDATE edContentDefs_refactor 
      SET 
      evenS1Label = '%s',
      oddS1Label = '%s',
      description = '%s',
      labelChoice = '%s',
      instLikert = '%s',
      labelReasons = '%s',
      instExtraLikert = '%s',
      labelFinalRating = '%s',
      labelChoiceFinalRating = '%s',
      labelReasonFinalRating = '%s',
      instFinalLikert = '%s',
      reasonGuidance = '%s',
      reasonGuidanceF = '%s',
      labelRating = '%s',
      s1IntentionLabel = '%s',
      s1AlignmentNoneLabel = '%s',
      s1AlignmentPartlyLabel = '%s',
      s1AlignmentMostlyLabel = '%s',
      s1AlignmentCompletelyLabel = '%s',
      s1AlignmentLabel = '%s',
      s1CategoryLabel = '%s',
      jTab='%s', 
      jTabUnconnected = '%s', 
      jTabWaiting='%s',
      jTabActive='%s',  
      jTabRating='%s',  
      jTabDone='%s',  
      jWaitingToStart='%s', 
      jPleaseAsk='%s',  
      jAskButton='%s',  
      jWaitingForReplies='%s',  
      jHistoryTitle='%s', 
      jRatingTitle='%s',  
      jFinalRatingTitle='%s',
      jRatingYourQuestion='%s',
      jRatingQ='%s',
      jRatingR1='%s',
      jRatingR2='%s',
      jAskAnotherB='%s',
      jNoMoreB='%s',
      jSaveFinalB='%s',
      jFinalMsg='%s',
      jConfirmHead='%s',
      jConfirmBody='%s',
      jConfirmOK='%s',
      jConfirmCancel='%s'
      npTab='%s',
      pTab='%s',
      rTabInactive='%s',
      rTabActive='%s',
      rTabWaiting='%s',
      rTabDone text='%s',
      rWaitFirst='%s',
      rWaitNext='%s',
      rHistoryTitle='%s',
      rCurrentQ='%s',
      rInstruction='%s',
      rSendB='%s',
      rGuidanceHeader='%s',
      rYourAnswer='%s',
      rFinalMsg='%s',
      npGuidance='%s',
      pGuidance='%s',
      step2_startMsg='%s',
      step2_startBLabel='%s',
      step2_finalMsg='%s',
      step2_closedMsg='%s',
      step2_replyMsg='%s',
      step2_endBLabel='%s',
      step2_invertedStartMsg='%s',
      step2_invertedStartBLabel='%s',
      step2_invertedFinalMsg='%s',
      step2_invertedClosedMsg='%s',
      step2_invertedReplyMsg='%s',
      step2_invertedEndBLabel='%s'
      s2PAlignmentLabel = '%s',
      s2PYesLabel = '%s',
      s2PNoLabel = '%s',
      s2ContinueLabel = '%s',
      s2CorrectedAnswerLabel = '%s',
      iS2NPAlignmentLabel = '%s',
      iS2NPYesLabel = '%s',
      iS2NPNoLabel = '%s',
      iS2ContinueLabel = '%s',
      iS2CorrectedAnswerLabel = '%s',
      step2_sendBLabel = '%s',
      step2_invertedSendBLabel = '%s',
      step2_ReplyLimitGuidance = '%s',
      istep2_ReplyLimitGuidance = '%s',
      
      step4_judgeNumberMsg='%s',
      step4_startMsg='%s',
      step4_startBLabel='%s',
      step4_closedMsg='%s',
      step4_finalMsg='%s',
      step4_nextBLabel='%s',
      step4_reasonMsg='%s',
      step4_reasonGuidance = '%s',
      step4_endBLabel = '%s',
      s4QCategoryLabel = '%s',
      step4IntentionLimitGuidance = '%s',
      s4IntentionLabel = '%s',
      s4AlignmentLabel = '%s',
      s4AlignmentNoneLabel = '%s',
      s4AlignmentPartlyLabel = '%s',
      s4AlignmentMostlyLabel = '%s',
      s4AlignmentCompletelyLabel = '%s',
      WHERE exptId='%s'",
      $this->evenS1Label,
      $this->oddS1Label,
      $this->description,
      $this->labelChoice,
      $this->instLikert,
      $this->labelReasons,
      $this->instExtraLikert,
      $this->labelFinalRating,
      $this->labelChoiceFinalRating,
      $this->labelReasonFinalRating,
      $this->instFinalLikert,
      $this->reasonGuidance,
      $this->reasonGuidanceF,
      $this->labelRating,
      $this->s1IntentionLabel,
      $this->s1AlignmentNoneLabel,
      $this->s1AlignmentPartlyLabel,
      $this->s1AlignmentMostlyLabel,
      $this->s1AlignmentCompletelyLabel,
      $this->s1AlignmentLabel,
      $this->s1CategoryLabel,
      $this->jTab, 
      $this->jTabUnconnected, 
      $this->jTabWaiting,
      $this->jTabActive, 
      $this->jTabRating,  
      $this->jTabDone,  
      $this->jWaitingToStart, 
      $this->jPleaseAsk,  
      $this->jAskButton,  
      $this->jWaitingForReplies,  
      $this->jHistoryTitle, 
      $this->jRatingTitle,  
      $this->jFinalRatingTitle,
      $this->jRatingYourQuestion,
      $this->jRatingQ,
      $this->jRatingR1,
      $this->jRatingR2,
      $this->jAskAnotherB,
      $this->jNoMoreB,
      $this->jSaveFinalB,
      $this->jFinalMsg,
      $this->jConfirmHead,
      $this->jConfirmBody,
      $this->jConfirmOK,
      $this->jConfirmCancel,
      $this->exptId,
      $this->npTab,
      $this->pTab,
      $this->rTabInactive,
      $this->rTabActive,
      $this->rTabWaiting,
      $this->rTabDone,
      $this->rWaitFirst,
      $this->rWaitNext,
      $this->rHistoryTitle,
      $this->rCurrentQ,
      $this->rInstruction,
      $this->rSendB,
      $this->rGuidanceHeader,
      $this->rYourAnswer,
      $this->rFinalMsg,
      $this->npGuidance,
      $this->pGuidance,

      $this->step2_startMsg,
      $this->step2_startBLabel,
      $this->step2_finalMsg,
      $this->step2_closedMsg,
      $this->step2_replyMsg,
      $this->step2_endBLabel,
      $this->step2_invertedStartMsg,
      $this->step2_invertedStartBLabel,
      $this->step2_invertedFinalMsg,
      $this->step2_invertedClosedMsg,
      $this->step2_invertedReplyMsg,
      $this->step2_invertedEndBLabel,
      $this->s2PAlignmentLabel,
      $this->s2PYesLabel,
      $this->s2PNoLabel,
      $this->s2ContinueLabel,
      $this->s2CorrectedAnswerLabel,
      $this->iS2NPAlignmentLabel,
      $this->iS2NPYesLabel,
      $this->iS2NPNoLabel,
      $this->iS2ContinueLabel,
      $this->iS2CorrectedAnswerLabel,
      $this->step2_sendBLabel,
      $this->step2_invertedSendBLabel,
      $this->step2_ReplyLimitGuidance,
      $this->istep2_ReplyLimitGuidance,
       
      $this->step4_judgeNumberMsg,
      $this->step4_startMsg,
      $this->step4_startBLabel,
      $this->step4_closedMsg,
      $this->step4_finalMsg,
      $this->step4_nextBLabel,
      $this->step4_reasonMsg,
      $this->step4_reasonGuidance,
      $this->step4_endBLabel,
      $this->s4QCategoryLabel,
      $this->step4IntentionLimitGuidance,
      $this->s4IntentionLabel,
      $this->s4AlignmentLabel,
      $this->s4AlignmentNoneLabel,
      $this->s4AlignmentPartlyLabel,
      $this->s4AlignmentMostlyLabel,
      $this->s4AlignmentCompletelyLabel,
      $this->exptId);
    return $qry;
  }
  
  function saveConfigToDb() {
    global $igrtSqli;
    $igrtSqli->query( $this->getSaveContentQry() );
    $igrtSqli->query( $this->getSaveSettingsQry() );
    // remove all sessions from this expt then re-insert
    $sqlCmd_ClearSessions=sprintf("DELETE FROM edSessions WHERE exptId=%s", $this->exptId);
    $igrtSqli->query($sqlCmd_ClearSessions);
    for ($i=0;$i<$this->noDays;$i++) {
      $dayNo=$i+1;
      for ($j=0;$j<$this->noSessions;$j++) {
        $sessionNo=$j+1;
        $sqlCmd_Insert=sprintf("INSERT INTO edSessions (exptId,dayNo,sessionNo,open,time) VALUES('%s','%s','%s','1','%s')", $this->exptId, $dayNo, $sessionNo, $this->days[$i][$j]['time']);
        $igrtSqli->query($sqlCmd_Insert);
      }               
    }
    // remove all primary likert labels first then re-insert
    $sqlCmd_ClearLabelLikert=sprintf("DELETE FROM edLabels WHERE exptId=%s AND whichLikert=0", $this->exptId);
    $igrtSqli->query($sqlCmd_ClearLabelLikert);
    for ($i=0;$i<$this->noLikert;$i++) {
      $sqlCmd_InsertLikert=sprintf("INSERT INTO edLabels (exptId,whichLikert,label) VALUES('%s','0','%s')", $this->exptId, $this->labelLikert[$i]['label']);
      $igrtSqli->query($sqlCmd_InsertLikert);
    }
    // remove all extra likert labels first then re-insert
    $sqlCmd_ClearLabelExtraLikert=sprintf("DELETE FROM edLabels WHERE exptId=%s AND whichLikert=1", $this->exptId);
    $igrtSqli->query($sqlCmd_ClearLabelExtraLikert);
    for ($i=0;$i<$this->noExtraLikert;$i++) {
      $sqlCmd_InsertExtraLikert=sprintf("INSERT INTO edLabels (exptId,whichLikert,label) VALUES('%s','1','%s')", $this->exptId, $this->labelExtraLikert[$i]['label']);
      $igrtSqli->query($sqlCmd_InsertExtraLikert);
    }
    // remove all final likert labels first then re-insert
    $sqlCmd_ClearLabelFinalLikert=sprintf("DELETE FROM edLabels WHERE exptId=%s AND whichLikert=2", $this->exptId);
    $igrtSqli->query($sqlCmd_ClearLabelFinalLikert);
    for ($i=0;$i<$this->noFinalLikert;$i++) {
      $sqlCmd_InsertFinalLikert=sprintf("INSERT INTO edLabels (exptId,whichLikert,label) VALUES('%s','2','%s')", $this->exptId, $this->labelFinalLikert[$i]['label']);
      $igrtSqli->query($sqlCmd_InsertFinalLikert);
    }
  }
    
  function markReadyStep1() {
    global $igrtSqli;
    $sqlCmd_update=sprintf("UPDATE igExperiments SET status='2' WHERE exptId='%s'", $this->exptId);
    $igrtSqli->query($sqlCmd_update);
  }
  
  function getFormsReadyStatus() {
    global $igrtSqli;
    $this->step1ConsentFormReady = false;
    $this->step1RecruitFormReady = false;
    $this->step1PreFormReady = false;
    $this->step1PostFormReady = false;
    $this->step2ConsentFormReady = false;
    $this->step2ConsentFormReady = false;
    $this->step2RecruitFormReady = false;
    $this->step2PreFormReady = false;
    $this->step2PostFormReady = false;
    $this->step4ConsentFormReady = false;
    $this->step4RecruitFormReady = false;
    $this->step4PreFormReady = false;
    $this->step4PostFormReady = false;
    $this->step2PreInvertReady = false;
    $this->step2PostInvertReady = false;
    $formsReadyQry = sprintf("SELECT * FROM fdStepForms WHERE exptId='%s' AND definitionComplete='1'", $this->exptId);
    $formsReadyResult = $igrtSqli->query($formsReadyQry);
    while ($formsReadyRow = $formsReadyResult->fetch_object()) {
      switch ($formsReadyRow->formType) {
        case 0 : { $this->step1ConsentFormReady = true; break; }
        case 1 : { $this->step1RecruitFormReady = true; break; }
        case 2 : { $this->step1PreFormReady = true; break; }
        case 3 : { $this->step1PostFormReady = true; break; }
        case 4 : { $this->step2ConsentFormReady = true; break; }
        case 5 : { $this->step2RecruitFormReady = true; break; }
        case 6 : { $this->step2PreFormReady = true; break; }
        case 7 : { $this->step2PostFormReady = true; break; }
        case 8 : { $this->step4ConsentFormReady = true; break; }
        case 9 : { $this->step4RecruitFormReady = true; break; }
        case 10 : { $this->step4PreFormReady = true; break; }
        case 11 : { $this->step4PostFormReady = true; break; }
        case 12 : { $this->step2PreInvertReady = true; break; }
        case 13 : { $this->step2PostInvertReady = true; break; }
      }
    }
  }
    
  function getLikertLabels() {
    global $igrtSqli;
    $sqlQry_Likert=sprintf("SELECT * FROM edLabels WHERE exptId='%s' AND whichLikert='0'", $this->exptId);
    $likertResults=$igrtSqli->query($sqlQry_Likert);
    if ($likertResults) {
      $i=0;
      while ($row=$likertResults->fetch_object()) {
        $labelDetail=array(
          'key'=>$i,
          'label'=>$row->label
        );
        array_push($this->labelLikert, $labelDetail);
        ++$i;
      }
    }               
  }

  function getFinalLikertLabels() {
    global $igrtSqli;
    $sqlQry_FinalLikert=sprintf("SELECT * FROM edLabels WHERE exptId='%s' AND whichLikert='2';",$this->exptId);
    $finalLikertResults=$igrtSqli->query($sqlQry_FinalLikert);
    if ($finalLikertResults) {
      $i=0;
      while ($row=$finalLikertResults->fetch_object()) {
        $labelDetail=array(
          'key'=>$i,
          'label'=>$row->label
        );
        array_push($this->labelFinalLikert, $labelDetail);
        ++$i;
      }
    }              
  }
  
  function getAlignmentLabels() {    
    global $igrtSqli;
    $sql = sprintf("SELECT * FROM edAlignmentControlLabels WHERE exptId='%s' AND step=1 ORDER BY displayOrder ASC", $this->exptId);
    $result = $igrtSqli->query($sql);
    if ($result) {
      while ($row = $result->fetch_object()) {
        array_push($this->s1AlignmentCategoryLabels, $row->label);
      }
    }
    $sql = sprintf("SELECT * FROM edAlignmentControlLabels WHERE exptId='%s' AND step=2 ORDER BY displayOrder ASC", $this->exptId);
    $result = $igrtSqli->query($sql);
    if ($result) {
      while ($row = $result->fetch_object()) {
        array_push($this->s2AlignmentCategoryLabels, $row->label);
      }
    }
    $sql = sprintf("SELECT * FROM edAlignmentControlLabels WHERE exptId='%s' AND step=3 ORDER BY displayOrder ASC", $this->exptId);
    $result = $igrtSqli->query($sql);
    if ($result) {
      while ($row = $result->fetch_object()) {
        array_push($this->is2AlignmentCategoryLabels, $row->label);
      }
    }
    $sql = sprintf("SELECT * FROM edAlignmentControlLabels WHERE exptId='%s' AND step=4 ORDER BY displayOrder ASC", $this->exptId);
    $result = $igrtSqli->query($sql);
    if ($result) {
      while ($row = $result->fetch_object()) {
        array_push($this->s4AlignmentCategoryLabels, $row->label);
      }
    }
  }

  function getSessions() {
    global $igrtSqli;
    for ($i=0;$i<$this->noDays;$i++) {
      $sessions=array();
      $dayNo=$i+1;
      // get sessions for each day
      $sqlQry_sessions=sprintf("SELECT * FROM edSessions WHERE exptId='%s' AND dayNo='%s';",$this->exptId,$dayNo);
      $sessionResult=$igrtSqli->query($sqlQry_sessions);
      if ($sessionResult) {
        while ($row=$sessionResult->fetch_object()) {
          $sessionDetail=array(
            'dayNo'=>$row->dayNo,
            'sessionNo'=>$row->sessionNo,
            'open'=>$row->open,
            'time'=>$row->time
          );
          array_push($sessions,$sessionDetail);
        }
        array_push($this->days,$sessions);
      }
    }        
  }
  
  function getExptDynamics() {
    $this->getSessions();
    $this->getLikertLabels();
    $this->getFinalLikertLabels();
    $this->getAlignmentLabels();
    $this->getFormsReadyStatus();
  }

  function getExptDetails() {
    global $igrtSqli;
    $sqlQry_exptDetails=sprintf("SELECT igExperiments.*, edExptStatic_refactor.*, edContentDefs_refactor.* "
        . "FROM igExperiments JOIN edExptStatic_refactor JOIN edContentDefs_refactor "
        . "WHERE edExptStatic_refactor.exptId='%s' AND igExperiments.exptId=edExptStatic_refactor.exptId "
        . "AND edExptStatic_refactor.exptId=edContentDefs_refactor.exptId", $this->exptId);
    //$sqlQry_exptDetails=sprintf("SELECT igExperiments.* FROM igExperiments WHERE igExperiments.exptId='%s'", $this->exptId);
    $edResult = $igrtSqli->query($sqlQry_exptDetails);
    if ($edResult) {
      $row = $edResult->fetch_object();
      $this->ownerId = $row->ownerId;
      $this->title = $row->title;
      $this->isInactive = $row->isInactive;
      $this->s1srcExptId = $row->s1srcExptId;
      $this->isInjected = $row->isInjected;
      $this->injectedS1Flag = $row->injectedS1Flag;
      $this->isClassic = $row->isClassic;
      $this->useAutoLogins = $row->useAutoLogins;
      $this->exptSubject = $row->exptSubject;
      $this->language = $row->language;
      $this->location = $row->location;
      $this->country = $row->country;
      $this->evenS1Label = $row->evenS1Label;
      $this->oddS1Label =$row->oddS1Label;
      $this->description = $row->description;
      $this->canClone = $row->canClone;
      
      $this->useS1Intention = $row->useS1Intention;
      $this->useS1IntentionMin = $row->useS1IntentionMin;
      $this->s1IntentionMin = $row->s1IntentionMin;
			$this->s1QuestionMinLabel = $row->s1QuestionMinLabel;
			$this->useS1MinQuestionLimit = $row->useS1MinQuestionLimit;
			$this->s1MinQuestionLimit = $row->s1MinQuestionLimit;
			$this->s1IntentionMinLabel = $row->s1IntentionMinLabel;
      $this->useS1AlignmentControl = $row->useS1AlignmentControl;
      $this->useS1QCategoryControl = $row->useS1QCategoryControl;
      $this->s1NoCategories = $row->s1NoCategories;
      $this->s1usersSet = $row->s1usersSet;
      $this->randomiseSideS1 = $row->randomiseSideS1;
      $this->s1barbilliardControl = $row->s1barbilliardControl;
      $this->s1QuestionCountAlternative = $row->s1QuestionCountAlternative;
      $this->noJudges = $row->noJudges;
      $this->extraJ = $row->extraJ;
      $this->extraNP = $row->extraNP;
      $this->extraP = $row->extraP;
      $this->useRating = $row->useRating;
      $this->useLikert = $row->useLikert;
      $this->noLikert = $row->noLikert;
      $this->useReasons = $row->useReasons;
      $this->useFinalRating = $row->useFinalRating;
      $this->useReasonFinalRating = $row->useReasonFinalRating;
      $this->useReasonCharacterLimit = $row->useReasonCharacterLimit;
      $this->reasonCharacterLimitValue = $row->reasonCharacterLimitValue;
      $this->useReasonCharacterLimitF = $row->useReasonCharacterLimitF;
      $this->reasonCharacterLimitValueF = $row->reasonCharacterLimitValueF;
      $this->useFinalRating = $row->useFinalRating;
      $this->useReasonFinalRating = $row->useReasonFinalRating;
      $this->useReasonCharacterLimit = $row->useReasonCharacterLimit;
      $this->reasonCharacterLimitValue = $row->reasonCharacterLimitValue;
      $this->useReasonCharacterLimitF = $row->useReasonCharacterLimitF;
      $this->reasonCharacterLimitValueF = $row->reasonCharacterLimitValueF;
      $this->useFinalLikert =  $row->useFinalLikert;
      $this->noFinalLikert = $row->noFinalLikert;
      $this->noDays = $row->noDays;
      $this->noSessions = $row->noSessions;             
      $this->choosingNP = $row->choosingNP;
      $this->s1correctFB = $row->s1correctFB;
      $this->s1incorrectFB = $row->s1incorrectFB;
      $this->s1runningScoreDividerLabel = isset($row->s1runningScoreDividerLabel) ? $row->s1runningScoreDividerLabel : '';
      $this->s1runningScoreLabel = $row->s1runningScoreLabel;
      $this->s1giveFeedback = $row->s1giveFeedback;
      $this->s1giveFeedbackFinal = $row->s1giveFeedbackFinal;
      $this->s1feedbackTime = $row->s1feedbackTime;
      $this->s1runningScore = $row->s1runningScore;
      $this->s1WinFeedbackLabel = $row->s1WinFeedbackLabel;
      $this->s1LoseFeedbackLabel = $row->s1LoseFeedbackLabel;
      $this->s1PercentForWinFeedbackFinal = $row->s1PercentForWinFeedbackFinal;

      $this->step2EvenConfigured = $row->step2EvenConfigured;
      $this->step2OddConfigured = $row->step2OddConfigured;
      $this->step2InvertedEvenConfigured = $row->step2InvertedEvenConfigured;
      $this->step2InvertedOddConfigured = $row->step2InvertedOddConfigured;
      $this->s2srcExptId = $row->s2srcExptId;
      $this->s2invertedsrcExptId = $row->s2invertedsrcExptId;
      $this->useStep2ReplyCalculation = $row->useStep2ReplyCalculation;
      $this->useOddInvertedS2 = $row->useOddInvertedS2;
      $this->useEvenInvertedS2 = $row->useEvenInvertedS2;
      $this->useS2CharacterLimit = $row->useS2CharacterLimit;
      $this->s2CharacterLimitValue = $row->s2CharacterLimitValue;
      $this->useIS2CharacterLimit = $row->useIS2CharacterLimit;
      $this->iS2CharacterLimitValue = $row->iS2CharacterLimitValue;
      $this->useS2PAlignment = $row->useS2PAlignment;
      $this->useIS2NPAlignment = $row->useIS2NPAlignment;
      $this->step2Sequential = $row->step2Sequential;

      $this->useS4CharacterLimit = $row->useS4CharacterLimit;
      $this->s4CharacterLimitValue = $row->s4CharacterLimitValue;
      $this->s4RandomiseSide = $row->s4RandomiseSide;
      $this->useS4IndividualTurn = $row->useS4IndividualTurn;
      $this->useS4Intention = $row->useS4Intention;
      $this->useS4IntentionMin = $row->useS4IntentionMin;
      $this->s4IntentionMin = $row->s4IntentionMin;
      $this->useS4AlignmentControl = $row->useS4AlignmentControl;
      $this->useS4QCategoryControl = $row->useS4QCategoryControl;
      $this->s4NoCategories = $row->s4NoCategories;
      $this->s4_reasonCharacterLimitValue = $row->s4_reasonCharacterLimitValue;
      $this->step4Sequential = $row->step4Sequential;

      $this->step1ConsentForm = $row->step1ConsentForm;
      $this->step1RecruitForm = $row->step1RecruitForm;
      $this->step1PreForm = $row->step1PreForm;
      $this->step1PostForm = $row->step1PostForm;
      $this->step2ConsentForm = $row->step2ConsentForm;
      $this->step2RecruitForm = $row->step2RecruitForm;
      $this->step2PreForm = $row->step2PreForm;
      $this->step2PostForm = $row->step2PostForm;
      $this->step2PreInvert = $row->step2PreInvert;
      $this->step2PostInvert = $row->step2PostInvert;
      $this->step4ConsentForm = $row->step4ConsentForm;
      $this->step4RecruitForm = $row->step4RecruitForm;
      $this->step4PreForm = $row->step4PreForm;
      $this->step4PostForm = $row->step4PostForm;

      // ensure content has at least '.' in case it is sent out in XML (cannot have empty nodes)
      $this->labelChoice = $row->labelChoice == '' ? '.' : $row->labelChoice;
      $this->instLikert = $row->instLikert== '' ? '.' : $row->instLikert;
      $this->labelReasons = $row->labelReasons== '' ? '.' : $row->labelReasons;
      $this->instExtraLikert = $row->instExtraLikert== '' ? '.' : $row->instExtraLikert;
      $this->labelFinalRating = $row->labelFinalRating== '' ? '.' : $row->labelFinalRating;
      $this->labelChoiceFinalRating = $row->labelChoiceFinalRating== '' ? '.' : $row->labelChoiceFinalRating;
      $this->labelReasonFinalRating = $row->labelReasonFinalRating== '' ? '.' : $row->labelReasonFinalRating;
      $this->instFinalLikert = $row->instFinalLikert== '' ? '.' : $row->instFinalLikert;
      $this->reasonGuidance = $row->instFinalLikert== '' ? '.' : $row->instFinalLikert;
      $this->reasonGuidanceF = $row->reasonGuidanceF== '' ? '.' : $row->reasonGuidanceF;
      $this->labelRating = $row->labelRating== '' ? '.' : $row->labelRating;
      $this->s1IntentionLabel = $row->s1IntentionLabel== '' ? '.' : $row->s1IntentionLabel;
      $this->s1AlignmentNoneLabel = $row->s1AlignmentNoneLabel== '' ? '.' : $row->s1AlignmentNoneLabel;
      $this->s1AlignmentPartlyLabel = $row->s1AlignmentPartlyLabel== '' ? '.' : $row->s1AlignmentPartlyLabel;
      $this->s1AlignmentMostlyLabel = $row->s1AlignmentMostlyLabel== '' ? '.' : $row->s1AlignmentMostlyLabel;
      $this->s1AlignmentCompletelyLabel = $row->s1AlignmentCompletelyLabel== '' ? '.' : $row->s1AlignmentCompletelyLabel;
      $this->s1AlignmentExtraLabel = $row->s1AlignmentExtraLabel== '' ? '.' : $row->s1AlignmentExtraLabel;
      $this->appendITypetoS1AlignmentNoneLabel = $row->appendITypetoS1AlignmentNoneLabel== '' ? '.' : $row->appendITypetoS1AlignmentNoneLabel;
      $this->appendITypetoS1AlignmentPartlyLabel = $row->appendITypetoS1AlignmentPartlyLabel== '' ? '.' : $row->appendITypetoS1AlignmentPartlyLabel;
      $this->appendITypetoS1AlignmentMostlyLabel = $row->appendITypetoS1AlignmentMostlyLabel== '' ? '.' : $row->appendITypetoS1AlignmentMostlyLabel;
      $this->appendITypetoS1AlignmentCompletelyLabel = $row->appendITypetoS1AlignmentCompletelyLabel== '' ? '.' : $row->appendITypetoS1AlignmentCompletelyLabel;
      $this->appendITypetoS1AlignmentExtraLabel = $row->appendITypetoS1AlignmentExtraLabel== '' ? '.' : $row->appendITypetoS1AlignmentExtraLabel;
      $this->useS1AlignmentNoneLabel = $row->useS1AlignmentNoneLabel== '' ? '.' : $row->useS1AlignmentNoneLabel;
      $this->useS1AlignmentPartlyLabel = $row->useS1AlignmentPartlyLabel== '' ? '.' : $row->useS1AlignmentPartlyLabel;
      $this->useS1AlignmentMostlyLabel = $row->useS1AlignmentMostlyLabel== '' ? '.' : $row->useS1AlignmentMostlyLabel;
      $this->useS1AlignmentCompletelyLabel = $row->useS1AlignmentCompletelyLabel== '' ? '.' : $row->useS1AlignmentCompletelyLabel;
      $this->useS1AlignmentExtraLabel = $row->useS1AlignmentExtraLabel== '' ? '.' : $row->useS1AlignmentExtraLabel;
      $this->s1AlignmentLabel = $row->s1AlignmentLabel== '' ? '.' : $row->s1AlignmentLabel;
      $this->s1CategoryLabel = $row->s1CategoryLabel== '' ? '.' : $row->s1CategoryLabel;
      $this->useS1AlignmentAsRB = $row->useS1AlignmentAsRB== '' ? '.' : $row->useS1AlignmentAsRB;

      $this->jTab = $row->jTab== '' ? '.' : $row->jTab;
      $this->jTabUnconnected = $row->jTabUnconnected== '' ? '.' : $row->jTabUnconnected;
      $this->jTabWaiting = $row->jTabWaiting== '' ? '.' : $row->jTabWaiting;
      $this->jTabActive = $row->jTabActive== '' ? '.' : $row->jTabActive;
      $this->jTabRating = $row->jTabRating== '' ? '.' : $row->jTabRating;
      $this->jTabDone = $row->jTabRating== '' ? '.' : $row->jTabRating;
      $this->jWaitingToStart = $row->jWaitingToStart== '' ? '.' : $row->jWaitingToStart;
      $this->jPleaseAsk = $row->jPleaseAsk== '' ? '.' : $row->jPleaseAsk;
      $this->jAskButton = $row->jAskButton== '' ? '.' : $row->jAskButton;
      $this->jWaitingForReplies = $row->jWaitingForReplies== '' ? '.' : $row->jWaitingForReplies;
      $this->jHistoryTitle = $row->jHistoryTitle== '' ? '.' : $row->jHistoryTitle;
      $this->jRatingTitle = $row->jRatingTitle== '' ? '.' : $row->jRatingTitle;
      $this->jFinalRatingTitle = $row->jFinalRatingTitle== '' ? '.' : $row->jFinalRatingTitle;
      $this->jRatingYourQuestion = $row->jRatingYourQuestion== '' ? '.' : $row->jRatingYourQuestion;
      $this->jRatingQ = $row->jRatingQ== '' ? '.' : $row->jRatingQ;
      $this->jRatingR1 = $row->jRatingR1== '' ? '.' : $row->jRatingR1;
      $this->jRatingR2 = $row->jRatingR2== '' ? '.' : $row->jRatingR2;
      $this->jAskAnotherB = $row->jAskAnotherB== '' ? '.' : $row->jAskAnotherB;
      $this->jNoMoreB = $row->jNoMoreB== '' ? '.' : $row->jNoMoreB;
      $this->jSaveFinalB = $row->jSaveFinalB== '' ? '.' : $row->jSaveFinalB;
      $this->jFinalMsg = $row->jFinalMsg== '' ? '.' : $row->jFinalMsg;
      $this->jConfirmHead = $row->jConfirmHead== '' ? '.' : $row->jConfirmHead;
      $this->jConfirmBody = $row->jConfirmBody== '' ? '.' : $row->jConfirmBody;
      $this->jConfirmOK = $row->jConfirmOK== '' ? '.' : $row->jConfirmOK;
      $this->jConfirmCancel = $row->jConfirmCancel== '' ? '.' : $row->jConfirmCancel;
      $this->npTab = $row->npTab== '' ? '.' : $row->npTab;
      $this->pTab = $row->pTab== '' ? '.' : $row->pTab;
      $this->rTabInactive = $row->rTabInactive== '' ? '.' : $row->rTabInactive;
      $this->rTabActive = $row->rTabActive== '' ? '.' : $row->rTabActive;
      $this->rTabWaiting = $row->rTabWaiting== '' ? '.' : $row->rTabWaiting;
      $this->rTabDone = $row->rTabDone== '' ? '.' : $row->rTabDone;
      $this->rWaitFirst = $row->rWaitFirst== '' ? '.' : $row->rWaitFirst;
      $this->rWaitNext = $row->rWaitNext== '' ? '.' : $row->rWaitNext;
      $this->rHistoryTitle = $row->rHistoryTitle== '' ? '.' : $row->rHistoryTitle;
      $this->rCurrentQ = $row->rCurrentQ== '' ? '.' : $row->rCurrentQ;
      $this->rInstruction = $row->rInstruction== '' ? '.' : $row->rInstruction;
      $this->rSendB = $row->rSendB== '' ? '.' : $row->rSendB;
      $this->rGuidanceHeader = $row->rGuidanceHeader== '' ? '.' : $row->rGuidanceHeader;
      $this->rYourAnswer = $row->rYourAnswer== '' ? '.' : $row->rYourAnswer;
      $this->rFinalMsg = $row->rFinalMsg== '' ? '.' : $row->rFinalMsg;
      $this->npGuidance = $row->npGuidance== '' ? '.' : $row->npGuidance;
      $this->pGuidance = $row->pGuidance== '' ? '.' : $row->pGuidance;

      $this->step2_startMsg = $row->step2_startMsg== '' ? '.' : $row->step2_startMsg;
      $this->step2_startBLabel = $row->step2_startBLabel== '' ? '.' : $row->step2_startBLabel;
      $this->step2_finalMsg = $row->step2_finalMsg== '' ? '.' : $row->step2_finalMsg;
      $this->step2_closedMsg = $row->step2_closedMsg== '' ? '.' : $row->step2_closedMsg;
      $this->step2_replyMsg = $row->step2_replyMsg== '' ? '.' : $row->step2_replyMsg;
      $this->step2_endBLabel = $row->step2_endBLabel== '' ? '.' : $row->step2_endBLabel;
      $this->step2_invertedStartMsg = $row->step2_invertedStartMsg== '' ? '.' : $row->step2_invertedStartMsg;
      $this->step2_invertedStartBLabel = $row->step2_invertedStartBLabel== '' ? '.' : $row->step2_invertedStartBLabel;
      $this->step2_invertedFinalMsg = $row->step2_invertedFinalMsg== '' ? '.' : $row->step2_invertedFinalMsg;
      $this->step2_invertedClosedMsg = $row->step2_invertedClosedMsg== '' ? '.' : $row->step2_invertedClosedMsg;
      $this->step2_invertedReplyMsg = $row->step2_invertedReplyMsg== '' ? '.' : $row->step2_invertedReplyMsg;
      $this->step2_invertedEndBLabel = $row->step2_invertedEndBLabel== '' ? '.' : $row->step2_invertedEndBLabel;
      $this->s2PAlignmentLabel = $row->s2PAlignmentLabel== '' ? '.' : $row->s2PAlignmentLabel;
      $this->s2PYesLabel = $row->s2PYesLabel== '' ? '.' : $row->s2PYesLabel;
      $this->s2PNoLabel = $row->s2PNoLabel== '' ? '.' : $row->s2PNoLabel;
      $this->s2ContinueLabel = $row->s2ContinueLabel== '' ? '.' : $row->s2ContinueLabel;
      $this->s2CorrectedAnswerLabel = $row->s2CorrectedAnswerLabel== '' ? '.' : $row->s2CorrectedAnswerLabel;
      $this->step2_ReplyLimitGuidance = $row->step2_ReplyLimitGuidance== '' ? '.' : $row->step2_ReplyLimitGuidance;
      $this->iS2NPAlignmentLabel = $row->iS2NPAlignmentLabel== '' ? '.' : $row->iS2NPAlignmentLabel;
      $this->iS2NPYesLabel = $row->iS2NPYesLabel== '' ? '.' : $row->iS2NPYesLabel;
      $this->iS2NPNoLabel = $row->iS2NPNoLabel== '' ? '.' : $row->iS2NPNoLabel;
      $this->iS2ContinueLabel = $row->iS2ContinueLabel== '' ? '.' : $row->iS2ContinueLabel;
      $this->iS2CorrectedAnswerLabel = $row->iS2CorrectedAnswerLabel== '' ? '.' : $row->iS2CorrectedAnswerLabel;
      $this->step2_sendBLabel = $row->step2_sendBLabel== '' ? '.' : $row->step2_sendBLabel;
      $this->step2_invertedSendBLabel = $row->step2_invertedSendBLabel== '' ? '.' : $row->step2_invertedSendBLabel;
      $this->istep2_ReplyLimitGuidance = $row->istep2_ReplyLimitGuidance== '' ? '.' : $row->istep2_ReplyLimitGuidance;

      $this->step4_judgeNumberMsg = $row->step4_judgeNumberMsg == '' ? '.' : $row->step4_judgeNumberMsg;
      $this->step4_startMsg = $row->step4_startMsg== '' ? '.' : $row->step4_startMsg;
      $this->step4_startBLabel = $row->step4_startBLabel== '' ? '.' : $row->step4_startBLabel;
      $this->step4_closedMsg = $row->step4_closedMsg== '' ? '.' : $row->step4_closedMsg;
      $this->step4_finalMsg = $row->step4_finalMsg== '' ? '.' : $row->step4_finalMsg;
      $this->step4_nextBLabel = $row->step4_nextBLabel== '' ? '.' : $row->step4_nextBLabel;
      $this->step4_reasonMsg = $row->step4_reasonMsg== '' ? '.' : $row->step4_reasonMsg;
      $this->step4_reasonGuidance = $row->step4_reasonGuidance== '' ? '.' : $row->step4_reasonGuidance;
      $this->step4_endBLabel = $row->step4_endBLabel== '' ? '.' : $row->step4_endBLabel;
            
      $this->s4IntentionLabel = $row->s4IntentionLabel== '' ? '.' : $row->s4IntentionLabel;
      $this->s4QCategoryLabel = $row->s4QCategoryLabel== '' ? '.' : $row->s4QCategoryLabel;
      $this->step4IntentionLimitGuidance = $row->step4IntentionLimitGuidance== '' ? '.' : $row->step4IntentionLimitGuidance;
      $this->s4AlignmentLabel = $row->s4AlignmentLabel== '' ? '.' : $row->s4AlignmentLabel;
      $this->s4AlignmentNoneLabel = $row->s4AlignmentNoneLabel== '' ? '.' : $row->s4AlignmentNoneLabel;
      $this->s4AlignmentPartlyLabel = $row->s4AlignmentPartlyLabel== '' ? '.' : $row->s4AlignmentPartlyLabel;
      $this->s4AlignmentMostlyLabel = $row->s4AlignmentMostlyLabel== '' ? '.' : $row->s4AlignmentMostlyLabel;
      $this->s4AlignmentCompletelyLabel = $row->s4AlignmentCompletelyLabel== '' ? '.' : $row->s4AlignmentCompletelyLabel;
      $this->getExptDynamics();
    }
  }
    
  function __construct($_exptId) {
    $this->exptId = $_exptId;
    $this->getExptDetails();
  }   
  
  // </editor-fold>

}





    
