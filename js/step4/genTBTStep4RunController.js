// <editor-fold defaultstate="collapsed" desc=" global vars">

var intervalId = '';
var finalIntervalId = '';
var respondent1IntervalId = '';
var respondent2IntervalId = '';
var categoryIntervalId = '';
var choice = '';
var reason = '';
var intention = '';
var useLikert;
var useReasons;
var useExtraLikert;
var useFinalReason;
var useFinalLikert;
var useS4IndividualTurn;
var s4RandomiseSide;
var useS4Intention;
var useS4IntentionMin;
var s4IntentionMin;
var useS4AlignmentControl;
var pAlignment, npAlignment;
var s4IntentionText;
var categoryChoice;
var useS4QCategoryControl;
var useS4CharacterLimit;
var s4CharacterLimitValue;
var jNo;
var urlParams = {};
var srcName;
var url;
var index;
var filename;
var exptId;
var jType;
var s4jNo;
var qNo = ''; // make '' in case of reconnection on fullRating
var surveyFlag;
var connected=false;
var jTabC = 'Judge',
    jTabUnconnectedC = '',
    jTabWaitingC,jTabActiveC,jTabRatingC,jTabDoneC,jWaitingToStartC,
    jPleaseAskC,jAskButtonC,jWaitingForRepliesC,jHistoryTitleC,jRatingTitleC,
    jRatingYourQuestionC,jFinalRatingTitleC,jRatingQC,jRatingR1C,jRatingR2C,
    jAskAnotherBC,jNoMoreBC,jSaveFinalBC,jFinalMsgC,jConfirmHeadC,
    jConfirmBodyC,jConfirmOKC,jConfirmCancelC,
    npTabC = '';
var pretenderRight, shuffleHalf;
var step4_startMsg, step4_startBLabel, step4_judgeNumberMsg, step4_closedMsg, step4_finalMsg, step4_nextBLabel;
var isFullRating;

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" helpers, comms and doc.ready">

function post_to_url(path, params) {
  var method = "post"; // Set method to post by default
  var form = document.createElement("form");
  form.setAttribute("method", method);
  form.setAttribute("action", path);
  for (var key in params) {
    if(params.hasOwnProperty(key)) {
      var hiddenField = document.createElement("input");
      hiddenField.setAttribute("type", "hidden");
      hiddenField.setAttribute("name", key);
      hiddenField.setAttribute("value", params[key]);
      form.appendChild(hiddenField);
     }
  }
  document.body.appendChild(form);
  form.submit();
}

