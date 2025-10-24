var uid;
var permissions;
var exptId;
var exptTitle;
var formType;
var exptType;
var surveysVM;

// <editor-fold defaultstate="collapsed" desc=" process dialogues">

function processSurveyView(exptType, surveyType, exptId, exptTitle) {
  $( "#dialog:ui-dialog" ).dialog("destroy");
  $('#legend').html(exptTitle);
  $( "#dialog-showSurvey" ).dialog({
      resizable: false,
      height:140,
      modal: true,
      buttons: {
        "View" : function() {
          var paramItems = {};
          paramItems['process'] = 0;
          paramItems['action'] = '7_2_3'; //
          paramItems['uid'] = uid;
          paramItems['permissions'] = permissions;
          paramItems['exptId'] = exptId;
          paramItems['surveyType'] = surveyType;
          paramItems['exptType'] = exptType;
          post_to_url('/webServices/forms/getSurveyDownload.php', paramItems);
          
          $(this).dialog("close");
        },
        "Download" : function() {
          var paramItems = {};
          paramItems['uid'] = uid;
          paramItems['permissions'] = permissions;
          paramItems['exptId'] = exptId;
          paramItems['surveyType'] = surveyType;
          paramItems['exptType'] = exptType;
          post_to_url('/webServices/forms/getSurveyDownload.php', paramItems);
          $(this).dialog("close");
        }
      }
  });
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" DOM ready and data-comms">

$(document).ready(function() {
  $('.saveStatus').hide();
  $('.waitingForAction').show();
  getData(); 
  setTabs();
  $('.waitingForAction').hide();
});

function post_to_url(path, params) {
  var method = "post"; // Set method to post by default
  var form = document.createElement("form");
  form.setAttribute("method", method);
  form.setAttribute("action", path);
  for (var key in params) {
    if(params.hasOwnProperty(key)) {
      var hiddenField = document.createElement("input");
      hiddenField.setAttribute("type", "hidden");
      hiddenField.setAttribute("name", key);
      hiddenField.setAttribute("value", params[key]);
      form.appendChild(hiddenField);
     }
  }
  document.body.appendChild(form);
  form.submit();
}

function getData() {
  uid = $('#hiddenUID').text();
  permissions = $('#hiddenPermissions').text();
  exptId = $('#hiddenExptId').text();
  exptType = $('#hiddenExptType').text();
  exptTitle = $('#hiddenExptTitle').text();
  formType = $('#hiddenFormType').text();
  var paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['exptId'] = exptId;
  paramSet['formType'] = formType;
  paramSet['getRTF'] = 0;
  $.ajax({
    type: 'GET',
    url: '/webServices/forms/getSurveyViewJSON.php',
    data: paramSet,
    dataType: 'json',
    error: function(xhr, textStatus, error) { getDataError(error, textStatus, this.url); },
    success: function(data) { processJSON(data); }
  });   
}

function processJSON(data) {
  surveysVM = new surveysViewModel(data)
  ko.applyBindings(surveysVM);
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


// function saveData() {
//   $('.saveStatus').html('Saving your changes......');
//   $('.saveStatus').show();
//   var currentData = ko.mapping.toJS(viewModel);
//   var jsonData = JSON.stringify(currentData, null , 2);
//   var postRequest = $.ajax({
//     url: "/webServices/step2/storeStep2ReviewedData.php",
//     type: "POST",
//     contentType:'application/json',
//     data: jsonData,
//     dataType: "text"
//   });
//   postRequest.done(function(msg) {
//       upDateSuccess(msg);
//   });
//   postRequest.fail(function(jqXHR, textStatus) {
//       upDateError("failed: "+textStatus);
//   });
// }

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" viewModels">

var surveysViewModel = function (data) {
  var _this = this;
  this.evenHeaderOpen = ko.observable(true);
  this.oddHeaderOpen = ko.observable(true);

  this.judgeTypes = data.judgeTypes;

  this.evenResponseViewModels = ko.observableArray();
  data.evenResponses.forEach(function(item) {
    var rVM = new respondentViewModel(item);
    _this.evenResponseViewModels.push(rVM);
  });

  this.oddResponseViewModels = ko.observableArray();
  data.oddResponses.forEach(function(item) {
    var rVM = new respondentViewModel(item);
    _this.oddResponseViewModels.push(rVM);
  });

  this.canDownloadRTF = ko.computed(function() {
    return ((_this.evenResponseViewModels().length > 0) || (_this.oddResponseViewModels().length > 0)) ? true : false;
  });

  this.evenHeaderText = ko.computed(function() {
    // note [0] is no contingency - not used here
    return _this.judgeTypes[1].label + ' respondents survey data';
  });
  this.oddHeaderText = ko.computed(function() {
    // note [0] is no contingency - not used here
    return _this.judgeTypes[2].label + ' respondents survey data';
  });

  this.evenHeaderClass = ko.computed(function(){
    return _this.evenHeaderOpen() === false ? "closed" : "open";
  });
  this.evenBodyVisible = ko.computed(function(){
    return _this.evenHeaderOpen();
  });
  this.toggleEvenHeader = function() {
    if (_this.evenHeaderOpen() === false) { _this.evenHeaderOpen(true); } else { _this.evenHeaderOpen(false); }
  };

  this.oddHeaderClass = ko.computed(function(){
    return _this.oddHeaderOpen() === false ? "closed" : "open";
  });
  this.oddBodyVisible = ko.computed(function(){
    return _this.oddHeaderOpen();
  });
  this.toggleOddHeader = function() {
    if (_this.oddHeaderOpen() === false) { _this.oddHeaderOpen(true); } else { _this.oddHeaderOpen(false); }
  };

  this.downloadRTF = function() {
    var paramSet = {};
    paramSet['uid'] = uid;
    paramSet['permissions'] = permissions;
    paramSet['exptId'] = exptId;
    paramSet['formType'] = formType;
    paramSet['getRTF'] = 1;
    post_to_url('/webServices/forms/getSurveyViewRTF.php', paramSet);
  };
};

var respondentViewModel = function(data) {
  var _this = this;
  this.chrono = ko.observable(data.chrono);
  this.restartUID = ko.observable(data.restartUID);
  this.pageViewModels = ko.observableArray();
  data.combinedPageResponses.forEach(function(item) {
    var pVM = new pageViewModel(item);
    _this.pageViewModels.push(pVM);
  });

  this.respondentText = ko.computed(function() {
    return 'ppt no:' + _this.restartUID() + ' time:' + _this.chrono();
  });

};

var pageViewModel = function(data) {
  var _this = this;
  this.pageNo = ko.observable(data.pageNo);
  this.isFilter = ko.observable(data.isFilter);
  this.filterQuestion = ko.observable(data.filterQuestion);
  this.filterSelection = ko.observable(data.filterSelection);
  this.filterResponseViewModels = ko.observableArray();
  data.filterResponses.forEach(function(item) {
    var rVM = new responseViewModel(item);
    _this.filterResponseViewModels.push(rVM);
  });
  this.nonfilterResponseViewModels = ko.observableArray();
  data.nonfilterResponses.forEach(function(item) {
    var rVM = new responseViewModel(item);
    _this.nonfilterResponseViewModels.push(rVM);
  });

  this.pageText = ko.computed(function() {
    return 'page ' + _this.pageNo();
  });
  this.filterQText = ko.computed(function() {
    return 'contingent selection --- ' + _this.filterQuestion() + ' ===> ' + _this.filterSelection();
  });

  this.filterSectionVisible = ko.computed(function() {
    return _this.isFilter() == 1 ? true : false;
  });
  this.nonfilterSectionVisible = ko.computed(function() {
    return _this.isFilter() == 0 ? true : false;
  });

};

var responseViewModel = function(data) {
  var _this = this;
  this.question = ko.observable(data.question);
  this.answer = ko.observable(data.answer);
  this.responseText = ko.computed(function() {
    return _this.question() + ' ===> ' + _this.answer();
  });
};

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" data mappings">

var surveysMapping = {
  create: function (options) {
    return new surveysViewModel(options.data, {});
  }
};

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" UI">

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

// </editor-fold>
