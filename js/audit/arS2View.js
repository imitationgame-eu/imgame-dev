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
    url: '/webServices/audit/getS2RespondentDetail.php',
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
  this.exptId = ko.observable();
  this.exptTitle = ko.observable();
  this.jType = ko.observable();
  this.dayCnt = ko.observable();
  this.sessionCnt = ko.observable();
  this.isDiscardedVisible = ko.observable(false);
  this.toggleDiscardedVisible = function() { _this.isDiscardedVisible() ? _this.isDiscardedVisible(false) : _this.isDiscardedVisible(true); };
  this.isIgnoredVisible = ko.observable(false);
  this.toggleIgnoredVisible = function() { _this.isIgnoredVisible() ? _this.isIgnoredVisible(false) : _this.isIgnoredVisible(true); };
  this.isGoodVisible = ko.observable(false);
  this.toggleGoodVisible = function() { _this.isGoodVisible() ? _this.isGoodVisible(false) : _this.isGoodVisible(true); };
  //ko.mapping.fromJS(data, target, this);
  //-- Call mapping function --//
  ko.mapping.fromJS(data, target, this);
};

var S2PptsViewModel = function (data, target) {
  var _this = this;
  this.isDiscardedVisible = ko.observable(false);
  this.toggleDiscardedVisible = function() { _this.isDiscardedVisible() ? _this.isDiscardedVisible(false) : _this.isDiscardedVisible(true); };
  this.isIgnoredVisible = ko.observable(false);
  this.toggleIgnoredVisible = function() { _this.isIgnoredVisible() ? _this.isIgnoredVisible(false) : _this.isIgnoredVisible(true); };
  this.isGoodVisible = ko.observable(false);
  this.toggleGoodVisible = function() { _this.isGoodVisible() ? _this.isGoodVisible(false) : _this.isGoodVisible(true); };
  ko.mapping.fromJS(data, target, this);
}
// --------------------------------------------------- Mappings ------ //
var mainMapping = {
  create: function (options) {
    return new mainViewModel(options.data, {}, options.parent);
  }
};
var S2PptsMapping = {
  's2ppts' : {
    create: function (options) {
      //return new S2PptsViewModel(options.data, {}, options.parent);
      return new S2PptsViewModel(options.data, options.parent);
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
    post_to_url('/webServices/audit/getS2RespondentAuditRTF.php', paramItems);    
  });  
}

function getDataError(xhr, error, textStatus, url) {
  console.log('there was an error with the ajax request from ' + url + ' > ' + error + ' >> ' + textStatus);
  var debug = xhr;
  $('.waitingForAction').hide();
}