function txtToXmlDoc(txt) {
  // check for spurious characters at beginning of message string
  if (txt.substring(0,1) != '<') {
    //var tl = txt.length;
    var i = txt.indexOf('<');
    var newTxt = txt.substring(i);
    console.log(newTxt);
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

function sendAction(messageType, content) {
  // content can be single value or array
  paramSet = {};
  paramSet['permissions'] = 255;
  paramSet['s4jNo'] = s4jNo;
  paramSet['exptId'] = exptId;
  paramSet['messageType'] = messageType;
  paramSet['content'] = content;
  $.ajax({
    type: 'GET',
    url: '/webServices/step4/genTBTStep4RunController.php',
    data: paramSet,
    dataType: 'text',
    success: function(data) { processData(data); }
  });      
}

function processData(data) {
  var xmlDoc = txtToXmlDoc(data);
  var messageType = xmlDoc.getElementsByTagName("messageType")[0].firstChild.nodeValue;
  console.log(data);
  switch (messageType) {
    // initial connection - gets language content
    case "contentDef":
      processContentVars(xmlDoc);
      injectContent();
      var contentArray = {};
      contentArray[0] = surveyFlag;
      content = contentArray;
      sendAction('ratingParams', content);
    break;          
    // get rating parameters for validation
    case "ratingParams" :
      useLikert = parseInt(xmlDoc.getElementsByTagName("reqLikert")[0].firstChild.nodeValue);
      useS4IndividualTurn = parseInt(xmlDoc.getElementsByTagName("useS4IndividualTurn")[0].firstChild.nodeValue);
      s4RandomiseSide = parseInt(xmlDoc.getElementsByTagName("s4RandomiseSide")[0].firstChild.nodeValue);
      useS4Intention = parseInt(xmlDoc.getElementsByTagName("useS4Intention")[0].firstChild.nodeValue);
      useS4IntentionMin = parseInt(xmlDoc.getElementsByTagName("useS4IntentionMin")[0].firstChild.nodeValue);
      s4IntentionMin = parseInt(xmlDoc.getElementsByTagName("s4IntentionMin")[0].firstChild.nodeValue);
      useS4AlignmentControl = parseInt(xmlDoc.getElementsByTagName("useS4AlignmentControl")[0].firstChild.nodeValue);
      useS4QCategoryControl = parseInt(xmlDoc.getElementsByTagName("useS4QCategoryControl")[0].firstChild.nodeValue);
      useS4CharacterLimit = parseInt(xmlDoc.getElementsByTagName("useS4CharacterLimit")[0].firstChild.nodeValue);
      s4CharacterLimitValue = parseInt(xmlDoc.getElementsByTagName("s4CharacterLimitValue")[0].firstChild.nodeValue);
      var contentArray = {};
      contentArray[0] = surveyFlag;
      content = contentArray;
      sendAction('startPage', content);
    break;
    case "step4startPage":
      $('#tabOne').removeClass('empty').addClass('startPage');
      setTabOne();
    break;
    // next transcript or full transcript between TBT sections
    case "step4Transcript":
      var jHtml = xmlDoc.getElementsByTagName("form")[0].firstChild.nodeValue;
      pretenderRight = parseInt(xmlDoc.getElementsByTagName("pretenderRight")[0].firstChild.nodeValue);
      jNo = parseInt(xmlDoc.getElementsByTagName("jNo")[0].firstChild.nodeValue);
      exptId = parseInt(xmlDoc.getElementsByTagName("exptId")[0].firstChild.nodeValue);
      qNo = parseInt(xmlDoc.getElementsByTagName("qNo")[0].firstChild.nodeValue);
      isFullRating = 0;
      $('#judgeContent').html(jHtml);
      $('#tabOne').removeClass('startPage').addClass('step4Page judging');
      setTabOne();
    break;
    case "step4FullRating":
      var jHtml = xmlDoc.getElementsByTagName("form")[0].firstChild.nodeValue;
      pretenderRight = parseInt(xmlDoc.getElementsByTagName("pretenderRight")[0].firstChild.nodeValue);
      jNo = parseInt(xmlDoc.getElementsByTagName("jNo")[0].firstChild.nodeValue);
      exptId = parseInt(xmlDoc.getElementsByTagName("exptId")[0].firstChild.nodeValue);
      if (qNo == '') { qNo = 0; } // special case for reconnection where on a full-rating
      isFullRating = 1;
      $('#judgeContent').html(jHtml);
      $('#tabOne').removeClass('startPage').addClass('step4Page judging');
      setTabOne();
    break;
    case 's4complete' :
      $('#tabOne').removeClass('judging').addClass('doneJudging');
      setTabOne();
      // usePost = xmlDoc.getElementsByTagName("usePost")[0].firstChild.nodeValue;
      // if (usePost == 'usePost') {
      //   // do postSurvey - google one for Kasia
      //   window.location('https://goo.gl/forms/F8Ud3TtrlyiLITG03');
      //   // var url='/sf_332_11_0_' + s4jNo;
      //   // var paramItems = {};
      //   // post_to_url(url, paramItems);
      // }
      // else {
      //   $('#tabOne').removeClass('judging').addClass('doneJudging');
      //   setTabOne();
      // }
    break;   
  }
}

$(window).load(function() {
  $('#step4Wrapper').hide();
  $('#admin').show();
  s4jNo = $('#hiddenS4jNo').text();
  exptId = $('#hiddenExptId').text();
  var contentArray = {};
  content = contentArray;
  sendAction('step4RunConnect', content);
});

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" UI processing">

function injectContent() {
  $('#tabOne').html(jTabC+'<span>'+jTabUnconnectedC+'</span>');
  $('#jR1').html(jRatingR1C);
  $('#jR2').html(jRatingR2C);
  $('#startB').val(step4_startBLabel);
  $('#step4_startMsg').html('<p><strong>' + step4_startMsg + '</strong></p>');
  $('#step4_judgeNumberMsg').html('<p>' + step4_judgeNumberMsg + ':' + s4jNo + '</p><hr/><br />');
  $('#s4nextB').val(step4_nextBLabel);  
}

function setTabOne() {
  if ($('#tabOne').hasClass('startPage')) {
    $('#admin').hide();
    $('#startButtonWrapper').show();
    $('#startB').click( function(e) {
      var contentArray = {};
      contentArray[0] = surveyFlag;
      content = contentArray;
      sendAction('nextPage', content);      
    });
  }
  if ($('#tabOne').hasClass('step4Page')) {
    $('#tabOne').html(jTabC+'<span>'+jTabRatingC+'</span>');
    setRatingControls();
    $('#jR1').html(jRatingR1C);
    $('#jR2').html(jRatingR2C);
    $('#admin').hide();
    $('#startButtonWrapper').hide();
    $('#step4Wrapper').show();
    $('#judgeContent').addClass('active');
    $('#judgeContent').show();
    $(document).scrollTop( $("#judgeContent").offset().top );  
  }
  if ($('#tabOne').hasClass('doneJudging')) {
    $('#admin').hide();
    $('#startButtonWrapper').hide();
    $('#step4Wrapper').show();
    $('#judgeContent').addClass('active');
    $('#judgeContent').show();
    $('#tabOne').html(jTabC+'<span>'+jTabDoneC+'</span>');
    $('#judgeContent').html('<h1>' + step4_finalMsg + ': ' + exptId + '_'+s4jNo + '</h1>');
    // make visible
  } 
  if ($('#tabOne').hasClass('exptClosed')) {
    $('#admin').hide();
    $('#startButtonWrapper').hide();
    $('#step4Wrapper').show();
    $('#judgeContent').addClass('active');
    $('#judgeContent').show();
    $('#tabOne').html(jTabC+'<span>'+jTabDoneC+'</span>');
    $('#judgeContent').html('<h1>' + step4_closedMsg + '</h1>');
    // make visible
  } 
}

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
  step4_startMsg = xmlDoc.getElementsByTagName("step4_startMsg")[0].firstChild.nodeValue;
  step4_startBLabel = xmlDoc.getElementsByTagName("step4_startBLabel")[0].firstChild.nodeValue;
  step4_judgeNumberMsg = xmlDoc.getElementsByTagName("step4_judgeNumberMsg")[0].firstChild.nodeValue;
  step4_closedMsg = xmlDoc.getElementsByTagName("step4_closedMsg")[0].firstChild.nodeValue;
  step4_finalMsg = xmlDoc.getElementsByTagName("step4_finalMsg")[0].firstChild.nodeValue;
  step4_nextBLabel = xmlDoc.getElementsByTagName("step4_nextBLabel")[0].firstChild.nodeValue;
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" UI controls">

function setRespondentRBControls() {
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
  function snapToMiddle(dragger, target) {
    var bottomMove = target.position().bottom - dragger.data('position').bottom + (target.outerHeight(true) - dragger.outerHeight(true)) / 2;
    var leftMove= target.position().left - dragger.data('position').left + (target.outerWidth(true) - dragger.outerWidth(true)) / 2;
    dragger.animate({bottom:bottomMove,left:leftMove},{duration:500,easing:'easeOutBack'});
  }
}

function checkRatingValidation() {
  var validated=true;
  var $radios = $('input:radio[name=judgement]');
  if ($radios[0]['checked'] == true) {choice='0';}
  if ($radios[1]['checked'] == true) {choice='1';}
  if (choice == '') {validated=false;}
  if (useS4CharacterLimit == 1 ) {
    var reasonLength = $('#jReason').val().length;
    if (reasonLength < s4CharacterLimitValue) {validated=false;}
  }
  reason = encodeURIComponent($('#jReason').val());
  if (useLikert== 1) {
    if (intervalId=='') {validated=false;}
  }
  if (useS4IntentionMin == 1) {
    var intentionLength = $('#iIntention').val().length;
    if (intentionLength < s4IntentionMin) {validated=false;}
    intention = encodeURIComponent($('#iIntention').val());
  }
  if ((useS4AlignmentControl == 1) && (isFullRating == false)) {
    if (respondent1IntervalId == '') {validated=false;}
    if (respondent2IntervalId == '') {validated=false;}
  }
  if (validated) {
    $('#s4nextB').removeAttr("disabled").removeClass('greyed');
  }
  else {
    $('#s4nextB').attr("disabled", "disabled");
    $('#s4nextB').addClass('greyed');    
  }
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
  setLikertControls();
  setRespondentRBControls();
  setCategoryLikertControls();  
  $('#jReason').keyup(function() { checkRatingValidation(); });
  $('#iIntention').keyup(function() { checkRatingValidation(); });
  $('#s4nextB').attr("disabled","disabled");
  $('#s4nextB').addClass('greyed');
  $('#s4nextB').val(step4_nextBLabel);
  $('#s4nextB').click( function(e) {
    var contentArray = {};
    contentArray[1] = 'haveResponse';
    contentArray[2] = choice;
    contentArray[3] = intervalId;
    contentArray[4] = reason;
    contentArray[5] = pretenderRight;
    contentArray[6] = jNo;
    contentArray[7] = exptId;
    contentArray[8] = qNo;
    contentArray[9] = isFullRating == 1 ? 0 : respondent1IntervalId;
    contentArray[10] = isFullRating == 1 ? 0 : respondent2IntervalId;
    contentArray[11] = intention;
    contentArray[12] = s4jNo;
    contentArray[13] = isFullRating;
    content = contentArray;
    console.log(content);
    sendAction('step4storeRating', content);      
  });
  $('.choice input').attr("checked", false);
  intervalId = '';
  choice = '';
  reason = '';
  intention = '';
  respondent1IntervalId = ''; 
  respondent2IntervalId = ''; 
}

// </editor-fold>











