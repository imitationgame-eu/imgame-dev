var uid;
var permissions;
var exptId;
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
  dayNo = $('#hiddenDayNo').text();
  sessionNo = $('#hiddenSessionNo').text();
  var paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['exptId'] = exptId;
  paramSet['dayNo'] = dayNo;
  paramSet['sessionNo'] = sessionNo;
  $.ajax({
    type: 'GET',
    url: '/webServices/classic/getJSONForView.php',
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
  this.title = ko.observable();
  this.dayNo = ko.observable();
  this.sessionNo = ko.observable();
  //-- Call mapping function --//
  ko.mapping.fromJS(data, target, this);
};
var dayViewModel = function (data, target) {
  var _this = this;
  //_this.dayNo = ko.observable();
  ko.mapping.fromJS(data, target, this);
}
var sessionViewModel = function (data, target) {
  var _this = this;
  //_this.sessionNo = ko.observable();
  ko.mapping.fromJS(data, target, this);
}
var ownerViewModel = function (data, target) {
  var _this = this;
  _this.owner = ko.observable();
  _this.email = ko.observable();
  ko.mapping.fromJS(data, target, this);
}
var turnViewModel = function (data, target) {
  var _this = this;
  _this.turnNo = ko.observable();
  _this.jQ = ko.observable();
  _this.npA = ko.observable();
  _this.pA = ko.observable();
  ko.mapping.fromJS(data, target, this);
}
// --------------------------------------------------- Mappings ------ //
var mainMapping = {
  create: function (options) {
    return new mainViewModel(options.data, daysMapping);
  }
};
var daysMapping = {
  'days' : {
    create: function (options) {
      return new dayViewModel(options.data, sessionsMapping);
    }
  }
};
var sessionsMapping = {
  'sessions' : {
    create: function (options) {
      return new sessionViewModel(options.data, ownersMapping);
    }
  }
};
var ownersMapping = {
  'owners' : {
    create: function (options) {
      return new ownerViewModel(options.data, turnsMapping);
    }
  }
};
var turnsMapping = {
  'turns' : {
    create: function (options) {
      return new turnViewModel(options.data, {}, {});
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


