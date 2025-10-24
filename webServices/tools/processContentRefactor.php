<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';     


  function moveExptStaticText($languageId, $evenS1Label, $oddS1Label, $staticRow) {
    $exptId = $staticRow->exptId;
    $description = $staticRow->description;
    $labelChoice=$staticRow->labelChoice;
    $instLikert  =$staticRow->instLikert;
    $labelReasons  =$staticRow->labelReasons;
    $instExtraLikert  =$staticRow->instExtraLikert;
    $labelFinalRating   =$staticRow->labelFinalRating;
    $labelChoiceFinalRating  =$staticRow->labelChoiceFinalRating;
    $labelReasonFinalRating  =$staticRow->labelReasonFinalRating;
    $instFinalLikert  =$staticRow->instFinalLikert;
    $reasonGuidance  =$staticRow->reasonGuidance;
    $reasonGuidanceF  =$staticRow->reasonGuidanceF;
    $labelRating  =$staticRow->labelRating;
    $s1IntentionLabel  =$staticRow->s1IntentionLabel;
    $s1R1Label  =$staticRow->s1R1Label;
    $s1R2Label  =$staticRow->s1R2Label;
    $s1BothLabel  =$staticRow->s1BothLabel;
    $s1NeitherLabel  =$staticRow->s1NeitherLabel;
    $s1AlignmentLabel  =$staticRow->s1AlignmentLabel;
    $s1CategoryLabel  =$staticRow->s1CategoryLabel;
    $s4IntentionLabel  =$staticRow->s4IntentionLabel;
    $s4R1Label  =$staticRow->s4R1Label;
    $s4R2Label  =$staticRow->s4R2Label;
    $s4BothLabel  =$staticRow->s4BothLabel;
    $s4NeitherLabel  =$staticRow->s4NeitherLabel;
    $s4AlignmentLabel  =$staticRow->s4AlignmentLabel;
    $s4QCategoryLabel  =$staticRow->s4QCategoryLabel;
    $s2PAlignmentLabel  =$staticRow->s2PAlignmentLabel;
    $s2PYesLabel =$staticRow->s2PYesLabel;
    $s2PNoLabel =$staticRow->s2PNoLabel;
    $s2ContinueLabel  =$staticRow->s2ContinueLabel;
    $s2CorrectedAnswerLabel  =$staticRow->s2CorrectedAnswerLabel;
    $iS2NPAlignmentLabel  =$staticRow->iS2NPAlignmentLabel;
    $iS2NPYesLabel =$staticRow->iS2NPYesLabel;
    $iS2NPNoLabel =$staticRow->iS2NPNoLabel;
    $iS2ContinueLabel =$staticRow->iS2ContinueLabel;
    $iS2CorrectedAnswerLabel =$staticRow->iS2CorrectedAnswerLabel;
    $insertStaticQry = sprintf("INSERT INTO edContentDefs_refactor (
      exptId,
      evenS1Label,
      oddS1Label,
      description,
      labelChoice,
      instLikert,
      labelReasons,
      instExtraLikert,
      labelFinalRating,
      labelChoiceFinalRating,
      labelReasonFinalRating,
      instFinalLikert,
      reasonGuidance,
      reasonGuidanceF,
      labelRating,
      s1IntentionLabel,
      s1R1Label,
      s1R2Label,
      s1BothLabel,
      s1NeitherLabel,
      s1AlignmentLabel,
      s1CategoryLabel,
      s4IntentionLabel,
      s4R1Label,
      s4R2Label,
      s4BothLabel,
      s4NeitherLabel,
      s4AlignmentLabel,
      s4QCategoryLabel,
      s2PAlignmentLabel,
      s2PYesLabel,
      s2PNoLabel,
      s2ContinueLabel,
      s2CorrectedAnswerLabel,
      iS2NPAlignmentLabel,
      iS2NPYesLabel,
      iS2NPNoLabel,
      iS2ContinueLabel,
      iS2CorrectedAnswerLabel
      )
      VALUES(
        '%s','%s','%s',
        '%s','%s','%s','%s','%s','%s',
        '%s','%s','%s','%s','%s','%s',
        '%s','%s','%s','%s','%s','%s',
        '%s','%s','%s','%s','%s','%s',
        '%s','%s','%s','%s','%s','%s',
        '%s','%s','%s','%s','%s','%s'
        )",
      $exptId,
      $evenS1Label,
      $oddS1Label,
      $description,
      $labelChoice,
      $instLikert,
      $labelReasons,
      $instExtraLikert,
      $labelFinalRating,
      $labelChoiceFinalRating,
      $labelReasonFinalRating,
      $instFinalLikert,
      $reasonGuidance,
      $reasonGuidanceF,
      $labelRating,
      $s1IntentionLabel,
      $s1R1Label,
      $s1R2Label,
      $s1BothLabel,
      $s1NeitherLabel,
      $s1AlignmentLabel,
      $s1CategoryLabel,
      $s4IntentionLabel,
      $s4R1Label,
      $s4R2Label,
      $s4BothLabel,
      $s4NeitherLabel,
      $s4AlignmentLabel,
      $s4QCategoryLabel,
      $s2PAlignmentLabel,
      $s2PYesLabel,
      $s2PNoLabel,
      $s2ContinueLabel,
      $s2CorrectedAnswerLabel,
      $iS2NPAlignmentLabel,
      $iS2NPYesLabel,
      $iS2NPNoLabel,
      $iS2ContinueLabel,
      $iS2CorrectedAnswerLabel
    ); 
    return $insertStaticQry;
  }

  function moveJContentText($jRow) {
    $jQry = sprintf("UPDATE edContentDefs_refactor 
    SET 
    jTab='%s', 
    jTabUnconnected='%s', 
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
    WHERE exptId='%s'",
    $jRow->jTab, 
    $jRow->jTabUnconnected, 
    $jRow->jTabWaiting,
    $jRow->jTabActive, 
    $jRow->jTabRating,  
    $jRow->jTabDone,  
    $jRow->jWaitingToStart, 
    $jRow->jPleaseAsk,  
    $jRow->jAskButton,  
    $jRow->jWaitingForReplies,  
    $jRow->jHistoryTitle, 
    $jRow->jRatingTitle,  
    $jRow->jFinalRatingTitle,
    $jRow->jRatingYourQuestion,
    $jRow->jRatingQ,
    $jRow->jRatingR1,
    $jRow->jRatingR2,
    $jRow->jAskAnotherB,
    $jRow->jNoMoreB,
    $jRow->jSaveFinalB,
    $jRow->jFinalMsg,
    $jRow->jConfirmHead,
    $jRow->jConfirmBody,
    $jRow->jConfirmOK,
    $jRow->jConfirmCancel,
    $jRow->exptId);
   
    return $jQry;
  }

  function moveRContentText($rRow) {
    $rQry = sprintf("UPDATE edContentDefs_refactor 
      SET 
      npTab='%s',
      pTab='%s',
      rTabInactive='%s',
      rTabActive='%s',
      rTabWaiting='%s',
      rTabDone='%s',
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
      step4_judgeNumberMsg='%s',
      step4_startMsg='%s',
      step4_startBLabel='%s',
      step4_closedMsg='%s',
      step4_finalMsg='%s',
      step4_nextBLabel='%s',
      step4_reasonMsg='%s',
      step2_invertedStartMsg='%s',
      step2_invertedStartBLabel='%s',
      step2_invertedFinalMsg='%s',
      step2_invertedClosedMsg='%s',
      step2_invertedReplyMsg='%s',
      step2_invertedEndBLabel='%s'
      WHERE exptId='%s'",
      $rRow->npTab,
      $rRow->pTab,
      $rRow->rTabInactive,
      $rRow->rTabActive,
      $rRow->rTabWaiting,
      $rRow->rTabDone,
      $rRow->rWaitFirst,
      $rRow->rWaitNext,
      $rRow->rHistoryTitle,
      $rRow->rCurrentQ,
      $rRow->rInstruction,
      $rRow->rSendB,
      $rRow->rGuidanceHeader,
      $rRow->rYourAnswer,
      $rRow->rFinalMsg,
      $rRow->npGuidance,
      $rRow->pGuidance,
      $rRow->step2_startMsg,
      $rRow->step2_startBLabel,
      $rRow->step2_finalMsg,
      $rRow->step2_closedMsg,
      $rRow->step2_replyMsg,
      $rRow->step2_endBLabel,
      $rRow->step4_judgeNumberMsg,
      $rRow->step4_startMsg,
      $rRow->step4_startBLabel,
      $rRow->step4_closedMsg,
      $rRow->step4_finalMsg,
      $rRow->step4_nextBLabel,
      $rRow->step4_reasonMsg,
      $rRow->step2_invertedStartMsg,
      $rRow->step2_invertedStartBLabel,
      $rRow->step2_invertedFinalMsg,
      $rRow->step2_invertedClosedMsg,
      $rRow->step2_invertedReplyMsg,
      $rRow->step2_invertedEndBLabel,
      $rRow->exptId);
    return $rQry;
  }

  function moveExptStaticValues($randomiseSideS1, $useStep2ReplyCalculation, $useOddInvertedS2, $useEvenInvertedS2, $staticRow) {
    global $igrtSqli; 
    $makeQry = sprintf("INSERT INTO edExptStatic_refactor (exptId) VALUES ('%s')", $staticRow->exptId);
    $igrtSqli->query($makeQry);
    $qry = sprintf("UPDATE edExptStatic_refactor 
      SET      
      owner='%s',
      isClassic='%s',
      useAutoLogins='%s',
      s1usersSet='%s',
      exptSubject='%s',
      location='%s',
      country='%s',
      noJudges='%s',
      extraJ='%s',
      extraNP='%s',
      extraP='%s',
      canClone='%s',
      useRating='%s',
      useLikert='%s',
      noLikert='%s',
      useReasons='%s',
      useFinalRating='%s',
      useReasonFinalRating='%s',
      noDays='%s',
      noSessions='%s',
      useFinalLikert='%s',
      noFinalLikert='%s',
      step1RecruitForm='%s',
      step1ConsentForm='%s',
      step1PreForm='%s',
      step1PostForm='%s',
      step2Sequential='%s',
      step2RecruitForm='%s',
      step2ConsentForm='%s',
      step2PreForm='%s',
      step2PostForm='%s',
      step4Sequential='%s',
      step4RecruitForm='%s',
      step4ConsentForm='%s',
      step4PreForm='%s',
      step4PostForm='%s',
      step2PreInvert='%s',
      step2PostInvert='%s',
      useReasonCharacterLimit='%s',
      reasonCharacterLimitValue='%s',
      useReasonCharacterLimitF='%s',
      reasonCharacterLimitValueF='%s',
      useS1Intention='%s',
      useS1IntentionMin='%s',
      s1IntentionMin='%s',
      useS1AlignmentControl='%s',
      useS1QCategoryControl='%s',
      s1NoCategories='%s',
      useS4Intention='%s',
      useS4IntentionMin='%s',
      s4IntentionMin='%s',
      useS4IndividualTurn='%s',
      s4RandomiseSide='%s',
      useS4AlignmentControl='%s',
      useS4QCategoryControl='%s',
      s4NoCategories='%s',
      useS2PAlignment='%s',
      useIS2NPAlignment='%s',
      s1barbilliardControl='%s',
      s1QuestionCountAlternative='%s',
      randomiseSideS1='%s',
      useStep2ReplyCalculation='%s',
      useOddInvertedS2='%s',
      useEvenInvertedS2='%s'
      WHERE exptId='%s'",
      $staticRow->owner,
      $staticRow->isClassic,
      $staticRow->useAutoLogins,
      $staticRow->s1usersSet,
      $staticRow->exptSubject,
      $staticRow->location,
      $staticRow->country,
      $staticRow->noJudges,
      $staticRow->extraJ,
      $staticRow->extraNP,
      $staticRow->extraP,
      $staticRow->canClone,
      $staticRow->useRating,
      $staticRow->useLikert,
      $staticRow->noLikert,
      $staticRow->useReasons,
      $staticRow->useFinalRating,
      $staticRow->useReasonFinalRating,
      $staticRow->noDays,
      $staticRow->noSessions,
      $staticRow->useFinalLikert,
      $staticRow->noFinalLikert,
      $staticRow->step1RecruitForm,
      $staticRow->step1ConsentForm,
      $staticRow->step1PreForm,
      $staticRow->step1PostForm,
      $staticRow->step2Sequential,
      $staticRow->step2RecruitForm,
      $staticRow->step2ConsentForm,
      $staticRow->step2PreForm,
      $staticRow->step2PostForm,
      $staticRow->step4Sequential,
      $staticRow->step4RecruitForm,
      $staticRow->step4ConsentForm,
      $staticRow->step4PreForm,
      $staticRow->step4PostForm,
      $staticRow->step2PreInvert,
      $staticRow->step2PostInvert,
      $staticRow->useReasonCharacterLimit,
      $staticRow->reasonCharacterLimitValue,
      $staticRow->useReasonCharacterLimitF,
      $staticRow->reasonCharacterLimitValueF,
      $staticRow->useS1Intention,
      $staticRow->useS1IntentionMin,
      $staticRow->s1IntentionMin,
      $staticRow->useS1AlignmentControl,
      $staticRow->useS1QCategoryControl,
      $staticRow->s1NoCategories,
      $staticRow->useS4Intention,
      $staticRow->useS4IntentionMin,
      $staticRow->s4IntentionMin,
      $staticRow->useS4IndividualTurn,
      $staticRow->s4RandomiseSide,
      $staticRow->useS4AlignmentControl,
      $staticRow->useS4QCategoryControl,
      $staticRow->s4NoCategories,
      $staticRow->useS2PAlignment,
      $staticRow->useIS2NPAlignment,
      $staticRow->s1barbilliardControl,
      $staticRow->s1QuestionCountAlternative,
      $randomiseSideS1,
      $useStep2ReplyCalculation,
      $useOddInvertedS2,
      $useEvenInvertedS2,
      $staticRow->exptId);
    return $qry;
  }
  
