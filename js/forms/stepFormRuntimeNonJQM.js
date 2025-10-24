var uid;
var firstName;
var sName;
var permissions;
var currentExptName;
var exptId;
var formType;
var jType;
var paramSet = {};
var messageType;
var formName;
var restartUID;   // -1 = new start, >0 = appropriate UID to continue
var respId;     // some are set up to elicit a userCode from a Qualtric recruitment phase
var nextUrl;

// <editor-fold defaultstate="collapsed" desc=" communications and process functions">

function txtToXmlDoc(txt) {
  // check for spurious characters at beginning of message string
  if (txt.substring(0,1) != '<') {
    var i = txt.indexOf('<');
    var newTxt = txt.substring(i);
    txt = newTxt;
  }
  var xmlDoc;
  if (window.ActiveXObject) {
    xmlDoc=new ActiveXObject("Msxml2.DOMDocument.6.0");
    xmlDoc.loadXML(txt);   
  }
  else {
    parser=new DOMParser();
    xmlDoc=parser.parseFromString(txt,"text/xml");
  } 
  return xmlDoc;
}

function postIneligible(ineligibleMsg) {
  var paramItems = {};
  paramItems['process'] = 0;
  paramItems['action'] = '7_2_1';
  paramItems['ineligibleMsg'] = ineligibleMsg;    
  post_to_url('/index.php', paramItems);  
}

function processData(data) {
  var xmlDoc = txtToXmlDoc(data);
  var messageType=xmlDoc.getElementsByTagName("messageType")[0].firstChild.nodeValue;
  switch (messageType) {
    case 'step2Parameters' :
      restartUID = xmlDoc.getElementsByTagName("restartUID")[0].firstChild.nodeValue;
      userCode =  'na'; //xmlDoc.getElementsByTagName("userCode")[0].firstChild.nodeValue;
      jType = xmlDoc.getElementsByTagName("jType")[0].firstChild.nodeValue;
      var endPost = false;
      switch (formType) {
        case "2":
          nextUrl = "/s1_" + exptId + '_' + jType + '_' + restartUID + '_' + userCode;
        break;
        case "6":
          nextUrl = "/s2_" + exptId + '_' + jType + '_' + restartUID + '_' + userCode;
        break;
        case "12":  // inverted s2 (get NP answers rather than P answers)
          nextUrl = "/is2_" + exptId + '_' + jType + '_' + restartUID + '_' + userCode;
        break;
        case "3":
        case "7":
        case "11":
        case "13":
          endPost = true;
        break;      
      }
      if (endPost) {
        $('#finalButton').hide();
      }
      else {
        var paramItems = {};
        //alert(nextUrl);
        post_to_url(nextUrl, paramItems);
        //window.href=url;
      }
    break;
    case 'postStepDone' : // remain on final page until button clicked
    break;
  }
}

function jsonReplacer(key, value) {
  if (typeof value === 'string') {
    return JSON.stringify(value, null, 2);
  }
  return value;
}

function saveSurveyData() {
  var currentData = ko.mapping.toJS(viewModel);
  var jsonData = JSON.stringify(currentData, jsonReplacer , 2);
  var postRequest = $.ajax({
     url: "/webServices/admin/storeStepFormResponses.php",
     type: "POST",
     contentType:'application/json',
     data: jsonData,
     dataType: "text"
  });
  postRequest.done(function(data) {
    processData(data);
  });
  postRequest.fail(function(jqXHR, textStatus) {
    console.log("save data failed: "+textStatus);
  });  
}

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
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" global helpers">

