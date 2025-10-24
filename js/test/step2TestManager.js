var fName = 'anonymous';
var exptId;

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
//  DOM ready & comms
//------------------------------------------------------------------------------

function processData(data) {
  var xmlDoc = txtToXmlDoc(data);
  var messageType=xmlDoc.getElementsByTagName("messageType")[0].firstChild.nodeValue;
  //console.log(messageType);
  switch (messageType) {
    case "done" :
      var dsHtml = xmlDoc.getElementsByTagName("form")[0].firstChild.nodeValue;
      $('#exptList').html(dsHtml);
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
  paramSet['messageType'] = messageType;
  paramSet['exptId'] = exptId;
  paramSet['content'] = content;
  $.ajax({
    type: 'GET',
    url: '/webServices/test/Test_step2Manager.php',
    data: paramSet,
    dataType: 'text',
    success: function(data) { processData(data); }
  });      
}

$(document).ready(function() {
  $('#tabOneContent').show();
  $('.adminTabs').show();
  $('#exptList').html("creating data set....");
  uid=$('#hiddenUID').text();
  messageType = 'step2RunTest';
  content = '';
  exptId = 38;  // hard coded here but maybe generalisable later
  sendAction(messageType, content);
});

