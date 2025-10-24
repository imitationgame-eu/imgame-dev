// naughty global variables - mostly for ws and ratings.
var Server;


function send( text ) {
  console.log('message: '+text);
  Server.send( 'message', text );
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

function wsConnect() {
  var hostname=location.host;
  var wsName='ws://'+hostname+':8080';
  console.log(wsName);
  Server = new igrtWebSocket(wsName);
  filename = window.location.href.substr(window.location.href.lastIndexOf("/")+1);//url.match(/.*\/(.*)$/)[1]; 
  srcName=filename.substr(0,filename.lastIndexOf("."));
    //Let the user know we're connected
    Server.bind('open', function() {
        //
    });
    //Disconnection occurred.
    Server.bind('close', function(data) {
      console.log( "Disconnected." );
    });
    //process messages sent from server (these may be discrete changes or whole info - will decide later)
    Server.bind('message', function( payload ) {
      console.log(payload);
        // process payload xml and insert appropriately into tags
        var xmlDoc = txtToXmlDoc(payload);  
        var messageType=xmlDoc.getElementsByTagName("messageType")[0].firstChild.nodeValue;
        switch (messageType) {
          case "updateHistory" :
            var content=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
            $('#history').html(content);
            break;                    
        }
    });
    Server.connect();
    // use a delay function to ensure the WS connection is stable before exptJoin
    $('#estConn').hide(3000, function() {
      $('#game').show();
    }); 
    $('#msgB').click(function(e) {
      var payload = '<message><messageType>send</messageType><content>' + $('#msgTxt').val() + '</content></message>';
      send(payload);
      $('#msgTxt').val('');
    });
}

//$(document).ready(function() {
$(window).load(function() {
  $('#game').hide();
  wsConnect();
});



