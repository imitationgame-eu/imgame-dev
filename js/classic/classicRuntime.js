var intervalId = '';
var finalIntervalId = '';
var useLikert;
var useFinalLikert;
var useReasons;
var useExtraLikert;
var useFinalReason;
var srcName;
var url;
var index;
var filename; 
var uid;
var exptId;
var jType;
var respRole;
var dayNo;
var sessionNo;
var exptType;
var exptStage;
var qNo;
var randomiseSideS1;
var npLeft;

var jTabC = 'Judge',
    jTabUnconnectedC = '',
    jTabWaitingC,jTabActiveC,jTabRatingC,jTabDoneC,jWaitingToStartC,
    jPleaseAskC,jAskButtonC,jWaitingForRepliesC,jHistoryTitleC,jRatingTitleC,
    jRatingYourQuestionC,jFinalRatingTitleC,jRatingQC,jRatingR1C,jRatingR2C,
    jAskAnotherBC,jNoMoreBC,jSaveFinalBC,jFinalMsgC,jConfirmHeadC,
    jConfirmBodyC,jConfirmOKC,jConfirmCancelC,
    npTabC = '',
    pTabC = '',
    rTabInactiveC = '',
    rTabActiveC,
    rTabWaitingC = '',
    rTabDoneC,rWaitFirstC,rWaitNextC,rHistoryTitleC,rCurrentQ,rYourAnswerC,
    rInstructionC,rSendBC,rGuidanceHeaderC,rFinalMsgC;
var npGuidanceC='';
var pGuidanceC='';

var alignmentChoice1;
var alignmentChoice2;
var s1IntentionLabel;
var useS1Intention;
var useS1IntentionMin;
var s1IntentionMin;
var useS1AlignmentControl;
var useS1MinQuestionLimit,s1MinQuestionLimit,s1IntentionMinLabel,s1QuestionMinLabel;
var useS1MinQuestionCount;
var s1MinQuestionCount;

var useS1QCategoryControl;



function setFinalLikertControls() {
  var intervalCount = $('.finalSlideBar').find('.finalInterval').length;
  $('.finalInterval').css('width' , 100 / intervalCount + '%');
  var number = 1;	
  $('.finalSlideBar').find('.finalInterval').each(function(){
          $(this).attr('id', 'finalInterval' + number++);
  });
  $('#finalInterval1').css('left' , 0 + '%');
  $('#finalInterval2').css('left' , 100 / intervalCount + '%');
  $('#finalInterval3').css('left' , 100 / intervalCount * 2 + '%');
  $('#finalInterval4').css('left' , 100 / intervalCount * 3 + '%');
  $('#finalInterval5').css('left' , 100 / intervalCount * 4 + '%');
  $('#finalInterval6').css('left' , 100 / intervalCount * 5 + '%');
  $('#finalInterval7').css('left' , 100 / intervalCount * 6 + '%');
  $('#finalInterval8').css('left' , 100 / intervalCount * 7 + '%');
  $('#finalInterval9').css('left' , 100 / intervalCount * 8 + '%');

  // Set the draggable
  $('.finalSlidePointer').draggable( {
          containment: '.finalSlideBar',
  create: function(){$(this).data('position',$(this).position())},
  cursorAt:{left:27},
  start:function(){$(this).stop(true,true)}
  });

  // Set the droppable
  $('.finalSlideBar').find('.finalInterval').droppable({
    drop:function(event, ui) {
      snapToMiddle(ui.draggable,$(this));
      // Do something when pointer lands over each interval
      finalIntervalId = $(this).attr('id');
      checkFinalRatingValidation()
    }
  });	
  // Snap the draggable to to middle of the droppabble
  function snapToMiddle(dragger, target){
    var bottomMove = target.position().bottom - dragger.data('position').bottom + (target.outerHeight(true) - dragger.outerHeight(true)) / 2;
    var leftMove= target.position().left - dragger.data('position').left + (target.outerWidth(true) - dragger.outerWidth(true)) / 2;
    dragger.animate({bottom:bottomMove,left:leftMove},{duration:500,easing:'easeOutBack'});
  }
}

function setLikertControls() {
  var intervalCount = $('.slideBar').find('.interval').length;
  $('.interval').css('width' , 100 / intervalCount + '%');
  var number = 1;	
  $('.slideBar').find('.interval').each(function(){
    $(this).attr('id', 'interval' + number++);
  });
  $('#interval1').css('left' , 0 + '%');
  $('#interval2').css('left' , 100 / intervalCount + '%');
  $('#interval3').css('left' , 100 / intervalCount * 2 + '%');
  $('#interval4').css('left' , 100 / intervalCount * 3 + '%');
  $('#interval5').css('left' , 100 / intervalCount * 4 + '%');
  $('#interval6').css('left' , 100 / intervalCount * 5 + '%');
  $('#interval7').css('left' , 100 / intervalCount * 6 + '%');
  $('#interval8').css('left' , 100 / intervalCount * 7 + '%');
  $('#interval9').css('left' , 100 / intervalCount * 8 + '%');
  $('.slidePointer').draggable( {
    containment: '.slideBar',
    create: function(){$(this).data('position',$(this).position())},
    cursorAt:{left:27},
    start:function(){$(this).stop(true,true)}
  });
  // Set the droppable
  $('.slideBar').find('.interval').droppable({
    drop:function(event, ui) {
      snapToMiddle(ui.draggable,$(this));
      // Do something when pointer lands over each interval
      intervalId = $(this).attr('id');
      checkRatingValidation();
    }
  });	
  // Snap the draggable to to middle of the droppabble
  function snapToMiddle(dragger, target){
    var bottomMove = target.position().bottom - dragger.data('position').bottom + (target.outerHeight(true) - dragger.outerHeight(true)) / 2;
    var leftMove= target.position().left - dragger.data('position').left + (target.outerWidth(true) - dragger.outerWidth(true)) / 2;
    dragger.animate({bottom:bottomMove,left:leftMove},{duration:500,easing:'easeOutBack'});
  }
  // Reset slider when ask another question button is clicked
  $('.tabContent').on('click', '#nextQ', function(event){
    $('.slidePointer').css('left',0);
    return false;
  });    
}

function setRatingControls() {
  $('.choice input').click(function(){
    checkRatingValidation();
    $('.choice').removeClass('chosen');
    var lr=$(this).attr('value');
    if (lr=='left_judgement') {
      $('#cLeft').addClass('chosen');
    }
    else {
      $('#cRight').addClass('chosen');
    }
  });
  alignmentChoice1 = '';
  alignmentChoice2 = '';
  $('input:radio[name=irb1]').click(function() {
    var id = $(this).attr('id');
    details = id.split('_');
    alignmentChoice1 = details[1];
    checkRatingValidation();
  });
  $('input:radio[name=irb2]').click(function() {
    var id = $(this).attr('id');
    details = id.split('_');
    alignmentChoice2 = details[1];
    checkRatingValidation();
  });
  setLikertControls();
  $('#jReason').keyup(function() {
    checkRatingValidation();
  });
  $('#nextQ').attr("disabled","disabled");
  $('#nextQ').addClass('greyed');
  $('#noMoreQ').attr("disabled","disabled");
  $('#noMoreQ').addClass('greyed');
     // clear radio buttons
  $('.choice input').attr("checked",false);
  $('input:radio[name=irb1]').attr("checked", false);
  $('input:radio[name=irb2]').attr("checked", false);
  intervalId='';
  extraIntervalId='';
  $('#noMoreQ').click(function(e) {
    $('#noMoreQ').unbind();
    // set are you sure? dialog for no-more-Q
    $( "#dialog-ui-dialog" ).dialog( "destroy" );
    $( "#dialog-confirm" ).dialog({
      resizable: false,
      height:180,
      modal: true,
      buttons: [
        {
            text: jConfirmOKC,
            click: function() {
               processRatingButton(1);
               $(this).dialog("close");
            }
        },{
            text: jConfirmCancelC,
            click: function() {
                $(this).dialog("close");
            }
        }
      ]
    });
  });
  $('#nextQ').click(function(e){
    $('#nextQ').unbind();
    processRatingButton(0);
  });
}

