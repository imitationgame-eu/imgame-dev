var uid;
var permissions;
var fName;

function ajaxSuccess(sessionDataHtml) {
  $('#sessionList').html(sessionDataHtml);
  $('#tabOneContent').show();
  $('.button').on('click', function(e) {
    var details = $(this).attr('id').split('_');
    var paramItems = {};
    paramItems['process'] = 0;
    paramItems['action'] = '3_2_4';
    paramItems['uid'] = uid;
    paramItems['permissions'] = permissions;
    paramItems['exptId'] = details[1];
    paramItems['jType'] = details[2];
    post_to_url('/index.php', paramItems);
  });
}


//------------------------------------------------------------------------------
//  DOM ready
//------------------------------------------------------------------------------

$(document).ready(function() {
  var paramSet = {};
  paramSet['p1'] = 28;
  paramSet['p2'] = 255;
  $.ajax({
    type: 'GET',
    url: '/webServices/system/versionHistory.php',
    data: paramSet,
    dataType: 'text',
    success: function(data) {
      ajaxSuccess(data);
    }
  });
});

