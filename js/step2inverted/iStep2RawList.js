var uid;
var permissions;
var fName;

function ajaxSuccess(sessionDataHtml) {
  $('#sessionList').html(sessionDataHtml);
  $('#tabOneContent').show();
  $('.button').on('click' , function(e) {
    var details=$(this).attr('id').split('_');
    var paramItems = {};
    paramItems['process'] = 0;
    paramItems['action'] = '3_2_6'; 
    paramItems['uid'] = uid;
    paramItems['permissions'] = permissions;
    paramItems['fName'] = fName;
    paramItems['exptId'] = details[1];
    paramItems['jType'] = details[2];
    post_to_url('/index.php',paramItems);
  });
  // hide accordions & set function
  $('h2').next('div').css('display', 'none');
  $('h2').on('click' , function(e) {
    if ($(this).hasClass('closed')) {
      $(this).removeClass('closed').addClass('open');
      $(this).next('div').css('display', 'inline');
    }
    else {
      $(this).removeClass('open').addClass('closed');
      $(this).next('div').css('display', 'none');
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
  $('#name').html(fName);
  var paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  $.ajax({
    type: 'GET',
    url: '/webServices/step2inverted/getInvertedStep2Datasets.php',
    data: paramSet,
    dataType: 'text',
    success: function(data) { ajaxSuccess(data); }
  });
});

