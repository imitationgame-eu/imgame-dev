var uid;
var permissions;
var fName;
var exptId;

//------------------------------------------------------------------------------
//  DOM ready
//------------------------------------------------------------------------------

function getData() {
  uid = $('#hiddenUID').text();
  permissions = $('#hiddenPermissions').text();
  fName = $('#hiddenfName').text();
  exptId = 328; //$('#hiddenExptId').text();
//  jType = $('#hiddenJType').text();
  $('#name').html(fName);
  var paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['exptId'] = exptId;
  $.ajax({
    type: 'GET',
    url: '/webServices/step4/getTBTMonitorDetail.php',
    data: paramSet,
    dataType: 'json',
    error: function(xhr, textStatus, error) { getDataError(error, textStatus, this.url); },
    success: function(data) { getDataSuccess(data); }
  });   
}

$(document).ready(function() {
  $('.saveStatus').hide();
  $('.waitingForAction').show();
  getData();
});

// --------------------------------------------------- View models ------ //
var mainViewModel = function (data, target) {
    var _this = this;
    //-- Observables go here --//
    //-- Call mapping function --//
    ko.mapping.fromJS(data, target, this);
};
var s4judgeViewModel = function (data, target) {
  var _this = this;
  this.getS4jNoLabel = ko.computed(function() {
    return _this.s4jNo() + 1;
  });
  this.isVisible = ko.observable(false);
  this.toggleView = function () { _this.isVisible() ? _this.isVisible(false) : _this.isVisible(true) };
  ko.mapping.fromJS(data, target, this);
}
var transcriptsViewModel = function(data, target) {
  var _this = this;
  this.colorCode = ko.computed(function() {
    return _this.confidence() == '-1' ? 'red' : 'black'; 
  });
  this.transcriptVisible = ko.observable(false);
  this.toggleTranscript = function() { _this.transcriptVisible() ? _this.transcriptVisible(false) : _this.transcriptVisible(true) };
  this.r1Header = ko.computed(function() {
    return (_this.pretenderRight() == 1) ? "NON-PRETENDER" : "PRETENDER";
  });
  this.r2Header = ko.computed(function() {
    return (_this.pretenderRight() == 1) ? "PRETENDER" : "NON-PRETENDER";
  });
  this.r1 = ko.computed(function() {
    return (_this.pretenderRight() == 1) ? _this.npr() : _this.pr();
  });
  this.r2 = ko.computed(function() {
    return (_this.pretenderRight() == 1) ? _this.pr() : _this.npr();
  });
  this.rpVisible = ko.computed(function() {
    return (_this.rated() == '1') ? true : false;
  });
  this.getReason = ko.computed(function() {
    return '<h4>Reason</h4><p>' + _this.reason() + '</p>';
  });
  this.getIntention = ko.computed(function() {
    return '<h4>Intention</h4><p>' + _this.intention() + '</p>';
  });
  this.getConfidence = ko.computed(function() {
    return '<p>confidence: ' + _this.confidence() + '</p>';
  });
  this.getCorrect = ko.computed(function() {
    return '<p>correct: ' + _this.correct() + '</p>';
  });

  ko.mapping.fromJS(data, target, this);
}
var turnsViewModel = function(data, target) {
  var _this = this;
  ko.mapping.fromJS(data, target, this);
}
// --------------------------------------------------- Mappings ------ //
var mainMapping = {
  create: function (options) {
    return new mainViewModel(options.data, s4judgesMapping);
  }
};
var s4judgesMapping = {
  's4judges' : {
    create: function (options) {
      return new s4judgeViewModel(options.data, transcriptsMapping);
    }
  }
};
var transcriptsMapping = {
  'transcripts': {
    create: function (options) {
      return new transcriptsViewModel(options.data, turnsMapping);
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
var viewModel;
// --------------------------------------------------- Functions ------ //
function getDataSuccess(data) {
  //console.log('json data successfully returned ');
  viewModel = ko.mapping.fromJS(data, mainMapping);
  //console.log(viewModel);
  ko.applyBindings(viewModel);
  $('.waitingForAction').hide();
  $('.tabContent').show();
  
}

function getDataError(error, textStatus, url) {
  console.log('there was an error with the ajax request from ' + url + ' > ' + error + ' >> ' + textStatus);
  $('.waitingForAction').hide();
}