function checkRatingValidation() {
  // validation to ensure selection made, slider moved and reasons given and alignment finished if used
  var reason;
  var choice='';
  var validated=true;
  var radios = $('input:radio[name=judgement]');
  if (radios[0]['checked'] == true) {choice='0';}
  if (radios[1]['checked'] == true) {choice='1';}
  if (choice=='') { validated=false; }
  reason=$('#jReason').val();
  if (reason=='') {validated=false;}
  if (useLikert==1) {
    if (intervalId=='') {validated=false;}
  }
  if (useS1AlignmentControl == "1") {
    if (alignmentChoice1 == '' || alignmentChoice2 == '') { validated = false; }
  }
    if (validated) {
      $('#nextQ').removeAttr("disabled").removeClass('greyed');
      if (useS1MinQuestionCount == "1")  {
        if (qNo > s1MinQuestionCount) {
          $('#noMoreQ').removeAttr("disabled").removeClass('greyed');
        }
      }
      else {
        $('#noMoreQ').removeAttr("disabled").removeClass('greyed');
      }
  }
}

function setFinalRatingControls() {
  setFinalLikertControls();
  $('.finalChoice input').click(function(){
    checkFinalRatingValidation();        
    // 3. Change background image when judge makes a choice
    $('.finalChoice').removeClass('chosen');
    var lr=$(this).attr('value');
    if (lr=='left_judgement') {
      $('#fcLeft').addClass('chosen');
    }
    else {
      $('#fcRight').addClass('chosen');
    }
   });
  $('#judgesMainReason').keyup(function() {
    checkFinalRatingValidation();
  });
  $('#judgesEndB').attr("disabled","disabled");
  $('#judgesEndB').addClass('greyed');
  $('.finalChoice input').attr("checked",false);
  $('#judgesEndB').click(function(e) {
    processFinalRatingButton();
  });
}

function checkFinalRatingValidation() {
  var choice='';
  var reason='';
  var validated=true;
  var $radios = $('input:radio[name=finalJudgement]');
  if ($radios[0]['checked'] == true) {choice='0';}
  if ($radios[1]['checked'] == true) {choice='1';}
  if (choice=='') { validated=false; } 
  reason=$('#judgesMainReason').val();
  if (reason=='') { validated=false; }
  if (useFinalLikert == 1) {
    if (finalIntervalId=='') {validated=false;}
  }
  if (validated) {
    $('#judgesEndB').removeAttr("disabled").removeClass('greyed');    
  }
}

function setNPControls() {
  $('#npSend').attr("disabled","disabled");
  $('#npSend').addClass('greyed');
  $('#npA').keyup(function() {
    if ($(this).val()>'') {
      $('#npSend').removeAttr("disabled").removeClass('greyed');
    } 
    else {
      $('#npSend').attr("disabled","disabled");
      $('#npSend').addClass('greyed');
    }
  });
  $('#npSend').click(function(e) {
    $('#nonPretenderContent').find('.currentAnswer').html('<h2>'+rCurrentQ+'</h2><p>'+$('#npA').val()+'</p>'); // ready for next phase
    $.post("/webServices/classic/respPost.php",{ userid: uid, experimentID: exptId, groupNo: groupNo, qNo: qNo, respA: $('#npA').val() } ,function(data) {
      processAJAX(data);
    });                
    $('#npSend').attr("disabled","disabled");
    $('#npSend').addClass('greyed');
    $('#npA').val('');
    $('#tabTwo').removeClass('answerQuestion').addClass('waiting');
  });
}

function setPControls() {
  $('#pSend').attr("disabled","disabled");
  $('#pSend').addClass('greyed');
  $('#pA').keyup(function() {
    if ($(this).val()>'') {
      $('#pSend').removeAttr("disabled").removeClass('greyed');
    }
    else {
      $('#pSend').attr("disabled","disabled");
      $('#pSend').addClass('greyed');            
    }
  });
  $('#pSend').click(function(e){        
    $('#pretenderContent').find('.currentAnswer').html('<h2>'+rCurrentQ+'</h2><p>'+$('#pA').val()+'</p>'); // ready for next phase
    $.post("/webServices/classic/respPost.php",{ userid: uid, experimentID: exptId, groupNo: groupNo, qNo: qNo, respA: $('#pA').val() } ,function(data) {
      processAJAX(data);
    }); 
    $('#pSend').attr("disabled","disabled");
    $('#pSend').addClass('greyed');
    $('#pA').val('');
    $('#tabThree').removeClass('answerQuestion').addClass('waiting');
  });
}

function questionLimitValidates() {
  if (useS1MinQuestionLimit == "1") {
    return ($('#jQ').val().length > s1MinQuestionLimit) ? true : false;
  }
  else {
    return true;
  }
}

function intentionLimitValidates() {
  if (useS1Intention == "1") {
    return ($('#iIntention').val().length > s1IntentionMin) ? true : false;
  }
  else {
    return true;
  }
}

function setJControls() {
  $('#jSend').attr("disabled","disabled");
  $('#jSend').addClass('greyed');
  $('#jQ').keyup(function() {
    if (questionLimitValidates() && intentionLimitValidates()) {
      $('#jSend').removeAttr("disabled").removeClass('greyed');
    }
    else {
      $('#jSend').attr("disabled","disabled");
      $('#jSend').addClass('greyed');            
    }
  });
  $('#iIntention').keyup(function() {
    if (questionLimitValidates() && intentionLimitValidates()) {
      $('#jSend').removeAttr("disabled").removeClass('greyed');
    }
    else {
      $('#jSend').attr("disabled","disabled");
      $('#jSend').addClass('greyed');
    }
  });
  $("#jQForm").submit(function() {
    $('#jSend').attr("disabled","disabled");
    $('#jSend').addClass('greyed');
    $('#tabOne').removeClass('askQuestion');
    //++qNo;
    //$('#soundPlayer').play();
    $.post("/webServices/classic/judgePostQ.php",{ userid:uid, experimentID:exptId, groupNo: groupNo, qNo: qNo, jQtext:$('#jQ').val(), useS1Intention:useS1Intention, intentionText:$('#iIntention').val() },
      function(data) {
        processAJAX(data);
      }
    );
    $('#jQ').val('');
    $('#iIntention').val('');
    return false;   // stop double-event
  });
}

