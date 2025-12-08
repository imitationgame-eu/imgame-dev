var uid;
var fName;
var sName;
var permissions;
var exptId;
var formType;
var paramSet = {};
var messageType;
var formName;
//var formData;
var pageLabel;
var initialBind = true;
var surveyVM = null;
var igControlTypes = [];
var igOptionControlTypes = [{id: -1, label:'choose control type'},{id: 0, label:'checkbox'},{id: 5, label:'radiobutton'},{id: 6, label: 'selector'}];
var igJTypeOptions = [];
var igEligibilityJTypeVM = ko.observableArray();

function sendAccordionUpdate(fieldName, value) {

  if (value === false || value ==='false' )
    value = 0;
  if (value === true || value ==='true' )
    value = 1;

  var paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['exptId'] = exptId;
  paramSet['formType'] = formType;
  paramSet['fieldName'] = fieldName;
  paramSet['status'] = value;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/uiFormDefinitionAccordionUpdate.php',
    data: paramSet,
    dataType: 'json',
    error: function(xhr, textStatus, error) { getDataError(xhr, error, textStatus, this.url); },
    success: function(data) { getUpdateSuccess(data); }
  });
}

//------------------------------------------------------------------------------
//  helpers
//------------------------------------------------------------------------------
$(document).ready(function() {
  uid = $('#hiddenUID').text();
  permissions = $('#hiddenPermissions').text();
  fName = $('#hiddenfName').text();
  sName= $('#hiddensName').text();
  formType= $('#hiddenFormType').text();
  exptId = $('#hiddenExptId').text();
  pageLabel = $('#hiddenPageLabel').text();

  // get a copy of the form as Json rather than iterate over UI to build data structure
  // this json is not used to build the form HTML - that is done in class.viewBuilder
  getFormAsJson();
});

// viewmodels for ko.js
var surveyViewModel = function(data) {
  var _this = this;

  //static members
  this.currentFocusControlId = data.currentFocusControlId;
  this.cntActivePages =[];
  for (var i=0;i<data.cntActivePages.length; i++) {
    _this.cntActivePages.push(data.cntActivePages[i]);
  }
  for (var i=0;i<data.judgeTypeOptions.length; i++) {
    igJTypeOptions.push(data.judgeTypeOptions[i].label);                 //({id: data.judgeTypeOptions[i].id, label: data.judgeTypeOptions[i].label});
  }
  for (var i=0;i<data.igControlTypes.length; i++) {
    igControlTypes.push({id: data.igControlTypes[i].id, label: data.igControlTypes[i].label});  // note: build global as used in many viewmodels
  }

  // observables
  this.exptId = ko.observable(data.exptId);
  this.formType = ko.observable(data.formType);
  this.formName = ko.observable(data.formName);
  this.formInst = ko.observable(data.formInst);
  this.formTitle = ko.observable(data.formTitle);

  this.finalPageAccordionClosed = ko.observable(data.finalPageAccordionClosed);
  this.finalButtonLabel = ko.observable(data.finalButtonLabel);
  this.finalMsg = ko.observable(data.finalMsg);

  this.pagesAccordionClosed = ko.observable(data.pagesAccordionClosed);
  this.pagesAccordionOperate = function() {
    _this.pagesAccordionClosed(!_this.pagesAccordionClosed());
    sendAccordionUpdate('pagesAccordionClosed', _this.pagesAccordionClosed());
  }
  this.useFinalPage = ko.observable(data.useFinalPage);

   // viewmodels
  this.dcVM = new formFieldViewModel({controlType: "checkbox", id: 'dcFS', class: 'classFS', legend: 'definition complete', value: data.definitionComplete});
  this.formTitleVM = new formFieldViewModel({controlType: "text", id: 'ftTA', class: 'classTA', legend: 'form title', value: data.formTitle});
  this.formInstructionVM = new formFieldViewModel({controlType: "text", id: 'fiTA', class: 'classTA', legend: 'form instruction', value: data.formInst});
  this.introPageVM = new introPageViewModel(data.introPage);

  this.pageVMs = ko.observableArray();
  for (var i=0; i<data.pages.length; i++)
  {
    _this.pageVMs().push( new pageViewModel(data.pages[i]));
  }

  // ko computeds
  this.getSurveyTitle = ko.computed(function() {
    return _this.exptId() + ' - ' + _this.formName();
  })

}

