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
  exptId = $('#hiddenExptId').text();
  jType = $('#hiddenJType').text();
  $('#name').html(fName);
  var paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['exptId'] = exptId;
  paramSet['jType'] = jType;
  $.ajax({
    type: 'GET',
    url: '/webServices/step4/getStep4RawTranscriptDetail.php',
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
  this.isVisible = ko.observable(false);
  this.toggleView = function () { _this.isVisible() ? _this.isVisible(false) : _this.isVisible(true) };
  ko.mapping.fromJS(data, target, this);
}
var transcriptsViewModel = function(data, target) {
  var _this = this;
  this.transcriptNo = ko.observable();
  this.actualJNo = ko.observable();
  this.respNo = ko.observable();
  this.s3respNo = ko.observable();
  this.s3rnLabel = ko.observable();
  this.rated = ko.observable();
  this.correct = ko.observable();
  this.confidence = ko.observable();
  this.reason = ko.observable();
  this.transcriptVisible = ko.observable(false);
  this.toggleTranscript = function() { _this.transcriptVisible() ? _this.transcriptVisible(false) : _this.transcriptVisible(true) };
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