function setTabOne() {
  //$('#judgeContent').unbind();
  if ($('#tabOne').hasClass('askQuestion')) {
    $('#judgeContent').find('.loggedIn').hide();
    $('#judgeContent').find('.waitingForStart').hide();
    $('#judgeContent').find('.responseOne, .responseTwo').show(); // so that the responses in the history show
    $('#r1id').hide();
    $('#r2id').hide();

    $('#judgeContent').find('.startJoin input, .latestQuestion, .judgesChoice, .judgesConfidence, .judgesReason, .button, .buttonBlue, .buttonRed, .waitingForAction, .alignmentRBs').hide();

    $('#judgeContent').find('.jAskQuestion, .jAskQuestion h2, .jAskQuestion textarea, .jAskQuestion input').show();
  }
  if ($('#tabOne').hasClass('waiting')) {
    $('#judgeContent').find('.loggedIn').hide();
    $('#judgeContent').find('.latestQuestion, .responseOne, .responseTwo, .judgesChoice, .judgesConfidence, .judgesReason, .button, .buttonBlue, .buttonRed, .alignmentRBs, .previousQuestions, .jAskQuestion').hide();
    $('#judgeContent').find('.waitingForAction').show();
    $('#judgeContent').find('.jAskQuestion').hide();
    $('#jSend').hide();
  }
  if ($('#tabOne').hasClass('rating')) {
    $('#judgeContent').find('.loggedIn').hide();
    $('#judgeContent').find('.jAskQuestion, .waitingForAction').hide();
    $('#judgesFinalRating').hide();
    $('#judgeContent').find('.latestQuestion, .responseOne, .responseTwo').show();        
    $('#judgesRating').show();
    $('#judgesRatingButtons').show();
    $('#judgeContent').find('.previousQuestions').show();
    $('#judgeContent').find('.finalMessage').hide();
    $('#judgeContent').find('.finalHeader').hide();
    $('#nextQ').show();
    $('#noMoreQ').show();
    $('#judgesEndB').hide();
  }
  if($('#tabOne').hasClass('finalRating')) {
    $('h2', '.finalHeader').html(jFinalRatingTitleC);
    $('#judgeContent').find('.jAskQuestion, .waitingForAction').hide();
    $('#judgeContent').find('.loggedIn').hide();
    $('#judgesRating').hide();
    $('#judgeContent').find('.latestQuestion').hide()
    $('#judgeContent').find('.responseOne, .responseTwo').show();
    $('#r1id').hide();
    $('#r2id').hide();
    $('#judgesFinalChoice').show();
    $('#judgesFinalReason').show();
    $('#judgesEndB').show();
    $('#judgesFinalRating').show();
    $('#judgeContent').find('.previousQuestions').show();
    $('#judgeContent').find('.finalMessage').hide();
    $('#judgeContent').find('.finalHeader').show();
    $('#nextQ').hide();
    $('#noMoreQ').hide();
    $('#historyControl').hide();
  }
  if($('#tabOne').hasClass('doneJudging')) {
    $('#judgeContent').find('.loggedIn').hide();
    $('#judgeContent').find('.latestQuestion, .responseOne, .responseTwo').hide();        
    $('#judgesFinalChoice').hide();
    $('#judgesMainReason').hide();
    $('#judgesEndB').hide();
    $('.judgesFinalRating').hide();
    $('.judgesReason').hide();
    $('#judgeContent').find('.previousQuestions').hide();
    $('#judgeContent').find('.finalHeader').hide();
    $('#judgeContent').find('.finalMessage').show();
    $('#tabOne').html(jTabC+' <span>'+jTabDoneC+'</span>');
    $('h2', '.finalMessage').html(jFinalMsgC);
  }
  if (randomiseSideS1 == '1') {
    // hide history
    $('#historyControl').hide();
  }
}

function setTabTwo() {
  //$('#nonPretenderContent').unbind();
  if ($('#tabTwo').hasClass('answerQuestion')) {
    $('#nonPretenderContent').find('.finalMessage').hide();
    $('#nonPretenderContent').find('.currentQuestion, .currentAnswer').hide();
    $('#nonPretenderContent').find('.initialPage').hide();
    $('#nonPretenderContent').find('.waitingPage').hide();
    $('#nonPretenderContent').find('.latestQuestion, .nonPretendersAnswer, .button').show();
    $('#nonPretenderContent').find('.latestQuestion').next('p').show();
    $('#sendNPAnswer').show();
    $('#tabTwo').html(npTabC+' <span>'+jTabActiveC+'</span>');
  }
  if ($('#tabTwo').hasClass('waitingForAction')) {
    $('#nonPretenderContent').find('.finalMessage').hide();
    $('#nonPretenderContent').find('.currentQuestion, .currentAnswer').hide();
    $('#nonPretenderContent').find('.latestQuestion').next('p').hide();
    $('#nonPretenderContent').find('.latestQuestion, .nonPretendersAnswer, .button').hide();
    $('#nonPretenderContent').find('.waitingPage').hide();
    $('#nonPretenderContent').find('.initialPage').show();            
    $('#nonPretenderContent').find('.latestQuestion').next('p').hide();
    $('#nonPretenderContent').find('.finalScreen').hide();
    $('#sendNPAnswer').hide();
    $('#tabTwo').html(npTabC+' <span>'+jTabWaitingC+'</span>');
  }
  if ($('#tabTwo').hasClass('waiting')) {
    $('#nonPretenderContent').find('.finalMessage').hide();
    $('#nonPretenderContent').find('.latestQuestion').next('p').hide();
    $('#nonPretenderContent').find('.latestQuestion, .nonPretendersAnswer, .button').hide();
    $('#nonPretenderContent').find('.initialPage').hide();            
    $('#nonPretenderContent').find('.waitingPage').show();
    $('#nonPretenderContent').find('.currentQuestion, .currentAnswer').show();
    $('#sendNPAnswer').hide();
    $('#tabTwo').html(npTabC+' <span>'+jTabWaitingC+'</span>');
  }
  if ($('#tabTwo').hasClass('done')) {
    $('#nonPretenderContent').find('.waitingPage').hide();
    $('#nonPretenderContent').find('.currentQuestion, .currentAnswer').hide();
    $('#nonPretenderContent').find('.previousQuestions').hide();        
    $('#nonPretenderContent').find('.initialPage').hide();
    $('#nonPretenderContent').find('.finalMessage').show();
    $('#sendNPAnswer').hide();
    $('#tabTwo').html(npTabC+' <span>'+jTabDoneC+'</span>');
  }    
}

function setTabThree() {
  //$('#pretenderContent').unbind();
  if ($('#tabThree').hasClass('answerQuestion')) {
    $('#pretenderContent').find('.finalMessage').hide();
    $('#pretenderContent').find('.currentQuestion, .currentAnswer').hide();
    $('#pretenderContent').find('.initialPage').hide();
    $('#pretenderContent').find('.waitingPage').hide();
    $('#pretenderContent').find('.latestQuestion, .pretendersAnswer, .button').show();
    $('#pretenderContent').find('.latestQuestion').next('p').show();
    $('#sendPAnswer').show();
    $('#tabThree').html(pTabC+' <span>'+jTabActiveC+'</span>');
  }
  if ($('#tabThree').hasClass('waitingForAction')) {
    $('#pretenderContent').find('.finalMessage').hide();
    $('#pretenderContent').find('.currentQuestion, .currentAnswer').hide();
    $('#pretenderContent').find('.latestQuestion').next('p').hide();
    $('#pretenderContent').find('.latestQuestion, .pretendersAnswer, .button').hide();
    $('#pretenderContent').find('.waitingPage').hide();
    $('#pretenderContent').find('.initialPage').show();            
    $('#pretenderContent').find('.latestQuestion').next('p').hide();
    $('#pretenderContent').find('.finalScreen').hide();
    $('#sendPAnswer').hide();
    $('#tabThree').html(pTabC+' <span>'+jTabWaitingC+'</span>');
  }
  if ($('#tabThree').hasClass('waiting')) {
    $('#pretenderContent').find('.finalMessage').hide();
    $('#pretenderContent').find('.latestQuestion').next('p').hide();
    $('#pretenderContent').find('.latestQuestion, .pretendersAnswer, .button').hide();
    $('#pretenderContent').find('.initialPage').hide();            
    $('#pretenderContent').find('.waitingPage').show();
    $('#pretenderContent').find('.latestQuestion').next('p').hide();
    $('#pretenderContent').find('.currentQuestion, .currentAnswer').show();
    $('#sendPAnswer').hide();
    $('#tabThree').html(pTabC+' <span>'+jTabWaitingC+'</span>');
  }
  if ($('#tabThree').hasClass('done')) {
    $('#pretenderContent').find('.waitingPage').hide();
    $('#pretenderContent').find('.currentQuestion, .currentAnswer').hide();
    $('#pretenderContent').find('.previousQuestions').hide();        
    $('#pretenderContent').find('.initialPage').hide();
    $('#pretenderContent').find('.finalMessage').show();
    $('#sendPAnswer').hide();
    $('#tabThree').html(pTabC+' <span>'+jTabDoneC+'</span>');
  }    
}

