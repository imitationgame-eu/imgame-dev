var uid;
var permissions;
var exptId;
var sessionNo;
var dayNo;
var jType;
var buttonAction;

//------------------------------------------------------------------------------
//  DOM ready
//------------------------------------------------------------------------------

function getData() {
  uid = $('#hiddenUID').text();
  permissions = $('#hiddenPermissions').text();
  buttonAction = $('#hiddenButtonAction').text();
  exptId = $('#hiddenExptId').text();
  sessionNo = $('#hiddenSessionNo').text();
  dayNo = $('#hiddenDayNo').text();
  jType = $('#hiddenJType').text();
  var paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['exptId'] = exptId;
  paramSet['sessionNo'] = sessionNo;
  paramSet['dayNo'] = dayNo;
  paramSet['buttonAction'] = buttonAction;
  paramSet['jType'] = jType;
  $.ajax({
    type: 'GET',
    url: '/webServices/step1/getStep1SessionReviewData.php',
    data: paramSet,
    dataType: 'json',
    error: function(xhr, textStatus, error) { getDataError(error, textStatus, this.url); },
    success: function(data) { getDataSuccess(data); }
  });   
}

$(document).ready(function() {
  $('.saveStatus').hide();
  getData(); 
});


// --------------------------------------------------- View models ------ //
var mainViewModel = function (data, target) {
  var _this = this;
  //-- Observables go here --//


  //-- Call mapping function --//
  ko.mapping.fromJS(data, target, this);
};
var daysViewModel = function (data, target) {
  var _this = this;
  //-- Observables go here --//
//  this.isReviewed = ko.computed(function () {
//      var rev = true;
//      ko.utils.arrayForEach(_this.sessions(), function (item) {
//          ko.utils.arrayForEach(item.judges(), function (i) {
//              if (i.reviewed() == false) {
//                  rev = false;
//              }
//          });
//      });
//      _this.summary.allReviewed(rev);
//      return rev;
//  });
  //-- Call mapping function --//
  ko.mapping.fromJS(data, target, this);
};
var sessionsViewModel = function (data, target) {
  var _this = this;
  //-- Observables go here --//

  //-- Call mapping function --//
  ko.mapping.fromJS(data, target, this);
};
var judgesViewModel = function (data, target) {
  var _this = this;
  //-- Observables go here --//
//  this.isReviewed = ko.computed(function () {
//    var rev = true;
//    ko.utils.arrayForEach(_this.questions(), function (item) {
//      if (item.useQSet() == false) { rev = false; }
//    });
//    // if (_this.discardJudge() == false) { rev = false ;}
//    _this.reviewed(rev);
//    return rev;
//  });
//  this.isVisible = ko.observable(false);
  this.igsnNo = ko.observable();
//  this.toggleView = function () { _this.isVisible() ? _this.isVisible(false) : _this.isVisible(true) };
//  this.discardJudge = ko.observable();    
  //-- Call mapping function --//
  ko.mapping.fromJS(data, target, this);
}
var questionsViewModel = function (data, target, parent) {
  var _this = this;
  //-- Observables go here --//
//  this.editing = ko.observable(false);
//  this.edit = function () { _this.editing(true); };
//  this.editingPR = ko.observable(false);
//  this.editPR = function () { _this.editingPR(true); };
//  this.editingNPR = ko.observable(false);
//  this.editNPR = function () { _this.editingNPR(true); };
//  this.useQ = ko.observable();
//  this.useQSet = ko.computed(function () {
//      if (_this.useQ() == 'use' || _this.useQ() == 'discard') {
//          return true;
//      }
//      else {
//          return false;
//      }
//  });
//  this.commenting = ko.observable(false);
//  this.comment = function () { _this.commenting(true); };
  //-- Call mapping function --//
  ko.mapping.fromJS(data, target, this);
};
// --------------------------------------------------- Mappings ------ //
var mainMapping = {
  create: function (options) {
    return new mainViewModel(options.data, daysMapping);
  }
};
var daysMapping = {
  'days': {
    create: function (options) {
      return new daysViewModel(options.data, sessionMapping);
    }
  }
};
var sessionMapping = {
  'sessions': {
    create: function (options) {
      return new sessionsViewModel(options.data, judgesMapping);
    }
  }
};
var judgesMapping = {
  'judges': {
    create: function (options) {
      return new judgesViewModel(options.data, questionsMapping);
    }
  }
};
var questionsMapping = {
  'questions': {
    create: function (options) {
      return new questionsViewModel(options.data, {}, options.parent);
    }
  }
};
var viewModel;
// --------------------------------------------------- Functions ------ //
function getDataSuccess(data) {
  //console.log('ajax data successfully returned ');
  viewModel = ko.mapping.fromJS(data, mainMapping);
  ko.applyBindings(viewModel);
  setTabs();
}

function getDataError(error, textStatus, url) {
  //console.log('there was an error with the ajax request from ' + url + ' > ' + textStatus + ' > ' + error);
}

function upDateSuccess(data) {
  $('.saveStatus').show().fadeOut(4000);
}

function upDateError(data) {
  alert(data);
}

function saveData() {
  var currentData = ko.mapping.toJS(viewModel);
  var jsonData = JSON.stringify(currentData, null , 2);
  var postRequest = $.ajax({
     url: "/webServices/step1/storeStep1ReviewData.php",
     type: "POST",
     contentType:'application/json',
     data: jsonData,
     dataType: "text"
  });
  postRequest.done(function(msg) {
    upDateSuccess(msg);
  });
  postRequest.fail(function(jqXHR, textStatus) {
    upDateError("failed: "+textStatus);
  });
}

function setTabs() {
  $('.tab:first, .tabContent:first').addClass('active');
  $('.gameTabs, .adminTabs').on('click', '.tab', function (event) {
    if ($(this).hasClass('active')) {
    }
    else {
      $('.gameTabs .active, .adminTabs .active').removeClass('active');
      $(this).addClass('active');
      $(this).next().addClass('active');
    }
  });
}

function printFriendly() {
  $('.headerDeep').hide();
  $('.tab').hide();
  $('.previousQuestions').show();
  $('.reviewed').hide();
  $('[input]').hide();
  $('h3').hide();
  $('label').hide();
  $('.buttonBlue').hide();
  $('.buttonPrint').hide();
  $('.questionUseDiscard').hide();
  $('.checkboxLabel').hide();
  $('.warning').hide();
  $('.igsnNo').show();
}

function revertNormal() {
  
}