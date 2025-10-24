var uid;
var permissions;
var fName;

function ajaxSuccess(step2DataHtml) {
  $('#step2List').html(step2DataHtml);
  $('#tabOneContent').show();
  $('.button').on('click' , function(e) {
    var details=$(this).attr('id').split('_');
    var paramItems = {};
    paramItems['process'] = 0;
    paramItems['action'] = '1_2_3';
    paramItems['uid'] = uid;
    paramItems['permissions'] = permissions;
    paramItems['fName'] = fName;
    paramItems['exptId'] = details[1];
    paramItems['jType'] = details[2];
    post_to_url('/index.php',paramItems);
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
        url: '/webServices/step2/listStep2BalancerStatus.php',
        data: paramSet,
        dataType: 'text',
        success: function(data) { ajaxSuccess(data); }
    });
});

