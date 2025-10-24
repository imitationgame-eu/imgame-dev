var uid;
var firstName;
var sName;
var permissions;
var currentExptName;
var exptId;
var formType;
var paramSet = {};
var messageType;
var formName;
var viewModel;

// <editor-fold defaultstate="collapsed" desc=" viewmodels">

var mainViewModel = function (data, target) {
  var _this = this;
  this.tabIndex = ko.observable(0);
  // toggle and status functions
  this.introAccordionVisible = ko.computed(function() { return _this.introAccordion() === '1' ? true : false; });
  this.toggleIntroAccordion = function() { 
    if (_this.introAccordion() === '1' ) { _this.introAccordion('0'); } else { _this.introAccordion('1'); }
    saveDataAction();
  };
  this.finalAccordionVisible = ko.computed(function() { return _this.finalAccordion() === '1' ? true : false; });
  this.toggleFinalAccordion = function() { 
    if (_this.finalAccordion() === '1' ) { _this.finalAccordion('0'); } else { _this.finalAccordion('1'); }
    saveDataAction();
  };
  // ID and control functions
  this.getTabIndex = ko.computed(function() { _this.tabIndex(_this.tabIndex() + 1); return _this.tabIndex(); });
  this.formTitleID = ko.computed(function() { return _this.formName() + '_0_formTitle'; });
  this.formInstID = ko.computed(function() { return _this.formName() + '_0_formInst'; });
  this.introPageTitleID = ko.computed(function() { return _this.formName() + '_0_introPageTitle'; });
  this.introPageMessageID = ko.computed(function() { return _this.formName() + '_0_introPageMessage'; });
  this.introPageButtonLabelID = ko.computed(function() { return _this.formName() + '_0_introButtonLabel'; });
  this.useIntroPageID = ko.computed(function() { return _this.formName() + '_0_useIntroPage'; });
  this.finalMsgID = ko.computed(function() { return _this.formName() + '_0_finalMsg'; });
  this.finalButtonLabelID = ko.computed(function() { return _this.formName() + '_0_finalButtonLabel'; });
  this.useEligibilityQID = ko.computed(function() { return _this.formName() + '_0_useEligibilityQ'; });
  this.eligibilityQLabelID = ko.computed(function() { return _this.formName() + '_0_eligibilityQLabel'; });
  this.eligibilityQTypeID = ko.computed(function() { return _this.formName() + '_0_eligibilityQType'; });
  this.eligibilityQValidationMsgID = ko.computed(function() { return _this.formName() + '_0_eligibilityQValidationMsg'; });
  this.eligibilityQContinuousSliderMaxID = ko.computed(function() { return _this.formName() + '_0_eligibilityQContinuousSliderMax'; });
  this.eligibilityQContinuousSliderMaxVisible = ko.computed(function() { 
    if (_this.eligibilityQType() === 'continuous slider') { return true; } else {return false;}
  });
  ko.mapping.fromJS(data, target, this);
};

