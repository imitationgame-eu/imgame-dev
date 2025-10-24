var viewModel;

// <editor-fold defaultstate="collapsed" desc=" data, ajax and UI flow-control">

$(document).ready(function() {
  exptId = $('#hiddenExptId').text();
  document.title = pageTitle;
  $('#mainHeader').text(pageTitle);
  messageType = 'exptControls';    
  content = 'top level experiment controls';
  sendAction(messageType, content);  
});

function sendAction(messageType, content) {
  // content can be single value or array
  paramSet = {};
  paramSet['uid'] = uid;
  paramSet['exptId'] = exptId;
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
//  console.log('got data' + data);
  viewModel = ko.mapping.fromJS(data, mainMapping);
  ko.applyBindings(viewModel);  
  // now update UI DOM with jQM
  $('#container').trigger('create');
  // set UI bindings
  setUIBindings();
}

function getDataError(xhr, error, textStatus, referer) {  
  console.log('houston' + textStatus);
}

function uiUpdateExperimentGroup(content) {
  paramSet = {};
  paramSet['uid'] = uid;
  paramSet['exptId'] = exptId;
  paramSet['permissions'] = permissions;
  paramSet['messageType'] = 'uiChange';
  paramSet['content'] = content;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/uiUpdateExperimentGroup.php',
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

function toggleValue(fieldName, currentValue) {
  paramSet = {};
  paramSet['uid'] = uid;
  paramSet['exptId'] = exptId;
  paramSet['permissions'] = permissions;
  paramSet['messageType'] = fieldName;
  content = {}
  content[0] = operationExperimentNo;
  content[1] = currentValue;
  paramSet['content'] = content;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/toggleValue.php',
    data: paramSet,
    dataType: 'text',
    success: function(data) { reloadPage(); }
  });        
}

function setUIBindings() {
  $('.functionGroupCollapsible').on('collapsibleexpand', function (event) {
    var id = $(this).attr('id');
    var msgContent = id + '_0';
    uiUpdateExperimentGroup(msgContent);
    event.stopPropagation();
  });
  $('.functionGroupCollapsible').on('collapsiblecollapse', function (event) {
    var id = $(this).attr('id');
    var msgContent = id + '_1';
    uiUpdateExperimentGroup(msgContent);
    event.stopPropagation();
  });
  $('#doDel').click(function(){
    $('#delExptPopup').popup("close");
    delExperiment();
  });
  $('#doDelCancel').click(function(){
    $('#delExptPopup').popup("close");
  });
  $('#delStatusOK').click(function(){
    $('#delExptStatusPopup').popup("close");
    reloadPage();
  });
  $('#doClone').click(function() {
    var nameLength = ($('#cloneExperimentName').val()).length;
    if (nameLength > 4) {
      $('#cloneExptPopup').popup("close");
      cloneExperiment($('#cloneExperimentName').val());
    }
  });
  $('#cancelClone').click(function() {
    $('#cloneExptPopup').popup("close");    
  });  
}

function cloneExperiment(newName) {
  paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['messageType'] = 'cloneExpt';
  var content = {};
  content[0] = operationExperimentNo;
  content[1] = newName;
  paramSet['content'] = content;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/experimentConfiguration.php',
    data: paramSet,
    dataType: 'json',
    success: function(data) { getCloneSuccess(data); }
  });        
}

function getCloneSuccess(data) {
  var statusMsg;
  var newExptId;
  $.each( data, function( key, val ) {
    switch (key) {
      case 'statusMsg':
        statusMsg = val;
      break;
      case 'newExptId':
        newExptId = val;
      break;
    }
  });
  if (statusMsg === 'duplicateName') {
    $('#cloneMsg').text('That name already exists!');
    $('#cloneExptPopup').popup("open");    
  }
  else {
    reloadPage();
  }
}

function delExperiment() { 
  paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['messageType'] = 'deleteExpt';
  paramSet['content'] = operationExperimentNo;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/experimentConfiguration.php',
    data: paramSet,
    dataType: 'json',
    success: function(data) { getDeleteSuccess(data); }
  });      
}

