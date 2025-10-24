var hName;
var viewModel;
var initialBinding = true;
var pageTitle = 'System configuration: locations';
var compValue;

hName = window.location.hostname;


// <editor-fold defaultstate="collapsed" desc=" data, ajax and UI flow-control">

$(document).ready(function() {
  document.title = pageTitle;
  $('#mainHeader').text(pageTitle);
  setPageFunctions(); // in js/mobile/systemPageControl - equivalent of JQM body calculations

  // get page data
  messageType = 'getLocations';
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
      case 'newLocation' :

        var nlVM = new locationViewModel(viewModel.canEdit(), data.payload);
        compValue = data.payload.label;
        var lessers = [];
        var greaters = [];
        for (var i=0; i<viewModel.locations().length; i++) {
          var p1 = viewModel.locations()[i].locationName();
          if (viewModel.locations()[i].locationName().localeCompare(compValue) === -1) {
            lessers.push(viewModel.locations()[i]);
          }
          else {
            greaters.push(viewModel.locations()[i]);
          }
        }
        viewModel.locations.removeAll();
        for (var j=0; j<lessers.length; j++) {
          viewModel.locations.push(lessers[j]);
        }
        viewModel.locations.push(nlVM);
        for (var k=0; k<greaters.length; k++) {
          viewModel.locations.push(greaters[k]);
        }
        viewModel.newLocations()[0].locationName('');
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
  this.newLocations = ko.observableArray(); // only 1, but foreach in js stops elements getting decorated by jQM
  this.newLocations.push(new newLocationViewModel(data.canEdit, ''));
  this.locations = ko.observableArray();
  this.canEdit = ko.observable(data.canEdit);

  for (var i=0; i<data.locations.length; i++) {
    var lVM = new locationViewModel(data.canEdit, data.locations[i]);
    this.locations.push(lVM);
  }
};

var locationViewModel = function(canEdit, data) {
  var _this = this;

  this.id = data.id;
  this.canEdit = canEdit;
  this.defaultText = data.label;
  this.locationName = ko.observable(data.label);
  this.acceptText = ko.observable('Accept changes');

  this.hasChanged = ko.computed(function() {
    return _this.canEdit ? _this.defaultText !== _this.locationName() : false;
  });

  this.accept = function() {
    var content = {};
    content.id = _this.id;
    content.label = _this.locationName();
    systemUpdate('updateLocation', content);
  }
};

var newLocationViewModel = function(canEdit, data) {
  var _this = this;

  this.canEdit = canEdit;
  this.defaultText = data;
  this.locationName = ko.observable(data);
  this.insertText = ko.observable('Add new location');


  this.hasChanged = ko.computed(function() {
    return _this.canEdit ? _this.defaultText !== _this.locationName() : false;
  });


  this.insert = function() {
    systemUpdate('newLocation', _this.locationName());
  }
};




// </editor-fold>



