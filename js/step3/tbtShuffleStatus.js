var mainViewModel;

// <editor-fold defaultstate="collapsed" desc=" data, ajax and UI flow-control">

$(document).ready(function() {
  exptId = $('#hiddenExptId').text();
  document.title = pageTitle;
  $('#mainHeader').text(pageTitle);
  messageType = 'tbtShuffleStatus';   
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
  console.log(data);
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

var judgesViewModel = function (data, target, parent) {
  var _this = this;
  ko.mapping.fromJS(data, target, this);
};

var exptIdsViewModel = function (data, target, parent) {
  var _this = this;
  this.formattedIgs = ko.computed(function() {
    var txt = _this.igs();
    var paras = txt.split('\n');
    var retStr = '';
    for (i=0; i<paras.length; i++) {
      retStr+= '<p>'+paras[i]+'</p>';
    }
    return retStr;
  });
  ko.mapping.fromJS(data, target, this);
};


// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" mappings">

var mainMapping = {
  create: function (options) {
    return new mainViewModel(options.data, judgesMapping);
  }
};

var judgesMapping = {
  's4judges': {
    create: function (options) {
      return new judgesViewModel(options.data, exptIdsMapping, options.parent);
    }
  }  
};

var exptIdsMapping = {
  'exptIds': {
    create: function (options) {
      return new exptIdsViewModel(options.data, {}, options.parent);
    }
  }  
};

// </editor-fold>
