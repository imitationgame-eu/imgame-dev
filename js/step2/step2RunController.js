var fName = 'anonymous';
var exptId;
var jType;
var manualIGR;
var igrChosen;
var pptNo;
var respId; // respId is subtly different from pptNo - it is the PK from wt_Step2pptStatus
var qNo = 0;
var npTab;
var rTabActive;
var rTabWaiting;
var rWaitNext;
var rCurrentQ;
var rInstruction;
var step2_SendBLabel;
var rGuidanceHeader;
var npGuidance;
var finishCode;
var pageNo;
var stage;
var yesno;
var useS2PAlignment;
var s2CorrectedAnswerLabel;
var s2CharacterLimitValue;
var step2_ReplyLimitGuidance;
var restartUID;


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


//------------------------------------------------------------------------------
// actual step2 probe run
//------------------------------------------------------------------------------

function setQUI(jQ) {
  $.mobile.loading('hide');
  $('#s2ayB').prop("checked",false);
  $('#s2anB').prop("checked",false);
  $('#s2ayB').checkboxradio('refresh');
  $('#s2anB').checkboxradio('refresh');
  $('#alignmentSection').hide();
  $('#qText').html(jQ);
  $('#answerTA').val('');
  $('#answerTA').trigger('refresh');
  $('#canswerTA').val('');
  $('#canswerTA').trigger('refresh');
  $('#container').show();
  $('#alignmentSection').hide();
  $('#qaSection').show();
  $('#processAnswer').button('disable');
  $('#processAnswer').button('refresh');        
}


//------------------------------------------------------------------------------
//  DOM ready & comms
//------------------------------------------------------------------------------

function processData(data) {
  console.log(data);
  var xmlDoc = txtToXmlDoc(data);
  var messageType=xmlDoc.getElementsByTagName("messageType")[0].firstChild.nodeValue;
  switch (messageType) {    
    case 'step2Settings':
      useS2PAlignment = xmlDoc.getElementsByTagName("useS2PAlignment")[0].firstChild.nodeValue;
      s2CharacterLimitValue = xmlDoc.getElementsByTagName("s2CharacterLimitValue")[0].firstChild.nodeValue;
//      $('#taId').html(rInstruction);
//      $('#processAnswer').text(step2_invertedSendBLabel);
//      $("#postControl").html('<h2>' + step2_invertFinalMsg + ':</h2>');
//      $("#step2StartMsg").html('<p><strong>' + step2_invertStartMsg + '</strong></p>');
//      $('#startB').val(step2_invertStartBLabel); 
      messageType = 'start';
      var contentArray = {};
      contentArray[0] = restartUID;
      content = contentArray;
      sendAction(messageType, content);        
    break;
    case 'respParameters' :
      $('#container').trigger('refresh');
      // set UI bindings after jQM decoration to avoid unwanted event firings when decorating the DOM
      setUIBindings();
      //finishCode = xmlDoc.getElementsByTagName("finishCode")[0].firstChild.nodeValue;
      pptNo = xmlDoc.getElementsByTagName("pptNo")[0].firstChild.nodeValue;
      respId = xmlDoc.getElementsByTagName("respId")[0].firstChild.nodeValue;
      restartUID = respId;
      igrChosen = xmlDoc.getElementsByTagName("igrChosen")[0].firstChild.nodeValue;
      messageType = 'getStep2Status';
      var contentArray = {};
      contentArray[0] = pptNo;
      contentArray[1] = respId;
      contentArray[2] = igrChosen;
      contentArray[3] = qNo;
      content = contentArray;
      $('#timer').fadeOut(1000, function(event,ui) { 
        // use delay to ensure JQM magic has worked before using any control
        sendAction(messageType, content);   
      });
    break;
    case 'step2Page':
      qNo = xmlDoc.getElementsByTagName("qNo")[0].firstChild.nodeValue;          
      var jQ = xmlDoc.getElementsByTagName("jQ")[0].firstChild.nodeValue;
      setQUI(jQ);
    break;
    case 'step2Done':
      usePost = xmlDoc.getElementsByTagName("usePost")[0].firstChild.nodeValue;
        $('#qaSection').hide();
        $('#alignmentSection').hide();

        // reinstate this later - hack to get Wroclaw April 2016 working
     if (usePost === '1') {
       // do postSurvey
       var url="/sf_" + exptId + '_7_' + jType + '_' + restartUID;
       var paramItems = {};
       post_to_url(url, paramItems);
     }
     else {
       $('#qaSection').hide();
       $('#alignmentSection').hide();
       var finalMsgTxt = $('#finalMsg').html();
       finalMsgTxt = finalMsgTxt + 'kodu: '+restartUID;
       $('#finalMsg').html(finalMsgTxt);
       $('#finalMsg').show();
       $('#closedMsg').hide();
     }
    break;
    case 'step2Closed':
      $('#qaSection').hide();
      $('#alignmentSection').hide();
      $('#finalMsg').hide();
      $('#closedMsg').show();
    break;
    case 'NOOP' :
      // non operational message
    break;
  }
  blockEvents = false;
}

