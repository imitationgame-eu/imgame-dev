var Server;
var evenJudges;
var oddJudges;
var currentExptTitle;
var currentExptDayNo;
var currentExptSessionNo;
var uid;
var fName;
var sName;
var permissions;
var email;

function send( text ) {
  Server.send( 'message', text );
}

function ProcessReconnect() {
  var header='<h1>Experiment Status: <span>'+currentExptTitle+' day:'+currentExptDayNo+' sess:'+currentExptSessionNo+'</span></h1>';
  $('#exptTitle').html(header);
  $('.adminTabs').hide();
  $('#statusPage').show();
  $('#connectionStatus').show();
}

function ProcessAllocAdmin(title) {
  var header='<h1>Experiment Status: <span>'+title+' day:'+currentExptDayNo+' sess:'+currentExptSessionNo+'</span></h1>';
  $('#exptTitle').html(header);
  $('.adminTabs').hide();
  $('#statusPage').show();
  $('#connectionStatus').show();
}

function ProcessOddConnectionData(cdata) {
  $('#statusPage').find('.OddConnections').html(cdata);
}
 
function ProcessEvenConnectionData(cdata) {
  $('#statusPage').find('.EvenConnections').html(cdata);
}

function ProcessActiveList(cdata) {
  $('#step1ExptTables').html(cdata);
  setMonitorControls();
}

function setMonitorControls() {
  $('.currentExperiments').on('click', 'h2', function(event){
    if ($(this).parent().hasClass('active')) {
      $(this).removeClass('open');
      $(this).addClass('closed');
      $(this).parent().removeClass('active');
      $(this).parent().find('table').hide();
    }
    else {
      $(this).removeClass('closed');
      $(this).addClass('open');
      $(this).parent().addClass('active');
      $(this).parent().find('table').show();
    }
  });
  $('#step1ExptTables').find('.button').click(function(e) {
    var buttonID=this.id;
    var buttonDetails=buttonID.split('_');
    var xml='<message><messageType>monitorStep1Session</messageType><content>'+buttonDetails[1]+'</content></message>';
    send(xml);        
  });
}

function DisplayStep1(step1Html) {
  //console.log('inint message from listener: ' + step1Html)
  $('#step1ExptTables').html(step1Html);
  setStep1Controls();
  $('.currentExperiments').find('h2').parent().removeClass('active').find('table').hide();
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

function SwitchToMonitor() {
  $('#tabOneContent').hide();
  $('#statusPage').show();
  $('#connectionStatus').show();
}

$(document).ready(function() {
  var hostname=location.host;
  var wsName='ws://'+hostname+':8080';
  Server = new igrtWebSocket(wsName);
  $('#statusPage').hide();
  $('.tab:first, .tabContent:first').addClass('active');
  $('#timeoutMsg').hide();
  //Let the client know we're connected
  Server.bind('open', function() {
  });

  //Disconnection occurred.
  Server.bind('close', function( data ) {
  });

  //process messages sent from server (these may be discrete changes or whole info - will decide later)
  Server.bind('message', function( payload ) {
    // send confirmation message to listener for debugging purposes

    // process payload xml and insert appropriately into tags
    var xmlDoc = txtToXmlDoc(payload);  
    var messageType=xmlDoc.getElementsByTagName("messageType")[0].firstChild.nodeValue;
    switch (messageType) {
      case 'SwitchToMonitor':
        SwitchToMonitor();
      break;
      case 'oddStatusUpdate' :
        var OddStatusData = xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        ProcessOddConnectionData(OddStatusData);
      break;
      case 'evenStatusUpdate' :
        var EvenStatusData = xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        ProcessEvenConnectionData(EvenStatusData);
      break;
      case 'step1ActiveSessionList':
        var ActiveData = xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        ProcessActiveList(ActiveData);
      break;
    }
  });
  Server.connect();
  uid=$('#hiddenUID').text();
  fName=$('#hiddenfName').text();
  sName=$('#hiddensName').text();
  permissions=$('#hiddenPermissions').text();
  email = $('#hiddenEmail').text();
  $('#configStage').show();
  $('#configStage').hide(1200, function() {
    // use this delay function to ensure the WS connection is stable
    xml="<message><messageType>monitorInit</messageType><content>"+uid+"</content><content>"+fName+"</content><content>"+sName+"</content><content>"+permissions+"</content><content>"+email+"</content></message>";  
    send(xml);
  });
});



