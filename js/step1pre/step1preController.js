var exptId;
var iType = '-1';
var dayNo;
var sessionNo;
var email = "";

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


function postData() {
  // content can be single value or array
  var viewModel = {};

  viewModel.permissions = 255;
  viewModel.exptId = exptId;
  viewModel.iType = iType;
  viewModel.email = email;
  console.log(viewModel);
  var jsonData = JSON.stringify(viewModel, null , 2);
  var postRequest = $.ajax({
    url: "/webServices/step1pre/process.php",
    type: "POST",
    contentType:'application/json',
    data: jsonData,
    dataType: "text"
  });
  postRequest.done(function(msg) {
    var returnPost = JSON.parse(msg);
    // now post into step1 with the correct UID
    var postdata = [];
    postdata["pageLabel"] = '4_0_1';
    postdata["process"] = '0';
    postdata["action"] = 0;
    postdata["uid"] = returnPost["uid"];
    postdata["exptId"] = exptId;
    postdata["permissions"] = 255;
    post_to_url("index.php", postdata);
  });
  postRequest.fail(function(jqXHR, textStatus) {
    //upDateError("failed: "+textStatus);
  });

}

$(document).bind( 'mobileinit', function(){
  $.mobile.loader.prototype.options.text = "loading";
  $.mobile.loader.prototype.options.textVisible = true;
  $.mobile.loader.prototype.options.theme = "a";
  $.mobile.loader.prototype.options.html = "";
});



$(document).ready(function() {
  // $('#container').hide();
  // $('#qaSection').hide();
  // $('#alignmentSection').hide();
  // $('#finalMsg').hide();
  //cue the page loader
  //$.mobile.loading( 'show' );
  exptId = $('#hiddenExptId').text();
  dayNo = $('#hiddenDayNo').text();
  sessionNo = $('#hiddenSessionNo').text();
  messageType = 'step1pre';
  setUIBindings();
//  sendAction(messageType, content);
});

function setUIBindings(jQ) {
  $('#container').show();
  // set initial states
  $('#s1OddB').prop("checked",false);
  $('#s1EvenB').prop("checked",false);
  $('#s1OddB').checkboxradio('refresh');
  $('#s1EvenB').checkboxradio('refresh');

  $('#emailTA').val('');
  $('#emailTA').trigger('refresh');
  $('#processLogin').button('disable');
  $('#processLogin').button('refresh');

  $('#processLogin').unbind();
  $('#processLogin').click( function() {
    postData();
  });

  $('#emailTA').bind('keyup', function () {
    if ($('#emailTA').val().length > 5) {
      email = $('#emailTA').val();
      if (iType != -1)  {
        $('#processLogin').button('enable');
        $('#processLogin').button('refresh');
      }
    }
    else {
      email = '';
      $('#processLogin').button('disable');
      $('#processLogin').button('refresh');
    }
  });

  $('#s1OddB').bind('change', function(event, ui) {
    iType = '1';
    if (email.length > 5) {
      $('#processLogin').button('enable');
      $('#processLogin').button('refresh');
    }
  });

  $('#s1EvenB').bind('change', function(event, ui) {
    iType = '0';
    if (email.length > 5) {
      $('#processLogin').button('enable');
      $('#processLogin').button('refresh');
    }
  });

};