var introPageViewModel = function(data) {
  var _this = this;

  this.useEligibilityQ = ko.observable(data.useEligibilityQ);
  this.introPageAccordionClosed = ko.observable(data.introPageAccordionClosed);

  this.fieldVMs = ko.observableArray();
  _this.fieldVMs().push(new formFieldViewModel({controlType: "checkbox", id: 'uipFS', class: 'classFS', legend: 'use a start page', value: data.useIntroPage}));
  _this.fieldVMs().push(new formFieldViewModel({controlType: "text", id: 'iptTA', class: 'classTA', legend: 'page title', value: data.introPageTitle}));
  _this.fieldVMs().push(new formFieldViewModel({controlType: "text", id: 'ipmTA', class: 'classTA', legend: 'page message', value: data.introPageMessage}));
  _this.fieldVMs().push(new formFieldViewModel({controlType: "text", id: 'ipblTA', class: 'classTA', legend: 'page button label', value: data.introPageButtonLabel}));

  this.recruitmentSectionVM = new recruitmentViewModel(data.recruitmentSection);
  this.eligibilityQVM = new eligibilityQViewModel(data.eligibilityQ);

  this.accordionOperate = function() {
    _this.introPageAccordionClosed(!_this.introPageAccordionClosed());
    sendAccordionUpdate('introPageAccordionClosed', _this.introPageAccordionClosed());
  }
}

var recruitmentViewModel = function(data) {
  var _this = this;

  this.id = 'recruitmentSection';

  this.recruitmentSectionClosed = ko.observable(data.recruitmentSectionClosed);
  this.fieldVMs = ko.observableArray();
  _this.fieldVMs().push(new formFieldViewModel({controlType: "checkbox", id: 'urcFS', class: 'classFS', legend: 'use a recruitment code', value: data.useRecruitmentCode}));
  _this.fieldVMs().push(new formFieldViewModel({controlType: "text", id: 'rcTA', class: 'classTA', legend: 'recruitment question', value: data.recruitmentCodeMessage}));
  _this.fieldVMs().push(new formFieldViewModel({controlType: "text", id: 'ncTA', class: 'classTA', legend: 'no code label', value: data.recruitmentCodeNoLabel}));
  _this.fieldVMs().push(new formFieldViewModel({controlType: "text", id: 'hcTA', class: 'classTA', legend: 'has code label', value: data.recruitmentCodeYesLabel}));
  _this.fieldVMs().push(new formFieldViewModel({controlType: "text", id: 'rcbTA', class: 'classTA', legend: 'code box label', value: data.recruitmentCodeLabel}));

  this.accordionOperate = function() {
    _this.recruitmentSectionClosed(!_this.recruitmentSectionClosed());
    sendAccordionUpdate('recruitmentSectionClosed', _this.recruitmentSectionClosed());
  }

}