function getDeleteSuccess(data) {
  var deletedUserCount = -1;
  var archivedRowCount = -1;
  $.each( data, function( key, val ) {
    switch (key) {
      case 'deletedUserCount':
        deletedUserCount = val;
      break;
      case 'archivedRowCount':
        archivedRowCount = val;
      break;
    }
  });
  $('#deletedUser').text(deletedUserCount);
  $('#archivedRows').text(archivedRowCount);
  $('#delExptStatusPopup').popup("open");
}

function processInactive(inActive) {
  toggleValue('inActive', inActive);
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" viewModels">

var mainViewModel = function (data, target) {
  var _this = this;
  ko.mapping.fromJS(data, target, this);
};

var experimentViewModel = function (data, target, parent) {
  var _this = this;
  ko.mapping.fromJS(data, target, this);
  this.operationGroupID = ko.computed(function() {
    return _this.controlName();    
  });
  this.operationGroupCollapsed = ko.computed(function() {
    return _this.controlClosed() === 1 ? "true" : "false";
  });
  this.inactiveId = ko.computed(function(){
    return 'activeStatus';
  });
  this.doProcessActive = function() {
    operationExperimentNo = _this.exptId();
    processInactive(_this.isInactive());
  };
  this.getInactiveLabel = ko.computed(function() {
    return _this.isInactive() === 1 ? "Mark as active" : "Mark as inactive";
  });
  this.delExptId = ko.computed( function() {
    return 'delExpt';
  });
  this.doDelPopup = function() {
    operationExperimentNo = _this.exptId();
    $('#delExptPopup').popup("open");
  };
  this.cloneExptId = ko.computed( function() {
    return 'cloneExpt';
  });
  this.doClonePopup = function() {
    operationExperimentNo = _this.exptId();
    $('#cloneMsg').text('Clone experiment');
    $('#cloneExptPopup').popup("open");
  };
  this.cloneExptDataId = ko.computed(function() {
    return 'cloneExptData';
  });
  this.doCloneExptData = function() {
    // nothing yet - but might use clonePopup to get name and pass to SP
  };
  this.canControlActivate = ko.computed(function(){
    return _this.hasActivatePermissions();
  });
  this.canDelete = ko.computed(function(){
    return _this.hasDeletePermissions();
  });
  this.canCloneStructure = ko.computed(function(){
    return _this.hasClonePermissions();
  });
  this.canCloneAll = ko.computed(function(){
    return _this.hasCloneAllPermissions();
  });
};

var experimentControlHeaderViewModel = function (data, target, parent) {
  var _this = this;
  // repeated generic  header elements
  this.controlHeaderCollapsed = ko.computed(function() {
    return _this.headerClosed() == 1 ? "true" : "false";    
  });
  this.controlHeaderCollapsibleID = ko.computed(function() {
    return _this.headerName();    
  });
  ko.mapping.fromJS(data, target, this);
};

var experimentControlViewModel = function (data, target, parent) {
  var _this = this;
  this.experimentControlId = ko.computed(function(){
    return _this.controlName();
  });
  this.doIt = function() {
    operationExperimentNo = _this.exptId();
    if (_this.isSubsection() == 1) {
      loadMultiSectionPage(_this.pageLabel(), _this.sectionNo());
    }
    else {
      loadPage(_this.pageLabel());        
    }
  };
  ko.mapping.fromJS(data, target, this);
};

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" mappings">

var mainMapping = {
  create: function (options) {
    return new mainViewModel(options.data, experimentMapping);
  }
};

var experimentMapping = {
  'experiments': {
    create: function (options) {
      return new experimentViewModel(options.data, experimentControlHeaderMapping, options.parent);
    }
  }
};

var experimentControlHeaderMapping = {
  'experimentControlHeaders': {
    create: function (options) {
      return new experimentControlHeaderViewModel(options.data, experimentControlMapping, options.parent);
    }
  }
};


var experimentControlMapping = {
  'experimentControls': {
    create: function (options) {
      return new experimentControlViewModel(options.data, {}, options.parent);
    }
  }
};

// </editor-fold>

