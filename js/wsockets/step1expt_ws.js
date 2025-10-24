var Server;
var intervalId;
var categoryIntervalId;
var respondent1IntervalId;
var respondent2IntervalId;
var useS1IntentionMin;
var useS1Intention;
var intentionMinValue = 0;
var intentionLabel;
var intentionGuidance;
var reasonMinValue;
var finalReasonMinValue;
var extraIntervalId;
var finalIntervalId = '';
var useLikert;
var useReasons;
var useFinalReason;
var useFinalLikert;
var useS1QCategoryControl;
var useS1AlignmentControl;
var urlParams = {};
var srcName;
var url;
var index;
var filename;
var clientId;
var uid;
var exptId;
var jType;
var jNo;
var dayNo;
var sessionNo;
var exptType;
var exptStage;
var finishedProbe;
var finalQ = 0;
var randomiseSideS1 = 0;
var npSide = 0;
var useBarbilliardsControl;
var noMandatoryQuestions;
var qNo;

var connected=false;
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
var useMacro = false;
var controlValidated = {};
var controlResponse = {};
var controlResponseType = {};


// <editor-fold defaultstate="collapsed" desc=" UI controls">

function jHistoryFlip() {
  if ($('#jHistoryTitle').hasClass('closed')) {          
    $('#jHistoryTitle').removeClass('closed').addClass('open');
    $('#jHistoryTitle').find('h2').removeClass('closed').addClass('open');
  }
  else {
    $('#jHistoryTitle').removeClass('open').addClass('closed');
    $('#jHistoryTitle').find('h2').removeClass('open').addClass('closed');
  }
  jHistorySync();
}

function jHistorySync() {
  if ($('#jHistoryTitle').hasClass('open')) {          
    $('#jHistory').css('display','inline-block');
    $('#judgeContent').find('.previousQuestion').css('display','block');
    $('#judgeContent').find('.previousQuestion').find('.responseOne').css('display','inline-block');
    $('#judgeContent').find('.previousQuestion').find('.responseTwo').css('display','inline-block');
  }
  else {
      $('#jHistory').css('display','none');
      $('#judgeContent').find('.previousQuestion').css('display','none');
      $('#judgeContent').find('.previousQuestion').find('.responseOne').css('display','none');
      $('#judgeContent').find('.previousQuestion').find('.responseTwo').css('display','none');            
  }   
}

function setJHistoryControls() {
  jHistorySync();
  $('#jHistoryTitle').unbind('click').on('click', 'h2', function(event) { jHistoryFlip(); });    
}

function npHistoryFlip() {
  if ($('#npHistoryTitle').hasClass('closed')) {
    $('#npHistoryTitle').removeClass('closed').addClass('open');
    $('#npHistoryTitle').find('h2').removeClass('closed').addClass('open');
  }
  else {
    $('#npHistoryTitle').removeClass('open').addClass('closed');
    $('#npHistoryTitle').find('h2').removeClass('open').addClass('closed');
  }
  npHistorySync();
}

function npHistorySync() {
  if($('#npHistoryTitle').hasClass('open')) {
    $('#npHistory').css('display', 'block');
    $('#nonPretenderContent').find('.previousQuestion').css('display','block');
    $('#nonPretenderContent').find('.response').css('display','block');
  }
  else {
    $('#npHistory').css('display', 'none');
    $('#nonPretenderContent').find('.previousQuestion').css('display','none');
    $('#nonPretenderContent').find('.response').css('display','none');        
  }    
}

function setNPHistoryControls() {
  npHistorySync();
  $('#npHistoryTitle').unbind('click').on('click', 'h2', function(event) { npHistoryFlip(); });
}

function pHistoryFlip() {
  if ($('#pHistoryTitle').hasClass('closed')) {
    $('#pHistoryTitle').removeClass('closed').addClass('open');
    $('#pHistoryTitle').find('h2').removeClass('closed').addClass('open');
  }
  else {
    $('#pHistoryTitle').removeClass('open').addClass('closed');
    $('#pHistoryTitle').find('h2').removeClass('open').addClass('closed');
  }
  pHistorySync();    
}

function pHistorySync() {
  if($('#pHistoryTitle').hasClass('open')) {
    $('#pHistory').css('display', 'block');
    $('#pretenderContent').find('.previousQuestion').css('display','block');
    $('#pretenderContent').find('.response').css('display','block');
  }
  else {
    $('#pHistory').css('display', 'none');
    $('#pretenderContent').find('.previousQuestion').css('display','none');
    $('#pretenderContent').find('.response').css('display','none');        
  }    
}

function setPHistoryControls() {
  pHistorySync();
  $('#pHistoryTitle').unbind('click').on('click', 'h2', function(event) { pHistoryFlip(); });
}