var eligibilityQViewModel = function(data) {
  var _this = this;
  this.id = 'eligibilityQ';

  // set global eligibility options for selects (e.g. contingent pages)
  var items = [];
  for (var i=0; i<data.optionsDef.options.length; i++)
    items.push(new eligibilityJTypeOptionViewModel(data.optionsDef.options[i]));
  igEligibilityJTypeVM(items);

  this.eligibilitySectionClosed = ko.observable(data.eligibilitySectionClosed);
  this.fieldVMs = ko.observableArray();
  _this.fieldVMs().push(new formFieldViewModel({controlType: "checkbox", id: 'ueFS', class: 'classFS', legend: 'use eligibility question', value: data.useEligibilityQ}));
  _this.fieldVMs().push(new formFieldViewModel({controlType: "text", id: 'TA_eq', class: 'classTA', legend: 'eligibility question', value: data.qLabel}));
  _this.fieldVMs().push(new formFieldViewModel({controlType: "text", id: 'TA_enem', class: 'classTA', legend: 'message for ineligible response', value: data.qNonEligibleMsg}));
  _this.fieldVMs().push(new formFieldViewModel({controlType: "select", id: 'eqType', class: 'classTA', legend: 'question type', value: data.qType, options: igOptionControlTypes}));
  _this.fieldVMs().push(new formFieldViewModel({controlType: "checkbox", id: 'jTypeSelectorFS', class: 'classFS', legend: 'use eligibility question as j-type selector', value: data.qUseJTypeSelector}));

  // special case that eligibility options are exclusive if used as jType selector
  data.optionsDef.qOptionsAreExclusive = data.qUseJTypeSelector;
  this.eqOptionsVM = new eqOptionsViewModel(data.optionsDef);

  this.accordionOperate = function() {
    _this.eligibilitySectionClosed(!_this.eligibilitySectionClosed());
    sendAccordionUpdate('eligibilitySectionClosed', _this.eligibilitySectionClosed());
  }

}

var eqOptionsViewModel = function(data) {
  var _this = this;

  this.eligibilityOptionsAccordionClosed = ko.observable(data.eligibilityOptionsAccordionClosed);
  this.fieldVMs = ko.observableArray();
  _this.fieldVMs().push(new formFieldViewModel({controlType: "checkbox", id: 'eq_me', class: 'classFS', legend: 'eligibility options are mutually exclusive', value: data.qOptionsAreExclusive}));
  this.questionVMs = ko.observableArray();
  for (var i=0;i<data.options.length; i++) {
    _this.questionVMs().push(new eqOptionViewModel(data.options[i]));
  }
  this.accordionOperate = function() {
    _this.eligibilityOptionsAccordionClosed(!_this.eligibilityOptionsAccordionClosed());
    sendAccordionUpdate('eligibilityOptionsAccordionClosed', _this.eligibilityOptionsAccordionClosed());
  }

}

var eqOptionViewModel = function(data) {
  var _this = this;

  this.id = ko.observable(data.id);
  this.fieldVMs = ko.observableArray();
  _this.fieldVMs().push(new formFieldViewModel({controlType: "text", id: 'TA_eq_option_'+data.id , class: 'classTA', legend: 'option response', value: data.label}));
  _this.fieldVMs().push(new formFieldViewModel({controlType: "select", id: 'eq_option'+data.id, class: 'classTA', legend: 'jType', value: data.jTypeLabel, options: igJTypeOptions}));

  this.jType = ko.observable(data.jType);
}

var pageViewModel = function(data) {
  var _this = this;

  this.pNo = ko.observable(data.pNo);
  this.pageAccordionClosed = ko.observable(data.pageAccordionClosed);
  this.accordionOperate = function() {
    _this.pageAccordionClosed(!_this.pageAccordionClosed());
    sendAccordionUpdate('pageAccordionClosed_' + _this.pNo(), _this.pageAccordionClosed());
  }

  this.fieldVMs = ko.observableArray();

  _this.fieldVMs().push(new formFieldViewModel({controlType: "checkbox", id: 'ipFS_'+data.pNo, class: 'classFS', legend: 'ignore page?', value: data.ignorePage}));
  _this.fieldVMs().push(new formFieldViewModel({controlType: "text", id: 'ptTA_'+data.pNo, class: 'classTA', legend: 'page title', value: data.pageTitle}));
  _this.fieldVMs().push(new formFieldViewModel({controlType: "text", id: 'piTA_'+data.pNo, class: 'classTA', legend: 'page instruction', value: data.pageInst}));
  _this.fieldVMs().push(new formFieldViewModel({controlType: "text", id: 'pblTA_'+data.pNo, class: 'classTA', legend: 'page button label', value: data.pageButtonLabel}));

  this.contingentVM = new contingentViewModel(data.pNo, data.contingentPage, data.contingentValue);
  this.jType = ko.observable(data.jType);

  this.questionFilterMandatoryVM = new questionFilterMandatoryViewModel(_this, data.questions[0]);

  this.questionVMs = ko.observableArray();
  for (var i=1;i<data.questions.length;i++) {
    _this.questionVMs().push(new questionViewModel(data.questions[i]));
  }

  this.getPageTitle = ko.computed(function() {
    return 'Page ' + _this.pNo();
  });
}

