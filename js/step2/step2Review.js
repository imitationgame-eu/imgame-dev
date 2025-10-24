var uid;
var permissions;
var exptId;

//------------------------------------------------------------------------------
//  DOM ready
//------------------------------------------------------------------------------

function getData() {
  uid = $('#hiddenUID').text();
  permissions = $('#hiddenPermissions').text();
  exptId = $('#hiddenExptId').text();
  jType = $('#hiddenJType').text();
  var paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['exptId'] = exptId;
  paramSet['jType'] = jType;
  $.ajax({
    type: 'GET',
    url: '/webServices/step2/getStep2ReviewSets.php',
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
var datasetsViewModel = function (data, target) {
  var _this = this;
  this.dayNo = ko.observable();
  this.isVisible = ko.observable(true);
  this.toggleView = function () { _this.isVisible() ? _this.isVisible(false) : _this.isVisible(true) };
  this.sessionNo = ko.observable();
  this.jNo = ko.observable();
  this.dayNo = ko.observable();
  this.jType = ko.observable();
  this.actualJNo = ko.observable();
  ko.mapping.fromJS(data, target, this);
}
var pptsViewModel = function (data, target) {
  var _this = this;
  //-- Observables go here --//
  this.reviewed = ko.observable();
  this.newData = ko.observable();
  this.dataLabel = ko.computed( function() {
    var legend1 = _this.newData() === 1 ? "new data for review" : "already reviewed";
    var legend2 = _this.isVirtual() === 1 ? ": virtual - do not exclude" : "";
    return legend1+legend2;
  });
  this.isReviewed = ko.computed(function () {
      var rev = true;
      if (_this.newData() == 1) { rev = false; }

      // ko.utils.arrayForEach(_this.turns(), function (item) {
      //     if (item.newData() == 0) {
      //         rev = false;
      //     }
      // });
      _this.reviewed(rev);
      return rev;
  });
  this.isVisible = ko.observable(false);
  this.toggleView = function () { _this.isVisible() ? _this.isVisible(false) : _this.isVisible(true) };
  this.discardPpt = ko.observable(); 
  this.warning = ko.observable();
  this.finished = ko.observable();
  this.ignorePpt = ko.computed(function () {
    return (this.discardPpt == 1) ? "checked" : "";
  });
  this.jNoLabel = ko.observable();
  this.respNo = ko.observable();
  this.reviewedRespNo = ko.observable();
  this.wordCnt = ko.observable();
  //-- Call mapping function --//
  ko.mapping.fromJS(data, target, this);
}
var turnsViewModel = function (data, target, parent) {
    var _this = this;
//    //-- Observables go here --//
    this.editing = ko.observable(false);
    this.edit = function () { _this.editing(true); };
    this.editingR = ko.observable(false);
    this.editR = function () { _this.editingR(true); };
    this.useQ = ko.observable();
    this.useQSet = ko.computed(function () {
        if (_this.useQ() == 'use' || _this.useQ() == 'discard') {
            return true;
        }
        else {
            return false;
        }
    });
    //-- Call mapping function --//
    ko.mapping.fromJS(data, target, this);
};
// --------------------------------------------------- Mappings ------ //
var mainMapping = {
    create: function (options) {
        return new mainViewModel(options.data, datasetsMapping);
    }
};
var datasetsMapping = {
  'datasets' : {
    create: function (options) {
      return new datasetsViewModel(options.data, pptsMapping);
    }
  }
};
var pptsMapping = {
    'ppts': {
        create: function (options) {
            return new pptsViewModel(options.data, turnsMapping);
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
  setTabs();
  $('.waitingForAction').hide();
}

function getDataError(error, textStatus, url) {
  console.log('there was an error with the ajax request from ' + url + ' > ' + error + ' >> ' + textStatus);
}

function upDateSuccess(data) {
  $('.saveStatus').html('You have saved your changes. You can continue to mark/edit this session.');
  $('.saveStatus').fadeOut(4000);
}

function upDateError(data) {
    alert(data);
}

function saveData() {
  $('.saveStatus').html('Saving your changes......');
  $('.saveStatus').show();
  var currentData = ko.mapping.toJS(viewModel);
  var jsonData = JSON.stringify(currentData, null , 2);
  var postRequest = $.ajax({
    url: "/webServices/step2/storeStep2ReviewedData.php",
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
  $('.tab:first, .tabContent').addClass('active');
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