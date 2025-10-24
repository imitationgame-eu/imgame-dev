var mainViewModel;

// <editor-fold defaultstate="collapsed" desc=" data, ajax and UI flow-control">

$(document).ready(function() {
  exptId = $('#hiddenExptId').text();
  document.title = pageTitle;
  $('#mainHeader').text(pageTitle);
  messageType = 'listStep1Users';   
  content = sectionNo;
  sendAction(messageType, content);  
});

function sendAction(messageType, content) {
  // content can be single value or array
  paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['messageType'] = messageType;
  paramSet['exptId'] = exptId;
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
  mainViewModel = ko.mapping.fromJS(data, mainMapping);
  ko.applyBindings(mainViewModel);  
  // set UI bindings
  setUIBindings();
  // now update UI DOM with jQM
  $('#container').trigger('create');
}

function getDataError(xhr, error, textStatus, referer) {  
}

function setUIBindings() {
  $('#refreshB').click(function() {
    refreshPage();
  });
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" viewModels">

var mainViewModel = function (data, target) {
  var _this = this;
  ko.mapping.fromJS(data, target, this);
};

var daysViewModel = function (data, target, parent) {
  var _this = this;
  ko.mapping.fromJS(data, target, this);
};

var sessionsViewModel = function (data, target, parent) {
  var _this = this;
  ko.mapping.fromJS(data, target, this);
};

var usersViewModel = function (data, target, parent) {
  var _this = this;
  var debug;
  this.nonClassic = ko.computed(function() {
    debug = _this.isClassic();
    return _this.isClassic() == "0" ? true : false;
  });
  this.Classic = ko.computed(function() {
    console.log(_this.isClassic());
    return _this.isClassic() == "1" ? true : false;
  });
  ko.mapping.fromJS(data, target, this);
};

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" mappings">

var mainMapping = {
  create: function (options) {
    return new mainViewModel(options.data, daysMapping);
  }
};

var daysMapping = {
  'days': {
    create: function (options) {
      return new daysViewModel(options.data, sessionsMapping, options.parent);
    }
  }  
};

var sessionsMapping = {
  'sessions': {
    create: function (options) {
      return new sessionsViewModel(options.data, usersMapping, options.parent);
    }
  }  
};

var usersMapping = {
  'users': {
    create: function (options) {
      return new usersViewModel(options.data, {}, options.parent);
    }
  }  
};


// </editor-fold>
