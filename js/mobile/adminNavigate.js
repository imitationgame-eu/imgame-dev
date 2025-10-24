if ($.mobile) {
  $.mobile.ajaxEnabled = false; // jqm only
}

var uid = $('#hiddenUID').text();
var fName = $('#hiddenfName').text();
var sName = $('#hiddensName').text();
var permissions = $('#hiddenPermissions').text();
var email = $('#hiddenEmail').text();
var referer = $('#hiddenReferer').text();
var lastChild = $('#hiddenChild').text();
var pageLabel = $('#hiddenPageLabel').text();
var pageTitle = $('#hiddenPageTitle').text();
var operationExperimentNo = $('#hiddenExptId').text();
var formType = $('#hiddenFormType').text();;

var sectionNo = -1;
var exptId = operationExperimentNo;

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

function loadPage(pageLabel) {
  var paramItems = {};
  var pageLabelItems = pageLabel.split('_');
  if (pageLabelItems.length > 3) {
    // re-constitute the target pageLabel
    pageLabel = pageLabelItems[0] + '_' + pageLabelItems[1] + '_' + pageLabelItems[2];
    switch (pageLabel) {
      case '1_3_1': // form clone
      case '1_3_2': // form config
      case '7_3_1': // form review results
        paramItems['formType'] = pageLabelItems[3];
        break;
      case '8_3_8': // classic step1 quant or qual
        pageLabel = pageLabel + '_' + pageLabelItems[3];
        break;
      default :
        paramItems['jType'] = pageLabelItems[3];
        break;
    }
    if (pageLabelItems.length == 6) {
       paramItems['dayNo'] = pageLabelItems[4];
       paramItems['sessionNo'] = pageLabelItems[5];
    }
  }
  paramItems['process'] = 0;
  paramItems['pageLabel'] = pageLabel;
  paramItems['uid'] = uid;
  paramItems['permissions'] = permissions;
  paramItems['fName'] = fName;
  paramItems['sName'] = sName;
  paramItems['referer'] = referer;
  paramItems['lastChild'] = '1_1_1';
  paramItems['exptId'] = operationExperimentNo;
  paramItems['isMultiSectionPage'] = 0;
  post_to_url('/index.php', paramItems);  
}

function loadMultiSectionPage(pageLabel, sectionNo) {
  var paramItems = {};
  paramItems['process'] = 0;
  paramItems['pageLabel'] = pageLabel;
  paramItems['uid'] = uid;
  paramItems['permissions'] = permissions;
  paramItems['fName'] = fName;
  paramItems['sName'] = sName;
  paramItems['referer'] = '1_1_1';
  paramItems['lastChild'] = 'unset';
  paramItems['exptId'] = operationExperimentNo;
  paramItems['isMultiSectionPage'] = 1;
  paramItems['sectionNo'] = sectionNo;
  post_to_url('/index.php', paramItems);    
}

function goBack() {
  var paramItems = {};
  paramItems['process'] = 0;
  switch (pageLabel) {
    case '1_1_1':
    case '1_1_2':
    case '1_1_3':
    case '1_1_4':
    case '1_1_5':
    case '1_1_6':
      // top level of individual expt, or top level of system-section  - need to go back to experiment list with login info
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
    case '8_3_4':
      // sub-section of individual experiment - need to go back to selector
      paramItems['pageLabel'] = '1_2_1';
      paramItems['exptId'] = exptId;
      paramItems['isMultiSectionPage'] = 1;
      paramItems['sectionNo'] = 30;
      break;
    case '8_3_7':
      // sub-section of individual experiment - need to go back to selector
      paramItems['pageLabel'] = '1_2_1';
      paramItems['exptId'] = exptId;
      paramItems['isMultiSectionPage'] = 1;
      paramItems['sectionNo'] = 34;
      break;
    case '8_3_8_0':
      // qual donwload of classic-exp data
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
}

function goChild() {
  var paramItems = {};
  paramItems['process'] = 0;
  paramItems['pageLabel'] = lastChild;
  paramItems['uid'] = uid;
  paramItems['permissions'] = permissions;
  paramItems['fName'] = fName;
  paramItems['sName'] = sName;
  paramItems['email'] = email;
  post_to_url('/index.php', paramItems);    
}

function reloadPage() {
  var paramItems = {};
  switch (pageLabel) {
    case '1_0_1':
      paramItems['userToken'] = uid + '_' + permissions;
      break;
  }
  paramItems['process'] = 0;
  paramItems['pageLabel'] = pageLabel;
  paramItems['uid'] = uid;
  paramItems['permissions'] = permissions;
  paramItems['fName'] = fName;
  paramItems['sName'] = sName;
  paramItems['referer'] = '1_0_1';
  paramItems['lastChild'] = 'unset';
  paramItems['formType'] = formType;  
  paramItems['exptId'] = exptId;  
  post_to_url('/index.php', paramItems);  
}

function refreshPage() {
  var paramItems = {};
  paramItems['process'] = 0;
  paramItems['pageLabel'] = pageLabel;
  paramItems['uid'] = uid;
  paramItems['permissions'] = permissions;
  paramItems['fName'] = fName;
  paramItems['sName'] = sName;
  paramItems['referer'] = referer;
  paramItems['lastChild'] = lastChild;
  post_to_url('/index.php', paramItems);    
}

$('#backB').click(function() {
  goBack();
});

// special case for main back button at foot of registrationViews on bottom of tree, e.g. download csv files from step4
$('#backBmain').click(function() {
  goBack();
});
