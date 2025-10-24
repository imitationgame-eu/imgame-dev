var uid;
var permissions;
var fName;


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
  dayNo = $('#hiddenDayNo').text();
  sessionNo = $('#hiddenSessionNo').text();
//  $('#name').html(fName);
  var paramItems = {};
  paramItems['uid'] = uid;
  paramItems['permissions'] = permissions;
  paramItems['exptId'] = exptId;
  paramItems['dayNo'] = dayNo;
  paramItems['sessionNo'] = sessionNo;
  post_to_url('/webServices/classic/classicCSVData.php', paramItems);
});

