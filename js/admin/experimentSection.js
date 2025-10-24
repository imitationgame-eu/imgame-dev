//var mainViewModel;

// <editor-fold defaultstate="collapsed" desc=" data, ajax and UI flow-control">

$(document).ready(function() {
  sectionNo = $('#hiddenSectionNo').text();
  exptId = $('#hiddenExptId').text();
  document.title = pageTitle;
  $('#mainHeader').text(pageTitle);
  messageType = 'exptSection';   
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
    error: function(xhr, textStatus, error) {
      getDataError(xhr, error, textStatus, this.url);
      },
    success: function(data) {
      getDataSuccess(data);
    }
  });      
}

function getDataSuccess(data) {
  mainViewModel = ko.mapping.fromJS(data, mainMapping);
  ko.applyBindings(mainViewModel);  
  // now update UI DOM with jQM
  $('#container').trigger('create');
  // set UI bindings after jQM decoration to avoid unwanted event firings when decorating the DOM
  setUIBindings();
}

function getDataError(xhr, error, textStatus, referer) {  
}

function setUIBindings() {
  $('#submitB').click(function() {
    var currentData = ko.mapping.toJS(mainViewModel);
    var jsonData = JSON.stringify(currentData, null , 2);
    var postRequest = $.ajax({
       url: "/webServices/admin/storeExperimentSection.php",
       type: "POST",
       contentType:'application/json',
       data: jsonData,
       dataType: "text"
    });
    postRequest.done(function(msg) {
      var isDynamic = false;
      switch (sectionNo) {
        case '1': // days and sessions
        case '2': // interrogator labels
        case '3': // interrogator final labels
        case '4': // i alignment categories
        case '7': // s4 j alignment categories
        {
          isDynamic = true;
          break;
        }
        case '23':  // s3 shuffle - so show results
        {
          loadPage('1_3_4');
          break;
        }
        case '24':  // snow shuffle shuffle - so show results
        {
          loadPage('1_3_5');
          break;
        }
        case '35' : // linked-experiment shuffle  - so show results
        {
          loadPage('1_3_6');
          break;
        }
        case '37' : // tbt (linked-experiment) shuffle  - so show results
        {
          loadPage('1_3_7');
          break;
        }
      }
      if (isDynamic) {
        loadMultiSectionPage(pageLabel, sectionNo)      }
    });
    postRequest.fail(function(jqXHR, textStatus) {
      //upDateError("failed: "+textStatus);
    });
  });  
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" viewModels">

var mainViewModel = function (data, target) {
  var _this = this;
  ko.mapping.fromJS(data, target, this);
};

var formFieldViewModel = function (data, target, parent) {
  var _this = this;
  this.isFieldText = ko.computed(function() {
    return _this.controlType() === "text" ? true : false;
  });
  this.isFieldCheckbox = ko.computed(function() {
    return _this.controlType() === "checkbox" ? true : false;
  });
  this.isFieldSelect = ko.computed(function() {
    return _this.controlType() === "select" ? true : false;
  });
  this.isPageWarning = ko.computed(function() {
    return _this.controlType() === "pageWarning" ? true : false;
  });
  this.isPageMessage = ko.computed(function() {
    return _this.controlType() === "pageMessage" ? true : false;
  });
  this.isFieldButton = ko.computed(function() {
    return _this.controlType() === "button" ? true : false;
  });
  this.ftId = ko.computed(function() {
    return 'ft_'+_this.index();
  });
  this.cbId = ko.computed(function() {
    return 'cb_'+_this.index();
  });
  this.selectId = ko.computed(function() {
    return 'select_'+_this.index();
  });
  this.buttonId = ko.computed(function() {
    return 'button_'+_this.index();
  });
  this.fsClick = function() {
    if (_this.booleanValue() == true) {
      _this.booleanValue(false);
    }
    else {
      _this.booleanValue(true);      
    }
    return true;
  };
  this.doProcessButton = function() {
    loadPage(_this.buttonTarget());
  }
  ko.mapping.fromJS(data, target, this);
};

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" mappings">

var mainMapping = {
  create: function (options) {
    return new mainViewModel(options.data, fieldMapping);
  }
};

var fieldMapping = {
  'formFields': {
    create: function (options) {
      return new formFieldViewModel(options.data, {}, options.parent);
    }
  }  
};

// </editor-fold>