var contingentViewModel = function(pNo, contingentPage, contingentValue) {
  var _this = this;
  this.pNo = ko.observable(pNo);
  this.contingentPage = ko.observable(contingentPage);
  this.contingentValue = ko.observable(contingentValue);
  // this.options = ko.observableArray();
  // for (var i=0; i<igEligibilityDef.length; i++) {
  //   _this.options().push(new optionItem(igEligibilityDef[i]));
  // }

  this.contingentFSId = ko.computed(function() {
    return 'cpf_' + _this.pNo();
  });
  this.contingentValueId = ko.computed(function() {
    return 'cpv_' + _this.pNo();
  });
  this.contingentTextId = ko.computed(function() {
    return 'cpt_' + _this.pNo();
  });
  this.contingentSId = ko.computed(function() {
    return 'cps_' + _this.pNo();
  });
  this.getContingentText = ko.computed(function() {
    return igEligibilityJTypeVM()[_this.contingentValue()].label();
    // var comp = parseInt(_this.contingentValue());
    // for (var i=0; i<igEligibilityDef.length; i++) {
    //   if (i === comp)
    //     return igEligibilityDef[i].label;
    // }
    return 'not found';
   });

  this.fsClick = function() {
    _this.contingentPage(!_this.contingentPage());
  }

}

var questionViewModel = function(parent, data) {
  var _this = this;
  this.parent = parent;
  this.pNo = ko.observable(data.pNo);
  this.qNo = ko.observable(data.qNo);
  this.accordionClosed = ko.observable(data.accordionClosed);
  this.fieldVMs = ko.observableArray();
  _this.fieldVMs().push(new formFieldViewModel({controlType: "checkbox", id: 'q0f_'+data.pNo, class: 'classFS', legend: 'question 0 is a filter', value: data.q0isFilter}));

  this.qMandatory = ko.observable(data.qMandatory);
  this.qType = ko.observable(data.qType);
  this.qLabel = ko.observable(data.qLabel);
  this.qValidationMsg = ko.observable(data.qValidationMsg);
  this.qContingentValue = ko.observable(data.qContingentValue);
  this.accordionClosed = ko.observable(data.accordionClosed);
  this.defaultOptionVM = new optionViewModel({pNo: data.pNo, qNo: data.qNo, id: 0, label: data.options[0].label})
  this.optionVMs = ko.observableArray();
  for (var i=1; i<data.options.length; i++)
    _this.optionVMs().push(new optionViewModel({pNo: data.pNo, qNo: data.qNo, id: data.options[i].id, label: data.options[i].label}))

  this.getQuestionTitle = ko.computed(function() {
    return 'Question ' + _this.qNo();
  });
  this.mandatoryId = ko.computed(function() {
    return 'qm_' + _this.pNo() + '_' + _this.qNo();
  });
  this.vMsgId = ko.computed(function() {
    return 'qvm_' + _this.pNo() + '_' + _this.qNo();
  });
  this.qTypeSId = ko.computed(function() {
    return 'qt_' + _this.pNo() + '_' + _this.qNo();
  });
  this.qlId = ko.computed(function() {
    return 'ql_' + _this.pNo() + '_' + _this.qNo();
  });
  this.qValidationVisible = ko.computed(function() {
    return _this.qMandatory();
  });
  this.optionsVisible = ko.computed(function() {
    return (_this.qType() == '0' || _this.qType() == '5' || _this.qType() == '6');
  });
  this.fsMClick = function() {
    _this.qMandatory(!_this.qMandatory());
  };


  this.accordionOperate = function() {
    _this.accordionClosed(!_this.accordionClosed());
    sendAccordionUpdate('questionAccordionClosed_' + _this.pNo() + '_' + _this.qNo(), _this.accordionClosed());
  }
  this.addQ = function(eData, event) {
    var targetID = parseInt(_this.qNo());
    var qData = {
      accordionClosed: true,
      pNo: _this.pNo(),
      qFilterValue: "0",
      qLabel: "question label",
      qMandatory: false,
      qNo: targetID + 1,
      qType: "5",
      qValidationMsg: "validation message",
      options: [{id: "0", label: "first option"}]
    };
    var newArray = [];
    var newQuestionVM = new questionViewModel(_this.parent, qData);
    for (var i=0; i<targetID; i++)
      newArray.push(_this.parent.questionVMs()[i]);
    newArray.push(newQuestionVM);
    for (var i= targetID; i<_this.parent.questionVMs().length; i++) {
      _this.parent.questionVMs()[i].qNo(parseInt(_this.parent.questionVMs()[i].qNo()) + 1);
      newArray.push(_this.parent.questionVMs()[i]);
    }
    _this.parent.questionVMs(newArray);
    decorateUI();
    event.stopPropagation();
  }
  this.delQ = function(eData, event) {
    var targetID = parseInt(_this.qNo());
    var newArray = [];
    for (var i=0;i<targetID; i++)
      newArray.push(_this.parent.questionVMs()[i]);
    for (var i= targetID + 1; i<_this.parent.questionVMs().length; i++) {
      var oldID = parseInt(_this.parent.questionVMs()[i].id());
      var newID = oldID - 1;
      _this.parent.questionVMs()[i].id(newID);
      newArray.push(_this.parent.questionVMs()[i]);
    }
    _this.parent.questionVMs(newArray);
    decorateUI();
    event.stopPropagation();
  }

}