function setExtraLikertControls() {
  var intervalCount = $('.slideBar').find('.extraInterval').length;
  $('.extraInterval').css('width' , 100 / intervalCount + '%');
  var number = 1;	
  $('.slideBar').find('.extraInterval').each(function(){
    $(this).attr('id', 'extrainterval' + number++);
  });
  $('#extraInterval1').css('left' , 0 + '%');
  $('#extraInterval2').css('left' , 100 / intervalCount + '%');
  $('#extraInterval3').css('left' , 100 / intervalCount * 2 + '%');
  $('#extraInterval4').css('left' , 100 / intervalCount * 3 + '%');
  $('#extraInterval5').css('left' , 100 / intervalCount * 4 + '%');
  $('#extraInterval6').css('left' , 100 / intervalCount * 5 + '%');
  $('#extraInterval7').css('left' , 100 / intervalCount * 6 + '%');
  $('#extraInterval8').css('left' , 100 / intervalCount * 7 + '%');
  $('#extraInterval9').css('left' , 100 / intervalCount * 8 + '%');
  $('.extraSlidePointer').draggable( { containment: '.slideBar',
    create: function(){$(this).data('position',$(this).position())},
    cursorAt:{left:27},
    start:function(){$(this).stop(true,true)}}
  );
  $('.slideBar').find('.extraInterval').droppable({
      drop:function(event, ui) {
        snapToMiddle(ui.draggable,$(this));
        // Do something when pointer lands over each interval
        extraIntervalId = $(this).attr('id');
        checkRatingValidation()
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
    $('.extraSlidePointer').css('left',0);
    return false;
  });        
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

function setRespondentLikertControls() {
  // note - these could be radio buttons or sliders, but the jQuery can try to bind to either
  $('.irb').change(function() {
    var id = $(this).attr('id');
    var details = id.split('_');
    if (details[1]=='1') {
      respondent1IntervalId = details[2];     
    }
    else {
      respondent2IntervalId = details[2];           
    }
    checkRatingValidation();
  });
  var intervalCount = $('.respondent1Slider').find('.respondentInterval').length;
  $('.respondentInterval').css('width' , 100 / intervalCount + '%');
  //var number = 1;	
  $('#respondent1Interval1').css('left' , 0 + '%');
  $('#respondent1Interval2').css('left' , 100 / intervalCount + '%');
  $('#respondent1Interval3').css('left' , 100 / intervalCount * 2 + '%');
  $('#respondent1Interval4').css('left' , 100 / intervalCount * 3 + '%');
  $('#respondent1Interval5').css('left' , 100 / intervalCount * 4 + '%');
  $('#respondent1Interval6').css('left' , 100 / intervalCount * 5 + '%');
  $('#respondent1Interval7').css('left' , 100 / intervalCount * 6 + '%');
  $('#respondent1Interval8').css('left' , 100 / intervalCount * 7 + '%');
  $('#respondent1Interval9').css('left' , 100 / intervalCount * 8 + '%');
  $('.respondent1SlidePointer').draggable( {
      containment: '#r1SlideBar',
      create: function(){$(this).data('position',$(this).position())},
      cursorAt:{left:27},
      start:function(){$(this).stop(true,true)}
  });
  $('.respondent1SlideBar').find('.respondentInterval').droppable({
      drop:function(event, ui) {
        snapToMiddle(ui.draggable,$(this));
        // Do something when pointer lands over each interval
        respondent1IntervalId = $(this).attr('id');
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
    $('.respondent1SlidePointer').css('left',0);
    return false;
  }); 
  intervalCount = $('.respondent2Slider').find('.respondentInterval').length;
  $('.respondentInterval').css('width' , 100 / intervalCount + '%');
  //number = 1;	
  $('#respondent2Interval1').css('left' , 0 + '%');
  $('#respondent2Interval2').css('left' , 100 / intervalCount + '%');
  $('#respondent2Interval3').css('left' , 100 / intervalCount * 2 + '%');
  $('#respondent2Interval4').css('left' , 100 / intervalCount * 3 + '%');
  $('#respondent2Interval5').css('left' , 100 / intervalCount * 4 + '%');
  $('#respondent2Interval6').css('left' , 100 / intervalCount * 5 + '%');
  $('#respondent2Interval7').css('left' , 100 / intervalCount * 6 + '%');
  $('#respondent2Interval8').css('left' , 100 / intervalCount * 7 + '%');
  $('#respondent2Interval9').css('left' , 100 / intervalCount * 8 + '%');
  $('.respondent2SlidePointer').draggable( {
      containment: '#r2SlideBar',
      create: function(){$(this).data('position',$(this).position())},
      cursorAt:{left:27},
      start:function(){$(this).stop(true,true)}
  });
  $('.respondent2SlideBar').find('.respondentInterval').droppable({
      drop:function(event, ui) {
        snapToMiddle(ui.draggable,$(this));
        // Do something when pointer lands over each interval
        respondent2IntervalId = $(this).attr('id');
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
    $('.respondent2SlidePointer').css('left',0);
    return false;
  });    
  
}

function setCategoryLikertControls() {
  var intervalCount = $('.categorySlideBar').find('.categoryInterval').length;
  $('.categoryInterval').css('width' , 100 / intervalCount + '%');
  var number = 1;	
//  $('.slideBar').find('.respondentInterval').each(function(){
//    $(this).attr('id', 'interval' + number++);
//  });
  $('#categoryInterval1').css('left' , 0 + '%');
  $('#categoryInterval2').css('left' , 100 / intervalCount + '%');
  $('#categoryInterval3').css('left' , 100 / intervalCount * 2 + '%');
  $('#categoryInterval4').css('left' , 100 / intervalCount * 3 + '%');
  $('#categoryInterval5').css('left' , 100 / intervalCount * 4 + '%');
  $('#categoryInterval6').css('left' , 100 / intervalCount * 5 + '%');
  $('#categoryInterval7').css('left' , 100 / intervalCount * 6 + '%');
  $('#categoryInterval8').css('left' , 100 / intervalCount * 7 + '%');
  $('#categoryInterval9').css('left' , 100 / intervalCount * 8 + '%');
  $('.categorySlidePointer').draggable( {
      containment: '.categorySlideBar',
      create: function(){$(this).data('position',$(this).position())},
      cursorAt:{left:27},
      start:function(){$(this).stop(true,true)}
  });
  $('.categorySlideBar').find('.categoryInterval').droppable({
      drop:function(event, ui) {
        snapToMiddle(ui.draggable,$(this));
        // Do something when pointer lands over each interval
        categoryIntervalId = $(this).attr('id');
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
    $('.categorySlidePointer').css('left',0);
    return false;
  });    
}

function setFinalCategoryLikertControls() {
  var intervalCount = $('.categorySlideBar').find('.categoryInterval').length;
  $('.categoryInterval').css('width' , 100 / intervalCount + '%');
  categoryIntervalId = '';
//  $('.slideBar').find('.respondentInterval').each(function(){
//    $(this).attr('id', 'interval' + number++);
//  });
  $('#categoryInterval1').css('left' , 0 + '%');
  $('#categoryInterval2').css('left' , 100 / intervalCount + '%');
  $('#categoryInterval3').css('left' , 100 / intervalCount * 2 + '%');
  $('#categoryInterval4').css('left' , 100 / intervalCount * 3 + '%');
  $('#categoryInterval5').css('left' , 100 / intervalCount * 4 + '%');
  $('#categoryInterval6').css('left' , 100 / intervalCount * 5 + '%');
  $('#categoryInterval7').css('left' , 100 / intervalCount * 6 + '%');
  $('#categoryInterval8').css('left' , 100 / intervalCount * 7 + '%');
  $('#categoryInterval9').css('left' , 100 / intervalCount * 8 + '%');
  $('.categorySlidePointer').draggable( {
      containment: '.categorySlideBar',
      create: function(){$(this).data('position',$(this).position())},
      cursorAt:{left:27},
      start:function(){$(this).stop(true,true)}
  });
  $('.categorySlideBar').find('.categoryInterval').droppable({
      drop:function(event, ui) {
        snapToMiddle(ui.draggable,$(this));
        // Do something when pointer lands over each interval
        categoryIntervalId = $(this).attr('id');
        checkFinalRatingValidation();
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
    $('.categorySlidePointer').css('left',0);
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
  $('#jReason').keyup(function() { checkRatingValidation(); });  
  setLikertControls();
  setRespondentLikertControls();
  setCategoryLikertControls();
  $('#nextQ').attr("disabled","disabled");
  $('#nextQ').addClass('greyed');
  $('#noMoreQ').attr("disabled","disabled");
  $('#noMoreQ').addClass('greyed');
  $('.choice input').attr("checked",false);
  intervalId='';
  respondent1IntervalId='';
  respondent2IntervalId='';
  categoryIntervalId='';
}

function checkRatingValidation() {
  var reason;
  var choice='';
  var validated=true;
  var $radios = $('input:radio[name=judgement]');
  if ($radios[0]['checked'] == true) {choice='0';}
  if ($radios[1]['checked'] == true) {choice='1';}
  if (choice=='') { validated=false; }
  if (useReasons==1) {
    reason=$('#jReason').val();
    if (reason.length < reasonMinValue) { validated=false; }
  }
  if (useLikert==1) {
    if (intervalId=='') { validated=false; }
  }
//  if (useS1IntentionMin == 1) {
//    alignmentIntention=$('#alignmentIntention').val();
//    if (alignmentIntention.length  < intentionMinValue) { validated=false; }       
//  }
  if (useS1QCategoryControl == 1) {
    if (categoryIntervalId == '') { validated=false; }    
  }
  if (useS1AlignmentControl == 1) {
    if (respondent1IntervalId == '') { validated=false; }    
    if (respondent2IntervalId == '') { validated=false; }    
  }  
  if (validated) {
    // barbilliard control is now called 'experimentor can force end' and works with
    // minimum n of questions so that E can choose to force an early end if games are slow
    // if not it defaults to check for minimum #
    // finalQ == 1 means E has pressed the button to force end
    if (finalQ === '1') {
      $('#noMoreQ').removeAttr("disabled").removeClass('greyed');
      $('#nextQ').attr("disabled","disabled");
      $('#nextQ').addClass('greyed');
    }
    else {
      if (qNo < noMandatoryQuestions) {
        $('#nextQ').removeAttr("disabled").removeClass('greyed');
      }
      else {
        $('#noMoreQ').removeAttr("disabled").removeClass('greyed');
        $('#nextQ').removeAttr("disabled").removeClass('greyed');
      }
    }
  }
}

function setFinalLikertControls() {  
  var intervalCount = $('.finalSlideBar').find('.finalInterval').length;
  $('.finalInterval').css('width' , 100 / intervalCount + '%');
  // Add incremental id to divs with a class of interval. These can then be spaced out using css
  finalIntervalId = '';	
  var number=1;
  $('.finalSlideBar').find('.finalInterval').each(function() {
    $(this).attr('id', 'finalInterval' + number++);
  });
  // Set the css for the different intervals
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
  $('.finalSlideBar').find('.finalInterval').droppable({
    drop:function(event, ui) {
      snapToMiddle(ui.draggable,$(this));
      // Do something when pointer lands over each interval
      finalIntervalId = $(this).attr('id');
      checkFinalRatingValidation();
    }
  });	
  // Snap the draggable to to middle of the droppabble
  function snapToMiddle(dragger, target) {
    var bottomMove = target.position().bottom - dragger.data('position').bottom + (target.outerHeight(true) - dragger.outerHeight(true)) / 2;
    var leftMove= target.position().left - dragger.data('position').left + (target.outerWidth(true) - dragger.outerWidth(true)) / 2;
    dragger.animate({bottom:bottomMove,left:leftMove},{duration:500,easing:'easeOutBack'});
  }
}

function setFinalRatingControls() {
  $('#judgesRating').html('');
  $('.finalChoice input').click(function() {
    checkFinalRatingValidation();        
    $('.finalChoice').removeClass('chosen');
    var lr=$(this).attr('value');
    if (lr=='left_judgement') {
      $('#fcLeft').addClass('chosen');
    }
    else {
      $('#fcRight').addClass('chosen');
    }
  });
  $('#judgesMainReason').keyup(function() { checkFinalRatingValidation(); });  
  setFinalLikertControls();
  //setRespondentLikertControls();
  setFinalCategoryLikertControls();
  $('#judgesMainReason').keyup(function() { checkFinalRatingValidation(); });
  $('#judgesEndB').attr("disabled","disabled");
  $('#judgesEndB').addClass('greyed');
  $('.finalChoice input').attr("checked",false);
  respondentIntervalId='';
  categoryIntervalId='';
}

function checkFinalRatingValidation() {
  var choice='';
  var reason='';
  var validated = true;
  var $radios = $('input:radio[name=finalJudgement]');
  if ($radios[0]['checked'] == true) {choice='0';}
  if ($radios[1]['checked'] == true) {choice='1';}
//  if ($radios.filter('[value=left_judgement]').attr('checked')) {choice='0';}
//  if ($radios.filter('[value=right_judgement]').attr('checked')) {choice='1';}            
  if (choice=='') {validated=false;} 
  if (useFinalReason==1) {
    reason=$('#judgesMainReason').val();
    if (reason.length <= finalReasonMinValue) {validated=false;}
  }
  if (useFinalLikert==1) {
    if (finalIntervalId == '') {validated=false;}      
  }
  
//  if (useS1QCategoryControl == 1) {
//    if (categoryIntervalId == '') { validated=false; }        
//  }
//  if (useS1AlignmentControl == 1) {
//    if (respondentIntervalId == '') { validated=false; }    
//  }  
  if (validated) {
    $('#judgesEndB').removeAttr("disabled").removeClass('greyed');    
  }
}

function setNPA() {
  $('#npSend').removeAttr("disabled");
  $('#npSend').removeClass("greyed");
  // TODO - disable by default on production - this helps iMacros scripts
  if (useMacro) {    
  }
  else {
    if ($('#npA').val()>'') {
      $('#npSend').removeAttr("disabled").removeClass('greyed');        
    }
    else {
      $('#npSend').attr("disabled","disabled");
      $('#npSend').addClass('greyed');
    }
  }
}

function setNPControls() {
  setNPA();
  $('#npA').keyup(function() { setNPA(); });
}

function setP() {
  $('#pSend').removeAttr("disabled");
  $('#pSend').removeClass('greyed');    
  // TODO - disable by default on production - this helps iMacros scripts 
  if (useMacro) {   
  }
  else {
    if ($('#pA').val()>'') {
      $('#pSend').removeAttr("disabled").removeClass('greyed');        
    }
    else {
        $('#pSend').attr("disabled","disabled");
        $('#pSend').addClass('greyed');
    }    
  }
}

function setPControls() {
  setP();
  $('#pA').keyup(function() { setP(); });
}

function setJQ() {
  if ($('#jQ').val()> '') {
    if (useS1Intention == 1) {
      if ($('#iIntention').val().length > intentionMinValue) {
        $('#jSend').removeAttr("disabled").removeClass('greyed');                
      }
    }
    else {
      $('#jSend').removeAttr("disabled").removeClass('greyed');                
    }
  }
  else {
    $('#jSend').attr("disabled","disabled");
    $('#jSend').addClass('greyed');
  }  
}

function setJControls() {
  setJQ();
  $('#jQ').keyup(function() { setJQ(); });
  $('#iIntention').keyup(function() { setJQ(); });  
}

function processRatingButton(finalRating) {
  var choice='';
  var $radios = $('input:radio[name=judgement]');
  if ($radios[0]['checked'] == true) {choice='0';}
  if ($radios[1]['checked'] == true) {choice='1';}
  var reason = encodeURIComponent($('#jReason').val());
  if (finalRating == 0) {
    var xml='<message><messageType>JR</messageType><content>'+choice+'</content>' + 
            '<content>'+reason+'</content><content>'+intervalId+'</content><content>'+npSide+'</content><content>'+respondent1IntervalId+'</content><content>'+respondent2IntervalId+'</content><content>'+categoryIntervalId+'</content></message>';
    send(xml);
    $('#tabOne').removeClass('rating').addClass('askQuestion');
    $('#tabOne').html(jTabC+'<span>'+jTabActiveC+'</span>'); 
    setUI(1);
  }
  else {
    var xml='<message><messageType>JlastR</messageType><content>'+choice+'</content>' +
            '<content>'+reason+'</content><content>'+intervalId+'</content><content>'+npSide+'</content><content>'+respondent1IntervalId+'</content><content>'+respondent2IntervalId+'</content><content>'+categoryIntervalId+'</content></message>';
    send(xml);
  }              
}

function processFinalRatingButton() {
  $('#judgesEndB').unbind();
  var choice='';
  var $radios = $('input:radio[name=finalJudgement]');
  if ($radios[0]['checked'] == true) {choice='0';}
  if ($radios[1]['checked'] == true) {choice='1';}
//  if ($radios.filter('[value=left_judgement]').attr('checked')) {choice='0';}
//  if ($radios.filter('[value=right_judgement]').attr('checked')) {choice='1';} 
  var reason = encodeURIComponent($('#judgesMainReason').val());  //judgesFinalReason
  var fLikert = 'unused';
  if (useFinalLikert == 1) { fLikert = finalIntervalId; }
  var xml='<message><messageType>JfinalR</messageType><content>'+choice+'</content>' + 
          '<content>'+reason+'</content><content>'+fLikert+'</content><content>'+npSide+'</content></message>';
  send(xml);
  $('#tabOne').removeClass('finalRating').addClass('doneJudging');
  $('#tabOne').html(jTabC+'<span>'+jTabDoneC+'</span>'); 
  setUI(1);                                   
}

function setFinalRating(frHtml) {
  finalIntervalId = '';
  $('#tabOne').removeClass('rating').addClass('finalRating');
  $('#judgesFinalRating').html(frHtml);
  setUI(1);     
}


// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" rebuild and reconnection">

function rebuildJUI(jState,history,Q,r1,r2,jrHtml,jfinalrHtml) {
  $('#tabOne').unbind();
  //reset all state classes
  $('#tabOne').removeClass('unConnected');
  $('#tabOne').removeClass('empty');
  $('#tabOne').removeClass('isLoggedIn');
  $('#tabOne').removeClass('waiting');
  $('#tabOne').removeClass('waitingToStart');
  $('#tabOne').removeClass('askQuestion');
  $('#tabOne').removeClass('rating');
  $('#tabOne').removeClass('finalRating');
  $('#tabOne').removeClass('doneJudging');
  $('#judgeContent').find('.latestQuestion').html('<p><span>'+rCurrentQ+'</span>'+Q+'</p>');   
  // set from actions
  switch (jState) {
    case "done": 
      $('#tabOne').addClass('doneJudging');
    break;
    case "active":
      $('#tabOne').html(jTabC+'<span>'+jTabActiveC+'</span>');
      if (useS1Intention == 1) { 
        $('#iIntentionBlock').show();      
      } 
      else {
        $('#iIntentionBlock').hide();              
      }
      setJControls();
      $('#tabOne').addClass('askQuestion');
    break;
    case "waiting":
      $('#tabOne').html(jTabC+'<span>'+jTabWaitingC+'</span>');
      $('#tabOne').addClass('waiting');
    break;
    case "rating":
      reconnectJRating(Q,r1,r2,jrHtml);
      $('#tabOne').addClass('rating');         
    break;            
    case "finalRating":
      reconnectJFinalRating(Q,r1,r2,jfinalrHtml);
      $('#tabOne').addClass('finalRating');
    break;
    case "done":
      $('#tabOne').addClass('doneJudging');
    break;
  }
  if (randomiseSideS1 == 0) {
    processJhistory(history);
    setJHistoryControls();
  }
  setUI(1);
}

function rebuildNPUI(npState,history,Q,A) {
  $('#tabTwo').unbind();
  //reset all state classes
  $('#tabTwo').removeClass('done');
  $('#tabTwo').removeClass('answerQuestion');
  $('#tabTwo').removeClass('waitingForAction');
  $('#tabTwo').removeClass('waiting');
  // set from actions
  switch (npState) {
    case "active" : 
      $('#nonPretenderContent').find('.latestQuestion').html('<h2>'+rCurrentQ+':</h2><p>'+Q+'</p>'); // show on waiting screen
      $('#nonPretenderContent').find('.currentQuestion').html('<h2>'+rCurrentQ+':</h2><p>'+Q+'</p>'); // ready for next phase
      $('#tabTwo').addClass('answerQuestion');          
    break;
    case "waitingForAction" :
      $('#nonPretenderContent').find('.currentQuestion, .currentAnswer').hide();
      $('#nonPretenderContent').find('.latestQuestion').html('<h2>'+rCurrentQ+':</h2><p>'+Q+'</p>'); // show on waiting screen
      $('#nonPretenderContent').find('.currentQuestion').html('<h2>'+rCurrentQ+':</h2><p>'+Q+'</p>'); // ready for next phase
      $('#nonPretenderContent').find('.currentAnswer').html('<h2>'+rYourAnswerC+':</h2> <p>'+A+'</p>'); // ready for next phase
      $('#tabTwo').addClass('waitingForAction');
    break;
    case "waiting" :
      $('#nonPretenderContent').find('.currentQuestion, .currentAnswer').hide();
      $('#nonPretenderContent').find('.latestQuestion').html('<h2>'+rCurrentQ+':</h2><p>'+Q+'</p>'); // show on waiting screen
      $('#nonPretenderContent').find('.currentQuestion').html('<h2>'+rCurrentQ+':</h2><p>'+Q+'</p>'); // ready for next phase
      $('#nonPretenderContent').find('.currentAnswer').html('<h2>'+rYourAnswerC+':</h2> <p>'+A+'</p>'); // ready for next phase
      $('#tabTwo').addClass('waiting');
    break;
    case "done" :
      $('#tabTwo').addClass('done');
    break;
  }
  processNPhistory(history);
  setNPHistoryControls();
  setUI(2);
  $('#tabTwo').show();
}

function rebuildPUI(pState,history,Q,A) {
  $('#tabThree').unbind();
  $('#tabThree').removeClass('done');
  $('#tabThree').removeClass('answerQuestion');
  $('#tabThree').removeClass('waitingForAction');
  $('#tabThree').removeClass('waiting');
  switch (pState) {
    case "active" : 
      $('#pretenderContent').find('.latestQuestion').html('<h2>'+rCurrentQ+':</h2><p>'+Q+'</p>'); // show on waiting screen
      $('#pretenderContent').find('.currentQuestion').html('<h2>'+rCurrentQ+':</h2><p>'+Q+'</p>'); // ready for next phase
      $('#tabThree').addClass('answerQuestion');          
    break;
    case "waitingForAction" :
      $('#pretenderContent').find('.currentQuestion, .currentAnswer').hide();
      $('#pretenderContent').find('.latestQuestion').html('<h2>'+rCurrentQ+':</h2><p>'+Q+'</p>'); // show on waiting screen
      $('#pretenderContent').find('.currentQuestion').html('<h2>'+rCurrentQ+':</h2><p>'+Q+'</p>'); // ready for next phase
      $('#pretenderContent').find('.currentAnswer').html('<h2>'+rYourAnswerC+':</h2> <p>'+A+'</p>'); // ready for next phase
      $('#tabThree').addClass('waitingForAction');
    break;
    case "waiting" :
      $('#pretenderContent').find('.currentQuestion, .currentAnswer').hide();
      $('#pretenderContent').find('.latestQuestion').html('<h2>'+rCurrentQ+':</h2><p>'+Q+'</p>'); // show on waiting screen
      $('#pretenderContent').find('.currentQuestion').html('<h2>'+rCurrentQ+':</h2><p>'+Q+'</p>'); // ready for next phase
      $('#pretenderContent').find('.currentAnswer').html('<h2>'+rYourAnswerC+':</h2> <p>'+A+'</p>'); // ready for next phase
      $('#tabThree').addClass('waiting');
    break;
    case "done" :
      $('#tabThree').addClass('done');
    break;
  }
  processPhistory(history);
  setPHistoryControls();
  setUI(3);
  $('#tabThree').show();
}

function reconnectJRating(Q,leftr,rightr,jrHtml) {
// for reconnect only
  if (leftr.length>70) {
    if (leftr.length > rightr.length) {
      var diff=leftr.length - rightr.length;
      for (var i=0; i<diff; i++) { rightr='. '+rightr; }
    }
  }
  $('#jQuestion').html('<p><span>'+jRatingQC+' '+Q+'</span></p>');
  $('#jR1').html('<h2>'+jRatingR1C+' </h2><p>'+leftr+'</p>');
  $('#jR2').html('<h2>'+jRatingR2C+' </h2><p>'+rightr+'</p>');
  $('#tabOne').html(jTabC+'<span>'+jTabRatingC+'</span>'); 
  $('#judgesRating').html(jrHtml);
}

function reconnectJFinalRating(Q,leftr,rightr,jrHtml) {
  if (leftr.length>70) {
    if (leftr.length > rightr.length) {
      var diff=leftr.length - rightr.length;
      for (var i=0; i<diff; i++) { rightr='. '+rightr; }
    }           
  }
  $('#jQuestion').html('<p><span>'+jRatingQC+' '+Q+'</span></p>');
  $('#jR1').html('<h2>'+jRatingR1C+' </h2><p>'+leftr+'</p>');
  $('#jR2').html('<h2>'+jRatingR2C+' </h2><p>'+rightr+'</p>');
  $('#tabOne').html(jTabC+'<span>'+jTabRatingC+'</span>'); 
  $('#judgesFinalRating').html(jrHtml);
  $('#judgesFinalRating').attr('display', 'block');
  $('#judgesFinalRating').show();
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" UI processing">

function showFeedback(feedbackMessage, runningScore) {
  $('#feedback').html(feedbackMessage).show().fadeIn(10000).fadeOut(10000);
  $('#runningScore').html(runningScore);
}

function showFinalFeedback(feedbackMessage) {
  $('#finalFeedback').html(feedbackMessage);
  $('.latestQuestion').hide();
  $('.responseOne').hide();
  $('.responseTwo').hide();
  $('#judgesRating').hide();
  $('#judgesRatingButtons').hide();
  $('#jFinalMsg').show();  
}

function processJ(leftr, rightr, jrHtml) {
  $('#judgeContent').find('.responseOne').html('<h2>'+jRatingR1C+':</h2><p>'+leftr+'</p>');
  $('#judgeContent').find('.responseTwo').html('<h2>'+jRatingR2C+':</h2><p>'+rightr+'</p>');
  $('#tabOne').removeClass('waiting').addClass('rating');
  $('#tabOne').html(jTabC+'<span>'+jTabRatingC+'</span>'); 
  $('#judgesRating').html(jrHtml);
  setUI(1);
}

function processNPQ(content) {
  $('#tabTwo').html(npTabC+'<span>'+rTabActiveC+'</span>');
  if ($('#tabTwo').hasClass('waitingForAction')) {
    $('#tabTwo').removeClass('waitingForAction').addClass('answerQuestion');
  }
  else {
    $('#tabTwo').removeClass('waiting').addClass('answerQuestion');
  }
  $('#nonPretenderContent').find('.latestQuestion').html('<h2>'+rCurrentQ+':</h2><p>'+content+'</p>'); // show on waiting screen
  $('#nonPretenderContent').find('.currentQuestion').html('<h2>'+rCurrentQ+':</h2><p>'+content+'</p>'); // ready for next phase
  setUI(2);
}

function processPQ(content) {
  $('#tabThree').html(pTabC+'<span>'+rTabActiveC+'</span>');
  if ($('#tabThree').hasClass('waitingForAction')) {
    $('#tabThree').removeClass('waitingForAction').addClass('answerQuestion');
  }
  else {
    $('#tabThree').removeClass('waiting').addClass('answerQuestion');
  }
  $('#pretenderContent').find('.latestQuestion').html('<h2>'+rCurrentQ+':</h2><p>'+content+'</p>'); // show on waiting screen
  $('#pretenderContent').find('.currentQuestion').html('<h2>'+rCurrentQ+':</h2><p>'+content+'</p>'); // ready for next phase
  setUI(3);
}

function processJhistory(txt) {
  setUI(1);
  $('#jHistory').html(txt);
}

function processNPhistory(txt) {
  setUI(2);
  $('#npHistory').html(txt);
}

function processPhistory(txt) {
  setUI(3);    
  $('#pHistory').html(txt);
}

function processUI() {
  $('#admin').hide();
  $('#step1Wrapper').show();
  $('#tabOne').removeClass('empty');
  $('#tabOne').addClass('isLoggedIn');
  setUI(0);    // 
  // set history controls
  setJHistoryControls();
  setNPHistoryControls();
  setPHistoryControls();
  $('.tab:first, .tabContent:first').addClass('active');
  $('.gameTabs, .adminTabs').on('click', '.tab', function(event) {
    if ($(this).hasClass('active')) {
    }
    else {
        $('.gameTabs .active, .adminTabs .active').removeClass('active');
        $(this).addClass('active');
        $(this).next().addClass('active');
    }
  });
  $('#judgesEndB').unbind().click(function(e) { processFinalRatingButton(); });
  $('#noMoreQ').unbind().click(function(e) {
    processRatingButton(1);
  });
  $('#nextQ').unbind().click(function(e) { processRatingButton(0); });
  $('#jSend').unbind().click(function(e) {
    var text = $('#jQ').val();
    if (text>'') {
      var iIntention = 'unset';
      iIntention = $('#iIntention').val();
      var xml='<message><messageType>JQ</messageType><content>'+encodeURIComponent(text)+'</content><content>'+encodeURIComponent(iIntention)+'</content></message>';
      send(xml);
      console.log(xml);
      $('#jQ').val('');
      $('#iIntention').val('');
      $('#judgeContent').find('.latestQuestion').html('<p><span>'+rCurrentQ+':</span>'+text+'</p>'); // ready for rating
      $('#tabOne').removeClass('askQuestion').addClass('waiting');
      $('#tabOne').html(jTabC+'<span>'+jTabWaitingC+'</span>'); 
      setUI(1);
    }
  });
  $('#npSend').unbind().click(function(e) {
    var text = $('#npA').val();
    if (text>'') {
      var xml='<message><messageType>NPA</messageType><content>'+encodeURIComponent(text)+'</content></message>';
      send(xml);
      $('#npA').val('');
      $('#tabTwo').removeClass('askQuestion').addClass('waiting');
      $('#tabTwo').html(npTabC+'<span>'+rTabWaitingC+'</span>');
      $('#nonPretenderContent').find('.currentAnswer').html('<h2>'+rYourAnswerC+':</h2><p>'+text+'</p>'); // ready for next phase
      setUI(2);
    }
  });
  $('#pSend').unbind().click(function(e) {
    var text = $('#pA').val();
    if (text>'') {
      var xml='<message><messageType>PA</messageType><content>'+encodeURIComponent(text)+'</content></message>';
      send(xml);
      $('#pA').val('');
      $('#tabThree').removeClass('askQuestion').addClass('waiting');
      $('#tabThree').html(pTabC+'<span>'+rTabWaitingC+'</span>');
      $('#pretenderContent').find('.currentAnswer').html('<h2>'+rYourAnswerC+':</h2><p>'+text+'</p>'); // ready for next phase
      setUI(3);
  }
  });
}

function setTabOne() {
  if ($('#tabOne').hasClass('empty')) {
    $('#dialog-confirm').hide();
    $('#judgeContent').find('.latestQuestion, .judgesChoice, .judgesConfidence, .judgesReason, .button, .buttonBlue, .buttonRed, .waitingForAction').hide();
    $('#judgeContent').find('.judgesAskQuestion').hide();
    $('#judgeContent').find('.previousQuestions').hide();
    $('#judgeContent').find('.waitingForStart').hide();
    $('#judgeContent').find('.multipleLogin').hide();
    $('#judgeContent').find('.loggedIn').hide();
    $('#judgeContent').find('.initScreen').show();
    $('#jQuestion').hide();
    $('#jR1').hide();
    $('#jR2').hide();
    $('#tabTwo').hide();
    $('#tabThree').hide();
    $('#judgesRating').hide();
    $('#judgesFinalRating').hide();
    $('#judgeContent').find('.finalMessage').hide();
    $('#judgeContent').find('.finalHeader').hide();
    $('#jHistory').hide();
  }
  if ($('#tabOne').hasClass('isLoggedIn')) {
    $('#judgeContent').find('.initScreen').hide();        
    $('#judgeContent').find('.loggedIn').show();
  }
  if ($('#tabOne').hasClass("waitingToStart")) {
    $('#tabTwo').show();
    $('#tabThree').show();
    $('#judgeContent').find('.loggedIn').hide();
    $('#judgeContent').find('.waitingForStart').show();
    $('#tabOne').html(jTabC+'<span>'+jTabWaitingC+'</span>');
  }
  if ($('#tabOne').hasClass('askQuestion')) {
    $('#judgesRating').hide();
    $('#judgeContent').find('.loggedIn').hide();
    $('#judgeContent').find('.waitingForStart').hide();
    $('#judgeContent').find('.startJoin input, .latestQuestion, .judgesChoice, .judgesConfidence, .judgesReason, .button, .buttonBlue, .buttonRed, .waitingForAction').hide();
    $('#judgeContent').find('.judgesAskQuestion, .judgesAskQuestion h2, .judgesAskQuestion textarea, .judgesAskQuestion input').show();
    if (useS1Intention == 1) {
      $('#iIntentionBlock').show();
    }
    else {
      $('#iIntentionBlock').hide();      
    }
    $('#jQuestion').hide();
    $('#jR1').hide();
    $('#jR2').hide();
    $('#tabOne').html(jTabC+'<span>'+jTabActiveC+'</span>');
    $('#judgeContent').find('.previousQuestions').show();
    setJControls();
  }
  if ($('#tabOne').hasClass('waiting')) {
    $('#judgeContent').find('.loggedIn').hide();
    $('#judgeContent').find('.latestQuestion, .responseOne, .responseTwo, .judgesChoice, .judgesConfidence, .judgesReason, .button, .buttonBlue, .buttonRed, .previousQuestions, .judgesAskQuestion').hide();
    $('#judgeContent').find('.waitingForAction').show();
    $('#judgeContent').find('.judgesAskQuestion').hide();
    $('#tabOne').html(jTabC+'<span>'+jTabWaitingC+'</span>');
    $('#jSend').hide();
    $('#jQuestion').hide();
    $('#jR1').hide();
    $('#jR2').hide();
  }
  if ($('#tabOne').hasClass('rating')) {
    $('#judgeContent').find('.loggedIn').hide();
    $('#judgeContent').find('.judgesAskQuestion, .waitingForAction').hide();
    $('#judgesFinalRating').hide();
    $('#judgeContent').find('.latestQuestion, .responseOne, .responseTwo').show();
    $('.judgesChoice').attr('display', 'inline-block');
    //$('#judgesRating').find('.judgesChoice, .judgesConfidence, .judgesReason, .button').show();   dynamically generated
    $('#judgesRating').show();
    $('#judgesRatingButtons').show();
    $('#judgeContent').find('.previousQuestions').show();
    $('#jQuestion').show();
    $('#jR1').show();
    $('#jR2').show();
    $('#judgeContent').find('.finalMessage').hide();
    $('#judgeContent').find('.finalHeader').hide();
    $('#nextQ').show();
    $('#noMoreQ').show();
    $('#tabOne').html(jTabC+'<span>'+jTabRatingC+'</span>');
    setRatingControls();
  }
  if($('#tabOne').hasClass('finalRating')) {
    $('#judgeContent').find('.loggedIn').hide();
    $('#judgesRating').hide();
    $('#jQuestion').hide();        
    $('#jR1').hide();
    $('#jR2').hide();
    $('.judgesChoice').attr('display', 'inline-block');
    $('#judgesFinalChoice').show();
    $('#judgesFinalReason').show();
    $('#judgesEndB').show();
    $('#judgesFinalRating').show();
    $('#judgeContent').find('.previousQuestions').show();
    $('#judgeContent').find('.finalMessage').hide();
    //$('#judgeContent').find('.finalHeader').show();
    $('#nextQ').hide();
    $('#noMoreQ').hide();
    $('#tabOne').html(jTabC+'<span>'+jTabRatingC+'</span>');
    setFinalRatingControls();
  }
  if($('#tabOne').hasClass('doneJudging')) {
    $('#judgeContent').find('.loggedIn').hide();
    $('#jQuestion').hide();
    $('#jR1').hide();
    $('#jR2').hide();
    $('#judgeContent').find('.latestQuestion, .responseOne, .responseTwo').hide();        
    $('#judgesFinalChoice').hide();
    $('#judgesMainReason').hide();
    $('#judgesEndB').hide();
    $('#judgesFinalRating').hide();
    //$('.judgesReason').hide();
    $('#judgeContent').find('.previousQuestions').hide();
    $('#judgeContent').find('.finalHeader').hide();
    $('#judgeContent').find('.finalMessage').show();
    $('#tabOne').html(jTabC+'<span>'+jTabDoneC+'</span>');
  }    
}

function setTabTwo() {
  if ($('#tabTwo').hasClass('answerQuestion')) {
    $('#nonPretenderContent').find('.finalMessage').hide();
    $('#nonPretenderContent').find('.currentQuestion, .currentAnswer').hide();
    $('#nonPretenderContent').find('.initialPage').hide();
    $('#nonPretenderContent').find('.waitingPage').hide();
    $('#nonPretenderContent').find('.latestQuestion, .nonPretendersAnswer, .button').show();
    $('#nonPretenderContent').find('.latestQuestion').next('p').show();
    $('#tabTwo').html(npTabC+'<span>'+rTabActiveC+'</span>');
    $('#npAskInst').show();
    $('#npHistoryTitle').show();
    setNPControls();
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
    $('#npAskInst').hide();
    $('#tabTwo').html(npTabC+'<span>'+rTabWaitingC+'</span>');
    $('#npHistory').hide();
  }
  if ($('#tabTwo').hasClass('waiting')) {
    $('#nonPretenderContent').find('.finalMessage').hide();
    $('#nonPretenderContent').find('.latestQuestion').next('p').hide();
    $('#nonPretenderContent').find('.latestQuestion, .nonPretendersAnswer, .button').hide();
    $('#nonPretenderContent').find('.initialPage').hide();            
    $('#nonPretenderContent').find('.waitingPage').show();
    $('#nonPretenderContent').find('.currentQuestion, .currentAnswer').hide();
    $('#npAskInst').hide();
    $('#tabTwo').html(npTabC+'<span>'+rTabWaitingC+'</span>');
  }
  if ($('#tabTwo').hasClass('done')) {
    $('#nonPretenderContent').find('.waitingPage').hide();
    $('#nonPretenderContent').find('.currentQuestion, .currentAnswer').hide();
    $('#nonPretenderContent').find('.previousQuestions').hide();        
    $('#nonPretenderContent').find('.initialPage').hide();
    $('#nonPretenderContent').find('.finalMessage').show();
    $('#nonPretenderContent').find('.latestQuestion, .nonPretendersAnswer, .button').hide();
    $('#npAskInst').hide();
    $('#tabTwo').html(npTabC+'<span>'+rTabDoneC+'</span>');
  }    
}

function setTabThree() {
  if ($('#tabThree').hasClass('answerQuestion')) {
    $('#pretenderContent').find('.finalMessage').hide();
    $('#pretenderContent').find('.currentQuestion, .currentAnswer').hide();
    $('#pretenderContent').find('.initialPage').hide();
    $('#pretenderContent').find('.waitingPage').hide();
    $('#pretenderContent').find('.latestQuestion, .pretendersAnswer, .button').show();
    $('#pretenderContent').find('.latestQuestion').next('p').show();
    $('#tabThree').html(pTabC+'<span>'+rTabActiveC+'</span>');
    $('#pAskInst').show();
    $('#pHistoryTitle').show();
    setPControls();
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
    $('#pAskInst').hide();
    $('#tabThree').html(pTabC+'<span>'+rTabWaitingC+'</span>');
    $('#pHistory').hide();
  }
  if ($('#tabThree').hasClass('waiting')) {
    $('#pretenderContent').find('.finalMessage').hide();
    $('#pretenderContent').find('.latestQuestion').next('p').hide();
    $('#pretenderContent').find('.latestQuestion, .pretendersAnswer, .button').hide();
    $('#pretenderContent').find('.initialPage').hide();            
    $('#pretenderContent').find('.waitingPage').show();
    $('#pretenderContent').find('.latestQuestion').next('p').hide();
    $('#pretenderContent').find('.currentQuestion, .currentAnswer').hide();
    $('#pAskInst').hide();
    $('#tabThree').html(pTabC+'<span>'+rTabWaitingC+'</span>');
  }
  if ($('#tabThree').hasClass('done')) {
    $('#pretenderContent').find('.waitingPage').hide();
    $('#pretenderContent').find('.currentQuestion, .currentAnswer').hide();
    $('#pretenderContent').find('.previousQuestions').hide();        
    $('#pretenderContent').find('.initialPage').hide();
    $('#pretenderContent').find('.finalMessage').show();
    $('#pretenderContent').find('.latestQuestion, .pretendersAnswer, .button').hide();
    $('#pAskInst').hide();
    $('#tabThree').html(pTabC+'<span>'+rTabDoneC+'</span>');
  }    
}

function setUI(tabNo) {
  if (tabNo===0 || tabNo===1) {setTabOne();}
  if (tabNo===0 || tabNo===2) {setTabTwo();}
  if (tabNo===0 || tabNo===3) {setTabThree();}
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" page furniture">

function processContentVars(xmlDoc) {
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
  intentionLabel = xmlDoc.getElementsByTagName("intentionLabel")[0].firstChild.nodeValue;
  intentionGuidance = xmlDoc.getElementsByTagName("intentionGuidance")[0].firstChild.nodeValue;
  intentionMinValue = xmlDoc.getElementsByTagName("intentionMinValue")[0].firstChild.nodeValue;
  if (randomiseSideS1 == '1') { $('#jHistoryTitle').hide(); };
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" helpers and messaging">

function sendInitConfirmation(title) {
  // title is the value sent form listener - we'll send this back to confirm
  // round-trip and to make sure admin scrren is only updated with logins when
  // everything is hunky-dory
  var icxml='<message><messageType>initConfirm</messageType><content>'+title+'</content></message>';
  send(icxml);  
}

function showDupLoginMsg() {
  $('#dialog-confirm').hide();
  $('#judgeContent').find('.latestQuestion, .responseOne, .responseTwo, .judgesChoice, .judgesConfidence, .judgesReason, .button, .buttonBlue, .buttonRed, .waitingForAction').hide();
  $('#judgeContent').find('.judgesAskQuestion').hide();
  $('#judgeContent').find('.previousQuestions').hide();
  $('#judgeContent').find('.waitingForStart').hide();
  $('#judgeContent').find('.loggedIn').hide();
  $('#judgeContent').find('.initScreen').show();        
  $('#tabTwo').hide();
  $('#tabThree').hide();
  $('#judgesRating').hide();
  $('#judgesFinalRating').hide();
  $('#judgeContent').find('.finalMessage').hide();
  $('#judgeContent').find('.finalHeader').hide();
  $('#jHistory').hide();   
  $('#admin').hide();
  $('#tabOne').removeClass('waiting');
  $('#tabOne').addClass('active askQuestion');
  $('#tabOne').show();
  $('#judgeContent').show();
  $('#judgeContent').find('.initScreen').hide();
  $('#multipleLoginMsg').show();
  $('#timeoutMsg').hide();
  $('#step1Wrapper').show();
  $('title').text('disconnected'); 

}

function injectContent() {
  $('#dialog-confirm').attr('title', jConfirmHeadC);
  $('#dialog-confirm').html("<p><span class=\"ui-icon ui-icon-alert\" style=\"float:left; margin: 0 7px 20px 0;\"></span>"+jConfirmBodyC+"</p>");
  $('#tabOne').html(jTabC+'<span>'+jTabUnconnectedC+'</span>');
  $('#jInitWaitingMsg').html('<h2>'+jWaitingToStartC+'</h2>');
  $('#jWaitingAction').html('<h2>'+jWaitingForRepliesC+'</h2>');
  $('#jqLabel').html(jPleaseAskC);
  $('#iIntentionLabel').html(intentionLabel);
  $('#iItentionGuidance').html(intentionGuidance);
  //$('#jFinalHeader').html('<h2>'+jFinalRatingTitleC+'</h2>'); // now built -into data from listener
  $('#jSend').attr('value', jAskButtonC);
  $('#nextQ').attr('value', jAskAnotherBC);
  $('#noMoreQ').attr('value', jNoMoreBC);
  $('#judgesEndB').attr('value', jSaveFinalBC);
  $('#jHistoryTitle').html('<h2 class="closed"><a href="#">'+jHistoryTitleC+'</a></h2>');
  $('#jFinalMsg').html('<h2>'+jFinalMsgC+'</h2>');
  $('#jQuestion').html('<p><span>'+jRatingQC+'</span></p>');
  $('#jR1').html(jRatingR1C);
  $('#jR2').html(jRatingR2C);
  
  $('#tabTwo').html(npTabC+'<span>'+rTabInactiveC+'</span>');
  $('#npInitMsg').html('<h2>'+rWaitFirstC+'</h2>');
  $('#npWaitMsg').html('<h2>'+rWaitNextC+'</h2>');
  $('#npAskInst').html('<p><strong>'+rInstructionC+'</strong></p>');
  $('#npGuidance').html('<h3>'+rGuidanceHeaderC+'</h3><p>'+npGuidanceC+'</p>');
  $('#npSend').attr('value', rSendBC);
  $('#npHistoryTitle').html('<h2 class="closed">'+rHistoryTitleC+'</h2>');
  $('#npDoneMsg').html('<h2>'+rFinalMsgC+'</h2>');
  
  $('#tabThree').html(pTabC+'<span>'+rTabInactiveC+'</span>');
  $('#pInitMsg').html('<h2>'+rWaitFirstC+'</h2>');
  $('#pWaitMsg').html('<h2>'+rWaitNextC+'</h2>');
  $('#pAskInst').html('<p><strong>'+rInstructionC+'</strong></p>');
  $('#pGuidance').html('<h3>'+rGuidanceHeaderC+'</h3><p>'+pGuidanceC+'</p>');
  $('#pSend').attr('value', rSendBC);
  $('#pHistoryTitle').html('<h2 class="closed">'+rHistoryTitleC+'</h2>');
  $('#pDoneMsg').html('<h2>'+rFinalMsgC+'</h2>');
}

function send(text) {
  Server.send( 'message', text );
}

function sendExptJoin() {
  var joinXML='<message><messageType>loginJoin</messageType><content>'+uid+'</content>';
  joinXML=joinXML+'<content>'+exptId+'</content>';
  joinXML=joinXML+'<content>'+jType+'</content>';
  joinXML=joinXML+'<content>'+jNo+'</content>';
  joinXML=joinXML+'<content>'+dayNo+'</content>';
  joinXML=joinXML+'<content>'+sessionNo+'</content>';
  joinXML=joinXML+'</message>';
  send(joinXML);
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

function initExpt(title) {
  $('#tabOne').removeClass('isLoggedIn');
  $('#tabOne').removeClass('empty');
  $('#tabOne').addClass('waitingToStart');
  $('#tabOne').html(jTabC+'<span>'+jTabUnconnectedC+'</span>');
  $('title').text(title); 
  setUI(0);
}

function initScreenForInactiveStep1(title) {
  
}

function startExpt() {
  //console.log("start expt");
  $('#tabOne').removeClass('waitingToStart').addClass('askQuestion');
  $('#tabTwo').show();
  $('#tabThree').show();
  $('#tabOne').html(jTabC+'<span>'+jTabActiveC+'</span>');
  setUI(0);
}


// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" login and websocket setup">

function wsConnect() {
  var hostname=location.host;
  var wsName='ws://'+hostname+':8080';
  Server = new igrtWebSocket(wsName);
  filename = window.location.href.substr(window.location.href.lastIndexOf("/")+1);//url.match(/.*\/(.*)$/)[1]; 
  srcName=filename.substr(0,filename.lastIndexOf("."));
  //Let the user know we're connected
  Server.bind('open', function() {
      //
  });
  //Disconnection occurred.
  Server.bind('close', function(data) {
    //console.log( "Disconnected." );
  });
  //process messages sent from server 
  Server.bind('message', function( payload ) {
    console.log(payload);
    var xmlDoc = txtToXmlDoc(payload);  
    var messageType=xmlDoc.getElementsByTagName("messageType")[0].firstChild.nodeValue;
    switch (messageType) {
      case 'feedback' :
        var feedbackMessage = xmlDoc.getElementsByTagName("feedbackMessage")[0].firstChild.nodeValue;
        var runningScore = xmlDoc.getElementsByTagName("runningScore")[0].firstChild.nodeValue;
        showFeedback(feedbackMessage, runningScore);
      break;
      case 'finalFeedback' :
        var winLoseMessage = xmlDoc.getElementsByTagName("winLoseMessage")[0].firstChild.nodeValue;
        showFinalFeedback(winLoseMessage);
      break;
      case "closeLogin":
        showDupLoginMsg();
      break;
      case "NPQ" :
        var npcontent=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        processNPQ(decodeURIComponent(npcontent));
      break;                    
      case "PQ" :
        var pcontent=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        processPQ(decodeURIComponent(pcontent));
      break;  
      case "JAs" :
        intervalId='';
        extraIntervalId='';
        var leftr = decodeURIComponent(xmlDoc.getElementsByTagName("lContent")[0].firstChild.nodeValue);
        var rightr = decodeURIComponent(xmlDoc.getElementsByTagName("rContent")[0].firstChild.nodeValue);
        var jrHtml = xmlDoc.getElementsByTagName("jrHtml")[0].firstChild.nodeValue;
        // list of fields to be used in rating: needed for js validation
        useLikert = xmlDoc.getElementsByTagName("useLikert")[0].firstChild.nodeValue;
        useReasons = xmlDoc.getElementsByTagName("useReasons")[0].firstChild.nodeValue;
        useBarbilliardsControl = xmlDoc.getElementsByTagName("useBarbilliardsControl")[0].firstChild.nodeValue;
        qNo = xmlDoc.getElementsByTagName("qNo")[0].firstChild.nodeValue;
        noMandatoryQuestions = xmlDoc.getElementsByTagName("noMandatoryQuestions")[0].firstChild.nodeValue;
        finalQ = xmlDoc.getElementsByTagName("finalQ")[0].firstChild.nodeValue;
        randomiseSideS1 = xmlDoc.getElementsByTagName("randomiseSideS1")[0].firstChild.nodeValue;
        npSide = xmlDoc.getElementsByTagName("npSide")[0].firstChild.nodeValue;
        intentionMinValue = xmlDoc.getElementsByTagName("intentionMinValue")[0].firstChild.nodeValue;
        reasonMinValue = xmlDoc.getElementsByTagName("reasonMinValue")[0].firstChild.nodeValue;
        useS1IntentionMin = xmlDoc.getElementsByTagName("useS1IntentionMin")[0].firstChild.nodeValue;
        useS1Intention = xmlDoc.getElementsByTagName("useS1Intention")[0].firstChild.nodeValue;
        reasonMinValue = xmlDoc.getElementsByTagName("reasonMinValue")[0].firstChild.nodeValue;        
        useS1QCategoryControl = xmlDoc.getElementsByTagName("useS1QCategoryControl")[0].firstChild.nodeValue;
        useS1AlignmentControl = xmlDoc.getElementsByTagName("useS1AlignmentControl")[0].firstChild.nodeValue;
        processJ(leftr, rightr, jrHtml);
      break;
      case "fRating":
        var frHtml = xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        useFinalReason = xmlDoc.getElementsByTagName("useFinalReason")[0].firstChild.nodeValue;
        useFinalLikert = xmlDoc.getElementsByTagName("useFinalLikert")[0].firstChild.nodeValue;
        npSide = xmlDoc.getElementsByTagName("npSide")[0].firstChild.nodeValue;
        finalReasonMinValue = xmlDoc.getElementsByTagName("finalReasonMinValue")[0].firstChild.nodeValue;        
        setFinalRating(frHtml);
      break;
      case "jDone":
        var target=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        signalDone(target);
      break;
      case "jHistory" :
        var jh=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        processJhistory(jh);
      break;
      case "npHistory" :
        var nph=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        processNPhistory(nph);
      break;
      case "pHistory" :
        var ph=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        processPhistory(ph);
      break;
      case "Initialise":
        var title=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        var um = xmlDoc.getElementsByTagName("useMacro")[0].firstChild.nodeValue;
        useMacro = (um == '1') ? true : false;
        processUI();
        initExpt(title);
        sendInitConfirmation(title);
      break;
      case "exptUI":
        var reconnectTitle=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;             
        processUI();
        $('title').text(reconnectTitle);
        console.log('reconnect');
        sendInitConfirmation(reconnectTitle);
      break;
      case "waitingStep1":
        var reconnectTitle=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;  
        $("#tabOne").addClass('isLoggedIn');
        processUI();
        $('title').text(reconnectTitle);
        sendInitConfirmation(reconnectTitle);
      break;
      case "Title":
        var rtitle=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        $('title').text(rtitle);
      break;
      case "startExpt":
        useS1Intention = xmlDoc.getElementsByTagName("useS1Intention")[0].firstChild.nodeValue;
        startExpt();
      break;
      case "rebuildJui":
        var jState=xmlDoc.getElementsByTagName("currentstate")[0].firstChild.nodeValue;                        
        var jH=xmlDoc.getElementsByTagName("jH")[0].firstChild.nodeValue;
        var jQ=xmlDoc.getElementsByTagName("jQ")[0].firstChild.nodeValue;
        var r1=xmlDoc.getElementsByTagName("r1")[0].firstChild.nodeValue;
        var r2=xmlDoc.getElementsByTagName("r2")[0].firstChild.nodeValue;
        var jrbHtml=xmlDoc.getElementsByTagName("jrHtml")[0].firstChild.nodeValue;
        var jfinalrHtml=xmlDoc.getElementsByTagName("jfinalrHtml")[0].firstChild.nodeValue;
        useLikert=xmlDoc.getElementsByTagName("useLikert")[0].firstChild.nodeValue;
        useReasons=xmlDoc.getElementsByTagName("useReasons")[0].firstChild.nodeValue;
        useFinalReason=xmlDoc.getElementsByTagName("useFinalReason")[0].firstChild.nodeValue;
        useFinalLikert = xmlDoc.getElementsByTagName("useFinalLikert")[0].firstChild.nodeValue;
        //finalQ = xmlDoc.getElementsByTagName("finalQ")[0].firstChild.nodeValue;
        useBarbilliardsControl = xmlDoc.getElementsByTagName("useBarbilliardsControl")[0].firstChild.nodeValue;
        qNo = xmlDoc.getElementsByTagName("qNo")[0].firstChild.nodeValue;
        noMandatoryQuestions = xmlDoc.getElementsByTagName("noMandatoryQuestions")[0].firstChild.nodeValue;
        finalQ = xmlDoc.getElementsByTagName("finalQ")[0].firstChild.nodeValue;
        randomiseSideS1 = xmlDoc.getElementsByTagName("randomiseSideS1")[0].firstChild.nodeValue;
        npSide = xmlDoc.getElementsByTagName("npSide")[0].firstChild.nodeValue;
        intentionMinValue = xmlDoc.getElementsByTagName("intentionMinValue")[0].firstChild.nodeValue;
        reasonMinValue = xmlDoc.getElementsByTagName("reasonMinValue")[0].firstChild.nodeValue;
        useS1IntentionMin = xmlDoc.getElementsByTagName("useS1IntentionMin")[0].firstChild.nodeValue;
        useS1Intention = xmlDoc.getElementsByTagName("useS1Intention")[0].firstChild.nodeValue;
        finalReasonMinValue = xmlDoc.getElementsByTagName("finalReasonMinValue")[0].firstChild.nodeValue;        
        useS1QCategoryControl = xmlDoc.getElementsByTagName("useS1QCategoryControl")[0].firstChild.nodeValue;
        useS1AlignmentControl = xmlDoc.getElementsByTagName("useS1AlignmentControl")[0].firstChild.nodeValue;
        rebuildJUI(jState,jH,jQ,r1,r2,jrbHtml,jfinalrHtml);
      break;
      case "rebuildNPui":
        var npClass=xmlDoc.getElementsByTagName("state")[0].firstChild.nodeValue;
        var npH=xmlDoc.getElementsByTagName("npH")[0].firstChild.nodeValue;
        var nprQ=xmlDoc.getElementsByTagName("rQ")[0].firstChild.nodeValue;
        var nprA=xmlDoc.getElementsByTagName("rA")[0].firstChild.nodeValue;
        rebuildNPUI(npClass,npH,nprQ,nprA);
      break;
      case "rebuildPui":
        var pClass=xmlDoc.getElementsByTagName("state")[0].firstChild.nodeValue;
        var pH=xmlDoc.getElementsByTagName("pH")[0].firstChild.nodeValue;
        var prQ=xmlDoc.getElementsByTagName("rQ")[0].firstChild.nodeValue;
        var prA=xmlDoc.getElementsByTagName("rA")[0].firstChild.nodeValue;
        rebuildPUI(pClass,pH,prQ,prA);
      break;
      case "beacon":
        connected=true;
      break;
      case "contentDef":
        processContentVars(xmlDoc);
        injectContent();
      break;             
      case "contentUpdate":
        processContentVars(xmlDoc);
        injectContent();
      break; 
      case "dupLogin":
        showDupLoginMsg();
      break;
      case "nextPhase":
        initiatePostSurvey();
        // goto post survey if applicable
      break;
    }
  });
  Server.connect();
  $('#estConnMsg').hide(3000, function() {
      $('#connectMsg').show();
      setUI(1);   // initialise all for empty, even though never shown with empty
      sendExptJoin();
    }
  );    
}

function doStart() {
  $('#admin').show();
  //$('#step1Wrapper').show();
  $('#estConnMsg').show();
  wsConnect();
}

$(window).load(function() {
  $('#step1Wrapper').hide();
  $('#validateMsg').hide();
  $('#connectMsg').hide();
  $('#estConnMsg').hide();
  $('#inActiveMsg').hide();
  $('#admin').show();
  uid = $('#hiddenUID').text();
  $.post("/webServices/step1/getStep1Parameters.php", { 
    post_uid : uid,
    rand : Math.random() },
    function(data) {
      var xmlDoc = txtToXmlDoc(data);  
      var messageType = xmlDoc.getElementsByTagName("messageType")[0].firstChild.nodeValue;
      if (messageType == "loginResults") {
        var success=xmlDoc.getElementsByTagName("success")[0].firstChild.nodeValue;
        if (success == "logged-in!") {
          isActive = xmlDoc.getElementsByTagName("isActive")[0].firstChild.nodeValue;
          exptType = xmlDoc.getElementsByTagName("exptType")[0].firstChild.nodeValue;
          exptStage = xmlDoc.getElementsByTagName("exptStage")[0].firstChild.nodeValue;
          if (isActive == "1" && exptType == "multi" && exptStage == "1") {
            uid = xmlDoc.getElementsByTagName("uid")[0].firstChild.nodeValue;
            exptId = xmlDoc.getElementsByTagName("exptId")[0].firstChild.nodeValue;
            jType = xmlDoc.getElementsByTagName("jType")[0].firstChild.nodeValue;
            jNo = xmlDoc.getElementsByTagName("jNo")[0].firstChild.nodeValue;            
            dayNo = xmlDoc.getElementsByTagName("dayNo")[0].firstChild.nodeValue;
            sessionNo = xmlDoc.getElementsByTagName("sessionNo")[0].firstChild.nodeValue; 
            finishedProbe = xmlDoc.getElementsByTagName("finishedProbe")[0].firstChild.nodeValue; 
            // go to start
            doStart();
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
});

// </editor-fold>