function setUI(tabNo) {
  if (tabNo==0 || tabNo==1) {setTabOne();}
  if (tabNo==0 || tabNo==2) {setTabTwo();}
  if (tabNo==0 || tabNo==3) {setTabThree();}
}

function processJ(leftr, rightr, jrHtml) {
  var paddingPtags='';
  if(leftr.length>70) {
    if (leftr.length > rightr.length) {
      var noPtags=((leftr.length - rightr.length)/70);
      for (var i=0; i<noPtags; i++) {
        paddingPtags=paddingPtags+'<p>&nbsp;</p>';
      }
      if (noPtags==0) {paddingPtags='<p>&nbsp;</p>';}
    }
  }
  $('#judgeContent').find('.responseOne').html('<h2>'+jRatingR1C+'</h2><p>'+leftr+'</p>');
  $('#judgeContent').find('.responseTwo').html('<h2>'+jRatingR2C+'</h2><p>'+rightr+'</p>'+paddingPtags);
  $('#tabOne').removeClass('waiting').addClass('rating');
  $('#tabOne').html(jTabC+'<span>'+jTabRatingC+'</span>'); 
  $('#judgesRating').html(jrHtml);
  setUI(1);
}

function reconnectJRating(leftr, rightr, jrHtml) {
  var paddingPtags='';
  if(leftr.length>70) {
    if (leftr.length > rightr.length) {
      var noPtags=((leftr.length - rightr.length)/70);
      for (var i=0; i<noPtags; i++) {
        paddingPtags=paddingPtags+'<p>&nbsp;</p>';
      }
      if (noPtags==0) {paddingPtags='<p>&nbsp;</p>';}
    }
  }
  $('#judgeContent').find('.responseOne').html('<h2>'+jRatingR1C+'</h2><p>'+leftr+'</p>').show();
  $('#judgeContent').find('.responseTwo').html('<h2>'+jRatingR2C+'</h2><p>'+rightr+'</p>'+paddingPtags).show();
  $('#tabOne').html(jTabC+'<span>'+jTabRatingC+'</span>'); 
  $('#judgesRating').html(jrHtml);
  if (randomiseSideS1 == '1') {
    // hide history
    $('#historyControl').hide();
  }
}

function reconnectJFinalRating(fullTranscript, jrHtml) {
  $('#r1id').hide();
  $("#r2id").hide();
  $('#tabOne').html(jTabC+'<span>'+jTabRatingC+'</span>');
  $('#judgesFinalRating').html(fullTranscript + jrHtml);
}

function processNPQ(content) {
  $('#tabTwo').html('Non-pretender<span>Active</span>');
  if ($('#tabTwo').hasClass('waitingForAction')) {
    $('#tabTwo').removeClass('waitingForAction').addClass('answerQuestion');
  }
  else {
    $('#tabTwo').removeClass('waiting').addClass('answerQuestion');
  }
  $('#nonPretenderContent').find('.latestQuestion').html('<h2>'+rCurrentQ+'</h2><p>'+content+'</p>'); // show on waiting screen
  $('#nonPretenderContent').find('.currentQuestion').html('<h2>'+rCurrentQ+'</h2><p>'+content+'</p>'); // ready for next phase
  setUI(2);
}

function processPQ(content) {
  $('#tabThree').html('Pretender<span>Active</span>');
  if ($('#tabThree').hasClass('waitingForAction')) {
    $('#tabThree').removeClass('waitingForAction').addClass('answerQuestion');
  }
  else {
    $('#tabThree').removeClass('waiting').addClass('answerQuestion');
  }
  $('#pretenderContent').find('.latestQuestion').html('<h2>'+rCurrentQ+'</h2><p>'+content+'</p>'); // show on waiting screen
  $('#pretenderContent').find('.currentQuestion').html('<h2>'+rCurrentQ+'</h2><p>'+content+'</p>'); // ready for next phase
  setUI(3);
}

function rebuildJUI(jState, history, Q, r1, r2, jrHtml, jfinalrHtml, fullTranscript){
  $('#tabOne').removeClass('waiting');
  $('#tabOne').removeClass('askQuestion');
  $('#tabOne').removeClass('rating');
  $('#tabOne').removeClass('finalRating');
  $('#tabOne').removeClass('doneJudging');
  processJhistory(history);
  $('#judgeContent').find('.latestQuestion').html('<p><span>'+jRatingQC+'</span>'+Q+'</p>');    
  // set from actions
  switch (jState) {
    case 'done': 
        $('#tabOne').addClass('doneJudging');
    break;
    case 'active':
      $('#tabOne').html(jTabC+' <span>'+jTabActiveC+'</span>');
      $('#tabOne').addClass('askQuestion');
    break;
    case 'waiting':
      $('#tabOne').html(jTabC+' <span>'+jTabWaitingC+'</span>');
      $('#tabOne').addClass('waiting');
      // set timed ajax call to check changed status
      $('#timer').show();
      $('#timer').hide(2000, function(e) { fireJStatus(); });                                                 
    break;
    case 'rating':
      //document.getElementById('soundPlayer').play();
      reconnectJRating(r1,r2,jrHtml);
      $('#tabOne').addClass('rating');
      setRatingControls();
    break;            
    case 'finalRating':
      //document.getElementById('soundPlayer').play();
      reconnectJFinalRating(fullTranscript, jfinalrHtml);
      $('#tabOne').addClass('finalRating');
      setFinalRatingControls();
    break;            
  }
  setUI(1);
}