var pagesViewModel = function (data, target, parent) {
  var _this = this;
  this.isFirstPage = ko.computed( function() {
    return _this.pageNo() === '0' ? true : false;
  });
  this.parentFormName = ko.observable(parent.formName());
  this.pageAccordionVisible = ko.computed(function() { return _this.pageAccordion() === '1' ? true : false; });
  this.togglePageAccordion = function() {
    if (_this.pageAccordion() === '1') { _this.pageAccordion('0'); } else { _this.pageAccordion('1'); }
    saveDataAction();
  };
  this.pageTitleID = ko.computed(function() { return parent.formName() + '_' + _this.pageNo() + '_pageTitle'; });
  this.pageInstID = ko.computed(function() { return parent.formName() + '_' + _this.pageNo() + '_pageInst'; });
  this.pageButtonLabelID = ko.computed(function() { return parent.formName() + '_' + _this.pageNo() + '_pageButtonLabel'; }); 
  this.contingentPageID = ko.computed(function() { return parent.formName() + '_' + _this.pageNo() + '_contingentPage'; });
  this.clonePageID = ko.computed(function() { return parent.formName() + '_' + _this.pageNo() + '_clonePage'; });
  this.addPageID = ko.computed(function() { return parent.formName() + '_' + _this.pageNo() + '_addPage'; });
  this.delPageID = ko.computed(function() { return parent.formName() + '_' + _this.pageNo() + '_delPage'; });
  this.canDelPage = ko.computed(function() {
    if (_this.pageNo() === '0') {
      if (parseInt(parent.pageCount()) > 1) { return true; } else { return false; } 
    }
    else {
      return true;
    }
  });
  this.canShowContingent = ko.computed(function() {
    if (parent.useEligibilityQ()) {
      return _this.contingentPage();
    }
    else {
      return false;
    } 
  });
  this.canShowJType = ko.computed(function() {
    return !parent.useEligibilityQ();
  });
  this.canShowJAttach = ko.computed(function() {
    return _this.contingentPage();
  });
  ko.mapping.fromJS(data, target, this);
};

var questionsViewModel = function(data, target, parent) {
  var _this = this;
  this.parentFormName = ko.observable(parent.parentFormName());
  this.parentPageNo = ko.observable(parent.pageNo());
  this.parentQ0IsFilter = ko.observable(parent.q0isFilter());
  this.isEvenQNo = ko.computed(function() { return _this.qNo() % 2 === '0' ? true : false; });
  this.qID = ko.computed(function() { return parent.parentFormName() + '_' + parent.pageNo() + '_' + _this.qNo() + '_qID';});
  this.qTypeID = ko.computed(function() { return parent.parentFormName() + '_' + parent.pageNo() + '_' + _this.qNo() + '_qTypeID';});
  this.qLabelID = ko.computed(function() { return parent.parentFormName() + '_' + parent.pageNo() + '_' + _this.qNo() + '_qLabelID';});
  this.qValidationMsgID = ko.computed(function() { return parent.parentFormName() + '_' + parent.pageNo() + '_' + _this.qNo() + '_qValidationMsgID';});
  this.qContingentValueID = ko.computed(function() { return parent.parentFormName() + '_' + parent.pageNo() + '_' + _this.qNo() + '_qContingentValue';});
  this.delQuestionID = ko.computed(function() { return parent.parentFormName() + '_' + parent.pageNo() + '_' + _this.qNo() + '_delQ';});
  this.addQuestionID = ko.computed(function() { return parent.parentFormName() + '_' + parent.pageNo() + '_' + _this.qNo() + '_addQ';});
  this.cloneQuestionID = ko.computed(function() { return parent.parentFormName() + '_' + parent.pageNo() + '_' + _this.qNo() + '_cloneQ';});
  this.qContinuousSliderMaxID = ko.computed(function() { return parent.parentFormName() + '_' + parent.pageNo() + '_' + _this.qNo() + '_qContinuousSliderMax';});
  this.qContinuousSliderMaxVisible = ko.computed(function() { 
    if (_this.qType() === 'continuous slider') { return true; } else {return false;}
  });
  this.canDelQ = ko.computed(function() { 
    if (_this.qNo() === '0') {
      if (parseInt(parent.qCnt()) > 1) { return true; } else { return false; }
    }
    else {
      return true;
    }
  });
  this.canShowFilter = ko.computed(function() {
    return _this.qNo() === '0' ? true : false;
  });
  this.showFilterSelect = ko.computed( function() {
    return ((parent.q0isFilter()) && (_this.qNo() !== '0')) ? true : false;
  }); 
  this.canShowNonMandatory = ko.computed(function() {
    return _this.qNo() === '0' ? false : true;    
  });
  this.needsContingentMsg = ko.computed(function() {
    if (parent.contingentPage() && _this.qNo()==='0') {
      if ( (_this.qType() === 'selector') || (_this.qType() === 'radiobutton') || (_this.qType() === 'slider') ) { 
        return false; 
      } 
      else { 
        return true;
      } 
    }
    else {
      return false;
    }
  });
  this.needsContingentValue = ko.computed(function() {
    if (parent.contingentPage() && _this.qNo() !== '0') {
      return true;
    }
    else {
      return false;
    }
  });
  this.multiOptions = ko.computed(function() {
    if ( (_this.qType() === 'selector') || (_this.qType() === 'radiobutton') || (_this.qType() === 'slider') || (_this.qType() === 'checkbox') ) { return true; } else { return false;} 
  });
  this.qAccordionVisible = ko.computed(function() { return _this.qAccordion()==='0' ? false : true;});
  this.isSlider = ko.computed(function() { return _this.qType === 'radiobuttonGrid' ? true : false;});
  this.qGrid = ko.computed(function() { return _this.qType() === 'radiobuttonGrid' ? true : false;});
  this.qGridInstructionID = ko.computed(function(){ return parent.parentFormName() + '_' + parent.pageNo() + '_' + _this.qNo() + '_gridInstruction'; });
  this.changeValue = function() { saveDataAction(); };
  this.toggleQAccordion = function() { 
    if (_this.qAccordion() === '1') { _this.qAccordion('0'); } else { _this.qAccordion('1'); } 
    saveDataAction();
  };
  ko.mapping.fromJS(data, target, this);
};