function invalidEmail(sEmail) {
  var filter = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;
  if (filter.test(sEmail)) {
    return false;
  }
  else {
    return true;
  }
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" ko binding handlers (sliders etc)">

ko.bindingHandlers.slider = {
  init: function (element, valueAccessor, allBindingsAccessor) {
    var options = allBindingsAccessor().sliderOptions || {};
    $(element).slider(options);
    ko.utils.registerEventHandler(element, "slidechange", function (event, ui) {
        var observable = valueAccessor();
        observable(ui.value);
    });
    ko.utils.domNodeDisposal.addDisposeCallback(element, function () {
        $(element).slider("destroy");
    });
    ko.utils.registerEventHandler(element, "slide", function (event, ui) {
        var observable = valueAccessor();
        observable(ui.value);
    });
  },
  update: function (element, valueAccessor) {
    var value = ko.utils.unwrapObservable(valueAccessor());
    if (isNaN(value)) value = 0;
    $(element).slider("value", value);

  }
};

ko.bindingHandlers.jqCheckboxRadio = {
    init: function(element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
      var currentValue = valueAccessor();
		$(element).controlgroup(currentValue);
      $(element).attr("data-role", "controlgroup");
      $( "input[type='radio']",element).on( "checkboxradiocreate", function( event, ui ) {$(element).data( "init", true )} );
    },
    update: function(element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
      var currValue = allBindingsAccessor().value();
      $("input[type='radio']",element).prop( "checked", false ).checkboxradio( "refresh" );
      $("input[type='radio'][value='"+currValue+"']",element).prop( "checked", true ).checkboxradio( "refresh" );
//        var initialized = $(element).data( "init");
//        if(initialized){
//            $("input[type='radio']",element).prop( "checked", false ).checkboxradio( "refresh" );
//            $("input[type='radio'][value='"+currValue+"']",element).prop( "checked", true ).checkboxradio( "refresh" );
//        }
    }
};


// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" viewmodels">

var mainViewModel = function (data, target) {
  var _this = this;
  var _data = data;
  this.allSectionsFinished = ko.observable(false);
  // <editor-fold defaultstate="collapsed" desc=" special case of no eligibility and no recruitment (nornally post-Step forms)">  
  this.introPageDisplayed = ko.observable(true);
  this.isStandaloneIntroButton = ko.computed(function() {
    return (_this.useEligibilityQ() || _this.useRecruitmentCode() ) ? false : true;
  });
  this.setIntroPageDone = function() {
    _this.introPageDisplayed(false);
  };
  // </editor-fold>

  // <editor-fold defaultstate="collapsed" desc=" eligibility section">
  this.eligibilitySelection = ko.observable();
  this.eligibilitySelected = ko.computed(function() {
    return _this.eligibilityQAnswerText() > '' ? true : false; 
  });
  this.eligibilityConfirmed = ko.observable(false);
  this.eligibleResponse = ko.observable(false);
  this.processEligibility = function() {
    if (_this.eligibilitySelected()) {
      _this.eligibilityConfirmed(true);
      if (_this.useEligibilityQ() === true) {
        $(_data['eqOptions']).each( function(key, value) {
          if (value['label'] === _this.eligibilityQAnswerText()) {
            _this.eligibleResponse(value['isEligibleResponse']);
            _this.jType(value['jType']);
          }
        });
        if (_this.eligibleResponse() === false) {
          // repost to ineligible message page - avoids ppt reloading and retrying
          postIneligible(_this.nonEligibleMsg());
        }     
      }      
    }
  };
  this.isEligibilityVisible = ko.computed(function() {
    if (_this.useEligibilityQ() === true) {
      return !_this.eligibilityConfirmed();
    }
    else {
      return false;
    }
  });
  // </editor-fold>  
     
  // <editor-fold defaultstate="collapsed" desc=" recruitment code section">
  this.gotRecruitmentCode = ko.observable(false);
  this.hasChosenHaveCode = ko.observable(false);
  this.recruitmentOptionSelected = ko.observable(false);
  this.recruitmentCodeCanFinish = ko.observable(false);
  this.recruitmentCodeVisible = ko.computed(function() {
    if (_this.useEligibilityQ() === true) {
      if (_this.eligibilityConfirmed() === false) { return false; }
    }
    if (_this.useRecruitmentCode()) {
      return !_this.gotRecruitmentCode();
    }
    else {
      return false;
    }
  });
  this.hasCodeOptionResponse = function() {
    _this.hasChosenHaveCode(true);
    if (_this.recruitmentCodeText() > '') {
      _this.recruitmentCodeCanFinish(true);
    }
    else {
      _this.recruitmentCodeCanFinish(false);      
    }
    return true;
  };
  this.hasNoCodeOptionResponse = function() {
    _this.hasChosenHaveCode(false);
    _this.recruitmentCodeCanFinish(true);
    return true;
  };
  this.recruitmentCodeChanged = function() {
    if (_this.hasChosenHaveCode()) {
      if (_this.recruitmentCodeText() > '') {
        _this.recruitmentCodeCanFinish(true);
      }
      else {
        _this.recruitmentCodeCanFinish(false);      
      }      
    }
    else {
      _this.recruitmentCodeCanFinish(false);      
    }
  };
  this.setRecruitmentResponse = function() {
    if (_this.recruitmentCodeCanFinish()) {
      _this.gotRecruitmentCode(true);      
    }
  }
  this.recruitmentButtonClass = ko.computed(function() {
    return _this.recruitmentCodeCanFinish() === true ? "buttonBlue" : "buttonBlue greyed";    
  });  
  // </editor-fold>
     
  // <editor-fold defaultstate="collapsed" desc=" intro and sub-intro page visibility">

  this.isSubIntroPageVisible = ko.computed(function() {
    return _this.useIntroPage();
  });
  this.isIntroPageVisible = ko.computed(function() {
    if (_this.useEligibilityQ() === true || _this.useRecruitmentCode() === true) {
      var eDone = true;
      var rDone = true;
      if (_this.useEligibilityQ() === true) {
        eDone = _this.eligibilityConfirmed();
      }
      if (_this.useRecruitmentCode() === true) {
        rDone = _this.gotRecruitmentCode();
      }
      return (rDone === true && eDone === true) ? false : true;
    }
    else {
      return _this.introPageDisplayed();
    }
  });  
  
  // </editor-fold>
  
  // <editor-fold defaultstate="collapsed" desc=" final section">

  this.hasSubmitted = ko.observable(false);
  this.isFinalBVisible = ko.computed(function(){
    if (_this.formType() == 2 || _this.formType() == 6 || _this.formType() == 10 || _this.formType() == 12 ) {
      return true;
    }
    else {
      return false;
    }
  });
  this.isFinalPageVisible = ko.computed(function() {
    if (_this.isIntroPageVisible() === true) {return false;}
    if (_this.bypassPages() === true) {
      return true;
    }
    else {
      if (_this.allSectionsFinished()) {
        if (_this.formType() == 3 || _this.formType() == 7 || _this.formType() == 11 || _this.formType() == 13) {
          // post-form so submit asap (but only once) in case user doesn't press final button
          if (_this.hasSubmitted() === false) {
            exptId = _this.exptId();
            jType = _this.jType();
            saveSurveyData();
            _this.hasSubmitted(true);
          }
        }
        return true;
      }
      else {
        return false;
      }
    }
  });
  this.setFinalStatus = function() {
    if (_this.hasSubmitted() === false) {
      exptId = _this.exptId();
      jType = _this.jType();
      saveSurveyData(); // post responses to backend on button press  - should have happened anyway    
    }
  };
  
  // </editor-fold>
    
  ko.mapping.fromJS(data, target, this);
};

var eqOptionsViewModel = function (data, target, parent) {
  var _this = this;
  this.eqRBId = ko.computed(function() {
    return 'eqRB' + _this.optionNo();
  });
  this.eqRBName = ko.computed(function() {
    return 'eqRB';
  });
  this.currentPage = ko.observable(0);
  this.setEligibilityOption = function() {
    parent.eqOptionSelected(_this.label());
    parent.eligibilitySelection(_this.label());
    parent.eligibilitySelected(true);
    parent.eligibleResponse(_this.isEligibleResponse());
    parent.jType(_this.jType());
  };
  this.pageSectionVisible = ko.computed(function() {
    if (parent.bypassPages() === true) {
      return false;
    }
    else {
      if (parent.isIntroPageVisible() === true) { return false; }
      if (parent.isFinalPageVisible() === true) { return false; }
      return true;
    }
  });
  this.processSectionDone = function(sectionNo) {
    _this.eligibleSectionsFinished()[sectionNo] = true;
    var allDone = true;
    for (i=0; i<_this.eligibleSectionsCount(); i++) {
      if (_this.eligibleSectionsFinished()[i] == false) { allDone = false; }
    }
    if (allDone) { parent.allSectionsFinished(true); }
    var newCP = parseInt(_this.currentPage()) + 1;
    _this.currentPage(newCP);
  }
  ko.mapping.fromJS(data, target, this);
};

var eligibleSectionsViewModel = function(data, target, parent) {
  var _this = this;
  this.sectionFinished = ko.observable(false);
  this.filterQuestionAnswer = ko.observable();
  this.filterQuestionSelected = ko.observable(false); // selected means choice has been made
  this.filterQuestionAnswered = ko.observable(false); // Answered means choice has been committed
  this.filterButtonClass = ko.computed(function() {
    return _this.filterQuestionSelected() === true ? "buttonBlue" : "buttonBlue greyed";
  });
  this.setFilterResponse = function() {
    if (_this.filterQuestionSelected()) {
      _this.filterQuestionAnswered(true);      
    }
  };
  this.isFQRadiobutton = ko.computed(function() {
    return _this.fqType() === "radiobutton" ? true : false;
  });
  this.isFQSlider = ko.computed(function() {
    return _this.fqType() === "slider" ? true : false;
  });
  this.isFQSelect = ko.computed(function() {
    return _this.fqType() === "selector" ? true : false;
  });
  this.isFQCheckbox = ko.computed(function() {
    return _this.fqType() === "checkbox" ? true : false;
  });
  this.isFQContinuousSlider = ko.computed(function() {
    return _this.fqType() === "continuous slider" ? true : false;
  });
  this.fqOptionResponse = function(data, event) {
    _this.filterQuestionSelected(true);
    _this.filterQuestionAnswer(data.label());
    return true;
  };
  this.isSectionVisible = ko.computed( function() {
    return _this.pageNo() == parent.currentPage();  // NOTE comparing string and number, so === doesn't work
  });
  this.filterPageVisible = ko.computed( function() {
    if (_this.filterPage() === true) {
      return _this.filterQuestionAnswered() ? false : true;
    }
    else {
      return false;
    }
  });
  this.filterSectionVisible = ko.computed(function() {
    return !_this.filterPageVisible();
  });
  this.processSectionDone = function(pn) {
    parent.processSectionDone(pn);
    _this.sectionFinished(true);
  };
  ko.mapping.fromJS(data, target, this);
};

var filterOptionsViewModel = function(data, target, parent) {
  var _this = this;
  this.pageButtonLabel = ko.observable(parent.pageButtonLabel());
  this.pageNo = ko.observable(parent.pageNo());
  this.filteredSectionResponse = function() {
    if (_this.allQuestionsAnswered()) {
      parent.processSectionDone(_this.pageNo());
      return true;      
    }
  };
  this.allQuestionsAnswered = ko.observable(false);
  this.filteredSectionVisible = ko.computed( function() {
    if (parent.sectionFinished() === true) {
      return false;
    }
    else {
      if (parent.filterPage() === true) {
        if (parent.filterQuestionAnswered()) {
          // if there are no sub-questions for the filtered response, then move to next section
          if (_this.optionLabel() === parent.filterQuestionAnswer()) {
            // this option is eligible for display, but if no questions then move onto next section
            if (_this.questionCount() === '0') {
              _this.allQuestionsAnswered(true);
              parent.processSectionDone(_this.pageNo());
              return false;
            }
            else {
              return true;
            }
          }
        }
        else {
          return false;
        }
      }
      else {
        return true;
      }      
    }
  });
  this.filteredSectionButtonClass = ko.computed(function() {
    return _this.allQuestionsAnswered() === true ? "buttonBlue" : "buttonBlue greyed";
  });
  this.checkAllAnswered = function() {
    var tempAll = true;
    for (var i=0; i<_this.questionCount(); i++) {
      if (_this.isSliderArray[i] == 1) {
        if ( _this.sliderAnsweredArray[i] == 0) { tempAll = false; }                
      }
      else {
        if ( _this.questionAnsweredArray[i] == 0) { tempAll = false; }        
      }
    }
    _this.allQuestionsAnswered(tempAll);      
  };
  this.setSliderAnswered = function(qn) {
    _this.sliderAnsweredArray[qn] = 1;
    _this.checkAllAnswered();    
  };
  this.unsetSliderAnswered = function(qn) {
    _this.sliderAnsweredArray[qn] = 0;
    _this.checkAllAnswered();
  };
  this.setQuestionsAnswered = function(qn) {
    _this.questionAnsweredArray[qn] = 1;
    _this.checkAllAnswered();
  };
  this.unsetQuestionsAnswered = function(qn) {
    var qPtr;
    qPtr = qn;
    _this.questionAnsweredArray[qPtr] = 0;
    _this.allQuestionsAnswered(false);
  };
  this.questionAnsweredArray = [];
  this.addQuestionAnsweredArray = function(isQMandatory) {
    _this.questionAnsweredArray.push(isQMandatory ? 0 : 1);
  };
  this.sliderAnsweredArray = [];
  this.addSliderAnsweredArray = function(answered) {
    _this.sliderAnsweredArray.push(answered);
  };
  this.isSliderArray = [];
  this.addIsSliderArray = function(isSlider) {
    _this.isSliderArray.push(isSlider);
  };
  ko.mapping.fromJS(data, target, this);
};

var questionsViewModel = function(data, target, parent) {
  var _this = this;
  this.qSliderAnswerValue = ko.observable();
  this.qSliderAnswerValue.subscribe(function(newValue) {
    if (newValue == 0) {
      // this slider has been set back to start (null response) position
      parent.unsetSliderAnswered(_this.logicalQNo());      
    }
    else {
      parent.setSliderAnswered(_this.logicalQNo());
      _this.QAnswerValue(newValue);
    }
  });
  this.pageNo = ko.observable(parent.pageNo());
  this.isEvenQNo = ko.computed(function() { return _this.qNo() % 2 === '0' ? true : false; });
  this.isQGrid = ko.computed(function() {
    return _this.qType() === "radiobuttonGrid" ? true : false; 
  });
  this.isQSingle = ko.computed(function() {
    return _this.qType() === "single-line edit" ? true : false;
  });
  this.isQMulti = ko.computed(function() {
    return _this.qType() === "multi-line edit" ? true : false;
  });
  this.isQRadiobutton = ko.computed(function() {
    return _this.qType() === "radiobutton" ? true : false;
  });
  this.isQSlider = ko.computed(function() {
    if (_this.qType() === "slider") {
      parent.addIsSliderArray(1);
      parent.addSliderAnsweredArray(0);
      return true;
    }
    else {
      parent.addIsSliderArray(0);
      parent.addSliderAnsweredArray(0);      
      return false;
    }
  });
  this.isQEmail = ko.computed(function() {
    return _this.qType() === "email" ? true : false;
  });
  this.isQDatepicker = ko.computed(function() {
    return _this.qType() === "datetime" ? true : false;
  });
  this.isQSelect = ko.computed(function() {
    return _this.qType() === "selector" ? true : false;
  });
  this.isQCheckbox = ko.computed(function() {
    return _this.qType() === "checkbox" ? true : false;
  });
  this.isQContinuousSlider = ko.computed(function() {
    return _this.qType() === "continuous slider" ? true : false;
  });
  this.confidenceSliderID = ko.computed(function() {
    return 'confidenceSlider_' + _this.pageNo() + '_' + _this.qNo();
  });
  this.sliderID = ko.computed(function() {
    return 'slider_' + _this.pageNo() + '_' + _this.qNo();
  });
  this.qValidationMsgVisibleStatus = ko.observable(true);
  this.isQValidationMsgVisible = function() {
    return _this.qValidationMsgVisibleStatus();
  };
  this.checkEmail = ko.computed(function() {
    if (invalidEmail(_this.QAnswerText())) {
      _this.qValidationMsgVisibleStatus(true);      
    }
    else {
      _this.qValidationMsgVisibleStatus(false);            
    }
  });
  this.datePickerID = ko.computed(function() {return 'qdt' + parent.pageNo() + '_' + _this.qNo();});
  this.doDT = function() {
      var dpID = '#' + _this.datePickerID();
      $(dpID).datepicker();
  };
  this.selectID = ko.computed(function() {return 'qselect' + parent.pageNo() + '_' + _this.qNo();});
  // page validation/progress functions  
  this.QAnswerText = ko.observable('');
  this.textValidated = ko.computed(function() {
    if (_this.qMandatory() === true) {
      if (_this.QAnswerText().length > 1) {
        parent.setQuestionsAnswered(_this.logicalQNo());
      }
      else {
        parent.unsetQuestionsAnswered(_this.logicalQNo());
      }
    }
  });  
  this.bubbleSectionResponse = function() {
    parent.setQuestionsAnswered(_this.logicalQNo());
  };
  this.gridResponseValue = ko.observable(0);
  this.processGridResponse = function(newValue, oldValue) {
    var workingValue = parseInt(_this.gridResponseValue());
    workingValue = workingValue - parseInt(oldValue);
    workingValue = workingValue + parseInt(newValue);
    _this.gridResponseValue(workingValue);
    if (workingValue == _this.qGridTarget()) {
      parent.setQuestionsAnswered(_this.logicalQNo());      
    }
    else {
      parent.unsetQuestionsAnswered(_this.logicalQNo());            
    }
  };
  ko.mapping.fromJS(data, target, this);
};

var optionsViewModel = function(data, target, parent) {
  var _this = this;
  this.pageNo = ko.observable(parent.pageNo());
  this.qNo = ko.observable(parent.qNo());
  this.qcbID = ko.computed(function(){
    return 'qcb_'+parent.qNo()+'_'+_this.optionNo();
  });  
  this.getUniqueRBName = ko.computed(function(){
    return 'rb_'+parent.pageNo()+'_'+parent.qNo();
  });
  this.sliderIntervalID = ko.computed(function() {
    return 'interval_' + parent.pageNo() + '_' + parent.qNo() + '_' + _this.optionNo();
  });
  this.getSliderLabel = ko.computed(function() {
    return _this.id() === '0' ? '' : _this.label();
  });
  this.getLabelLeft = ko.computed(function() {
    var sp = parseInt(_this.getTickLeft());
    var hw = parseInt(_this.getWidth()) / 2;
    var diff = parseInt(sp - hw);
    return diff + '%';
  });
  this.getTickLeft = ko.computed(function() {
    if (_this.optionNo() === '0') { return '0%'; }
    var oNo = parseInt(_this.optionNo());
    var pc = parseInt(100/parent.optionCnt());
    return (oNo * pc) + '%';
  });
  this.getWidth = ko.computed(function() {
    return 100 / (parent.optionCnt() - 1) + '%';    
  });
  this.getIntervalClass = ko.computed(function() {
    return _this.optionNo() === '0' ? '' : 'interval';
  });
  this.optionResponse = function() {
    parent.bubbleSectionResponse();
    // parent.qSelectedOption(_this.optionSelected());
    return true;
  };
  this.prevGridResponse = ko.observable(0);
  this.bubbleGridResponse = function(rbValue) {
    parent.processGridResponse(rbValue, _this.prevGridResponse());
    _this.prevGridResponse(rbValue);
  };
  ko.mapping.fromJS(data, target, this);
};

var rowColumnsViewModel = function(data, target, parent) {
  var _this = this;
  this.getUniqueRBName = ko.computed(function(){
    return 'rb_'+parent.pageNo()+'_'+parent.qNo()+'_'+parent.label();
  }); 
  this.gridResponse = function() {
    parent.bubbleGridResponse(_this.colValue());
    return true;
  };
  ko.mapping.fromJS(data, target, this);  
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" mappings">

var mainMapping = {
  create: function (options) {
    return new mainViewModel(options.data, eqOptionsMapping);
  }
};

var eqOptionsMapping = {
  'eqOptions' : {
    create: function (options) {
      return new eqOptionsViewModel(options.data, eligibleSectionsMapping, options.parent);
    }
  }
};

var eligibleSectionsMapping = {
  'eligibleSections' : {
    create: function(options) {
      return new eligibleSectionsViewModel(options.data, filterOptionsMapping, options.parent);
    }
  }
};

var filterOptionsMapping = {
  'filterOptions' : {
    create: function(options) {
      return new filterOptionsViewModel(options.data, questionsMapping, options.parent);
    }
  }
};

var questionsMapping = {
  'questions' : {
    create: function (options) {
      return new questionsViewModel(options.data, optionsMapping, options.parent);
    }
  }
};

var optionsMapping = {
  'options' : {
    create: function (options) {
      return new optionsViewModel(options.data, rowColumnsMapping, options.parent);
    }
  }
};

var rowColumnsMapping = {
  'rowColumns' : {
    create: function(options) {
      return new rowColumnsViewModel(options.data, {}, options.parent);
    }
  }
}

var viewModel;
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" DOM document ready">

$(document).ready(function() {
  permissions = $('#hiddenPermissions').text();
  exptId = $('#hiddenExptId').text();
  jType = $('#hiddenJType').text();
  formType = $('#hiddenFormType').text();
  restartUID = $('#hiddenRestartUID').text();
  respId = $('#hiddenRespId').text();
  $('#name').html('anonymous');
  var paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['exptId'] = exptId;
  paramSet['jType'] = jType;
  paramSet['formType'] = formType;
  paramSet['restartUID'] = restartUID;
  paramSet['respId'] = respId;
  $.ajax({
    type: 'GET',
    url: '/webServices/forms/getStepFormRuntimeAsJSON.php',
    data: paramSet,
    dataType: 'json',
    error: function(xhr, textStatus, error) { getDataError(xhr, error, textStatus, this.url); },
    success: function(data) { getDataSuccess(data); }
  });   
});

function getDataSuccess(data) {
  viewModel = ko.mapping.fromJS(data, mainMapping);
  //console.log(viewModel);
  ko.applyBindings(viewModel);
  // now update UI DOM with jQM
  $('#container').trigger('create');
  // set UI bindings after jQM decoration to avoid unwanted event firings when decorating the DOM
  setUIBindings();
}

function getDataError(xhr, error, textStatus, url) {
  console.log('there was an error with the ajax request from ' + url + ' > ' + error + ' >> ' + textStatus + xhr);
}

function setUIBindings() {
//  $('#backB').click(function() {
//    goBack();
//  });
//  $('#submitB').click(function() {
//    var currentData = ko.mapping.toJS(mainViewModel);
//    var jsonData = JSON.stringify(currentData, null , 2);
//    var postRequest = $.ajax({
//       url: "/webServices/admin/storeExperimentSection.php",
//       type: "POST",
//       contentType:'application/json',
//       data: jsonData,
//       dataType: "text"
//    });
//    postRequest.done(function(msg) {
//      if (sectionNo == 1) { loadPage(pageLabel); } // dynamic page (days and sessions)
//      if (sectionNo == 4) { loadPage(pageLabel); } // dynamic page (# of q categories)
//    });
//    postRequest.fail(function(jqXHR, textStatus) {
//      //upDateError("failed: "+textStatus);
//    });
//  });  
}



// </editor-fold>

