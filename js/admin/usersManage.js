var hName;
var viewModel;
var initialBinding = true;

var currentUserPermissions;

hName = window.location.hostname;


// <editor-fold defaultstate="collapsed" desc=" data, ajax and UI flow-control">

$(document).ready(function() {
  document.title = pageTitle;
  $('#mainHeader').text(pageTitle);
  setPageFunctions(); // in js/mobile/systemPageControl - equivalent of JQM body calculations

  // get page data
  messageType = 'userGroupsPermissions';
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

  currentUserPermissions = parseInt(data.currentUser.permissions);
  viewModel = new mainViewModel(data);
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

function moveGroupMapping(itemVM) {
   if (itemVM.isMember) {
    viewModel.groupMembershipViewModels()[itemVM.groupIndex].members.remove(itemVM);
    itemVM.isMember = false;
    viewModel.groupMembershipViewModels()[itemVM.groupIndex].nonmembers.push(itemVM);
  }
  else {
    viewModel.groupMembershipViewModels()[itemVM.groupIndex].nonmembers.remove(itemVM)
    itemVM.isMember = true;
    viewModel.groupMembershipViewModels()[itemVM.groupIndex].members.push(itemVM);
  }

  sendAction('memberUpdate', [itemVM.groupId, itemVM.id, itemVM.isMember ? 1 : 0]);
}

function moveExptMapping(itemVM) {
  if (itemVM.isMember) {
    viewModel.exptViewModels()[itemVM.exptIndex].members.remove(itemVM);
    itemVM.isMember = false;
    viewModel.exptViewModels()[itemVM.exptIndex].nonmembers.push(itemVM);
  }
  else {
    viewModel.exptViewModels()[itemVM.exptIndex].nonmembers.remove(itemVM);
    itemVM.isMember = true;
    viewModel.exptViewModels()[itemVM.exptIndex].members.push(itemVM);
  }
  sendAction('exptUpdate',[itemVM.exptId, itemVM.id, itemVM.isMember ? 1 : 0])
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" viewModels">

var mainViewModel = function (data) {
  var _this = this;

  this.usersCollapsed = ko.observable(true);
  this.groupsCollapsed = ko.observable(true);
  this.exptsCollapsed = ko.observable(true);
  this.groupMembershipViewModels = ko.observableArray();
  this.userViewModels = ko.observableArray();
  this.exptViewModels = ko.observableArray();
  this.usersHeaderText = ko.computed(function() {
    return _this.usersCollapsed() === true ? 'User roles >>>' : '<<< User roles';
  });
  this.usersHeaderClass = ko.computed(function() {
    return _this.usersCollapsed() === true ? 'h2HeaderClosed' : 'h2HeaderOpen';
  });
  this.usersVisible = ko.computed(function() {
    return _this.usersCollapsed() === true ? false : true;
  });
  this.groupsHeaderText = ko.computed(function() {
    return _this.groupsCollapsed() ? 'Group memberships >>>' : '<<< Group memberships';
  });
  this.groupsHeaderClass = ko.computed(function(){
    return _this.groupsCollapsed() === true ? 'h2HeaderClosed' : 'h2HeaderOpen';
  });
  this.groupsVisible = ko.computed(function() {
    return _this.groupsCollapsed() === true ? false : true;
  });
  this.exptsHeaderText = ko.computed(function() {
    return _this.exptsCollapsed() === true ? 'Individual experiment permissions >>>' : '<<< Individual experiment permissions';
  });
  this.exptsHeaderClass = ko.computed(function() {
    return _this.exptsCollapsed() === true ? 'h2HeaderClosed' : 'h2HeaderOpen';
  });
  this.exptsVisible = ko.computed(function() {
    return _this.exptsCollapsed() === true ? false : true;
  });


  this.usersClick = function() {
    _this.usersCollapsed(!_this.usersCollapsed());
  };
  this.groupsClick = function() {
    _this.groupsCollapsed(!_this.groupsCollapsed());
  };
  this.exptsClick = function() {
    _this.exptsCollapsed(!_this.exptsCollapsed());
  };

  for (var i=0; i< data.groupsMembership.length; i++) {
    _this.groupMembershipViewModels().push(new groupMembershipViewModel(data.groupsMembership[i], i));
  }
  for (var j=0; j < data.users.length; j++) {
    _this.userViewModels().push(new userViewModel(data.users[j]));
  }

  for (var k= 0; k<data.exptUsers.length; k++) {
    _this.exptViewModels().push(new exptUsersViewModel(data.exptUsers[k], k));
  }
};

var exptUsersViewModel = function (data, index) {
  var _this = this;
  this.id = data.id;
  this.index = index;

  this.exptCollapsed = ko.observable(true);
  this.title = ko.observable(data.title);

  this.members = ko.observableArray();
  this.nonmembers = ko.observableArray();

  this.exptHeaderText = ko.computed(function(){
    return _this.exptCollapsed() === true ? _this.title() + ' >>>' : '<<< ' + _this.title();
  });

  this.exptHeaderClass = ko.computed(function() {
    return _this.exptCollapsed() === true ? 'itemHeaderClosed' : 'itemHeaderOpen';
  });

  this.exptVisible = ko.computed(function(){
    return _this.exptCollapsed() === true ? false : true;
  });

  this.exptClick = function() {
    _this.exptCollapsed(!_this.exptCollapsed());
  };

  for (var i=0; i<data.members.length; i++) {
    _this.members().push(new exptUserViewModel(data.members[i], i, true, index, data.id));
  }

  for (var j=0; j<data.nonmembers.length; j++) {
    _this.nonmembers().push(new exptUserViewModel(data.nonmembers[j], j, false, index, data.id));
  }
};

var exptUserViewModel = function(data, index, isMember, exptIndex, exptId) {
  var _this = this;
  this.index = index;
  this.isMember = isMember;
  this.id = data.id;
  this.exptId = exptId;
  this.exptIndex = exptIndex;

  this.email = ko.observable(data.email);

  this.moveRightText = ko.computed( function() {
    return _this.email() + " >>>";
  });
  this.moveLeftText = ko.computed( function() {
    return '<<< ' + _this.email();
  });

  this.changeMapping = function() {
    moveExptMapping(_this);
  };
};

var groupMembershipViewModel = function (data, index) {
  var _this = this;
  this.groupIndex = index;
  this.groupId = data.id;
  this.groupName = data.groupName;
  this.members = ko.observableArray();
  this.nonmembers = ko.observableArray();
  this.groupCollapsed = ko.observable(true);

  this.groupHeaderClass = ko.computed(function() {
    return _this.groupCollapsed() === true ? 'itemHeaderClosed' : 'itemHeaderOpen';
  });
  this.groupVisible = ko.computed(function() {
    return _this.groupCollapsed() === true ? false : true;
  });
  this.groupHeaderText = ko.computed(function() {
    return _this.groupCollapsed() === true ? _this.groupName + ' >>>' : '<<< ' + _this.groupName;
  });
  this.groupClick = function() {
    _this.groupCollapsed(!_this.groupCollapsed());
  };

  for (var i=0; i<data.members.length; i++) {
    _this.members.push(new groupUserViewModel(data.members[i], i, true, index, data.id));
  }
  for (var j=0; j<data.nonmembers.length; j++) {
    _this.nonmembers.push(new groupUserViewModel(data.nonmembers[j], j, false, index, data.id));
  }
};

var groupUserViewModel = function(data, index, isMember, groupIndex, groupId) {
  var _this = this;
  this.index = index;
  this.groupIndex = groupIndex;
  this.groupId = groupId;
  this.isMember = isMember;
  this.email = ko.observable(data.email);
  this.id = data.id;

  this.moveRightText = ko.computed( function() {
    return _this.email() + " >>>";
  });
  this.moveLeftText = ko.computed( function() {
    return '<<< ' + _this.email();
  });

  this.changeMapping = function() {
    moveGroupMapping(_this);
  };
};

var userViewModel = function (data) {
  var _this = this;
  this.id = ko.observable(data.id);
  this.email = ko.observable(data.email);
  this.permissions = data.permissions;
  this.isVisible = ko.computed(function() {
    return currentUserPermissions >= _this.permissions ? true : false;
  });

  this.ddOptions = ko.computed(function() {
    var options = [];
    if (currentUserPermissions >= 1024) options.push('Superuser');
    if (currentUserPermissions >= 512)  options.push('Experimenter');
    if (currentUserPermissions >= 256)  options.push('Local Organiser');
    if (currentUserPermissions >= 128)  options.push('Analyst');
    options.push('Undefined');
    return options;
  });

  this.ddValue = ko.computed(function() {
    switch (_this.permissions) {
      case '1024' :
        return 'Superuser';
      case '512' :
        return 'Experimenter';
      case '256' :
        return 'Local Organiser';
      case '128' :
        return 'Analyst';
    }
    return 'Undefined';
  });
};


// </editor-fold>


