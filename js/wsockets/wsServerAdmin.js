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

function send(text) {
  Server.send('message', text );
}

function processReconnect() {
  var header='<h1>Experiment Status: <span>'+currentExptTitle+' day:'+currentExptDayNo+' sess:'+currentExptSessionNo+'</span></h1>';
  $('#exptTitle').html(header);
  $('.adminTabs').hide();
  $('#statusPage').show();
  $('#connectionStatus').show();
}

function processAllocAdmin(title) {
  var header='<h1>Experiment Status: <span>'+title+' day:'+currentExptDayNo+' sess:'+currentExptSessionNo+'</span></h1>';
  $('#exptTitle').html(header);
  $('.adminTabs').hide();
  $('#statusPage').show();
  $('#connectionStatus').show();
}

function processOddConnectionData(cdata) {
  $('#statusPage').find('.OddConnections').html(cdata);
}
 
function processEvenConnectionData(cdata) {
  $('#statusPage').find('.EvenConnections').html(cdata);
}

function setStep1Controls() {
  $('.currentExperiments').on('click', 'h2', function(event){
    if ($(this).parent().hasClass('active')) {
      $(this).removeClass('open');
      $(this).addClass('closed');
      $(this).parent().removeClass('active');
      $(this).parent().find('table').hide();
      $(this).parent().find('p').hide();
    }
    else {
      $(this).removeClass('closed');
      $(this).addClass('open');
      $(this).parent().addClass('active');
      $(this).parent().find('table').show();
      $(this).parent().find('p').show();
    }
  });
  $('#step1ExptTables').find('.button').click(function(e) {
    var buttonID=this.id;
    var buttonDetails=buttonID.split('_');
    currentExptDayNo=buttonDetails[2];
    currentExptSessionNo=buttonDetails[3];
    var useMacro = buttonDetails[5];
    var actualJudges=$('#jNo'+buttonDetails[1]+'D'+buttonDetails[2]+'S'+buttonDetails[3]).val();
    var xml='<message><messageType>initStep1</messageType><content>'+buttonDetails[1]+'</content><content>'+buttonDetails[2]+'</content><content>'+buttonDetails[3]+'</content><content>'+buttonDetails[4]+'</content><content>'+actualJudges+'</content><content>'+useMacro+'</content></message>';
    send(xml);        
  });
}

