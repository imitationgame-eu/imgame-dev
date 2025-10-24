var uid;
var firstName;
var sName;
var permissions;
var exptId;
var formType;
var jType;
var paramSet = {};
var restartUID;   // -1 = new start (pre-form), >0 = appropriate UID to continue
var nextUrl;

var cntActivePages = [];
var pNo;
var qNo;
var optionNo;
var qType;

var formData;

var questionResponseStatus = [];

// <editor-fold defaultstate="collapsed" desc=" communications and process functions">

function getFormDataStructure() {
  // content can be single value or array
  paramSet = {};
  paramSet['permissions'] = 255;
  paramSet['exptId'] = exptId;    
  paramSet['formType'] = formType;
  paramSet['jType'] = jType;    
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/getStepFormAsJSON.php',
    data: paramSet,
    dataType: 'json',
    success: function(data) { processStructure(data); }
  });  
}

function processStructure(data) {
  formData = data;
}

function sendAction(messageType, content) {
  // content can be single value or array
  paramSet = {};
  paramSet['permissions'] = 255;
  console.log(messageType+' '+content);
  paramSet['messageType'] = messageType;
  paramSet['exptId'] = exptId;    
  paramSet['formType'] = formType;
  paramSet['jType'] = jType;    
  paramSet['content'] = content;
  $.ajax({
    type: 'GET',
    url: '/webServices/forms/stepFormRuntimeController.php',
    data: paramSet,
    dataType: 'text',
    success: function(data) { processData(data); }
  });      
}