var questionFilterMandatoryViewModel = function(parent, data) {
  var _this = this;

  //this.isFinalQ = ko.observable(isFinalQ);
  this.parent = parent;
  this.pNo = ko.observable(data.pNo);
  this.qNo = ko.observable(data.qNo);
  this.qMandatory = ko.observable(data.qMandatory);
  this.qValidationMsg = ko.observable(data.qValidationMsg);
  this.qType = ko.observable(data.qType);
  this.qTypeFilter = ko.observable(data.qType).extend({monitorBackingValue: _this}); // qTypeFilter is constrained to select types that can be used with options
  this.qIsFilter = ko.observable(data.qIsFilter);
  this.qLabel = ko.observable(data.qLabel);
  this.optionVMs = ko.observableArray();
  this.accordionClosed = ko.observable(data.accordionClosed);
  this.accordionOperate = function() {
    _this.accordionClosed(!_this.accordionClosed());
    sendAccordionUpdate('questionAccordionClosed_' + _this.pNo() + '_' + _this.qNo(), _this.accordionClosed());
  }
  this.defaultOptionVM = new optionViewModel({pNo: data.pNo, qNo: data.qNo, id: 0, label: data.options[0].label})

  for (var i=1; i<data.options.length; i++) // add all options after first to array
    _this.optionVMs().push(new optionViewModel({pNo: data.pNo, qNo: data.qNo, id: data.options[i].id, label: data.options[i].label}))

  this.mandatoryId = ko.computed(function() {
    return 'qm_' + _this.pNo() + '_' + _this.qNo();
  });
  this.vMsgId = ko.computed(function() {
    return 'qvm_' + _this.pNo() + '_' + _this.qNo();
  });
  this.q0FilterId = ko.computed(function() {
    return 'qf_' + _this.pNo() + '_' + _this.qNo();
  });
  this.qTypeSId = ko.computed(function() {
    return 'qt_' + _this.pNo() + '_' + _this.qNo();
  });
  this.qTypelimitedSId = ko.computed(function() {
    return 'qlt_' + _this.pNo() + '_' + _this.qNo();
  });
  this.qlId = ko.computed(function() {
    return 'ql_' + _this.pNo() + '_' + _this.qNo();
  });
  this.isQ0 = ko.computed(function() {
    return _this.qNo() === '0';
  });
  this.selectWarningVisible = ko.computed(function() {
    return _this.qIsFilter();
  });
  this.selectVisible = ko.computed(function() {
    return !_this.qIsFilter();
  });
  this.qValidationVisible = ko.computed(function() {
    if (_this.qIsFilter())
      return true;
    if (_this.qMandatory())
      return true;
    return false;
  });
  this.optionsVisible = ko.computed(function() {
    return (_this.qType() == '0' || _this.qType() == '5' || _this.qType() == '6');
  });
  this.getQuestionTitle = ko.computed(function() {
    return 'Question ' + _this.qNo();
  });


  this.fsMClick = function() {
    _this.qMandatory(!_this.qMandatory());
  };
  this.fsFClick = function() {
    _this.qIsFilter(!_this.qIsFilter());
    if (_this.qIsFilter()){
      _this.qMandatory(true);   // a filter question must always be mandatory
    }
  };
  this.addQ = function(eData, event) {
    var targetID = parseInt(_this.qNo());
    var qData = {
      accordionClosed: true,
      pNo: _this.pNo(),
      qFilterValue: "0",
      qLabel: "question label",
      qMandatory: false,
      qNo: targetID + 1,
      qType: "5",
      qValidationMsg: "validation message",
      options: [{id: "0", label: "first option"}]
    };
    var newArray = [];
    var newQuestionVM = new questionViewModel(_this.parent, qData);
    for (var i=0; i<targetID; i++)
      newArray.push(_this.parent.questionVMs()[i]);
    newArray.push(newQuestionVM);
    for (var i=targetID; i<_this.parent.questionVMs().length; i++) {
      _this.parent.questionVMs()[i].qNo(parseInt(_this.parent.questionVMs()[i].qNo()) + 1);
      newArray.push(_this.parent.questionVMs()[i]);
    }
    _this.parent.questionVMs(newArray);
    decorateUI();
    event.stopPropagation();
  }
}