function rebuildNPUI(npState, history, Q, A) {
  $('#tabTwo').removeClass('done');
  $('#tabTwo').removeClass('answerQuestion');
  $('#tabTwo').removeClass('waitingForAction');
  $('#tabTwo').removeClass('waiting');
  processNPhistory(history);
  switch(npState) {
    case 'answerQuestion': 
      $('#nonPretenderContent').find('.latestQuestion').html('<h2>'+rCurrentQ+'</h2><p>'+Q+'</p>'); // show on waiting screen
      $('#nonPretenderContent').find('.currentQuestion').html('<h2>'+rCurrentQ+'</h2><p>'+Q+'</p>'); // ready for next phase
      //document.getElementById('soundPlayer').play();
      $('#tabTwo').addClass('answerQuestion');
    break;
    
    case 'waitingForAction': 
      $('#tabTwo').addClass('waitingForAction');
      // set timed ajax call to check changed status
      $('#timer').show();
      $('#timer').hide(2000, function(e) { fireRespStatus(); });                                                 
    break;
    case 'waiting' : 
      $('#nonPretenderContent').find('.latestQuestion').html('<h2>'+rCurrentQ+'</h2><p>'+Q+'</p>'); // show on waiting screen
      $('#nonPretenderContent').find('.currentQuestion').html('<h2>'+rCurrentQ+'</h2><p>'+Q+'</p>'); // ready for next phase
      $('#nonPretenderContent').find('.currentAnswer').html('<h2>'+rYourAnswerC+'</h2><p>'+A+'</p>'); // ready for next phase
      $('#tabTwo').addClass('waiting');                    
      // set timed ajax call to check changed status
      $('#timer').show();
      $('#timer').hide(2000, function(e) { fireRespStatus(); });                                                 
    break;    
    case 'done' : 
      $('#tabTwo').addClass('done');       
    break;    
  }
  setUI(2);
}

function rebuildPUI(pState, history, Q, A) {
  $('#tabThree').removeClass('done');
  $('#tabThree').removeClass('answerQuestion');
  $('#tabThree').removeClass('waitingForAction');
  $('#tabThree').removeClass('waiting');
  processPhistory(history);
  switch (pState) {
      case 'answerQuestion': 
        $('#pretenderContent').find('.latestQuestion').html('<h2>'+rCurrentQ+'</h2><p>'+Q+'</p>'); // show on waiting screen
        $('#pretenderContent').find('.currentQuestion').html('<h2>'+rCurrentQ+'</h2><p>'+Q+'</p>'); // ready for next phase
        $('#tabThree').addClass('answerQuestion');
        //document.getElementById('soundPlayer').play();
      break;
      case 'waitingForAction': 
        $('#tabThree').addClass('waitingForAction');
        // set timed ajax call to check changed status
        $('#timer').show();
        $('#timer').hide(2000, function(e) { fireRespStatus(); });                                                 
      break;      
      case 'waiting' : 
        $('#pretenderContent').find('.latestQuestion').html('<h2>'+rCurrentQ+'</h2><p>'+Q+'</p>'); // show on waiting screen
        $('#pretenderContent').find('.currentQuestion').html('<h2>'+rCurrentQ+'</h2><p>'+Q+'</p>'); // ready for next phase
        $('#pretenderContent').find('.currentAnswer').html('<h2>'+rYourAnswerC+'</h2><p>'+A+'</p>'); // ready for next phase
        $('#tabThree').addClass('waiting');                    
        // set timed ajax call to check changed status
        $('#timer').show();
        $('#timer').hide(2000, function(e) { fireRespStatus(); });                                                 
      break;      
      case 'done' : 
        $('#tabThree').addClass('done');       
      break;      
  }
  setUI(3);
}

function txtToXmlDoc(txt) {
  // check for spurious characters at beginning of message string
  if (txt.substring(0,1) != '<') {
    var tl = txt.length;
    var i = txt.indexOf('<');
    var newTxt = txt.substring(i);
    txt = newTxt;
  }
  var xmlDoc;
  if (window.ActiveXObject) {
    xmlDoc=new ActiveXObject("Msxml2.DOMDocument.6.0");
    xmlDoc.loadXML(txt);   
  }
  else {
    parser=new DOMParser();
    xmlDoc=parser.parseFromString(txt,"text/xml");
  } 
  return xmlDoc;
}

function processJhistory(txt) {
  $('#judgeContent').find('.previousQuestions h2 a').html(jHistoryTitleC);
  $('#judgeContent').find('.history').html(txt);
  setUI(1);
}

function processNPhistory(txt) {
  $('#nonPretenderContent').find('.previousQuestions h2 a').html(rHistoryTitleC);
  $('#nonPretenderContent').find('.history').html(txt);
  setUI(2);
}

function processPhistory(txt) {
  $('#pretenderContent').find('.previousQuestions h2 a').html(rHistoryTitleC);
  $('#pretenderContent').find('.history').html(txt);
  setUI(3);    
}

function signalDone(target) {
  if (target==='NP') {
    $('#tabTwo').removeClass('waiting alert answerQuestion').addClass('done');
    setUI(2);
  }
  else {
    $('#tabThree').removeClass('waiting alert answerQuestion').addClass('done'); 
    setUI(3);
  }  
}

function processRatingButton(finalRating) {
  var choice='';
  var $radios = $('input:radio[name=judgement]');
  if ($radios[0]['checked'] == true) {choice='0';}
  if ($radios[1]['checked'] == true) {choice='1';}
  var reason=$('#jReason').val();
  if (finalRating == "1") {
    $('#tabOne').removeClass('rating');
    $('#tabOne').addClass('finalRating');
  }
  $.post("/webServices/classic/judgePostRating.php",{ alignmentChoice1: alignmentChoice1, alignmentChoice2: alignmentChoice2, userid: uid, groupNo: groupNo, qNo: qNo, experimentID:exptId, stage:finalRating ,uChoice: choice,uConfidence: intervalId,uReason: reason, npLeft: npLeft} ,function(data) {
    processAJAX(data);
  });
  ++qNo;
}

function processFinalRatingButton() {
  var choice='';
  var $radios = $('input:radio[name=finalJudgement]');
  if ($radios[0]['checked'] == true) {choice='0';}
  if ($radios[1]['checked'] == true) {choice='1';}
  var reason=$('#judgesMainReason').val();  //judgesFinalReason
  $.post("/webServices/classic/judgePostFinalRating.php",{ userid: uid, groupNo: groupNo, qNo: qNo, experimentID:exptId, uChoice: choice, uConfidence: finalIntervalId,  uReason: reason, npLeft: npLeft} ,function(data) {
    //processAJAX(data);
  });    
  $('#tabOne').removeClass('finalRating').addClass('doneJudging');
  $('#tabOne').html('Judge<span>Finished Judging</span>');
  $('#judgesFinalRating').hide();
  setUI(1);                                   
}

function setFinalRating(fullTranscript, frHtml, fhTxt) {
  finalIntervalId='';
  $('#tabOne').removeClass('rating').addClass('finalRating');
  $('#tabOne').html(jTabC+'<span>'+jTabRatingC+'</span>'); 
  $('#judgesFinalRating').html(fullTranscript + frHtml);
  $('#finalHeader').html('<h2>'+fhTxt+'</h2>');
  setUI(1);     
}

function fireRespStatus() {
  $.post("/webServices/classic/respStatus.php",{ userid: uid, experimentID: exptId, groupNo: groupNo } ,function(data) {
    processAJAX(data);
  });                    
}

function fireJStatus() {
  $.post("/webServices/classic/judgeStatus.php",{ userid: uid, experimentID: exptId, groupNo: groupNo, jType: jType} ,function(data) {
    processAJAX(data);
  });                        
}

