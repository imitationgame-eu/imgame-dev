var viewModel;

function getDataJSON() {
  $.post("/webServices/classic/classicRawData.php", { 
    permissions: 255
  },
  function(data) {
    processJSON(data);
  });                        
}


function processJSON(data) {
  $('#container').html(data);
//  viewModel = ko.mapping.fromJS(data, mainMapping);
//  ko.applyBindings(viewModel);  
//  // now update UI DOM with jQM
  $('#container').trigger('create');
  // set UI bindings after jQM decoration to avoid unwanted event firings when decorating the DOM
}

$(document).ready(function() {
  getDataJSON();
});

var mainViewModel = function (data, target) {
  var _this = this;
  ko.mapping.fromJS(data, target, this);
};

var experimentsViewModel = function(data, target) {
  var _this = this;
  ko.mapping.fromJS(data, target, this);
}

var daysViewModel = function (data, target) {
  var _this = this;
  ko.mapping.fromJS(data, target, this);
};

var sessionsViewModel = function (data, target) {
  var _this = this;
  ko.mapping.fromJS(data, target, this);
};

var groupsViewModel = function (data, target) {
  var _this = this;
  ko.mapping.fromJS(data, target, this);
}

var turnsViewModel = function (data, target, parent) {
  var _this = this;
  ko.mapping.fromJS(data, target, this);
};
// --------------------------------------------------- Mappings ------ //
var mainMapping = {
  create: function (options) {
    return new mainViewModel(options.data, experimentMapping);
  }
};

var experimentsMapping = {
  'experiments' : {
    create: function(options) {
      return new experimentViewModel(options.data, daysMapping)
    }
  }
}

var daysMapping = {
  'days': {
    create: function (options) {
      return new daysViewModel(options.data, sessionsMapping);
    }
  }
};

var sessionsMapping = {
  'sessions': {
    create: function (options) {
      return new sessionsViewModel(options.data, groupsMapping);
    }
  }
};

var groupsMapping = {
  'groups': {
    create: function (options) {
      return new groupsViewModel(options.data, turnsMapping);
    }
  }
};

var turnsMapping = {
  'turns': {
    create: function (options) {
      return new turnsViewModel(options.data, {}, options.parent);
    }
  }
};


