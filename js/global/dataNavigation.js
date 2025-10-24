var fName = $('#hiddenfName').text();
var sName = $('#hiddensName').text();
var fullName = fName + ' ' + sName;
$('#name').html(fullName);

$('#BackButton').click(function() {
  var uid = $('#hiddenUID').text();
  var permissions = $('#hiddenPermissions').text();
  var email = $('#hiddenEmail').text();
  var referer = $('#hiddenReferer').text();
  var lastChild = $('#hiddenChild').text();
  var pageLabel = $('#hiddenPageLabel').text();
  var pageTitle = $('#hiddenPageTitle').text();
  var exptId = $('#hiddenExptId').text();
  //var formType = $('#hiddenFormType').text();;

  var paramItems = {};
  paramItems['process'] = 0;
  switch (pageLabel) {
    case '1_1_1':
      // top level of individual expt, need to go back to experiment list with login info
      paramItems['pageLabel'] = '1_0_1';
      paramItems['userToken'] = uid + '_' + permissions;
      break;
    case '1_2_1':
      // sub-section of individual experiment - need to go back to individual experiment top level options
      paramItems['pageLabel'] = '1_1_1';
      paramItems['exptId'] = exptId;
      break;
    case '1_3_0':
      // step1 user detail  - - need to go back this parent selector
      paramItems['pageLabel'] = '1_2_1';
      paramItems['exptId'] = exptId;
      paramItems['isMultiSectionPage'] = 1;
      paramItems['sectionNo'] = 1;
      break;
    case '7_3_1':
      // quant donwload of classic-exp data
      paramItems['pageLabel'] = '1_2_1';
      paramItems['exptId'] = exptId;
      paramItems['isMultiSectionPage'] = 1;
      paramItems['sectionNo'] = 16;
      break;
    case '8_3_3' :
      // data view from odd-even selection - need to go back this selector
      paramItems['pageLabel'] = '1_2_1';
      paramItems['exptId'] = exptId;
      paramItems['isMultiSectionPage'] = 1;
      paramItems['sectionNo'] = 29;
      break;
    case '8_3_5' :
      // data view from odd-even selection - need to go back this selector
      paramItems['pageLabel'] = '1_2_1';
      paramItems['exptId'] = exptId;
      paramItems['isMultiSectionPage'] = 1;
      paramItems['sectionNo'] = 31;
      break;
    case '8_3_6' :
      // data view from odd-even selection - need to go back this selector
      paramItems['pageLabel'] = '1_2_1';
      paramItems['exptId'] = exptId;
      paramItems['isMultiSectionPage'] = 1;
      paramItems['sectionNo'] = 33;
      break;
    case '8_3_8_1':
      // quant donwload of classic-exp data
      paramItems['pageLabel'] = '1_2_1';
      paramItems['exptId'] = exptId;
      paramItems['isMultiSectionPage'] = 1;
      paramItems['sectionNo'] = 39;
      break;
    default:
      paramItems['pageLabel'] = referer;
      paramItems['exptId'] = exptId;
  }
  paramItems['uid'] = uid;
  paramItems['permissions'] = permissions;
  paramItems['fName'] = fName;
  paramItems['sName'] = sName;
  paramItems['email'] = email;
  post_to_url('/index.php', paramItems);
});

function post_to_url(path, params) {
  var method = "post"; // Set method to post by default
  var form = document.createElement("form");
  form.setAttribute("method", method);
  form.setAttribute("action", path);
  for (var key in params) {
    if (params.hasOwnProperty(key)) {
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