function processAJAX(payload) {
  var xmlDoc = txtToXmlDoc(payload);  
  var messageType=xmlDoc.getElementsByTagName("messageType")[0].firstChild.nodeValue;
  switch (messageType) {
    // ----------------------------------------- reconnection and initialisation 
    case 'jStateUpdate':
      var jStateUpdate=xmlDoc.getElementsByTagName("jState")[0].firstChild.nodeValue;                        
      var jH=xmlDoc.getElementsByTagName("historyHtml")[0].firstChild.nodeValue;
      var jQ=xmlDoc.getElementsByTagName("jQ")[0].firstChild.nodeValue;
      var r1=xmlDoc.getElementsByTagName("lContent")[0].firstChild.nodeValue;
      var r2=xmlDoc.getElementsByTagName("rContent")[0].firstChild.nodeValue;
      var jrbHtml=xmlDoc.getElementsByTagName("jRatingHtml")[0].firstChild.nodeValue;
      var jfinalrHtml=xmlDoc.getElementsByTagName("jFinalRatingHtml")[0].firstChild.nodeValue;
      var fullTranscript = xmlDoc.getElementsByTagName("finalTranscript")[0].firstChild.nodeValue;
      useLikert=xmlDoc.getElementsByTagName("useLikert")[0].firstChild.nodeValue;
      useFinalLikert=xmlDoc.getElementsByTagName("useFinalLikert")[0].firstChild.nodeValue;
      useFinalReason=xmlDoc.getElementsByTagName("useReasonFinalRating")[0].firstChild.nodeValue;
      randomiseSideS1 = xmlDoc.getElementsByTagName("randomiseSideS1")[0].firstChild.nodeValue;
      npLeft = xmlDoc.getElementsByTagName("npLeft")[0].firstChild.nodeValue;
      rebuildJUI(jStateUpdate, jH, jQ, r1, r2, jrbHtml, jfinalrHtml, fullTranscript);
    break;
    case 'npUpdate':
      var npClass=xmlDoc.getElementsByTagName("respState")[0].firstChild.nodeValue;
      var npH=xmlDoc.getElementsByTagName("historyHtml")[0].firstChild.nodeValue;
      var nprQ=xmlDoc.getElementsByTagName("recentQ")[0].firstChild.nodeValue;
      var nprA=xmlDoc.getElementsByTagName("recentA")[0].firstChild.nodeValue;
      rebuildNPUI(npClass, npH, nprQ, nprA);
    break;
    case 'pUpdate':
      var pClass=xmlDoc.getElementsByTagName("respState")[0].firstChild.nodeValue;
      var pH=xmlDoc.getElementsByTagName("historyHtml")[0].firstChild.nodeValue;
      var prQ=xmlDoc.getElementsByTagName("recentQ")[0].firstChild.nodeValue;
      var prA=xmlDoc.getElementsByTagName("recentA")[0].firstChild.nodeValue;
      rebuildPUI(pClass, pH, prQ, prA);
    break;
   // ---------------------- ------------------------------ongoing normal play
    case 'jStateInfo':
      var jState=xmlDoc.getElementsByTagName("jState")[0].firstChild.nodeValue; 
      switch (jState) {
        case 'waiting': 
          $('#tabOne').removeClass('active').addClass('waiting');
          setUI(1);
          // set timed ajax call to check changed status
          $('#timer').show();
          $('#timer').hide(2000, function(e) { fireJStatus(); });                                                 
        break;        
        case 'active': 
          $('#tabOne').removeClass('rating').addClass('askQuestion');
          var jHistory=xmlDoc.getElementsByTagName("historyHtml")[0].firstChild.nodeValue;
          $('#judgeContent').find('.history').html(jHistory);
          setUI(1);
        break;        
        case 'rating' :
          // try {
          //   $('#soundPlayer').play();
          // }
          // catch(err) {
          //   console.log(err.message);
          // }
          var rjH=xmlDoc.getElementsByTagName("historyHtml")[0].firstChild.nodeValue;
          var rjQ=xmlDoc.getElementsByTagName("jQ")[0].firstChild.nodeValue;
          var rr1=xmlDoc.getElementsByTagName("lContent")[0].firstChild.nodeValue;
          var rr2=xmlDoc.getElementsByTagName("rContent")[0].firstChild.nodeValue;
          var rjrbHtml=xmlDoc.getElementsByTagName("jRatingHtml")[0].firstChild.nodeValue;
          var rjfinalrHtml=xmlDoc.getElementsByTagName("jFinalRatingHtml")[0].firstChild.nodeValue;
          useLikert=xmlDoc.getElementsByTagName("useLikert")[0].firstChild.nodeValue;
          useFinalReason=xmlDoc.getElementsByTagName("useReasonFinalRating")[0].firstChild.nodeValue;
          randomiseSideS1 = xmlDoc.getElementsByTagName("randomiseSideS1")[0].firstChild.nodeValue;
          npLeft = xmlDoc.getElementsByTagName("npLeft")[0].firstChild.nodeValue;
          rebuildJUI('rating', rjH, rjQ, rr1, rr2, rjrbHtml, rjfinalrHtml);
        break;
        case 'finalRating' : 
          // do a quickfire jStatus reload to get final rating screen
          $('#timer').show();
          $('#timer').hide(500, function(e) { fireJStatus(); });                                                                    
        break;        
      }
      break;
      case 'respStateInfo':
        var respState=xmlDoc.getElementsByTagName("respState")[0].firstChild.nodeValue;
        var retRole=xmlDoc.getElementsByTagName("role")[0].firstChild.nodeValue;
        switch (respState) {
          case 'waiting': 
            if (retRole == 'NP') {
              $('#tabTwo').removeClass('answerQuestion').addClass('waiting');
              setUI(2);  
            }
            else {
              $('#tabThree').removeClass('answerQuestion').addClass('waiting');
              setUI(3);                                                        
          }
            // set timed ajax call to check change status
            $('#timer').show();
            $('#timer').hide(1000, function(e) { fireRespStatus(); });                                                 
          break;            
        }
      break;
      // ----------   TODO: modify for Classic
      case 'NPQ' :
        var npcontent=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        processNPQ(npcontent);
      break;                    
      case 'PQ' :
        var pcontent=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        processPQ(pcontent);
      break;  
      case 'JAs' :
        var leftr=xmlDoc.getElementsByTagName("lContent")[0].firstChild.nodeValue;
        var rightr=xmlDoc.getElementsByTagName("rContent")[0].firstChild.nodeValue;
        var jrHtml=xmlDoc.getElementsByTagName("jrHtml")[0].firstChild.nodeValue;
        // list of fields to be used in rating: needed for js validation
        useLikert=xmlDoc.getElementsByTagName("useLikert")[0].firstChild.nodeValue;
        processJ(leftr, rightr, jrHtml);
      break;
      case 'fRating':
        var frHtml=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        useFinalReason=xmlDoc.getElementsByTagName("useFinalReason")[0].firstChild.nodeValue;
        var fhTxt = xmlDoc.getElementsByTagName("header")[0].firstChild.nodeValue;
        var fullTranscript = xmlDoc.getElementsByTagName("finalTranscript")[0].firstChild.nodeValue;
        npLeft = xmlDoc.getElementsByTagName("npLeft")[0].firstChild.nodeValue;
        setFinalRating(fullTranscript, frHtml, fhTxt);
      break;
      case 'jDone':
        var target=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        signalDone(target);
      break;
      case 'jHistory' :
        var jh=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        processJhistory(jh);
      break;
      case 'npHistory' :
        var nph=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        processNPhistory(nph);
      break;
      case 'pHistory' :
        var ph=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        processPhistory(ph);
      break;
    }    
}

