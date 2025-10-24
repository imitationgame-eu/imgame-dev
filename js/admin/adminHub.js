var hName;
var viewModel;

hName = window.location.hostname;


// <editor-fold defaultstate="collapsed" desc=" data, ajax and UI flow-control">

$(document).ready(function() {
  document.title = pageTitle;
  $('#mainHeader').text(pageTitle);
  messageType = 'adminHubItems';    
  content = 'controls and items for admin hub';
  sendAction(messageType, content);  
});

function uiUpdate(controlName, content) {
  // content can be single value or array
  paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['controlName'] = controlName;
  paramSet['content'] = content;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/uiUpdate.php',
    data: paramSet,
    dataType: 'text',
    error: function(xhr, textStatus, error) { uiDataError(xhr, error, textStatus, this.url); },
    success: function(data) { uiDataSuccess(data); }
  });      
}

function uiDataSuccess(data) {
  // not expecting data 
}

function uiDataError(xhr, error, textStatus, referer) {  
}

function sendAction(messageType, content) {
  // content can be single value or array
  paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['messageType'] = messageType;
  paramSet['content'] = content;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/experimentConfiguration.php',
    data: paramSet,
    dataType: 'json',
    error: function(xhr, textStatus, error) { getDataError(xhr, error, textStatus, this.url); },
    success: function(data) { getDataSuccess(data); }
  });      
}

function getDataSuccess(data) {
  viewModel = ko.mapping.fromJS(data, mainMapping);
  ko.applyBindings(viewModel);  
  // now update UI DOM with jQM
  $('#container').trigger('create');
  // set UI bindings after jQM decoration to avoid unwanted event firings when decorating the DOM
  setUIBindings();
}

function getDataError(xhr, error, textStatus, referer) {  
}

function setUIBindings() {
  $('.accordionControl').on('collapsibleexpand', function (event) {
    var id = $(this).attr('id');
    var msgContent = {};
    msgContent[0] = 0;
    uiUpdate(id, msgContent);
    event.stopPropagation();
  });
  $('.accordionControl').on('collapsiblecollapse', function (event) {
    var id = $(this).attr('id');
    var msgContent = {};
    msgContent[0] = 1;
    uiUpdate(id, msgContent);
    event.stopPropagation();
  });
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" viewModels">

var mainViewModel = function (data, target) {
  var _this = this;
  //-- Call mapping function --//
  ko.mapping.fromJS(data, target, this);
};

var tabViewModel = function (data, target, parent) {
  var _this = this;
  this.accordionCollapsed = ko.computed(function() {
    return _this.accordionClosed() == 1 ? "true" : "false";
  });
  ko.mapping.fromJS(data, target, this);
};

var categoryViewModel = function (data, target, parent) {
  var _this = this;
  this.accordionCollapsed = ko.computed(function() {
    return _this.accordionClosed() == 1 ? "true" : "false";
  });
  ko.mapping.fromJS(data, target, this);
};

var itemViewModel = function(data, target, parent) {
  var _this = this; 
  this.selectExperiment = function() {
    var paramItems = {};
    paramItems['process'] = 0;
    paramItems['pageLabel'] = '1_1_1';
    paramItems['uid'] = uid;
    paramItems['permissions'] = permissions;
    paramItems['fName'] = fName;
    paramItems['sName'] = sName;
    paramItems['referer'] = '1_0_1';
    paramItems['lastChild'] = 'unset';
    paramItems['exptId'] = _this.exptId();
    post_to_url('/index.php', paramItems);
  };
  this.selectSection = function() {
    var paramItems = {};
    paramItems['process'] = 0;
    paramItems['pageLabel'] = _this.pageLabel();
    paramItems['uid'] = uid;
    paramItems['permissions'] = permissions;
    paramItems['fName'] = fName;
    paramItems['sName'] = sName;
    paramItems['referer'] = '1_0_1';
    paramItems['lastChild'] = 'unset';
    post_to_url('/index.php', paramItems);
  };
  ko.mapping.fromJS(data, target, this);
};


// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" mappings">

var mainMapping = {
  create: function (options) {
    return new mainViewModel(options.data, tabMapping);
  }
};

var tabMapping = {
  'tabs': {
    create: function (options) {
      return new tabViewModel(options.data, categoryMapping, options.parent);
    }
  }
};

var categoryMapping = {
  'categories': {
    create: function (options) {
      return new categoryViewModel(options.data, itemMapping, options.parent);
    }
  }
};

var itemMapping = {
  'items': {
    create: function (options) {
      return new itemViewModel(options.data, {}, options.parent);
    }
  }
};

// </editor-fold>

