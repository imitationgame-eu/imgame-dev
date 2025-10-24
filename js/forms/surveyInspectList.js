var uid;
var permissions;
var fName;
var exptId;
var activeStandardVM;
var activeInjectedVM;
var inactiveStandardVM;
var inactiveInjectedVM;

// <editor-fold defaultstate="collapsed" desc=" process dialogues">

function processSurveyView(exptType, formType, exptId, exptTitle) {
  $("#dialog:ui-dialog").dialog("destroy");
  $('#legend').html(exptTitle);
  $("#dialog-showSurvey").dialog({
      resizable: false,
      height:140,
      modal: true,
      buttons: {
        "View" : function() {
          var paramItems = {};
          paramItems['process'] = 0;
          paramItems['action'] = '7_2_2'; //
          paramItems['uid'] = uid;
          paramItems['permissions'] = permissions;
          paramItems['exptId'] = exptId;
          paramItems['formType'] = formType;
          paramItems['exptType'] = exptType;
          paramItems['exptTitle'] = exptTitle;
          post_to_url('/index.php', paramItems);
          
          $(this).dialog("close");
        },
        "Download" : function() {
          var paramItems = {};
          paramItems['uid'] = uid;
          paramItems['permissions'] = permissions;
          paramItems['exptId'] = exptId;
          paramItems['formType'] = formType;
          paramItems['exptType'] = exptType;
          paramItems['exptTitle'] = exptTitle;
          post_to_url('/webServices/forms/getSurveyDownload.php', paramItems);
          $(this).dialog("close");
        }
      }
  });
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" DOM ready and data-comms">

$(document).ready(function() {
  $('.saveStatus').hide();
  $('.waitingForAction').show();
  getData(); 
  setTabs();
  $('.waitingForAction').hide();
});

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
  fName = $('#hiddenfName').text();
  exptId = $('#hiddenExptId').text();
  jType = $('#hiddenJType').text();
  $('#name').html(fName);
  var paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['exptType'] = 0; //active-standard
  $.ajax({
    type: 'GET',
    url: '/webServices/forms/listSurveyDatasets.php',
    data: paramSet,
    dataType: 'json',
    error: function(xhr, textStatus, error) { getDataError(error, textStatus, this.url); },
    success: function(data) { processActiveStandard(data); }
  });   
  paramSet['exptType'] = 1; //active-injected
  $.ajax({
    type: 'GET',
    url: '/webServices/forms/listSurveyDatasets.php',
    data: paramSet,
    dataType: 'json',
    error: function(xhr, textStatus, error) { getDataError(error, textStatus, this.url); },
    success: function(data) { processActiveInjected(data); }
  });   
  paramSet['exptType'] = 2; //inactive-standard
  $.ajax({
    type: 'GET',
    url: '/webServices/forms/listSurveyDatasets.php',
    data: paramSet,
    dataType: 'json',
    error: function(xhr, textStatus, error) { getDataError(error, textStatus, this.url); },
    success: function(data) { processInactiveStandard(data); }
  });   
  paramSet['exptType'] = 3; //inactive-injected
  $.ajax({
    type: 'GET',
    url: '/webServices/forms/listSurveyDatasets.php',
    data: paramSet,
    dataType: 'json',
    error: function(xhr, textStatus, error) { getDataError(error, textStatus, this.url); },
    success: function(data) { processInactiveInjected(data); }
  });   
}

function processActiveStandard(data) {
  activeStandardVM = ko.mapping.fromJS(data, activeStandardMapping);
  ko.applyBindings(activeStandardVM, document.getElementById("activeStandardExperiments"));
}

function processActiveInjected(data) {
  activeInjectedVM = ko.mapping.fromJS(data, activeInjectedMapping);
  ko.applyBindings(activeInjectedVM, document.getElementById("activeInjectedExperiments"));
}

function processInactiveStandard(data) {
  inactiveStandardVM = ko.mapping.fromJS(data, inactiveStandardMapping);
  ko.applyBindings(inactiveStandardVM, document.getElementById("inactiveStandardExperiments"));
}

function processInactiveInjected(data) {
  inactiveInjectedVM = ko.mapping.fromJS(data, inactiveInjectedMapping);
  ko.applyBindings(inactiveInjectedVM, document.getElementById("inactiveInjectedExperiments"));
}

function getDataError(error, textStatus, url) {
  console.log('there was an error with the ajax request from ' + url + ' > ' + error + ' >> ' + textStatus);
}