function jsonReplacer(key, value) {
  if (typeof value === 'string') {
    return JSON.stringify(value, null, 2);
  }
  return value;
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

function setupEligibilityandRecruitment() {
  if (formData.useRecruitmentCode == 1) {
    $('#recruitmentCodeSection').show();
    bindRecruitmentControls();
  }
  if (formData.useEligibilityQ == 1) {
     if (formData.useRecruitmentCode == 0) {
       $('#eligibilitySection').show(); //only show if no recruitment
     }
    bindEligibilityControls();
  }
}

function processData(data) {
  var xmlDoc = txtToXmlDoc(data);
  console.log(data);
  var messageType=xmlDoc.getElementsByTagName("messageType")[0].firstChild.nodeValue;
  switch (messageType) {
    case 'step2Parameters' :
      restartUID = xmlDoc.getElementsByTagName("restartUID")[0].firstChild.nodeValue;
      userCode =  'na'; //xmlDoc.getElementsByTagName("userCode")[0].firstChild.nodeValue;
      jType = xmlDoc.getElementsByTagName("jType")[0].firstChild.nodeValue;
      var endPost = false;
      switch (formType) {
        case "2":
          nextUrl = "/index.php/?restartUID=" + restartUID ;
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
    case 'postStepDone' : // hide final button
      $('#processFinalB').hide();
      $('#finalPageSectionEligible').show();
    break;
  }
}

function disableAllPageButtons() {
  // heavy-handed way to ensure all buttons are disabled when first shown
 $('[id^=fsb_]').each(function() {
   var name = $(this).attr('id');
   disableButton(name);
 });
 $('[id^=frsb_]').each(function() {
   var name = $(this).attr('id');
   disableButton(name);
 });
 $('[id^=nfsb_]').each(function() {
   var name = $(this).attr('id');
   disableButton(name);
 });
}

function countActivePages() {
  cntActivePages[1] = 0;
  cntActivePages[0] = 0;
  for (var i=0; i<formData.pages.length; i++) {
    var page = formData.pages[i];
    if (page.contingentPage != 1) {
      ++cntActivePages[0];
      ++cntActivePages[1];
    }
    else {
      ++cntActivePages[page.contingentValue];
    }
  }
}


// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" process control bindings, declared when elements created by class.viewBuilder">

function processRecruitment() {
  $('#instructionSection').hide();
  responseData.recruitmentCode = $('#recruitmentCodeTA').val();
  $('#recruitmentCodeSection').hide();
  if (formData.useEligibilityQ == 1) {
    $('#eligibilitySection').show();
  }
  else {
    startActivePages();
  }
}

function processRecruitmentCode() {
  if (recruitmentSelection > -1) {
    if (allowNullRecruitmentCode == 1) {
      enableButton('processRecruitmentB');
    }
    else {
      if ($('#recruitmentCodeTA').val().length > 0) {
        enableButton('processRecruitmentB');        
      }
    }    
  }
}

function processEligibility() {
  $('#instructionSection').hide();
  $('#introPageSectionTop').hide();
  $('#introPageSectionBottom').hide();  
  $('#eligibilitySection').hide();
  if (responseData.isEligibleResponse == 1) {
    jType = responseData.eligibilitySelection;
    startActivePages();
  }
  else {
    doFinalPage();
  }  
}

function processFinalStatus() {
  // hide the final button - it doesn't do anything
  $('#processFinalB').hide();
  var currentData = {
    jsonType: 'jqm',
    formType: formType,
    exptId: exptId,
    jType: jType,
    restartUID: restartUID,
    formData: formData
  };
  var jsonData = JSON.stringify(currentData, jsonReplacer , 2);
  var postRequest = $.ajax({
     url: "/webServices/forms/storeStepFormResponses.php",
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

function processAccept() {
  $('#instructionSection').hide();
  $('#introPageSectionTop').hide();
  $('#introPageSectionBottom').hide();
  startActivePages();
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" control bindings and process functions for JQM controls">

function startActivePages() {
  var selectedJType = formData.useEligibilityQ == 1 ? formData.eligibilitySelection : jType;
  countActivePages();
  if (cntActivePages[selectedJType] > 0) {
    pNo = 0;
    $('#pageSections').show();
    $('#page_' + pNo).show();
    disableAllPageButtons();
    if (formData.pages[pNo].useFilter == 1) {
      bindFilterQuestionControl(formData.pages[pNo].questions[0].qType);
    }
    else {
      bindPageControls();
    }
  }
  else {
    doFinalPage();
  }
}

function doFinalPage() {
  if (formData.isEligibleResponse == 1) {
    $('#finalPageSectionEligible').show();
  }
  else {
    $('#finalPageSectionInEligible').show();
  }
}

function nextPage() {
  // hide current page
  $('#page_' + pNo).hide();
  ++pNo;
  if (pNo == cntActivePages[jType]) {
    $('#finalPageSection').show();
    processFinalStatus();
  }
  else {
    $('#page_' + pNo).show();
    bindPageControls();
  }
}

function enableButton(name) {
  $('#' + name).button('enable');
  $('#' + name).button('refresh');
}

function disableButton(name) {
  $('#' + name).button('disable');
  $('#' + name).button('refresh');
}

function bindEligibilityControls() {
  disableButton('processEligibilityB');
  $('[id^=eq_]').each(function() {
    $(this).click(function() {
      var selected = $(this).attr('id').split('_');
      formData.eligibilitySelection = selected[1];
      formData.isEligibleResponse = (selected[1] == 0 || selected[1] == 1) ? 1 : 0;
      enableButton('processEligibilityB');
    });
  });  
}

function bindRecruitmentControls() {
  disableButton('processRecruitmentB');
  $('[id^=rec_]').each(function() {
    $(this).click(function() {
      var selected = $(this).attr('id').split('_');
      formData.recruitmentSelection = selected[1];
      enableButton('processRecruitmentB');
    });
  });    
}

function bindPageControls() {
  if (formData.pages[pNo].useFilter == 1) {
    bindFilterQuestionControl(formData.pages[pNo].questions[0].qType);
  }
  else {
    bindPageResponseControls();
  }
 }

function bindPageResponseControls() {
  var tempQuestionResponseStatus = [];
  var logicalQNo = 0;
  for (var i=0; i<formData.pages[pNo].questions.length; i++) {
    var question = formData.pages[pNo].questions[i];
    var responseMask = {};
    responseMask.mandatory = question.qMandatory == 1 ? 1 : 0;
    responseMask.answered = question.qMandatory == 1  ? 0 : 1;
    tempQuestionResponseStatus.push(responseMask);
    makeQuestionBinding(false, question, logicalQNo);
    ++logicalQNo;
  }
  questionResponseStatus = tempQuestionResponseStatus;
  if (logicalQNo == 0) {
    nextPage(); // no filter responses to selected filter option
  }
}

function bindFilterQuestionControl(qType) {
  switch (qType) {
//    case 'checkbox':
//    break;
    case '5':    //'radiobutton':
      selector = 'fq_rb_' + pNo + '_0_' ;
      $('[id^=' + selector + ']').click(function () {
        var details= $(this).attr('id').split('_');
        formData.pages[pNo].questions[0].selectedValue = details[4];
        enableButton('fsb_' + pNo);
        $('#fsb_'+pNo).click(function() {
          $('#filterSection_' + pNo).hide();
          $('#filterResponseSection_' + pNo + '_' + details[4]).show();
          $('#fButtonSection_' + pNo).show();
          bindFilterResponseControls();
        });
      });
    break; 
//    case 'selector':
//    break
//    case 'slider'
//    break;
//    case 'continuousSlider':
//    break;
//    case radiobuttonGrid:
//    break;   
  }

}

// 0 = cb
// 1,2 = edit
// 3 = email
// 4 = datetime
// 5 = radiobutton
// 6 = select
// 7 = slider
// 8 = continuous slider
// 9 = radio-button grid
// 10 = numeric text input

function bindFilterResponseControls() {
  var filterTarget = formData.pages[pNo].questions[0].selectedValue;
  var tempQuestionResponseStatus = [];
  var responseStatus = {};
  responseStatus.mandatory = 1;
  responseStatus.answered = 1;

  tempQuestionResponseStatus.push(responseStatus); // filter question has been answered
  var logicalQNo = 0;
  for (var i=1; i<formData.pages[pNo].questions.length; i++) {
    var question = formData.pages[pNo].questions[i];
    if (question.qContingentValue == filterTarget) {
      ++logicalQNo;
      var responseMask = {};
      responseMask.mandatory = question.qMandatory == 1 ? 1 : 0;
      responseMask.answered = question.qMandatory == 1  ? 0 : 1;
      tempQuestionResponseStatus.push(responseMask);
      makeQuestionBinding(true, question, logicalQNo);
    }
  }
  questionResponseStatus = tempQuestionResponseStatus;
  if (logicalQNo == 0) {
    nextPage(); // no filter responses to selected filter option
  }
}

function makeQuestionBinding(isFilterResponse, question, logicalQNo) {
  var prefix = isFilterResponse ? "fq" : "";
  switch (question.qType) {
    case '0':
      bindCheckboxInput(prefix + 'response_cb_' + pNo + '_' + logicalQNo + '_');
      break;
    case '1':   //'single-line edit':
    case '2':   //'multi-line edit':
      bindTextInput('#' + prefix + 'response_ta_' + pNo + '_' + logicalQNo)
      break;
    case '3':   // email
      bindEmailInput('#' + prefix + 'response_email_' + pNo + '_' + logicalQNo);
      break;
    case '4':   // date
      bindDateInput('#' + prefix + 'response_date_' + pNo + '_' + logicalQNo);
      break;
    case '5':   //'radiobutton':
      bindRadioButton(prefix + 'response_rb_' + pNo + '_' + logicalQNo + '_');  // no # in selector for multi response questions
      break;
    case '6':   // select
      bindSelectMenu('#' + prefix + 'response_select_' + pNo + '_' + logicalQNo);
      break;
    case '7':
    case '8':   // slider and continuous slider
      bindSliderInput('#' + prefix + 'response_slider_' + pNo + '_' + logicalQNo);
      break;
    case '10':  //'numericInput':
      bindNumericInput('#' + prefix + 'response_numeric_' + pNo + '_' + logicalQNo);
      break;
   }
}

function bindSliderInput(selector) {
  $(selector).change(function() {
    var details = selector.split('_');
    formData.pages[pNo].questions[details[3]].sliderValue = $(this).val();
    questionResponseStatus[details[3]].answered = 1;
    checkPageValidation();
  });

}

function bindSelectMenu(selector) {
  $(selector).change(function() {
    var details = selector.split('_');
    formData.pages[pNo].questions[details[3]].date = $(this).val();
    questionResponseStatus[details[3]].answered = 1;
    checkPageValidation();
  });
}

function bindDateInput(selector) {
  $(selector).change(function() {
    var details = selector.split('_');
    formData.pages[pNo].questions[details[3]].date = $(this).val();
    questionResponseStatus[details[3]].answered = 1;
    checkPageValidation();
  });
}

function bindEmailInput(selector) {
  $(selector).change(function() {
    var details = selector.split('_');
    var email = $(this).val();
    if (isEmail(email)) {
      formData.pages[pNo].questions[details[3]].textResponse = email;
      questionResponseStatus[details[3]].answered = 1;
      checkPageValidation();
    }
    else {
      questionResponseStatus[details[3]].answered = 0;
    }
  });
}

function bindCheckboxInput(selector) {
  $('[id^=' + selector + ']').on('click', function() {
    var id = $(this).attr('id');
    var details = id.split('_');
    var status = $('#label_' + id).hasClass('ui-checkbox-off');
    formData.pages[pNo].questions[details[3]].options[details[4]].checked = $('#label_' + id).hasClass('ui-checkbox-off') ? 1 : 0;  // note class does not change to opposite until after this binding has been processed
    questionResponseStatus[details[3]].answered = 1;
    checkPageValidation();
  });
}

function bindTextInput(selector) {
  $(selector).change(function() {
    var details = selector.split('_');
    formData.pages[pNo].questions[details[3]].textResponse = $(this).val();
    questionResponseStatus[details[3]].answered = $(this).val().length > 1 ? 1 : 0;
    checkPageValidation();
  });
}

function bindNumericInput(selector) {
  $(selector).change(function() {
    var details = $(this).attr('id').split('_');
    var response = parseInt($(this).val());
    if (isNaN(response)) {
      $(this).val('1');
    }
    else {
      formData.pages[pNo].questions[details[3]].integerResponse = response;
      questionResponseStatus[details[3]].answered = 1;
      checkPageValidation();
    }
  });
}

function bindRadioButton(selector) {
  $('[id^=' + selector + ']').on('click', function () {
    var details = $(this).attr('id').split('_');
    formData.pages[pNo].questions[details[3]].selectedValue = details[4];
    questionResponseStatus[details[3]].answered = 1;
    checkPageValidation();
  });
}

function checkPageValidation() {
  var validated = true;
  for (var i= 0; i<questionResponseStatus.length; i++) {
    if (questionResponseStatus[i].mandatory == 1 && questionResponseStatus[i].answered == 0) {validated = false;}
  }
  if (validated) {
    var selector = formData.pages[pNo].useFilter == 1 ? "frsb_" + pNo : "nfsb_" + pNo;
    enableButton(selector);
    $('#' + selector).on('click', function() {
      nextPage();
    });
  }
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" global helpers">

function isEmail(sEmail) {
  var filter = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;
  if (filter.test(sEmail)) {
    return true;
  }
  else {
    return false;
  }
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" DOM document ready">

$(document).ready(function() {
  permissions = $('#hiddenPermissions').text();
  exptId = $('#hiddenExptId').text();
  jType = $('#hiddenJType').text();
  formType = $('#hiddenFormType').text();
  restartUID = $('#hiddenRestartUID').text();
  respId = $('#hiddenRespId').text();
  //$('#name').html('anonymous');
  getFormDataStructure();
});

// </editor-fold>