var optionViewModel = function(data) {
  var _this = this;
  this.id = ko.observable(data.id);
  this.label = ko.observable(data.label);
  this.pNo = ko.observable(data.pNo);
  this.qNo = ko.observable(data.qNo);

  this.qoId = ko.computed(function() {
    return 'qo_' + _this.pNo() + '_' + _this.qNo() + '_' + _this.id();
  });
  this.getOptionText = ko.computed(function() {
    return 'option ' + _this.id() + ' text';
  });
  this.addO = function() {
    var parentVM = null;
    var isQ0 = _this.qNo() === '0';
    if (isQ0)
      parentVM = surveyVM.pageVMs()[_this.pNo()].questionFilterMandatoryVM;
    else
      parentVM = surveyVM.pageVMs()[_this.pNo()].questionVMs[_this.qNo()];

    var maxID = parseInt(parentVM.optionVMs().length);
    var newID = parseInt(_this.id()); // first option is always the default option defined in defaultOptionVM rather than optionVMs
    var labelID = newID + 1;
    var newOptionVM = new optionViewModel({id: labelID , label: 'new option', pNo: _this.pNo(), qNo: _this.qNo() });  //, isFinal: maxID === newID
    // insert in correct location in option list
    var newArray = [];
    for (var i=0; i<newID; i++ ) {
      newArray.push(parentVM.optionVMs()[i]);
    }
    newArray.push(newOptionVM);
    for (var i=newID; i<maxID; i++) {
      parentVM.optionVMs()[i].id(parseInt(parentVM.optionVMs()[i].id()) + 1);
      newArray.push(parentVM.optionVMs()[i]);
    }
    parentVM.optionVMs(newArray);
    decorateUI();
  }
  this.delO = function() {
    var parentVM = null;
    var isQ0 = _this.qNo() === '0';
    if (isQ0)
      parentVM = surveyVM.pageVMs()[_this.pNo()].questionFilterMandatoryVM;
    else
      parentVM = surveyVM.pageVMs()[_this.pNo()].questionVMs[_this.qNo()];

    var maxID = parseInt(parentVM.optionVMs().length);
    var targetID = parseInt(_this.id() - 1); // first option is always the default option defined in defaultOptionVM rather than optionVMs

    var newArray = [];
    for (var i=0; i<targetID; i++)
      newArray.push(parentVM.optionVMs[i]);
    for (var i=targetID+1; i<maxID; i++) {
      var oldID = parseInt( parentVM.optionVMs()[i].id());
      var newID = oldID - 1;
      parentVM.optionVMs()[i].id(newID);
      //console.log(ko.toJSON(surveyVM.pageVMs()[0].questionFilterMandatoryVM, null ,2))
      newArray.push(parentVM.optionVMs()[i]);
    }
    parentVM.optionVMs(newArray);
    decorateUI();


  }

}

