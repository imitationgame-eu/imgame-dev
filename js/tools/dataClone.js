var uid;
var permissions;
var fName;
var exptId;
var viewModel;

// <editor-fold defaultstate="collapsed" desc=" DOM ready and data-comms">

$(document).ready(function() {
  $('.saveStatus').hide();
  $('.waitingForAction').show();
  getData(); 
});

function getData() {
  uid = $('#hiddenUID').text();
  permissions = $('#hiddenPermissions').text();
  fName = $('#hiddenfName').text();
  var buttonId = $('#hiddenButtonId').text();
  var buttonDetails = buttonId.split('_');
  exptId = buttonDetails[1];
  $('#name').html(fName);
  var paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['exptId'] = exptId;
  $.ajax({
    type: 'GET',
    url: '/webServices/tools/getDataCloneSets.php',
    data: paramSet,
    dataType: 'json',
    error: function(xhr, textStatus, error) { getDataError(error, textStatus, this.url); },
    success: function(data) { getDataSuccess(data); }
  });   
}

function getDataSuccess(data) {
  //console.log('json data successfully returned ');
  viewModel = ko.mapping.fromJS(data, experimentsMapping);
  //console.log(viewModel);
  ko.applyBindings(viewModel);
  $('.waitingForAction').hide();
}

function getDataError(error, textStatus, url) {
  console.log('there was an error with the ajax request from ' + url + ' > ' + error + ' >> ' + textStatus);
}

function processClone() {
  var currentData = ko.mapping.toJS(viewModel);
  var jsonData = JSON.stringify(currentData, null , 2);
  var postRequest = $.ajax({
     url: "/webServices/tools/processDataCloneSets.php",
     type: "POST",
     contentType:'application/json',
     data: jsonData,
     dataType: "text"
  });
  postRequest.done(function() {
    // reload with original page parameters to obtain new status
    var paramItems = {};
    paramItems['process'] = 0;
    paramItems['uid'] = uid;
    paramItems['permissions'] = permissions;
    paramItems['buttonId'] = 'dataclone_' + exptId;
    paramItems['action'] = '1_2_9';
    post_to_url('/index.php', paramItems);    
  });
  postRequest.fail(function(jqXHR, textStatus) {
    console.log("save data failed: "+textStatus);
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

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" viewModels">

var experimentsViewModel = function (data, target) {
  var _this = this;
  this.getReminder = ko.computed(function() {
    return "Remember: you are cloning data FROM " + _this.currentExperimentName() + " onto any selected item below. You may override a previous injection if one exists.";
  });
  //-- Call mapping function --//
  ko.mapping.fromJS(data, target, this);
};

var cloneViewModel = function(data, target, parent) {
  var _this = this;
  this.sendingData = ko.observable(false);
  this.getExptTitle = ko.computed(function(){
    return _this.exptId() + ' - ' + _this.title();
  });
  this.s1srcLegend = ko.computed(function() {
    if (_this.manuallyInjected() === true) {
      return 'manually injected: override?';
    }
    return _this.s1srcExptId() > -1 ? 'previous injection-' + _this.s1srcExptId() + ': override?' : 'clone';
  });
  this.s2srcLegend = ko.computed(function() {
    return _this.s2srcExptId() > -1 ? 'previous injection-' + _this.s2srcExptId() + ': override?' : 'clone';
  });
  this.s2invertedsrcLegend = ko.computed(function() {
    return _this.s2invertedsrcExptId() > -1 ? 'previous injection-' + _this.s2invertedsrcExptId() + ': override?' : 'clone';
  });
  this.showOverrideS1 = ko.computed(function() {
    if (_this.manuallyInjected() === true) { return true; }
    return _this.s1srcExptId() > -1 ? true : false;   
  });
  this.showOverrideS2 = ko.computed(function() {
    return _this.s2srcExptId() > -1 ? true : false;   
  });
  this.showOverrideS2inverted = ko.computed(function() {
    return _this.s2invertedsrcExptId() > -1 ? true : false;   
  });
  this.showCloneS1 = ko.computed(function() {
    if (_this.manuallyInjected() === true) { return false; }
    return _this.s1srcExptId() > -1 ? false : true;   
  });
  this.showCloneS2 = ko.computed(function() {
    return _this.s2srcExptId() > -1 ? false : true;   
  });
  this.showCloneS2inverted = ko.computed(function() {
    return _this.s2invertedsrcExptId() > -1 ? false : true;   
  });
  this.showSaveRow = ko.computed(function(){
    if (_this.injected() == 1) {
      return _this.injectedNo() == (parent.injectedCount() - 1)? true : false;
    }
    else {
      return _this.standardNo() == (parent.standardCount() - 1) ? true : false;      
    }
  });
  this.showStandardHeader = ko.computed(function(){
    if (_this.injected() == 1) {
      return _this.injectedNo() == (parent.injectedCount() - 1) ? true : false;
    }
    else {
      return false;
    }
  });
  this.doCloneButtonClass = ko.computed(function(){
    return _this.sendingData() === false ? "" : "greyed";
  });
  this.doClone = function() {
    if (_this.sendingData() === false) {
      _this.sendingData(true);
      processClone();
    }
  };
  this.getRowHeight = ko.computed(function() {
    return (28 + _this.daySessionsCount()*32) + 'px'; 
  });
  //-- Call mapping function --//
  ko.mapping.fromJS(data, target, this);  
};

var daySessionsViewModel = function(data, target, parent) {
  var _this = this;
  this.sLegend = ko.computed(function() {
    return 'day:' + _this.dayNo() + ' session:' + _this.sessionNo() + '(approx ' + _this.jCnt() + ' judges)';
  });
  this.s1cloneId = ko.computed(function() {
    return "s1clone_" + parent.exptId() + '_' + _this.dayNo() + '_' + _this.sessionNo();
  });
  this.overrides1cloneId = ko.computed(function() {
    return "overrides1clone_" + parent.exptId() + '_' + _this.dayNo() + '_' + _this.sessionNo();
  });
  this.s2cloneId = ko.computed(function() {
    return "s2clone_" + parent.exptId();
  });
  this.s2invertedcloneId = ko.computed(function() {
    return "s2invertedclone_" + parent.exptId();
  });
  this.overrides2cloneId = ko.computed(function() {
    return "overrides2clone_" + parent.exptId();
  });
  this.overrides2invertedcloneId = ko.computed(function() {
    return "overrides1invertedclone_" + parent.exptId();
  });
  ko.mapping.fromJS(data, target, this);  
};

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" mappings">

var experimentsMapping = {
    create: function (options) {
        return new experimentsViewModel(options.data, cloneMapping);
    }
};

var cloneMapping = {
  'experiments' : {
    create: function (options) {
      return new cloneViewModel(options.data, daySessionsMapping, options.parent);
    }
  } 
};

var daySessionsMapping = {
  'daySessions' : {
    create: function (options) {
      return new daySessionsViewModel(options.data, {}, options.parent);
    }
  }   
};

// </editor-fold>