function getJStatus() {
  $.post("/webServices/classic/judgeStatus.php",{ userid: uid, experimentID: exptId, groupNo: groupNo, rand:Math.random() } ,function(data) {
    processAJAX(data);
  });    
}

function getRespStatus() {
  $.post("/webServices/classic/respStatus.php",{ userid: uid, experimentID: exptId, groupNo: groupNo, rand:Math.random() } ,function(data) {
    processAJAX(data);
  });        
}

function InitUI(role) {
  switch (role) {
    case 'J' : 
      $('#tabOne').show();
      $('#judgeContent').show();
      $('#tabTwo').hide();
      $('#nonPretenderContent').hide();
      $('#tabThree').hide();
      $('#pretenderContent').hide();
      $('.finalMessage').hide();
      $('.finalHeader').hide();
      $('#dialog-confirm').hide();
      setUI(1);
      setJControls();
      getJStatus();
    break;
    case 'NP' : 
      $('#tabOne').hide();
      $('#judgeContent').hide();
      $('#tabTwo').show();
      $('#nonPretenderContent').addClass('active');
      $('#nonPretenderContent').show();
      $('#tabThree').hide();
      $('#pretenderContent').hide(); 
      $('.finalMessage').hide();
      $('.finalHeader').hide();
      $('#dialog-confirm').hide();
      setUI(2);
      setNPControls();
      getRespStatus();
    break;
    case 'P' : 
      $('#tabOne').hide();
      $('#judgeContent').hide();
      $('#tabTwo').hide();
      $('#nonPretenderContent').hide();
      $('#tabThree').show();
      $('#pretenderContent').addClass('active');
      $('#pretenderContent').show(); 
      $('.finalMessage').hide();
      $('.finalHeader').hide();
      $('#dialog-confirm').hide();
      setUI(3);
      setPControls();
      getRespStatus();
    break;
  }
}

function doPageFurniture(xmlDoc) {
  jTabC=xmlDoc.getElementsByTagName("jTab")[0].firstChild.nodeValue;
  jTabUnconnectedC=xmlDoc.getElementsByTagName("jTabUnconnected")[0].firstChild.nodeValue;
  jTabWaitingC=xmlDoc.getElementsByTagName("jTabWaiting")[0].firstChild.nodeValue;
  jTabActiveC=xmlDoc.getElementsByTagName("jTabActive")[0].firstChild.nodeValue;
  jTabRatingC=xmlDoc.getElementsByTagName("jTabRating")[0].firstChild.nodeValue;
  jTabDoneC=xmlDoc.getElementsByTagName("jTabDone")[0].firstChild.nodeValue;
  jWaitingToStartC=xmlDoc.getElementsByTagName("jWaitingToStart")[0].firstChild.nodeValue;
  jPleaseAskC=xmlDoc.getElementsByTagName("jPleaseAsk")[0].firstChild.nodeValue;
  jAskButtonC=xmlDoc.getElementsByTagName("jAskButton")[0].firstChild.nodeValue;
  jWaitingForRepliesC=xmlDoc.getElementsByTagName("jWaitingForReplies")[0].firstChild.nodeValue;
  jHistoryTitleC=xmlDoc.getElementsByTagName("jHistoryTitle")[0].firstChild.nodeValue;
  jRatingTitleC=xmlDoc.getElementsByTagName("jRatingTitle")[0].firstChild.nodeValue;
  jFinalRatingTitleC=xmlDoc.getElementsByTagName("jFinalRatingTitle")[0].firstChild.nodeValue;
  jRatingYourQuestionC=xmlDoc.getElementsByTagName("jRatingYourQuestion")[0].firstChild.nodeValue;
  jRatingQC=xmlDoc.getElementsByTagName("jRatingQ")[0].firstChild.nodeValue;
  jRatingR1C=xmlDoc.getElementsByTagName("jRatingR1")[0].firstChild.nodeValue;
  jRatingR2C=xmlDoc.getElementsByTagName("jRatingR2")[0].firstChild.nodeValue;
  jAskAnotherBC=xmlDoc.getElementsByTagName("jAskAnotherB")[0].firstChild.nodeValue;
  jNoMoreBC=xmlDoc.getElementsByTagName("jNoMoreB")[0].firstChild.nodeValue;
  jSaveFinalBC=xmlDoc.getElementsByTagName("jSaveFinalB")[0].firstChild.nodeValue;
  jFinalMsgC=xmlDoc.getElementsByTagName("jFinalMsg")[0].firstChild.nodeValue;
  jConfirmHeadC=xmlDoc.getElementsByTagName("jConfirmHead")[0].firstChild.nodeValue;
  jConfirmBodyC=xmlDoc.getElementsByTagName("jConfirmBody")[0].firstChild.nodeValue;
  jConfirmOKC=xmlDoc.getElementsByTagName("jConfirmOK")[0].firstChild.nodeValue;
  jConfirmCancelC=xmlDoc.getElementsByTagName("jConfirmCancel")[0].firstChild.nodeValue;
  npTabC=xmlDoc.getElementsByTagName("npTab")[0].firstChild.nodeValue;
  pTabC=xmlDoc.getElementsByTagName("pTab")[0].firstChild.nodeValue;
  rTabInactiveC=xmlDoc.getElementsByTagName("rTabInactive")[0].firstChild.nodeValue;
  rTabActiveC=xmlDoc.getElementsByTagName("rTabActive")[0].firstChild.nodeValue;
  rTabWaitingC=xmlDoc.getElementsByTagName("jTabWaiting")[0].firstChild.nodeValue;
  rTabDoneC=xmlDoc.getElementsByTagName("rTabDone")[0].firstChild.nodeValue;
  rWaitFirstC=xmlDoc.getElementsByTagName("rWaitFirst")[0].firstChild.nodeValue;
  rWaitNextC=xmlDoc.getElementsByTagName("rWaitNext")[0].firstChild.nodeValue;
  rHistoryTitleC=xmlDoc.getElementsByTagName("rHistoryTitle")[0].firstChild.nodeValue;
  rCurrentQ=xmlDoc.getElementsByTagName("rCurrentQ")[0].firstChild.nodeValue;
  rYourAnswerC=xmlDoc.getElementsByTagName("rYourAnswer")[0].firstChild.nodeValue;
  rInstructionC=xmlDoc.getElementsByTagName("rInstruction")[0].firstChild.nodeValue;
  rSendBC=xmlDoc.getElementsByTagName("rSendB")[0].firstChild.nodeValue;
  rGuidanceHeaderC=xmlDoc.getElementsByTagName("rGuidanceHeader")[0].firstChild.nodeValue;
  npGuidanceC=xmlDoc.getElementsByTagName("npGuidance")[0].firstChild.nodeValue;
  pGuidanceC=xmlDoc.getElementsByTagName("pGuidance")[0].firstChild.nodeValue;
  rFinalMsgC=xmlDoc.getElementsByTagName("rFinalMsg")[0].firstChild.nodeValue;
  randomiseSideS1 = xmlDoc.getElementsByTagName("randomiseSideS1")[0].firstChild.nodeValue;
  s1IntentionLabel = xmlDoc.getElementsByTagName("s1IntentionLabel")[0].firstChild.nodeValue;
  useS1Intention = xmlDoc.getElementsByTagName("useS1Intention")[0].firstChild.nodeValue;
  useS1IntentionMin = xmlDoc.getElementsByTagName("useS1IntentionMin")[0].firstChild.nodeValue;
  s1IntentionMin = xmlDoc.getElementsByTagName("s1IntentionMin")[0].firstChild.nodeValue;
  useS1AlignmentControl = xmlDoc.getElementsByTagName("useS1AlignmentControl")[0].firstChild.nodeValue;
  useS1MinQuestionLimit = xmlDoc.getElementsByTagName("useS1MinQuestionLimit")[0].firstChild.nodeValue;
  s1MinQuestionLimit = xmlDoc.getElementsByTagName("s1MinQuestionLimit")[0].firstChild.nodeValue;
  s1IntentionMinLabel = xmlDoc.getElementsByTagName("s1IntentionMinLabel")[0].firstChild.nodeValue;
  s1QuestionMinLabel = xmlDoc.getElementsByTagName("s1QuestionMinLabel")[0].firstChild.nodeValue;
  useS1MinQuestionCount = xmlDoc.getElementsByTagName("useS1MinQuestionCount")[0].firstChild.nodeValue;
  s1MinQuestionCount = xmlDoc.getElementsByTagName("s1MinQuestionCount")[0].firstChild.nodeValue;
  $('#dialog-confirm').attr('title', jConfirmHeadC);
  $('#dialog-confirm').html("<p><span class=\"ui-icon ui-icon-alert\" style=\"float:left; margin: 0 7px 20px 0;\"></span>"+jConfirmBodyC+"</p>");
  $('#tabOne').html(jTabC+' <span>'+jTabUnconnectedC+'</span>');
  $('#jInitWaitingMsg').html('<h2>'+jWaitingToStartC+'</h2>');
  $('#jWaitingAction').html('<h2>'+jWaitingForRepliesC+'</h2>');
  $('h2', '.jAskQuestion').html(jPleaseAskC);
  //$('#jFinalHeader').html('<h2>'+jFinalRatingTitleC+'</h2>'); // now built -into data from listener
  $('#jSend').attr('value', jAskButtonC);
  $('#nextQ').attr('value', jAskAnotherBC);
  $('#noMoreQ').attr('value', jNoMoreBC);
  $('#judgesEndB').attr('value', jSaveFinalBC);
  //$('#jHistoryTitle').html('<h2 class="closed"><a href="#">'+jHistoryTitleC+'</a></h2>');
  $('#jFinalMsg').html('<h2>'+jFinalMsgC+'</h2>');
  $('#jQuestion').html('<p><span>'+jRatingQC+'</span></p>');
  $('#jR1').html(jRatingR1C);
  $('#jR2').html(jRatingR2C);
  $('h2', '#judgeContent .waitingForAction').html(jWaitingForRepliesC);
  
  $('#tabTwo').html(npTabC+' <span>'+rTabInactiveC+'</span>');
  $('#sendNPAnswer').html('<strong>' + rInstructionC + '</strong>');
  $('.initialPage').html('<h2>'+rWaitFirstC+'</h2>');
  $('.waitingPage').html('<h2>'+rWaitNextC+'</h2>');
  $('#npSend').attr('value', rSendBC);
  $('#npHistoryTitle').html('<h2 class="closed">'+rHistoryTitleC+'</h2>');
  $('#npDoneMsg').html('<h2>'+rFinalMsgC+'</h2>');
  
  $('#tabThree').html(pTabC+' <span>'+rTabInactiveC+'</span>');
  $('#sendPAnswer').html('<strong>' + rInstructionC + '</strong>');
  $('#pSend').attr('value', rSendBC);
  $('#pDoneMsg').html('<h2>'+rFinalMsgC+'</h2>');
  $('#pretenderContent').find('.previousQuestions h2 a').html(rHistoryTitleC);
  $('#nonPretenderContent').find('.previousQuestions h2 a').html(rHistoryTitleC);
  $('#judgeContent').find('.previousQuestions h2 a').html(jHistoryTitleC);
  $('h3', '.guidanceNotes').html(rGuidanceHeaderC);
  $('p', '.nonPretendersAnswer > .guidanceNotes').html(npGuidanceC);
  $('p', '.pretendersAnswer > .guidanceNotes').html(pGuidanceC);
  $('strong', '.nonPretendersAnswer').html(rInstructionC);
  $('strong', '.pretendersAnswer').html(rInstructionC);
  // min question-length
  if (useS1MinQuestionLimit) {
    $('#questionMinMessage').html(s1QuestionMinLabel).show();
  }
  else {
    $('#questionMinMessage').hide();
  }
  // intention and alignment
  if (useS1Intention == "1") {
    $('#s1IntentionLabel').html(s1IntentionLabel);
    if (useS1IntentionMin == "1") {
      $('#intentionMinMessage').html(s1IntentionMinLabel).show();
    }
    else {
      $('#intentionMinMessage').hide();
    }
    $('#iIntentionBlock').show();
  }
  else {
    $('#iIntentionBlock').hide();
  }
}