var formFieldViewModel = function (data) {
  var _this = this;

  this.controlType = ko.observable(data.controlType);
  this.controlId = ko.observable(data.id);
  this.legend = ko.observable(data.legend);
  this.booleanValue = ko.observable();
  this.textValue = ko.observable();
  this.selectedValue = ko.observable();
  this.options = ko.observableArray();

  switch(data.controlType) {
    case "text":
    _this.textValue(data.value);
    _this.textValue.subscribe(function(newValue) {
      var comps = _this.controlId().split('_');
      if (comps[1] === 'eq') {
        // need to propagate text to any contingent pages
        igEligibilityJTypeVM()[comps[3]].label(_this.textValue());
      }
    });
    break;
    case "pageWarning":
    case "pageMessage":
    _this.textValue(data.value);
    break;
    case "checkbox":
     _this.booleanValue(data.value);
    break;
    case "select":
    _this.selectedValue(data.value);
    _this.options(data.options);
    break;
  }

  this.isFieldText = ko.computed(function() {
    return _this.controlType() === "text" ? true : false;
  });
  this.isFieldCheckbox = ko.computed(function() {
    return _this.controlType() === "checkbox" ? true : false;
  });
  this.isFieldSelect = ko.computed(function() {
    return _this.controlType() === "select" ? true : false;
  });
  this.isFieldButton = ko.computed(function() {
    return _this.controlType() === "button" ? true : false;
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

  }
};

var eligibilityJTypeOptionViewModel = function(data) {
  //var _this = this;
  this.id = ko.observable(data.id);
  this.label = ko.observable(data.label);
  this.jType = ko.observable(data.jType);
  this.jTypeLabel = ko.observable(data.jTypeLabel);


}


// ---- custom bindings and extenders ---------------//
ko.bindingHandlers.jqmButtonEnable = {
  update: function(element, valueAccessor){
    //first call the real enable binding
    ko.bindingHandlers.enable.update(element, valueAccessor);

    //do our extra processing
    var value = ko.utils.unwrapObservable(valueAccessor());
    $(element).button(value ? "enable" : "disable");
  }
};

ko.extenders.monitorBackingValue = function(target, vm) {
  target.subscribe(function(newValue) {
    if (!initialBind) {
      vm.qType(newValue);
      $('#'+vm.qTypeSId()).selectmenu('refresh', true);
    }
  });
  return target;
}


// --------------------------------------------------- Functions ------ //


function getFormAsJson() {
  $('#name').html('anonymous');
  var paramSet = {};
  paramSet['userId'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['exptId'] = exptId;
  paramSet['formType'] = formType;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/getStepFormAsJSON.php',
    data: paramSet,
    dataType: 'json',
    error: function(xhr, textStatus, error) { getDataError(xhr, error, textStatus, this.url); },
    success: function(data) { getDataSuccess(data); }
  });
}

function getDataSuccess(data) {
  //formData = data; //JSON.parse(data);
  console.log(data);

  surveyVM = new surveyViewModel(data);
  ko.applyBindings(surveyVM);

  decorateUI();
  initialBind = false;  // stop flip switches triggering on initial binding
}

function getDataError(xhr, error, textStatus, url) {
  console.log('there was an error with the ajax request from ' + url + ' > ' + error + ' >> ' + textStatus);
}

function getUpdateSuccess(data) {

}

function decorateUI() {
  $('#container').trigger('create');

  // bloody stupid hack to set data-on data -on text settings (for some reason working in most other pages but not here !!!!
  $('.ui-flipswitch-on').text('yes');
  $('.ui-flipswitch-off').text('no');

}