function sendAction(messageType, content) {
  // content can be single value or array
  paramSet = {};
  paramSet['permissions'] = 255;
  console.log(messageType+' '+content);
  paramSet['messageType'] = messageType;
  paramSet['exptId'] = exptId;  // always need to know exptId
  paramSet['jType'] = jType;    // always need to know whether even or odd judge
  paramSet['content'] = content;
  $.ajax({
    type: 'GET',
    url: '/webServices/step2/step2RunController.php',
    data: paramSet,
    dataType: 'text',
    success: function(data) { processData(data); }
  });      
}

$(document).bind( 'mobileinit', function(){
  $.mobile.loader.prototype.options.text = "loading";
  $.mobile.loader.prototype.options.textVisible = true;
  $.mobile.loader.prototype.options.theme = "a";
  $.mobile.loader.prototype.options.html = "";
});



$(document).ready(function() {
  $('#container').hide();
  $('#qaSection').hide();
  $('#alignmentSection').hide();
  $('#finalMsg').hide();
  //cue the page loader
  $.mobile.loading( 'show' );
  exptId = $('#hiddenExptId').text();
  jType = $('#hiddenJType').text();
  restartUID = $('#hiddenRestartUID').text();
  messageType = 'step2Settings';
  qNo = 0;
  var contentArray = {};
  contentArray[0] = restartUID;
//  contentArray[1] = formType;
  content = contentArray;
  sendAction(messageType, content);   
});

function setUIBindings(jQ) {
  $('#processAnswer').unbind();
  $('#answerTA').bind('keyup', function () {
    if ($('#answerTA').val().length > s2CharacterLimitValue) {
      $('#processAnswer').button('enable');
      $('#processAnswer').button('refresh');      
    }
    else {
      $('#processAnswer').button('disable');
      $('#processAnswer').button('refresh');      
    }
  });
  $('#processAnswer').click( function() {
    if (useS2PAlignment === '1') {
      $('#qaSection').hide();
      $('#ynSection').show();
      $('#alignmentProceed').button('disable');
      $('#alignmentProceed').button('refresh'); 
      $('#s2ayB').bind('change', function(event, ui) {
        yesno = '1';
        $('#alignmentProceed').button('enable');
        $('#alignmentProceed').button('refresh');         
      });
      $('#s2anB').bind('change', function(event, ui) {
        yesno = '0';
        $('#alignmentProceed').button('enable');
        $('#alignmentProceed').button('refresh');         
      });
      $('#correctedAnswerSection').hide();
      $('#alignmentSection').show();
    }
    else {
      messageType = 'storeStep2reply';
      var contentArray = {};
      contentArray[0] = qNo;
      contentArray[1] = encodeURIComponent($('#answerTA').val());
      contentArray[2] = pptNo;
      contentArray[3] = respId;
      contentArray[4] = 0;  // 0 = no alignment data expected
      contentArray[5] = -1;  // 
      contentArray[6] = '';
      content = contentArray;
      sendAction(messageType, content);
    }
  });
  $('#alignmentProceed').click(function() {
    if (yesno === '1') {
      messageType = 'storeStep2reply';
      var contentArray = {};
      contentArray[0] = qNo;
      contentArray[1] = encodeURIComponent($('#answerTA').val());
      contentArray[2] = pptNo;
      contentArray[3] = respId;
      contentArray[4] = 1;  // 1 = alignment data expected
      contentArray[5] = 1;  // is aligned, no text input required
      contentArray[6] = '';
      content = contentArray;
      sendAction(messageType, content);      
    }
    else {
      $('#ynSection').hide();
      $('#cprocessAnswer').button('disable');
      $('#cprocessAnswer').button('refresh');
      $('#canswerTA').html('');
      $('#caId').html(s2CorrectedAnswerLabel);
      $('#canswerTA').bind('keyup', function () {
        if ($('#canswerTA').val().length > s2CharacterLimitValue) {
          $('#cprocessAnswer').button('enable');
          $('#cprocessAnswer').button('refresh');      
        }
        else {
          $('#cprocessAnswer').button('disable');
          $('#cprocessAnswer').button('refresh');      
        }
      });
      $('#correctedAnswerSection').show();
    }
  });
  $('#cprocessAnswer').click( function() {
    messageType = 'storeStep2reply';
    var contentArray = {};
    contentArray[0] = qNo;
    contentArray[1] = encodeURIComponent($('#answerTA').val());
    contentArray[2] = pptNo;
    contentArray[3] = respId;
    contentArray[4] = 1;  // 1 = alignment data expected
    contentArray[5] = 0;  // not aligned, include corrected answer
    contentArray[6] = encodeURIComponent($('#canswerTA').val());
    content = contentArray;
    sendAction(messageType, content);          
  });
  $('#processAnswer').button('disable');
  $('#processAnswer').button('refresh'); 
};

