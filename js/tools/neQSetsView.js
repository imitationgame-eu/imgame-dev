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
    url: '/webServices/tools/getCompleteExperimentDetail.php',
    data: paramSet,
    dataType: 'json',
    error: function(xhr, textStatus, error) { getDataError(error, textStatus, this.url); },
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
  this.exptId = ko.observable();
  this.exptTitle = ko.observable();
  //-- Call mapping function --//
  ko.mapping.fromJS(data, target, this);
};
var completeQSetsViewModel = function (data, target) {
  var _this = this;
  this.isDetailVisible = ko.observable(false);
  this.toggleDetailVisible = function() { _this.isDetailVisible() ? _this.isDetailVisible(false) : _this.isDetailVisible(true); };
  this.isStep1Visible = ko.observable(false);
  this.toggleStep1Visible = function() { _this.isStep1Visible() ? _this.isStep1Visible(false) : _this.isStep1Visible(true); };
  this.isStep2PretendersVisible = ko.observable(false);
  this.toggleStep2PretendersVisible = function() { _this.isStep2PretendersVisible() ? _this.isStep2PretendersVisible(false) : _this.isStep2PretendersVisible(true); };
  ko.mapping.fromJS(data, target, this);
}
// --------------------------------------------------- Mappings ------ //
var mainMapping = {
  create: function (options) {
    return new mainViewModel(options.data, completeQSetsMapping);
  }
};
var completeQSetsMapping = {
  'completeQsets' : {
    create: function (options) {
      return new completeQSetsViewModel(options.data, {}, options.parent);
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
  $('.download').click(function(e) {
    var paramItems = {};
    paramItems['uid'] = uid;
    paramItems['permissions'] = permissions;
    paramItems['exptId'] = exptId;
    paramItems['jType'] = jType;
    paramItems['jsonData'] = JSON.stringify(jsonData);
    post_to_url('/webServices/tools/getCompleteExperimentDetailRTF.php', paramItems);    
  });
//  $('.viewAccordion').click(function(e) {
//    if ($(this).hasClass('contracted')) {
//      $(this).removeClass('contracted').addClass('expanded');
//    }
//    else {
//      $(this).removeClass('expanded').addClass('contracted');      
//    }
//  });
  
}

function getDataError(error, textStatus, url) {
  console.log('there was an error with the ajax request from ' + url + ' > ' + error + ' >> ' + textStatus);
  $('.waitingForAction').hide();
}


