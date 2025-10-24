var uid;
var firstName;
var sName;
var permissions;
var currentExptName;
var sourceExptId;
var formType;
var formName;
var paramSet = {};
var messageType;
var formName; 

var dataVM = {};
dataVM.flipSwitchStates = [];

//------------------------------------------------------------------------------
//  helpers
//------------------------------------------------------------------------------

function doClone() {
  // send form to back-end for save, before any structure changes
  var jsonData = JSON.stringify(dataVM, null , 2);
  var postRequest = $.ajax({
     url: "/webServices/admin/cloneStepForm.php",
     type: "POST",
     contentType:'application/json',
     data: jsonData,
     dataType: "text"
  });
  postRequest.done(function(msg) {
    $('#statusMsg').html("cloned data successfully saved").show().fadeOut(2000);

    // reload this clone controller page
    // var paramItems = {};
    // paramItems['process'] = 0;
    // paramItems['pageLabel'] = '1_3_1';
    // paramItems['formType'] = dataVM.sourceFormType;
    // paramItems['exptId'] = dataVM.sourceExptId;
    // paramItems['uid'] = $('#hiddenUID').text();
    // paramItems['permissions'] = $('#hiddenPermissions').text();
    // // paramItems['buttonId'] = 'clone_' + formName;
    // // paramItems['messageType'] = 'stepFormClone';
    // // paramItems['currentExptName'] = currentExptName;
    // post_to_url('/index.php', paramItems);
  });
  postRequest.fail(function(jqXHR, textStatus) {
    $('#statusMsg').html("problem saving cloned data").show().fadeOut(2000);
    console.log("structure change failed: "+textStatus);
  });
}

$(document).ready(function() {
  $('#statusMsg').hide();
  dataVM.uid = $('#hiddenUID').text();
  dataVM.sourceExptId = $('#hiddenExptId').text();
  dataVM.sourceFormType = $('#hiddenFormType').text();
  dataVM.permissions = $('#hiddenPermissions').text();
  // create data structure to store results of changing a flipswitch
  $('[id^=fs_]').each(function() {
    var id = $(this).attr('id');
    var fsState = {};
    fsState.id = id;
    fsState.cloneHere = 0;
    dataVM.flipSwitchStates.push(fsState);
  });
  $('.clone_fs').change(function() {
    var id = $(this).attr('id');
    for (var i=0; i<dataVM.flipSwitchStates.length; i++) {
      if (dataVM.flipSwitchStates[i].id === id) {
        dataVM.flipSwitchStates[i].cloneHere =  dataVM.flipSwitchStates[i].cloneHere === 0 ? 1 : 0;
      }
    }
  });

  $('#submitB').click(function() {
    doClone();
  });
  $('#backB').click(function() {
    loadMultiSectionPage('1_2_1', '15');
  });
});