function upDateSuccess(data) {
  $('.saveStatus').html('You have saved your changes. You can continue to mark/edit this session.');
  $('.saveStatus').fadeOut(4000);
}

function upDateError(data) {
    alert(data);
}

function saveData() {
  $('.saveStatus').html('Saving your changes......');
  $('.saveStatus').show();
  var currentData = ko.mapping.toJS(viewModel);
  var jsonData = JSON.stringify(currentData, null , 2);
  var postRequest = $.ajax({
    url: "/webServices/step2/storeStep2ReviewedData.php",
    type: "POST",
    contentType:'application/json',
    data: jsonData,
    dataType: "text"
  });
  postRequest.done(function(msg) {
      upDateSuccess(msg);
  });
  postRequest.fail(function(jqXHR, textStatus) {
      upDateError("failed: "+textStatus);
  });
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" viewModels">

var asViewModel = function (data, target) {
  var _this = this;
  this.headerOpen = ko.observable(false);
  this.headerClass = ko.computed(function(){
    return _this.headerOpen() === false ? "closed" : "open";
  });
  this.bodyVisible = ko.computed(function(){
    return _this.headerOpen();
  });
  this.toggleHeader = function() {
    if (_this.headerOpen() === false) { _this.headerOpen(true); } else { _this.headerOpen(false); }
  };
  this.exptTypeSectionVisible = ko.computed(function(){
    return _this.exptCount() === 0 ? false : true;
  });
  ko.mapping.fromJS(data, target, this);
};

var aiViewModel = function (data, target) {
  var _this = this;
  this.headerOpen = ko.observable(false);
  this.headerClass = ko.computed(function(){
    return _this.headerOpen() === false ? "closed" : "open";
  });
  this.bodyVisible = ko.computed(function(){
    return _this.headerOpen();
  });
  this.toggleHeader = function() {
    _this.headerOpen(!_this.headerOpen());
  };
  this.exptTypeSectionVisible = ko.computed(function(){
    return _this.exptCount() === 0 ? false : true;
  });
  ko.mapping.fromJS(data, target, this);
};

var isViewModel = function (data, target) {
  var _this = this;
  this.headerOpen = ko.observable(false);
  this.headerClass = ko.computed(function(){
    return _this.headerOpen() === false ? "closed" : "open";
  });
  this.bodyVisible = ko.computed(function(){
    return _this.headerOpen();
  });
  this.toggleHeader = function() {
    _this.headerOpen(!_this.headerOpen());
  };
  this.exptTypeSectionVisible = ko.computed(function(){
    return _this.exptCount() === 0 ? false : true;
  });
  ko.mapping.fromJS(data, target, this);
};

var iiViewModel = function (data, target) {
  var _this = this;
  this.headerOpen = ko.observable(false);
  this.headerClass = ko.computed(function(){
    return _this.headerOpen() === false ? "closed" : "open";
  });
  this.bodyVisible = ko.computed(function(){
    return _this.headerOpen();
  });
  this.toggleHeader = function() {
    _this.headerOpen(!_this.headerOpen());
  };
  this.exptTypeSectionVisible = ko.computed(function(){
    return _this.exptCount() === 0 ? false : true;
  });
  ko.mapping.fromJS(data, target, this);
};

var as_experimentViewModel = function (data, target, parent) {
  var _this = this;

  this.s2preClass = ko.computed(function() {
    return _this.s2preNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewS2preMsg = ko.computed(function () {
    return 'view ' + _this.s2preNo() + ' s2pre';
  });
  this.processS2pre = function() {
    if (_this.s2preNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 6, _this.exptId(), _this.exptTitle());
    }
  };

  this.s2postClass = ko.computed(function() {
    return _this.s2postNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewS2postMsg = ko.computed(function () {
    return 'view ' + _this.s2postNo() + ' s2post';
  });
  this.processS2post = function() {
    if (_this.s2postNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 7, _this.exptId(), _this.exptTitle());
    }
  };

  this.is2preClass = ko.computed(function() {
    return _this.inverteds2preNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewiS2preMsg = ko.computed(function () {
    return 'view ' + _this.inverteds2preNo() + ' is2pre';
  });
  this.processiS2pre = function() {
    if (_this.inverteds2preNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 12, _this.exptId(), _this.exptTitle());
    }
  };

  this.is2postClass = ko.computed(function() {
    return _this.inverteds2preNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewiS2postMsg = ko.computed(function () {
    return 'view ' + _this.inverteds2preNo() + ' is2post';
  });
  this.processiS2post = function() {
    if (_this.inverteds2postNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 13, _this.exptId(), _this.exptTitle());
    }
  };

  this.s4preClass = ko.computed(function() {
    return _this.s4preNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewS4preMsg = ko.computed(function () {
    return 'view ' + _this.s4preNo() + ' s4pre';
  });
  this.processS4pre = function() {
    if (_this.s4preNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 10, _this.exptId(), _this.exptTitle());
    }
  };

  this.s4postClass = ko.computed(function() {
    return _this.s4postNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewS4postMsg = ko.computed(function () {
    return 'view ' + _this.s4postNo() + ' s4post';
  });
  this.processS4post = function() {
    if (_this.s4postNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 11, _this.exptId(), _this.exptTitle());
    }
  };
  
  ko.mapping.fromJS(data, target, this);
};

var ai_experimentViewModel = function (data, target, parent) {
  var _this = this;

  this.s2preClass = ko.computed(function() {
    return _this.s2preNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewS2preMsg = ko.computed(function () {
    return 'view ' + _this.s2preNo() + ' s2pre';
  });
  this.processS2pre = function() {
    if (_this.s2preNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 6, _this.exptId(), _this.exptTitle());
    }
  };

  this.s2postClass = ko.computed(function() {
    return _this.s2postNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewS2postMsg = ko.computed(function () {
    return 'view ' + _this.s2postNo() + ' s2post';
  });
  this.processS2post = function() {
    if (_this.s2postNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 7, _this.exptId(), _this.exptTitle());
    }
  };

  this.is2preClass = ko.computed(function() {
    return _this.inverteds2preNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewiS2preMsg = ko.computed(function () {
    return 'view ' + _this.inverteds2preNo() + ' is2pre';
  });
  this.processiS2pre = function() {
    if (_this.inverteds2preNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 12, _this.exptId(), _this.exptTitle());
    }
  };

  this.is2postClass = ko.computed(function() {
    return _this.inverteds2preNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewiS2postMsg = ko.computed(function () {
    return 'view ' + _this.inverteds2preNo() + ' is2post';
  });
  this.processiS2post = function() {
    if (_this.inverteds2postNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 13, _this.exptId(), _this.exptTitle());
    }
  };

  this.s4preClass = ko.computed(function() {
    return _this.s4preNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewS4preMsg = ko.computed(function () {
    return 'view ' + _this.s4preNo() + ' s4pre';
  });
  this.processS4pre = function() {
    if (_this.s4preNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 10, _this.exptId(), _this.exptTitle());
    }
  };

  this.s4postClass = ko.computed(function() {
    return _this.s4postNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewS4postMsg = ko.computed(function () {
    return 'view ' + _this.s4postNo() + ' s4post';
  });
  this.processS4post = function() {
    if (_this.s4postNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 11, _this.exptId(), _this.exptTitle());
    }
  };

  ko.mapping.fromJS(data, target, this);
};

var is_experimentViewModel = function (data, target, parent) {
  var _this = this;

  this.s2preClass = ko.computed(function() {
    return _this.s2preNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewS2preMsg = ko.computed(function () {
    return 'view ' + _this.s2preNo() + ' s2pre';
  });
  this.processS2pre = function() {
    if (_this.s2preNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 6, _this.exptId(), _this.exptTitle());
    }
  };

  this.s2postClass = ko.computed(function() {
    return _this.s2postNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewS2postMsg = ko.computed(function () {
    return 'view ' + _this.s2postNo() + ' s2post';
  });
  this.processS2post = function() {
    if (_this.s2postNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 7, _this.exptId(), _this.exptTitle());
    }
  };

  this.is2preClass = ko.computed(function() {
    return _this.inverteds2preNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewiS2preMsg = ko.computed(function () {
    return 'view ' + _this.inverteds2preNo() + ' is2pre';
  });
  this.processiS2pre = function() {
    if (_this.inverteds2preNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 12, _this.exptId(), _this.exptTitle());
    }
  };

  this.is2postClass = ko.computed(function() {
    return _this.inverteds2preNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewiS2postMsg = ko.computed(function () {
    return 'view ' + _this.inverteds2preNo() + ' is2post';
  });
  this.processiS2post = function() {
    if (_this.inverteds2postNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 13, _this.exptId(), _this.exptTitle());
    }
  };

  this.s4preClass = ko.computed(function() {
    return _this.s4preNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewS4preMsg = ko.computed(function () {
    return 'view ' + _this.s4preNo() + ' s4pre';
  });
  this.processS4pre = function() {
    if (_this.s4preNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 10, _this.exptId(), _this.exptTitle());
    }
  };

  this.s4postClass = ko.computed(function() {
    return _this.s4postNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewS4postMsg = ko.computed(function () {
    return 'view ' + _this.s4postNo() + ' s4post';
  });
  this.processS4post = function() {
    if (_this.s4postNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 11, _this.exptId(), _this.exptTitle());
    }
  };
  
  ko.mapping.fromJS(data, target, this);
};

var ii_experimentViewModel = function (data, target, parent) {
  var _this = this;

  this.s2preClass = ko.computed(function() {
    return _this.s2preNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewS2preMsg = ko.computed(function () {
    return 'view ' + _this.s2preNo() + ' s2pre';
  });
  this.processS2pre = function() {
    if (_this.s2preNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 6, _this.exptId(), _this.exptTitle());
    }
  };

  this.s2postClass = ko.computed(function() {
    return _this.s2postNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewS2postMsg = ko.computed(function () {
    return 'view ' + _this.s2postNo() + ' s2post';
  });
  this.processS2post = function() {
    if (_this.s2postNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 7, _this.exptId(), _this.exptTitle());
    }
  };

  this.is2preClass = ko.computed(function() {
    return _this.inverteds2preNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewiS2preMsg = ko.computed(function () {
    return 'view ' + _this.inverteds2preNo() + ' is2pre';
  });
  this.processiS2pre = function() {
    if (_this.inverteds2preNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 12, _this.exptId(), _this.exptTitle());
    }
  };

  this.is2postClass = ko.computed(function() {
    return _this.inverteds2preNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewiS2postMsg = ko.computed(function () {
    return 'view ' + _this.inverteds2preNo() + ' is2post';
  });
  this.processiS2post = function() {
    if (_this.inverteds2postNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 13, _this.exptId(), _this.exptTitle());
    }
  };

  this.s4preClass = ko.computed(function() {
    return _this.s4preNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewS4preMsg = ko.computed(function () {
    return 'view ' + _this.s4preNo() + ' s4pre';
  });
  this.processS4pre = function() {
    if (_this.s4preNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 10, _this.exptId(), _this.exptTitle());
    }
  };

  this.s4postClass = ko.computed(function() {
    return _this.s4postNo() === 0 ? 'erButton greyed' : 'erButton';
  });
  this.viewS4postMsg = ko.computed(function () {
    return 'view ' + _this.s4postNo() + ' s4post';
  });
  this.processS4post = function() {
    if (_this.s4postNo() === 0) {
      return true;
    }
    else {
      processSurveyView(1, 11, _this.exptId(), _this.exptTitle());
    }
  };
  
  ko.mapping.fromJS(data, target, this);
};

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" data mappings">

var activeStandardMapping = {
  create: function (options) {
    return new asViewModel(options.data, as_experimentsMapping);
  }
};

var as_experimentsMapping = {
  'asExperiments' : {
    create: function (options) {
      return new as_experimentViewModel(options.data, {}, options.parent);
    }
  }
};

var activeInjectedMapping = {
  create: function (options) {
    return new aiViewModel(options.data, ai_experimentsMapping);
  }
};

var ai_experimentsMapping = {
  'aiExperiments' : {
    create: function (options) {
      return new ai_experimentViewModel(options.data, {}, options.parent);
    }
  }
};

var inactiveStandardMapping = {
  create: function (options) {
    return new isViewModel(options.data, is_experimentsMapping);
  }
};

var is_experimentsMapping = {
  'isExperiments' : {
    create: function (options) {
      return new is_experimentViewModel(options.data, {}, options.parent);
    }
  }
};

var inactiveInjectedMapping = {
  create: function (options) {
    return new iiViewModel(options.data, ii_experimentsMapping);
  }
};

var ii_experimentsMapping = {
  'iiExperiments' : {
    create: function (options) {
      return new ii_experimentViewModel(options.data, {}, options.parent);
    }
  }
};



// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" UI">

function setTabs() {
  $('.tab:first, .tabContent:first').addClass('active');
  $('.gameTabs, .adminTabs').on('click', '.tab', function (event) {
    if ($(this).hasClass('active')) {
    }
    else {
      $('.gameTabs .active, .adminTabs .active').removeClass('active');
      $(this).addClass('active');
      $(this).next().addClass('active');
    }
  });
}

// </editor-fold>
