var hName;
var viewModel;
var initialBinding = true;
var pageTitle = 'System configuration: topics';
var compValue;

hName = window.location.hostname;


// <editor-fold defaultstate="collapsed" desc=" data, ajax and UI flow-control">

$(document).ready(function() {
  document.title = pageTitle;
  $('#mainHeader').text(pageTitle);
  setPageFunctions(); // in js/mobile/systemPageControl - equivalent of JQM body calculations

  // get page data
  messageType = 'getTopics';
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
    url: '/webServices/admin/systemController.php',
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
    url: '/webServices/admin/systemController.php',
    data: paramSet,
    dataType: 'json',
    error: function(xhr, textStatus, error) { systemDataError(xhr, error, textStatus, this.url); },
    success: function(data) { systemDataSuccess(data); }
  });      
}

function systemDataSuccess(data) {
  if (data !== null) {
    console.log(data);
    switch (data.messageType) {
      case 'newTopic' :

        var ntVM = new locationViewModel(viewModel.canEdit(), data.payload);
        compValue = data.payload.label;
        var lessers = [];
        var greaters = [];
        for (var i=0; i<viewModel.topics().length; i++) {
          var p1 = viewModel.topics()[i].topicName();
          if (viewModel.topics()[i].topicName().localeCompare(compValue) === -1) {
            lessers.push(viewModel.topics()[i]);
          }
          else {
            greaters.push(viewModel.topics()[i]);
          }
        }
        viewModel.topics.removeAll();
        for (var j=0; j<lessers.length; j++) {
          viewModel.topics.push(lessers[j]);
        }
        viewModel.topics.push(ntVM);
        for (var k=0; k<greaters.length; k++) {
          viewModel.topics.push(greaters[k]);
        }
        viewModel.newTopic()[0].topicName('');
        break;
    }
  }
}

function systemDataError(xhr, error, textStatus, referer) {
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc = " blank section"



// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" viewModels">

var mainViewModel = function (data) {
  var _this = this;
  //-- Call mapping function --//
  this.newTopics = ko.observableArray(); // only 1, but foreach in js stops elements getting decorated by jQM
  this.newTopics.push(new newTopicViewModel(data.canEdit, ''));
  this.topics = ko.observableArray();
  this.canEdit = ko.observable(data.canEdit);

  for (var i=0; i<data.topics.length; i++) {
    var tVM = new topicViewModel(data.canEdit, data.topics[i]);
    this.topics.push(tVM);
  }
};

var topicViewModel = function(canEdit, data) {
  var _this = this;

  this.id = data.id;
  this.canEdit = canEdit;
  this.defaultText = data.label;
  this.topicName = ko.observable(data.label);
  this.acceptText = ko.observable('Accept changes');

  this.hasChanged = ko.computed(function() {
    return _this.canEdit ? _this.defaultText !== _this.topicName() : false;
  });

  this.accept = function() {
    var content = {};
    content.id = _this.id;
    content.label = _this.topicName();
    systemUpdate('updateTopic', content);
  }
};

var newTopicViewModel = function(canEdit, data) {
  var _this = this;

  this.canEdit = canEdit;
  this.defaultText = data;
  this.topicName = ko.observable(data);
  this.insertText = ko.observable('Add new topic');


  this.hasChanged = ko.computed(function() {
    return _this.canEdit ? _this.defaultText !== _this.topicName() : false;
  });


  this.insert = function() {
    systemUpdate('newTopic', _this.topicName());
  }
};




// </editor-fold>