var optionsViewModel = function(data, target, parent) {
  var _this = this;
  this.optionID = ko.computed(function() {return parent.parentFormName() + '_' + parent.parentPageNo() + '_' + parent.qNo() + '_' + _this.optionNo();});
  this.delOptionID = ko.computed(function() {return 'del_option_' + parent.parentPageNo() + '_' + parent.qNo() + '_' + _this.optionNo();});
  this.addOptionID = ko.computed(function() {return 'add_option_' + parent.parentPageNo() + '_' + parent.qNo() + '_' + _this.optionNo();});
  this.delOptionVisible = ko.computed(function() { return _this.optionNo() === '0' ? false : true;});
  this.changeOptionValue = function() {
    if (parent.parentQ0IsFilter()) {
      if (parent.qNo() === '0') {
        viewModel.currentFocusControlId(_this.optionID());      
        saveDataAction();
        reloadForm();
      }
      else {
        saveDataAction();        
      }
    }
  };
  ko.mapping.fromJS(data, target, this);
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" mappings">

var mainMapping = {
  create: function (options) {
    return new mainViewModel(options.data, pagesMapping);
  }
};

var pagesMapping = {
  'pages' : {
    create: function (options) {
      return new pagesViewModel(options.data, questionsMapping, options.parent);
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
      return new optionsViewModel(options.data, {}, options.parent);
    }
  }
};

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" UI and controls - gradually move into viewmodel bindings">

function setControls() {
  if (viewModel.currentFocusControlId() != 'unset') {
    var jqId = '#'+viewModel.currentFocusControlId();
    $(jqId).focus();    
  }
  $('.checkboxButton').focusout(function() {
    saveDataAction();    
  });
  $('.select').focusout(function() {
    saveDataAction();    
  });
  $('.text').focusout(function() {
    viewModel.currentFocusControlId($(this).attr('id'));
    saveDataAction();
  }); 
  $('.text').focusin(function() {
    viewModel.currentFocusControlId($(this).attr('id'));
  }); 
  $('.eligibilityOptionText').focusout(function() {
    // find id of next input and make as active control for reload, otherwise
    // results in UI loop
    viewModel.currentFocusControlId("step1PreFormOdd_0_contingentPage");
    saveDataAction();
    reloadForm();
  }); 
  $('.eligibilityOptionText').focusin(function() {
    viewModel.currentFocusControlId($(this).attr('id'));
  }); 
  $('.addOption').on("click", function() {    
    var aID=$(this).attr('id');
    var aDetails=aID.split('_');
    var optionType = aDetails[1];
    var pageNo = aDetails[2]; // X for eligibilty special options
    var qNo = aDetails[3];    // X for eligibilty special options
    var optionNo = aDetails[4];
    var newOptionNo = parseInt(optionNo);
    ++newOptionNo;
    var newControlId = formName+'_'+aDetails[2]+'_'+aDetails[3]+'_'+newOptionNo;
    viewModel.currentFocusControlId(newControlId);
    messageType = 'addOption';
    var contentArray = {};
    contentArray[0] = optionType;
    contentArray[1] = pageNo; 
    contentArray[2] = qNo;
    contentArray[3] = optionNo;
    content = contentArray;
    sendStructureAction(messageType, content);      
  });
  $('.delOption').on("click", function() {    
    var aID=$(this).attr('id');
    var aDetails=aID.split('_');
    var optionType = aDetails[1];
    var pageNo = aDetails[2]; // X for eligibilty special options
    var qNo = aDetails[3];    // X for eligibilty special options
    var optionNo = aDetails[4];
    var prevOptionNo = parseInt(optionNo);
    --prevOptionNo;
    var prevControlId = formName+'_'+aDetails[2]+'_'+aDetails[3]+'_'+prevOptionNo;
    viewModel.currentFocusControlId(prevControlId);
    messageType = 'delOption';
    var contentArray = {};
    contentArray[0] = optionType;
    contentArray[1] = pageNo; 
    contentArray[2] = qNo;
    contentArray[3] = optionNo;
    content = contentArray;
    sendStructureAction(messageType, content);      
  });
  $('.cloneQuestion').on("click", function() {    
    var aID=$(this).attr('id');
    var aDetails=aID.split('_');
    var qType = aDetails[1];
    var pageNo = aDetails[2];
    var qNo = aDetails[3];
    messageType = 'cloneQuestion';
    var contentArray = {};
    contentArray[0] = qType;
    contentArray[1] = pageNo; 
    contentArray[2] = qNo;
    content = contentArray;
    sendStructureAction(messageType, content);      
  });
  $('.addQuestion').on("click", function() {    
    var aID=$(this).attr('id');
    var aDetails=aID.split('_');
    var qType = aDetails[1];
    var pageNo = aDetails[2];
    var qNo = aDetails[3];
    messageType = 'addQuestion';
    var contentArray = {};
    contentArray[0] = qType;
    contentArray[1] = pageNo; 
    contentArray[2] = qNo;
    content = contentArray;
    sendStructureAction(messageType, content);      
  });
  $('.delQuestion').on("click", function() {    
    var aID=$(this).attr('id');
    var aDetails=aID.split('_');
    var qType = aDetails[1];
    var pageNo = aDetails[2];
    var qNo = aDetails[3];
    messageType = 'delQuestion';
    var contentArray = {};
    contentArray[0] = qType;
    contentArray[1] = pageNo; 
    contentArray[2] = qNo;
    content = contentArray;
    sendStructureAction(messageType, content);      
  });
  $('.clonePage').on("click", function() {    
    var aID=$(this).attr('id');
    var aDetails=aID.split('_');
    var pageType = aDetails[1];
    var pageNo = aDetails[2];
    messageType = 'clonePage';
    var contentArray = {};
    contentArray[0] = pageType;
    contentArray[1] = pageNo; 
    content = contentArray;
    sendStructureAction(messageType, content);      
  });
  $('.addPage').on("click", function() {    
    var aID=$(this).attr('id');
    var aDetails=aID.split('_');
    var pageType = aDetails[1];
    var pageNo = aDetails[2];
    messageType = 'addPage';
    var contentArray = {};
    contentArray[0] = pageType;
    contentArray[1] = pageNo; 
    content = contentArray;
    sendStructureAction(messageType, content);      
  });
  $('.delPage').on("click", function() {    
    var aID=$(this).attr('id');
    var aDetails=aID.split('_');
    var pageType = aDetails[1];
    var pageNo = aDetails[2];
    messageType = 'delPage';
    var contentArray = {};
    contentArray[0] = pageType;
    contentArray[1] = pageNo; 
    content = contentArray;
    sendStructureAction(messageType, content);      
  });
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" comms and ajax">

function saveDataAction() {
  // send form to back-end for any save that does not alter form structure 
  // and hence does not need reload 
  var currentData = ko.mapping.toJS(viewModel);
  var jsonData = JSON.stringify(currentData, null , 2);
//  alert(jsonData);
  var postRequest = $.ajax({
     url: "/webServices/admin/storeStepFormDefinition.php",
     type: "POST",
     contentType:'application/json',
     data: jsonData,
     dataType: "text"
  });
  postRequest.done(function() {
  });
  postRequest.fail(function(jqXHR, textStatus) {
    console.log("save data failed: "+textStatus);
  });
  
}

function sendStructureAction(messageType, content) {
  // send form to back-end for save, before any structure changes
  var currentData = ko.mapping.toJS(viewModel);
  var jsonData = JSON.stringify(currentData, null , 2);
  var postRequest = $.ajax({
     url: "/webServices/admin/storeStepFormDefinition.php",
     type: "POST",
     contentType:'application/json',
     data: jsonData,
     dataType: "text"
  });
  postRequest.done(function(msg) {
    paramSet = {};
    paramSet['uid'] = uid;
    paramSet['permissions'] = permissions;
    paramSet['exptId'] = exptId;
    paramSet['messageType'] = messageType;
    paramSet['formName'] = formName;
    paramSet['content'] = content;
    $.ajax({
      type: 'GET',
      url: '/webServices/admin/formConfiguration.php',
      data: paramSet,
      dataType: 'text',
      success: function(data) { processData(data); }
    });      
  });
  postRequest.fail(function(jqXHR, textStatus) {
    console.log("structure change failed: "+textStatus);
  });
}

function reloadForm() {
  var paramItems = {};
  paramItems['process'] = 0;
  paramItems['action'] = '1_2_5';
  paramItems['uid'] = $('#hiddenUID').text();
  paramItems['permissions'] = $('#hiddenPermissions').text();
  paramItems['fName'] = $('#hiddenfName').text();
  paramItems['sName'] = $('#hiddensName').text();
  paramItems['buttonId'] = 'config_' + formName;
  paramItems['messageType'] = 'stepFormConfig';    
  paramItems['exptId'] = exptId;    
  post_to_url('/index.php', paramItems);  
}

function getData() {
  uid = $('#hiddenUID').text();
  permissions = $('#hiddenPermissions').text();
  exptId = $('#hiddenExptId').text();
  var formType = $('#hiddenFormType').text();
//  var buttonDetails = buttonId.split('_');
//  formName = buttonDetails[1];
  $('#name').html('anonymous');
  var paramSet = {};
  paramSet['uid'] = uid;
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

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" helpers">

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

function processData(data) {
  var xmlDoc = txtToXmlDoc(data);
  var messageType=xmlDoc.getElementsByTagName("messageType")[0].firstChild.nodeValue;
  switch (messageType) {
    case 'reloadForm':
      reloadForm();
    break;
  }
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

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" DOM doc ready">

$(document).ready(function() {
  $('.saveStatus').hide();
  $('.waitingForAction').show();
  getData();
});

function getDataSuccess(data) {
  viewModel = ko.mapping.fromJS(data, mainMapping);
  //console.log(viewModel);
  ko.applyBindings(viewModel);
  $('.waitingForAction').hide();
  $('.tabContent').show();
  setControls();
}

function getDataError(xhr, error, textStatus, url) {
  console.log('there was an error with the ajax request from ' + url + ' > ' + error + ' >> ' + textStatus);
}

// </editor-fold>