$(document).ready(function() {
  $('#classicWrapper').hide();
  $('#validateMsg').hide();
  $('#connectMsg').hide();
  $('#inActiveMsg').hide();
  $('#admin').show();
  $('h2', '.previousQuestions').removeClass('open');
  $('h2', '.previousQuestions').addClass('closed');
  $('.history').hide();  
  $('h2', '.previousQuestions').click(function(e){
    if ($(this).hasClass('open')) {
      $(this).removeClass('open').addClass('closed');
      $('.history').hide();
    }
    else {
      $(this).removeClass('closed').addClass('open');
      $('.history').show();     
    }
  });
  $("#loginFormID").submit(function() {
    $('#validateMsg').hide();
    $('#connectMsg').hide();
    $('#inActiveMsg').hide();
    //check the username exists or not from ajax
    $.post("/webServices/classic/authenticateClassic.php",{ username:$('#emailT').val(),password:$('#p1').val(),rand:Math.random() } ,function(data) {
      var xmlDoc = txtToXmlDoc(data);  
      var messageType=xmlDoc.getElementsByTagName("messageType")[0].firstChild.nodeValue;
      if (messageType=='loginResults') {
        var success=xmlDoc.getElementsByTagName("success")[0].firstChild.nodeValue;
        if (success=='logged-in!') {
          uid=xmlDoc.getElementsByTagName("uid")[0].firstChild.nodeValue;
          isActive=xmlDoc.getElementsByTagName("isActive")[0].firstChild.nodeValue;
          exptType=xmlDoc.getElementsByTagName("exptType")[0].firstChild.nodeValue;
          if (isActive=='1' && exptType=='classic') {
            exptId=xmlDoc.getElementsByTagName("exptId")[0].firstChild.nodeValue;
            respRole=xmlDoc.getElementsByTagName("role")[0].firstChild.nodeValue;
            dayNo=xmlDoc.getElementsByTagName("dayNo")[0].firstChild.nodeValue;
            sessionNo=xmlDoc.getElementsByTagName("sessionNo")[0].firstChild.nodeValue;                  
            groupNo = xmlDoc.getElementsByTagName("groupNo")[0].firstChild.nodeValue;                  
            qNo = xmlDoc.getElementsByTagName("qNo")[0].firstChild.nodeValue;  
            doPageFurniture(xmlDoc);
            // move to Classic 
            $('#connectMsg').show();
            $('#loginB').hide();                        
            $('#admin').hide(3000, function() {$('#classicWrapper').show();});                        
            InitUI(respRole);
          }
          else {
            $('#inActiveMsg').show();
          }
        }
        else {
          $('#validateMsg').show();                    
        }
      }
    });
    return false; //don't post the form physically
  });
});