// get each exptId
$exptQry = "SELECT * FROM igExperiments ORDER BY exptId ASC";
$exptResult = $igrtSqli->query($exptQry);
if ($exptResult) {
  while ($exptRow = $exptResult->fetch_object()) {
    $exptId = $exptRow->exptId;
    // combine all textual content into 1 table
    $evenS1Label = $exptRow->evenS1Label;
    $oddS1Label = $exptRow->oddS1Label;
    $randomiseSideS1 = $exptRow->randomiseSideS1;
    $useStep2ReplyCalculation = $exptRow->useStep2ReplyCalculation;
    $useOddInvertedS2 = $exptRow->useOddInvertedS2;
    $useEvenInvertedS2 = $exptRow->useEvenInvertedS2;     
    $languageQry = sprintf("SELECT * FROM edContentDefs WHERE exptId='%s'", $exptId);
    $languageResult = $igrtSqli->query($languageQry);
    if ($languageResult) {
      $languageRow = $languageResult->fetch_object();
      $languageId = $languageRow->languageId;
      // get content from edExptStatic
      $staticQry = sprintf("SELECT * FROM edExptStatic WHERE exptId='%s'", $exptId);
      $staticResult = $igrtSqli->query($staticQry);
      if ($staticResult) {
        $staticRow = $staticResult->fetch_object();
        $qry = moveExptStaticText($languageId, $evenS1Label, $oddS1Label, $staticRow);
//        echo $qry.'<br/>';
//        $igrtSqli->query($qry);
        $qry = moveExptStaticValues($randomiseSideS1, $useStep2ReplyCalculation, $useOddInvertedS2, $useEvenInvertedS2, $staticRow);
//        echo $qry.'<br/>';
//        $igrtSqli->query($qry);
      }
      $jContentQry = sprintf("SELECT * FROM edJudgeContent WHERE exptId='%s'", $exptId);
      $jResult = $igrtSqli->query($jContentQry);
      if ($jResult) {
        $jRow = $jResult->fetch_object();
        $qry = moveJContentText($jRow);
//        echo $qry.'<br/>';
//        $igrtSqli->query($qry);
      }
      $rContentQry = sprintf("SELECT * FROM edRespContent WHERE exptId='%s'", $exptId);
      $rResult = $igrtSqli->query($rContentQry);
      if ($rResult) {
        $rRow = $rResult->fetch_object();
        $qry = moveRContentText($rRow);
//        echo $qry.'<br/>';
//        $igrtSqli->query($qry);
      }
    }
  }
}