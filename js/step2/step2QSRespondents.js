var uid;
var permissions;
var exptId;
var jType;
var jsonData;

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

function getData() {
  uid = $('#hiddenUID').text();
  permissions = $('#hiddenPermissions').text();
  exptId = $('#hiddenExptId').text();
  jType = $('#hiddenJType').text();
  var paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['exptId'] = exptId;
  paramSet['jType'] = jType;
  $.ajax({
    type: 'GET',
    url: '/webServices/step2/getS1QSs2RespondentDetail.php',
    data: paramSet,
    dataType: 'json',
    error: function(xhr, textStatus, error) { getDataError(xhr, error, textStatus, this.url); },
    success: function(data) { getDataSuccess(data); }
  });   
}

$(document).ready(function() {
  $('.saveStatus').hide();
  $('.waitingForAction').show();
  getData();
});

// --------------------------------------------------- View models ------ //
var mainViewModel = function (data, target) {
  var _this = this;
  //-- Observables go here --//
  //-- Call mapping function --//
  ko.mapping.fromJS(data, target, this);
};

var S1QSViewModel = function (data, target, parent) {
  var _this = this;
  
  ko.mapping.fromJS(data, target, this);
}

var S2PptsViewModel = function (data, target, parent) {
  var _this = this;
  ko.mapping.fromJS(data, target, this);
}
// --------------------------------------------------- Mappings ------ //
var mainMapping = {
  create: function (options) {
    return new mainViewModel(options.data, S1QSMapping);
  }
};

var S1QSMapping = {
  'S1QS' : {
    create: function(options) {
      return new S1QSViewModel(options.data, S2PptsMapping, options.parent)
    }
  }
};

var S2PptsMapping = {
  's2ppts' : {
    create: function (options) {
      //return new S2PptsViewModel(options.data, {}, options.parent);
      return new S2PptsViewModel(options.data, {}, options.parent);
    }
  }
};

var viewModel;
// --------------------------------------------------- Functions ------ //
function getDataSuccess(data) {
  jsonData = data;  // in case of RTF download
  //console.log('json data successfully returned ');
  viewModel = ko.mapping.fromJS(data, mainMapping);
  //console.log(viewModel);
  ko.applyBindings(viewModel);
  $('.waitingForAction').hide();
  $('.tabContent').show();
//  $('.download').click(function(e) {
//    var paramItems = {};
//    paramItems['uid'] = uid;
//    paramItems['permissions'] = permissions;
//    paramItems['exptId'] = exptId;
//    paramItems['jType'] = jType;
//    paramItems['jsonData'] = JSON.stringify(jsonData);
//    post_to_url('/webServices/audit/getS2RespondentAuditRTF.php', paramItems);    
//  });  
}

function getDataError(xhr, error, textStatus, url) {
  console.log('there was an error with the ajax request from ' + url + ' > ' + error + ' >> ' + textStatus);
  var debug = xhr;
  $('.waitingForAction').hide();
}


