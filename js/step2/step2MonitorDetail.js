var uid;
var permissions;
var fName;
var exptId;
var jType;

function injectRespDetail(accordionId, transcriptHtml) {
  var jqId = "#" + accordionId; 
  $(jqId).next('div').find('.left').html(transcriptHtml);
}

function getTranscript(accordionId) {
  var paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['exptId'] = exptId;
  paramSet['jType'] = jType;
  paramSet['messageType'] = "getTranscript";
  var contentArray = {};
  contentArray[0] = accordionId;
  paramSet['content'] = contentArray;
  $.ajax({
    type: 'GET',
    url: '/webServices/step2/step2RespDetailController.php',
    data: paramSet,
    dataType: 'text',
    success: function(data) { injectRespDetail(accordionId, data); }
  });
}

function injectDSDetail(headerId, data) {
  $(headerId).next('div').html(data);
  $(headerId).next('div').find('.s2Resp').each( function(e) {
    $(this).removeClass("open");
    $(this).addClass("closed");
    $(this).next('div').hide();
  });
  $(headerId).next('div').find('.s2Resp').click( function(e) {
    if ($(this).hasClass("closed")) {
      $(this).removeClass("closed").addClass("open");
      $(this).next('div').show();
      var accordionId = $(this).attr('id');
      getTranscript(accordionId);
    }
    else {
      $(this).removeClass("open").addClass("closed");
      $(this).next('div').hide();      
    }
  });
  $('.button').unbind();
  $('.button').on('click' , function(e) {
    discardPpt($(this).attr('id'));
  });

}

function discardPpt(buttonId) {
  // firstly discard extra id info used to make markup clearer
  var details = buttonId.split('&');
  var pptCode = details[1];
  var pptDetails = pptCode.split('_');
  var headerId = "#dsHeader_s2_" + pptDetails[1] + '_' + pptDetails[2] + '_' + pptDetails[3];
  var paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['exptId'] = exptId;
  paramSet['jType'] = jType;
  paramSet['messageType'] = "discardPpt";
  var contentArray = {};
  contentArray[0] = pptCode;
  paramSet['content'] = contentArray;
  $.ajax({
    type: 'GET',
    url: '/webServices/step2/step2RespDetailController.php',
    data: paramSet,
    dataType: 'text',
    success: function(data) { injectDSDetail(headerId, data); }
  });  
}

function ajaxSuccess(step2DataHtml) {
  $('#step2RespondentDetail').html(step2DataHtml);
  $('#tabOneContent').show();
  $('.button').on('click' , function(e) {
    discardPpt($(this).attr('id'));
  });
  $('#step2RespondentDetail').find('.s2Resp').each( function(e) {
    $(this).removeClass("open");
    $(this).addClass("closed");
    $(this).next('div').hide();
  });
  $('.s2Resp').click( function(e) {
    if ($(this).hasClass("closed")) {
      $(this).removeClass("closed").addClass("open");
      $(this).next('div').show();
      var accordionId = $(this).attr('id');
      getTranscript(accordionId);
    }
    else {
      $(this).removeClass("open").addClass("closed");
      $(this).next('div').hide();      
    }
  });
}

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


//------------------------------------------------------------------------------
//  DOM ready
//------------------------------------------------------------------------------

$(document).ready(function() {
  uid = $('#hiddenUID').text();
  permissions = $('#hiddenPermissions').text();
  fName = $('#hiddenfName').text();
  exptId = $('#hiddenExptId').text();
  jType = $('#hiddenJType').text();
  $('#name').html(fName);
  var paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['exptId'] = exptId;
  paramSet['jType'] = jType;
  $.ajax({
    type: 'GET',
    url: '/webServices/step2/getStep2RespDetail.php',
    data: paramSet,
    dataType: 'text',
    success: function(data) { ajaxSuccess(data); }
  });
});