function displayStep1(step1Html) {
  $('#step1ExptTables').html(step1Html);
  setStep1Controls();
  $('.currentExperiments').find('h2').parent().removeClass('active').find('table').hide();
  $('.currentExperiments').find('h2').parent().find('p').hide();
  $('.adminTabs').show();
  $('#statusPage').hide();
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

function showTimeoutMsg() {
  $('#statusPage').hide();
  $('#timeoutMsg').show();  
}

$(document).ready(function() {
  var hostname=location.host;
  var wsName='ws://'+hostname+':8080';
  Server = new igrtWebSocket(wsName);
  $('#statusPage').hide();
  $('#startSessionID').hide();
  $('#dialog-close').hide();
  $('#dialog-discard').hide();
  $('#timeoutMsg').hide();
  $('#closeSessionID').hide();
  $('#closePartialSessionID').hide();
  $('#discardSessionID').hide();
  $('#controlNextID').hide();
  $('.tab:first, .tabContent:first').addClass('active');
  $('#allowStartB').click(function(e) {
    var xml='<message><messageType>startStep1</messageType><content>dummy</content></message>';
    send(xml);
    $('#controlNextB').removeAttr("disabled").removeClass("greyed");
    $('#closeSessionB').attr("disabled", "disabled").addClass("greyed");    
    $('#closePSessionB').attr("disabled", "disabled").addClass("greyed");    
    $('#discardSessionB').attr("disabled", "disabled").addClass("greyed");    
  });
  $('#finalQB').click(function(e) {
    var xml='<message><messageType>toggleNextButton</messageType><content>dummy</content></message>';
    send(xml);
    $('#controlNextID').hide();
  });
  $('#closeSessionB').click(function(e) {
    $( "#dialog:ui-dialog" ).dialog( "destroy" );
    $( "#dialog-close" ).dialog({
      resizable: false,
      height:140,
      modal: true,
      buttons: {
        "Close": function() {
          var xml='<message><messageType>closeSession</messageType><content>'+uid+'</content><content>'+permissions+'</content></message>';
          //send(xml);
          $(this).dialog("close");
        },
        Cancel: function() {
          $(this).dialog("close");
        }
      }
    });
  });
  $('#closePSessionB').click(function(e) {
    $( "#dialog:ui-dialog" ).dialog( "destroy" );
    $( "#dialog-pclose" ).dialog({
      resizable: false,
      height:140,
      modal: true,
      buttons: {
        "Close": function() {
          var xml='<message><messageType>closePSession</messageType><content>'+uid+'</content><content>'+permissions+'</content></message>';
          //send(xml);
          $(this).dialog("close");
        },
        Cancel: function() {
          $(this).dialog("close");
        }
      }
    });
  });
  $('#discardSessionB').click(function(e) {
    $( "#dialog:ui-dialog" ).dialog( "destroy" );
    $( "#dialog-discard" ).dialog({
        resizable: false,
        height:140,
        modal: true,
        buttons: {
            "Close": function() {
              var xml='<message><messageType>discardSession</messageType><content>'+uid+'</content><content>'+permissions+'</content></message>';
              //send(xml);
              $(this).dialog("close");
            },
            Cancel: function() {
              $(this).dialog("close");
            }
        }
    });
  });

  //Let the client know we're connected
  Server.bind('open', function() {
  });

  //Disconnection occurred.
  Server.bind('close', function( data ) {
  });

  //process messages sent from server (these may be discrete changes or whole info - will decide later)
  Server.bind('message', function( payload ) {
    //console.log(payload);
    var xmlDoc = txtToXmlDoc(payload);  
    var messageType=xmlDoc.getElementsByTagName("messageType")[0].firstChild.nodeValue;
    switch (messageType) {
      case 'hideNoMoreQ':
        $('#controlNextID').hide();
      break;
      case 'reconnectAdmin' :
        currentExptDayNo = xmlDoc.getElementsByTagName("dn")[0].firstChild.nodeValue;;
        currentExptSessionNo = xmlDoc.getElementsByTagName("sn")[0].firstChild.nodeValue;
        currentExptTitle = xmlDoc.getElementsByTagName("title")[0].firstChild.nodeValue;
        processReconnect();
      break;
      case 'allocSuccess' :
        var title = xmlDoc.getElementsByTagName("title")[0].firstChild.nodeValue;
        processAllocAdmin(title);
        $('#startSessionID').show();
      break;
      case 'oddStatusUpdate' :
        var OddStatusData = xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        processOddConnectionData(OddStatusData);
      break;
      case 'evenStatusUpdate' :
        var EvenStatusData = xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
        processEvenConnectionData(EvenStatusData);
      break;
      case 'controlStart' :
        $('#startSessionID').hide();
//        $('#closeSessionID').show(3000, function(){
//          $('#closeSessionB').attr("disabled", "disabled").addClass("greyed");          
//        });
//        $('#discardSessionID').show(3000, function() {
//          $('#discardSessionB').removeAttr("disabled").removeClass("greyed");          
//        });
        $('#controlNextID').show(3000, function() {
          $('#finalQB').removeAttr("disabled").removeClass("greyed");
          // then hide and set accordion
          $('.adminContent h2').removeClass('open');
          $('.adminContent h2').addClass('closed');
          $('#connectionControls').hide(3000, function(){
            $('.adminContent h2').unbind();
            $('.adminContent h2').click(function(e) {
              if ($(this).hasClass('open')) {
                $(this).removeClass('open').addClass('closed');
                $('#connectionControls').hide();
              }
              else {
                $(this).removeClass('closed').addClass('open');
                $('#connectionControls').show();      
              }
            });            
          });          
        });
      break;
      case 'step1Control':
        var step1Html = xmlDoc.getElementsByTagName("step1")[0].firstChild.nodeValue;
        displayStep1(step1Html);
        break;
      case 'resetStep1Control':
        var step1Html = xmlDoc.getElementsByTagName("step1")[0].firstChild.nodeValue;
        displayStep1(step1Html);
        break;
      case 'enableStartB':
        $('#allowStartB').removeAttr("disabled").removeClass("greyed");
      break;
      case 'step1Complete':
        $('#closeSessionB').removeAttr("disabled").removeClass("greyed");
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
  $('#configStage').hide(1500, function() {
    // use this delay function to ensure the WS connection is stable
    xml="<message><messageType>controllerInit</messageType><content>"+uid+"</content><content>"+fName+"</content><content>"+sName+"</content><content>"+permissions+"</content><content>"+email+"</content></message>";  
    send(xml);
  });
});



