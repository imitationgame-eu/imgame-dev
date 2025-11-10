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
  this.collapsed = ko.observable(true);
  this.hasChanged = ko.computed(function(){
    return _this.originalText === _this.groupname() ? false : true;
  });
  this.acceptText = ko.computed(function() {
    return 'accept';
  });
  this.mappingsText = ko.computed(function() {
    return _this.collapsed() ? 'show mappings' : 'hide mappings';
  });
  this.toggleMappings = function() {
    _this.collapsed(!_this.collapsed());
  };
  this.accept = function() {
    systemUpdate('groupNameUpdate', [_this.id(), _this.groupname()]);
    _this.originalText = _this.groupname();
  };
  ko.mapping.fromJS(data, target, this);
  this.groupMembershipVM = new groupMembershipViewModel(_this.id(),data.groupmappings);
};

groupMembershipViewModel = function (groupId, data) {
  var _this = this;
  this.groupId = groupId;
  this.experimentVMs = ko.observableArray();
  this.nonexperimentVMs = ko.observableArray();
  for (var i=0; i<data.experiments.length; i++) {
    this.experimentVMs().push(new experimentViewModel(true, _this, data.experiments[i]));
  }
  for (var i=0; i<data.nonexperiments.length; i++) {
    this.nonexperimentVMs().push(new experimentViewModel(false, _this, data.nonexperiments[i]));
  }
  this.addMapping = function(exptVM) {
    if (!exptVM.isMapped) {
      exptVM.isMapped = true;
      var insertIndex = 0;
      var removeIndex = 0;
      var experimentVMs = _this.experimentVMs();
      var nonexperimentVMs = _this.nonexperimentVMs();

      for (var i=0; i<experimentVMs.length; i++) {
        if (experimentVMs[i].exptId() < exptVM.exptId())
          insertIndex = i;
      }
      for (var i=0; i<nonexperimentVMs.length; i++) {
        if (nonexperimentVMs[i].exptId() === exptVM.exptId())
          removeIndex = i;
      }
      if (insertIndex > -1) {
        var newArray = [];
        for (var i=0; i<= insertIndex; i++)
          newArray.push(experimentVMs[i]);
        newArray.push(exptVM);
        for (var i=insertIndex+1; i<experimentVMs.length; i++)
          newArray.push(experimentVMs[i]);
        _this.experimentVMs(newArray);
      }
      if (removeIndex > -1) {
        var newArray = [];
        for (var i=0; i<removeIndex; i++)
          newArray.push(nonexperimentVMs[i]);
        for (var i=removeIndex+1; i<nonexperimentVMs.length; i++)
          newArray.push(nonexperimentVMs[i]);
        _this.nonexperimentVMs(newArray);
      }
      // persist to db
      systemUpdate('mappingUpdate', [_this.groupId, exptVM.exptId(), 1]);

    }
  }
  this.removeMapping = function(exptVM) {
    if (exptVM.isMapped) {
      exptVM.isMapped = false;
      var insertIndex = 0;
      var removeIndex = 0;
      var experimentVMs = _this.experimentVMs();
      var nonexperimentVMs = _this.nonexperimentVMs();
      for (var i=0; i<nonexperimentVMs.length; i++) {
        var thisEI = exptVM.exptId();
        var compEI = nonexperimentVMs[i].exptId();
        if (exptVM.exptId() > nonexperimentVMs[i].exptId() )
          insertIndex = i+1;
      }
      for (var i=0; i<experimentVMs.length; i++) {
        if (experimentVMs[i].exptId() === exptVM.exptId())
          removeIndex = i;
      }
      if (insertIndex > -1) {
        var newArray = [];
        if (insertIndex > 0) {
          for (var i=0; i< insertIndex; i++)
            newArray.push(nonexperimentVMs[i]);
        }
        newArray.push(exptVM);
        for (var i=insertIndex; i<nonexperimentVMs.length; i++)
          newArray.push(nonexperimentVMs[i]);
        _this.nonexperimentVMs(newArray);
      }
      if (removeIndex > -1) {
        var newArray = [];
        for (var i=0; i<removeIndex; i++)
          newArray.push(experimentVMs[i]);
        for (var i=removeIndex+1; i<experimentVMs.length; i++)
          newArray.push(experimentVMs[i]);
        _this.experimentVMs(newArray);
      }
      // persist to db
      systemUpdate('mappingUpdate', [_this.groupId, exptVM.exptId(), 0]);
    }
  }
};

experimentViewModel = function(isMapped, parent, data) {
  var _this = this;
  this.isMapped = isMapped;
  this.parent = parent;
  this.exptId = ko.observable(data.exptId);
  this.exptTitle = ko.observable(data.title);
  this.getExperimentText = ko.computed( function() {
    return _this.exptId() + '. ' + _this.exptTitle();
  });
  this.addMapping = function() {
    _this.parent.addMapping(_this);
  };
  this.removeMapping = function() {
    _this.parent.removeMapping(_this);
  };
}


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

