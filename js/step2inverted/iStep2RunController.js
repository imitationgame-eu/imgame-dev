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
var rGuidanceHeader;
var npGuidance;
var finishCode;
var pageNo;
var stage;
var step2_invertStartMsg;
var step2_invertStartBLabel;
var step2_invertFinalMsg;
var step2_invertClosedMsg;
var yesno;
//var step2_invertedSendBLabel;
var useIS2CharacterLimit;
//var istep2_ReplyLimitGuidance;
var iS2CharacterLimitValue;
var useIS2NPAlignment;

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
  $('#iS2ayB').prop("checked",false);
  $('#iS2anB').prop("checked",false);
  $('#iS2ayB').checkboxradio('refresh');
  $('#iS2anB').checkboxradio('refresh');
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
    case 'istep2Settings':
      //step2_invertedSendBLabel = xmlDoc.getElementsByTagName("step2_invertedSendBLabel")[0].firstChild.nodeValue;
      useIS2CharacterLimit = xmlDoc.getElementsByTagName("useIS2CharacterLimit")[0].firstChild.nodeValue;
      //istep2_ReplyLimitGuidance = xmlDoc.getElementsByTagName("istep2_ReplyLimitGuidance")[0].firstChild.nodeValue;
      iS2CharacterLimitValue = xmlDoc.getElementsByTagName("iS2CharacterLimitValue")[0].firstChild.nodeValue;
      useIS2NPAlignment = xmlDoc.getElementsByTagName("useIS2NPAlignment")[0].firstChild.nodeValue;
//      $('#processAnswer').val(step2_invertedSendBLabel);
//      $('#taId').html(rInstruction);
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
      igrChosen = xmlDoc.getElementsByTagName("igrChosen")[0].firstChild.nodeValue;
      messageType = 'getIStep2Status';
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
    case 'istep2Page':
      qNo = xmlDoc.getElementsByTagName("qNo")[0].firstChild.nodeValue;          
      var jQ = xmlDoc.getElementsByTagName("jQ")[0].firstChild.nodeValue;
      setQUI(jQ);
    break;
    case 'istep2Done':
      usePost = xmlDoc.getElementsByTagName("usePost")[0].firstChild.nodeValue;
      if (usePost === '1') {
        // do postSurvey
        var url="/sf_" + exptId + '_13_' + jType + '_' + restartUID;
        var paramItems = {};
        post_to_url(url, paramItems);
      }
      else {
        $('#qaSection').hide();
        $('#alignmentSection').hide();
        $('#finalMsg').show();
        $('#closedMsg').hide();
      }
    break;
    case 'istep2Closed':
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
    url: '/webServices/step2inverted/iStep2RunController.php',
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
  messageType = 'istep2Settings';
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
    if (useIS2CharacterLimit == 1) {
      if ($('#answerTA').val().length > iS2CharacterLimitValue) {
        $('#processAnswer').button('enable');
        $('#processAnswer').button('refresh');      
      }
      else {
        $('#processAnswer').button('disable');
        $('#processAnswer').button('refresh');      
      }      
    }
    else {
      if (($('#answerTA').val().length > 1)) {
        $('#processAnswer').button('enable');
        $('#processAnswer').button('refresh');              
      }
      else {
        $('#processAnswer').button('disable');
        $('#processAnswer').button('refresh');      
      }      
    }
  });
  $('#processAnswer').click( function() {
    if (useIS2NPAlignment === '1') {
      $('#qaSection').hide();
      $('#ynSection').show();
      $('#alignmentProceed').button('disable');
      $('#alignmentProceed').button('refresh'); 
      $('#iS2ayB').bind('change', function(event, ui) {
        yesno = '1';
        $('#alignmentProceed').button('enable');
        $('#alignmentProceed').button('refresh');         
      });
      $('#iS2anB').bind('change', function(event, ui) {
        yesno = '0';
        $('#alignmentProceed').button('enable');
        $('#alignmentProceed').button('refresh');         
      });
      $('#correctedAnswerSection').hide();
      $('#alignmentSection').show();
    }
    else {
      messageType = 'storeIStep2reply';
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
      messageType = 'storeIStep2reply';
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
      $('#caId').html(iS2CorrectedAnswerLabel);
      $('#canswerTA').bind('keyup', function () {
        if ($('#canswerTA').val().length > iS2CharacterLimitValue) {
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
    messageType = 'storeIStep2reply';
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

