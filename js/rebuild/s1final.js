var finalIntervalId = '';
var useLikert;
var useReasons;
var useFinalReason;
var srcName;
var url;
var index;
var filename; 
var uid;
var exptId;
var emailNo;


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
  if (finalIntervalId=='') {validated=false;}
  if (validated) {
    $('#judgesEndB').removeAttr("disabled").removeClass('greyed');    
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

function processFinalRatingButton() {
  var choice='';
  var $radios = $('input:radio[name=finalJudgement]');
  if ($radios[0]['checked'] == true) {choice='0';}
  if ($radios[1]['checked'] == true) {choice='1';}
  var reason=$('#judgesMainReason').val();  //judgesFinalReason
  $.post("/webServices/rebuild/s1PostFinalRating.php",{ userid: uid, experimentID: exptId, uChoice: choice, uConfidence: finalIntervalId,  uReason: reason} ,function(data) {
    //processAJAX(data);
  });    
  $('#judgesFinalRating').hide();
  $('#judgesEndB').hide();
  $('.finalHeader').hide();
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
  $('#tabOne').html(jTabC+'<span>'+jTabUnconnectedC+'</span>');
  $('#jInitWaitingMsg').html('<h2>'+jWaitingToStartC+'</h2>');
  $('#jWaitingAction').html('<h2>'+jWaitingForRepliesC+'</h2>');
  $('h2', '.jAskQuestion').html(jPleaseAskC);
  $('.finalHeader').html('<h2>'+jFinalRatingTitleC+'</h2>'); // now built -into data from listener
//  $('#jSend').attr('value', jAskButtonC);
//  $('#nextQ').attr('value', jAskAnotherBC);
//  $('#noMoreQ').attr('value', jNoMoreBC);
  $('#judgesEndB').attr('value', jSaveFinalBC);
//  //$('#jHistoryTitle').html('<h2 class="closed"><a href="#">'+jHistoryTitleC+'</a></h2>');
//  $('#jFinalMsg').html('<h2>'+jFinalMsgC+'</h2>');
//  $('#jQuestion').html('<p><span>'+jRatingQC+'</span></p>');
  $('#jR1').html(jRatingR1C);
  $('#jR2').html(jRatingR2C);
  $('h2', '#judgeContent .waitingForAction').html(jWaitingForRepliesC);
  
}

function processAJAX(payload) {
  var xmlDoc = txtToXmlDoc(payload);  
  var messageType=xmlDoc.getElementsByTagName("messageType")[0].firstChild.nodeValue;
  switch (messageType) {
    case 'pageFurniture':
      doPageFurniture(xmlDoc);
      getS1Status();
    break;
    case 'fRating' : 
      var rHtml = xmlDoc.getElementsByTagName("jFinalRatingHtml")[0].firstChild.nodeValue;
      uid = xmlDoc.getElementsByTagName("uid")[0].firstChild.nodeValue;
      $('#judgesFinalRating').html(rHtml);
      $('#judgeContent').show();
      $('#tabTwo').hide();
      $('#tabThree').hide();
      $('#pretenderContent').hide();
      $('#nonPretenderContent').hide();
      $('.waitingForAction').hide();
      $('.jAskQuestion').hide();
      $('.latestResponses').hide();
      $('.latestQuestion').hide();
      $('.previousQuestions').hide();
      $('#judgesRating').hide();
      $('#judgesRatingButtons').hide();
      $('.finalMessage').hide();
      setFinalRatingControls();
      break;
  }    
}

function getS1Status() {
  $.post("/webServices/rebuild/s1final.php",{ msgType: "fRating", exptId: exptId, emailNo: emailNo },function(data) {
    processAJAX(data);
  });    
}

$(document).ready(function() {
  var params = window.location.search.substr(1).split('&');
  for (var i = 0; i < params.length; i++) {
    var p=params[i].split('=');
    var key = p[0];
    var value = decodeURIComponent(p[1]);
    if (key == 'exptId') {
      exptId = value;
    }
    if (key == 'uid') {
      emailNo = value;
    }
  }
  $.post("/webServices/rebuild/s1final.php",{ msgType: "init", exptId: exptId, emailNo: emailNo },function(data) {
    processAJAX(data);
  });    
});



