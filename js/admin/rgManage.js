var hName;
var viewModel;
var initialBinding = true;

hName = window.location.hostname;


// <editor-fold defaultstate="collapsed" desc=" data, ajax and UI flow-control">

$(document).ready(function() {
  document.title = pageTitle;
  $('#mainHeader').text(pageTitle);
  setPageFunctions(); // in js/mobile/systemPageControl - equivalent of JQM body calculations

  // get page data
  messageType = 'rgExperimentGroups';
  content = '';
  sendAction(messageType, content);  
});

function sendAction(messageType, content) {
  // content can be single value or array
  paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['messageType'] = messageType;
  paramSet['content'] = content;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/rgController.php',
    data: paramSet,
    dataType: 'json',
    error: function(xhr, textStatus, error) { getDataError(xhr, error, textStatus, this.url); },
    success: function(data) { getDataSuccess(data); }
  });
}

function getDataSuccess(data) {
  if (!initialBinding)
    return; // used for update mapping after initial getData so no rebinding required
  console.log(data);
  viewModel = ko.mapping.fromJS(data, mainMapping);
  ko.applyBindings(viewModel);
  setUIBindings();
  initialBinding = false;
}

function getDataError(xhr, error, textStatus, referer) {
}

function setUIBindings() {
}

function systemUpdate(messageType, content) {
  // content can be single value or array
  paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['messageType'] = messageType;
  paramSet['content'] = content;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/rgController.php',
    data: paramSet,
    dataType: 'text',
    error: function(xhr, textStatus, error) { systemDataError(xhr, error, textStatus, this.url); },
    success: function(data) { systemDataSuccess(data); }
  });      
}

function systemDataSuccess(data) {
  // not expecting data 
}

function systemDataError(xhr, error, textStatus, referer) {
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc = " maintain observable arrays and update mappings depending on user actions"

function moveMapping(itemVM) {
  var tempPotential = [];
  var tempCurrent = [];

  var clength = viewModel.groups()[itemVM.groupId - 1].currentMappings().length;
  var plength = viewModel.groups()[itemVM.groupId - 1].potentialMappings().length;

  var insertFound = false;

  if (itemVM.isCurrent) {
    itemVM.isCurrent = false;
    viewModel.groups()[itemVM.groupId - 1].potentialMappings().forEach(function(item){
      if (itemVM.exptId() > item.exptId() && !insertFound) {
        tempPotential.push(itemVM);  // push itemVM into correct location in tempPotential
        insertFound = true;
      }
      tempPotential.push(item);
    });
    if (!insertFound)
      tempPotential.push(itemVM);  // push itemVM onto bottom of tempPotential

    viewModel.groups()[itemVM.groupId - 1].currentMappings().forEach(function(item){
      if (itemVM.exptId() !== item.exptId())
        tempCurrent.push(item);
    });

  }
  else {
    itemVM.isCurrent = true;
    viewModel.groups()[itemVM.groupId - 1].currentMappings().forEach(function(item){
      if (itemVM.exptId() > item.exptId() && !insertFound) {
        tempCurrent.push(itemVM);  // push itemVM into correct location in tempCurrent
        insertFound = true;
      }
      tempCurrent.push(item);
    });
    if (!insertFound)
      tempCurrent.push(itemVM);  // push itemVM onto bottom of tempCurrent

    viewModel.groups()[itemVM.groupId - 1].potentialMappings().forEach(function(item){
      if (itemVM.exptId() !== item.exptId())
        tempPotential.push(item);
    });

  }
  viewModel.groups()[itemVM.groupId - 1].potentialMappings.removeAll();
  viewModel.groups()[itemVM.groupId - 1].currentMappings.removeAll();

  sendAction('mappingUpdate', [itemVM.groupId, itemVM.exptId(), itemVM.isCurrent ? 1 : 0]);

  tempPotential.forEach(function(item) {
    viewModel.groups()[itemVM.groupId - 1].potentialMappings.push(item);
  });
  tempCurrent.forEach(function(item) {
    viewModel.groups()[itemVM.groupId - 1].currentMappings.push(item);
  });
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" viewModels">

var mainViewModel = function (data, target) {
  var _this = this;
  //-- Call mapping function --//
  this.newGroup = new newGroupViewModel('');
  ko.mapping.fromJS(data, target, this);
};

var newGroupViewModel = function (data) {
  var _this = this;
  this.groupname = ko.observable(data);
  this.originalText = data;
  this.hasChanged = ko.computed(function(){
    return _this.originalText === _this.groupname() ? false : true;
  });
  this.insertText = ko.computed(function() {
    return 'insert new group';
  });
  this.insert = function() {
    systemUpdate('groupNameInsert', [_this.groupname()]);
    reloadPage();
  };
};

var groupViewModel = function (data, target) {
  var _this = this;
  this.originalText = data.groupname;
  this.hasChanged = ko.computed(function(){
    return _this.originalText === _this.groupname() ? false : true;
  });
  this.acceptText = ko.computed(function() {
    return 'accept';
  });
  this.accept = function() {
    systemUpdate('groupNameUpdate', [_this.id(), _this.groupname()]);
    _this.originalText = _this.groupname();
  };
  ko.mapping.fromJS(data, target, this);
};


// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" mappings">

var mainMapping = {
  create: function (options) {
    return new mainViewModel(options.data, groupMapping);
  }
};

var groupMapping = {
  'groups': {
    create: function (options) {
      return new groupViewModel(options.data, {});
    }
  }
};


// </editor-fold>

