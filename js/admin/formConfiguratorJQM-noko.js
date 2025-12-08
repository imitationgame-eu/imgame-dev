var uid;
var fName;
var sName;
var permissions;
var exptId;
var formType;
var paramSet = {};
var messageType;
var formName;
var formData;
var pageLabel;
var initialBind = true;

function setControls() {
  if (formData.currentFocusControlId != 'unset') {
    var jqId = '#'+formData.currentFocusControlId;
    $(jqId).focus();
  }
  $('#saveB').click(function() {
    sendStructureAction();  // this causes a save-reload
  });
  $('#backB').click(function() {
    loadMultiSectionPage('1_2_1', 15);
  });
  // form structure functions ----------------------------------------------------------------
  // these look for changes that alter form structure and hence need a re-load
  // -----------------------------------------------------------------------------------------
  $('[id^=del_page_]').click(function() {
    var id = $(this).attr('id');
    var details = id.split('_');
    var pageToDelete = details[2];
    var remainingPages = [];
    for (var i=0; i<formData.pages.length; i++) {
      if (formData.pages[i].pNo != pageToDelete) {
        remainingPages.push(formData.pages[i]);
      }
    }
    formData.pages = remainingPages;
    sendStructureAction();  // this causes a save-reload
  });
  $('#addPage').click(function() {
    getNewPageComponent();
  });
  // eqOptions.click looks for add/del events
  $('[id^=eqOptions_]').click(function() {
    var details = $(this).attr('id').split('_');
    switch (details[1]) {
      case 'add':
        getNewEQOptionComponent();
        break;
      case 'del':
        var remainingOptions = [];
        for (var i=0; i<formData.eligibilityQ.options.length; i++) {
          if (formData.eligibilityQ.options[i].id != details[2]) {
            remainingOptions.push(formData.eligibilityQ.options[i]);
          }
        }
        formData.eligibilityQ.options = remainingOptions;
        sendStructureAction();  // this causes a save-reload
        break;
    }
  });
  // eqOptions.change looks for text value changes
  $('[id^=eqOptions_]').change(function(e) {
    var details = $(this).attr('id').split('_');
    switch (details[1]) {
      case 'jType':
        var selectedValue = $(this).children('option:selected').val();
        formData.eligibilityQ.options[details[2]].jType = selectedValue;
        break;
      case 'label':
        formData.eligibilityQ.options[details[2]].label = $(this).val();
        break;
    }
    e.stopPropagation();
  });
  $('#jTypeSelectorFS').on('change', function() {
    if (!initialBind) {
      formData.eligibilityQ.qUseJTypeSelector = !formData.eligibilityQ.qUseJTypeSelector;
      formData.currentFocusControlId = $(this).attr('id');
      sendStructureAction();
    }
  });
  $('[id^=qB_]').click(function() {
    var details = $(this).attr('id').split('_');
    switch (details[2]) {
      case 'del': delPageQuestion(details[1], details[3]); break;
      case 'add': addPageQuestion(details[1], details[3]); break;
    }
  });
  $('[id^=qoB_]').click(function() {
    var details = $(this).attr('id').split('_');
    switch (details[3]) {
      case 'del': delPageQuestionOption(details[1], details[2], details[4]); break;
      case 'add': addPageQuestionOption(details[1], details[2], details[4]); break;
    }
  });
  // form control functions ------------------------------------------------------------------
  // these monitor value changes that do not require a re-load,
  // but keep the model (formData) in sync
  // -----------------------------------------------------------------------------------------
  $('[id^=ignorePage_]').on('change', function() {
    var pageNo = $(this).attr('id').split('_')[1];
    if (!initialBind)
      formData.pages[pageNo].ignorePage = formData.pages[pageNo].ignorePage === "0" ? "1" : "0";
  });
  $('[id^=pageTitleTA_]').change(function() {
    var details = $(this).attr('id').split('_');
    formData.pages[details[1]].pageTitle = $(this).val();
  });
  $('[id^=pageInstTA_]').change(function() {
    var details = $(this).attr('id').split('_');
    formData.pages[details[1]].pageInst = $(this).val();
  });
  $('[id^=qTA_]').change(function() {
    var details = $(this).attr('id').split('_');
    formData.pages[details[1]].questions[details[2]].qLabel = $(this).val();
  });
  $('[id^=qoTA_]').change(function() {
    var details = $(this).attr('id').split('_');
    formData.pages[details[1]].questions[details[2]].options[details[3]].label = $(this).val();
  });
  $('[id^=TA_]').change(function(e) {
    var details = $(this).attr('id').split('_');
    switch (details[1]) {
      case 'ft': formData.formTitle = $(this).val(); break;
      case 'fi': formData.formInst = $(this).val(); break;
      case 'spt': formData.introPageTitle = $(this).val(); break;
      case 'spi': formData.introPageMessage = $(this).val(); break;
      case 'spb': formData.introPageButtonLabel = $(this).val(); break;
      case 'recQ': formData.recruitmentCodeMessage = $(this).val(); break;
      case 'recNo': formData.recruitmentCodeNoLabel = $(this).val(); break;
      case 'recYes': formData.recruitmentCodeYesLabel = $(this).val(); break;
      case 'recCode': formData.recruitmentCodeLabel = $(this).val(); break;
      case 'fm': formData.finalMsg = $(this).val(); break;
      case 'fb': formData.finalButtonLabel = $(this).val(); break;
      case 'eq': formData.eligibilityQ.qLabel = $(this).val(); break;
      case 'enem': formData.eligibilityQ.qNonEligibleMsg = $(this).val(); break;
    }
  });
  $('#useFinalPage').click(function(e){
    if (!initialBind) formData.useFinalPage = formData.useFinalPage === "0" ? "1" : "0";
    e.stopPropagation();
  });
  $('#useIntroPage').click(function(e) {
    if (!initialBind) formData.useIntroPage = formData.useIntroPage === "0" ? "1" : "0";
    e.stopPropagation();
  });
  $('#dcFS').click(function() {
    if (!initialBind) formData.definitionComplete = formData.definitionComplete === "0" ? "1" : "0";
  });
  $('#urFS').click(function() {
    if (!initialBind) formData.useRecruitmentCode = formData.useRecruitmentCode === "0" ? "1" : "0";
  });
  $('#ueFS').click(function() {
    if (!initialBind) formData.useEligibilityQ = formData.useEligibilityQ === "0" ? "1" : "0";
  });
  $('[id^=q0Filter_]').on('change', function() {
    var pageNo = $(this).attr('id').split('_')[1];
    if (!initialBind) formData.pages[pageNo].q0isFilter = formData.pages[pageNo].q0isFilter === "0" ? "1" : "0";
  });
  $('[id^=qType_]').on('change', function(e) {
    var details = $(this).attr('id').split('_');
    var selectedValue = $(this).children('option:selected').val();
    if (!initialBind) {
      formData.pages[details[1]].questions[details[2]].qType = selectedValue;
      // show or hide options as per selector
      updateOptionsVisibility(details, selectedValue);
    }
    e.stopPropagation();
   });
  $('[id^=isContingentPage_]').on('change', function() {
    var pageNo = $(this).attr('id').split('_')[1];
    if (!initialBind) {
      if (formData.pages[pageNo].contingentPage === "0") {
        formData.pages[pageNo].contingentPage = "1";
        $('#contingent_' + pageNo).show();
      } else {
        formData.pages[pageNo].contingentPage = "0";
        $('#contingent_' + pageNo).hide();
      }
    }
  });
  $('[id^=qMandatory_]').on('change', function(e) {
    var details = $(this).attr('id').split('_');
    var pageNo = details[1];
    var qNo = details[2];
    if (!initialBind) formData.pages[pageNo].questions[qNo].qMandatory = formData.pages[pageNo].questions[qNo].qMandatory === "1" ?  "0" : "1";
    e.stopPropagation();
  });
  $('[id^=filterR_]').on('change', function(e) {
    var details = $(this).attr('id').split('_');
    var pageNo = details[1];
    var qNo = details[2];
    var selectedValue = $(this).children('option:selected').val();
    if (!initialBind) formData.pages[pageNo].questions[qNo].qContingentValue = selectedValue;
    e.stopPropagation();
  });
  $('[id^=contingencyMatch_]').on('change', function(e) {
    var details = $(this).attr('id').split('_');
    var pageNo = details[1];
    var selectedValue = $(this).children('option:selected').val();
    if (!initialBind) formData.pages[pageNo].contingentValue = selectedValue;
    e.stopPropagation();
  });
  $('[id^=qMax_]').on('change', function(e) {
    var details = $(this).attr('id').split('_');
    if (!initialBind) formData.pages[details[1]].questions[details[2]].qContinuousSliderMax = $(this).val();
    e.stopPropagation();
  });

  // ui status functions ---------------------------------------------------------------------

  function updateOptionsVisibility(details, qType) {
    switch (qType) {
      case "0":
      case "5":
      case "6":
      case "7":
      case "8":
        $('#qoBlock_' + details[1] + '_' + details[2]).show();
        $('#gridBlock_' + details[1] + '_' + details[2]).hide();
        break;
      case "9": // rb grid
        $('#gridBlock_' + details[1] + '_' + details[2]).show();
        $('#qoBlock_' + details[1] + '_' + details[2]).hide();
        break;
      default:
        $('#qoBlock_' + details[1] + '_' + details[2]).hide();
        $('#gridBlock_' + details[1] + '_' + details[2]).hide();
    }
  }
  $('#eqAccordion').on('collapsiblecollapse', function(e) {
    formData.eligibilityQ.qAccordionClosed = "1";
    e.stopPropagation();
  });
  $('#eqAccordion').on('collapsibleexpand', function(e) {
    formData.eligibilityQ.qAccordionClosed= "0";
    e.stopPropagation();
  });
  $('#recruitmentAccordion').on('collapsiblecollapse', function(e) {
    formData.recruitmentAccordionClosed = "1";
    e.stopPropagation();
  });
  $('#recruitmentAccordion').on('collapsibleexpand', function(e) {
    formData.recruitmentAccordionClosed = "0";
    e.stopPropagation();
  });
  $('#spAccordion').on('collapsiblecollapse', function(e) {
    formData.startPageAccordionClosed = "1";
    e.stopPropagation();
  });
  $('#spAccordion').on('collapsibleexpand', function(e) {
    formData.startPageAccordionClosed = "0";
    e.stopPropagation();
  });
  $('#pagesAccordion').on('collapsiblecollapse', function(e) {
    formData.pagesAccordionClosed = "1";
    e.stopPropagation();
  });
  $('#pagesAccordion').on('collapsibleexpand', function(e) {
    formData.pagesAccordionClosed = "0";
    e.stopPropagation();
  });
  $('#epAccordion').on('collapsiblecollapse', function(e) {
    formData.finalAccordionClosed = "1";
    e.stopPropagation();
  });
  $('#epAccordion').on('collapsibleexpand', function(e) {
    formData.finalAccordionClosed = "0";
    e.stopPropagation();
  });
  $('#eqOptionsAccordion').on('collapsiblecollapse', function(e) {
    formData.eligibilityQ.qOptionsAccordionClosed = "1";
    e.stopPropagation();
  });
  $('#eqOptionsAccordion').on('collapsibleexpand', function(e) {
    formData.eligibilityQ.qOptionsAccordionClosed = "0";
    e.stopPropagation();
  });
  $('[id^=pageAccordion_]').on('collapsiblecollapse', function(e) {
    var id = $(this).attr('id');
    var pageNo = id.split('_')[1];
    formData.pages[pageNo].pageAccordionClosed = "1";
    e.stopPropagation();
  });
  $('[id^=pageAccordion_]').on('collapsibleexpand', function(e) {
    var id = $(this).attr('id');
    var pageNo = id.split('_')[1];
    formData.pages[pageNo].pageAccordionClosed = "0";
    e.stopPropagation();
  });
  $('[id^=qAccordion_]').on('collapsiblecollapse', function(e) {
    var details = $(this).attr('id').split('_');
    formData.pages[details[1]].questions[details[2]].qAccordionClosed = "1";
    e.stopPropagation();
  });
  $('[id^=qAccordion_]').on('collapsibleexpand', function(e) {
    var details = $(this).attr('id').split('_');
    formData.pages[details[1]].questions[details[2]].qAccordionClosed = "0";
    e.stopPropagation();
  });
  $('[id^=qOptionsAccordion_]').on('collapsiblecollapse', function(e) {
    var details = $(this).attr('id').split('_');
    formData.pages[details[1]].questions[details[2]].optionsAccordionClosed = "1";
    e.stopPropagation();
  });
  $('[id^=qOptionsAccordion_]').on('collapsibleexpand', function(e) {
    var details = $(this).attr('id').split('_');
    formData.pages[details[1]].questions[details[2]].optionsAccordionClosed = "0";
    e.stopPropagation();
  });
}

//------------------------------------------------------------------------------
//  helpers
//------------------------------------------------------------------------------

function reloadForm() {
  var paramItems = {};
  paramItems['process'] = 0;
  paramItems['pageLabel'] = pageLabel;
  paramItems['uid'] = uid;
  paramItems['permissions'] = permissions;
  paramItems['fName'] = fName;
  paramItems['sName'] = sName;
  paramItems['referer'] = '1_1_1';
  paramItems['lastChild'] = '1_1_1';
  paramItems['formType'] = formType;
  paramItems['exptId'] = exptId;
  paramItems['isMultiSectionPage'] = 0;
  post_to_url('/index.php', paramItems);
}

function sendStructureAction() {
  // send form to back-end for save, before any structure changes
  // ensure that control with current focus is included
  formData.currentFocusControlId = $(document.activeElement).attr('id');
  var jsonData = JSON.stringify(formData, null , 2);
  var postRequest = $.ajax({
     url: "/webServices/admin/storeStepFormConfiguration.php",
     type: "POST",
     contentType:'application/json',
     data: jsonData,
     dataType: "text"
  });
  postRequest.done(function(msg) {
    reloadForm();
  });
  postRequest.fail(function(jqXHR, textStatus) {
    console.log("structure change failed: "+textStatus);
  });
}

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

// --------------------------------------------------- Functions ------ //

function delPageQuestion(pNo, qNo) {
  var questions = [];
  for (var i = 0; i<qNo; i++) {
    questions.push(formData.pages[pNo].questions[i]);
  }
  for (var i = qNo+1; i<formData.pages[pNo].questions.length; i++) {
    --formData.pages[pNo].questions[i].qNo;
    questions.push(formData.pages[pNo].questions[i]);
  }
  formData.pages[pNo].questions = questions;
  sendStructureAction();  // this causes a reload
}

function addPageQuestion(pNo, qNo) {
  var paramSet = {};
  paramSet['permissions'] = permissions;
  paramSet['componentType'] = 'newQuestion';
  paramSet['currentPNo'] = pNo;
  paramSet['currentQNo'] = qNo;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/getStepFormComponentAsJSON.php',
    data: paramSet,
    dataType: 'json',
    error: function(xhr, textStatus, error) { getDataError(xhr, error, textStatus, this.url); },
    success: function(data) { getNewQuestionDataSuccess(pNo, qNo, data); }
  });
}

function getNewQuestionDataSuccess(pNo, qNo, data) {
  var questions = [];
  for (var i=0; i<qNo; i++) {
    questions.push(formData.pages[pNo].questions[i]);
  }
  questions.push(data);
  for (var i= qNo; i<formData.pages[pNo].questions.length; i++) {
    ++formData.pages[pNo].questions[i].qNo;
    questions.push(formData.pages[pNo].questions[i]);
  }
  formData.pages[pNo].questions = questions;
  sendStructureAction();  // this causes a reload
}

function delPageQuestionOption(pNo, qNo, oNo) {
  var options = [];
  for (var i = 0; i<qNo; i++) {
    options.push(formData.pages[pNo].questions[qNo].options[i]);
  }
  for (var i = qNo+1; i<formData.pages[pNo].questions[qNo].options.length; i++) {
    --formData.pages[pNo].questions[qNo].options[i].id;
    options.push(formData.pages[pNo].questions[qNo].options[i]);
  }
  formData.pages[pNo].questions[qNo].options = options;
  sendStructureAction();  // this causes a reload
}

function addPageQuestionOption(pNo, qNo, oNo) {
  var paramSet = {};
  paramSet['permissions'] = permissions;
  paramSet['componentType'] = 'newQuestionOption';
  paramSet['currentONo'] = oNo;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/getStepFormComponentAsJSON.php',
    data: paramSet,
    dataType: 'json',
    error: function(xhr, textStatus, error) { getDataError(xhr, error, textStatus, this.url); },
    success: function(data) { getNewQuestionOptionDataSuccess(pNo, qNo, oNo, data); }
  });
}

function getNewQuestionOptionDataSuccess(pNo, qNo, oNo, data) {
  var options = [];
  for (var i=0; i<oNo; i++) {
    options.push(formData.pages[pNo].questions[qNo].options[i]);
  }
  options.push(data);
  for (var i= oNo; i<formData.pages[pNo].questions[qNo].options.length; i++) {
    ++formData.pages[pNo].questions[qNo].options[i].id;
    options.push(formData.pages[pNo].questions[qNo].options[i]);
  }
  formData.pages[pNo].questions[qNo].options = options;
  sendStructureAction();  // this causes a reload
}

function getFormAsJson() {
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

function getDataSuccess(data) {
  formData = data; //JSON.parse(data);
  console.log(data);
  setControls();
  initialBind = false;  // stop flip switches triggering on initial binding
}

function getDataError(xhr, error, textStatus, url) {
  console.log('there was an error with the ajax request from ' + url + ' > ' + error + ' >> ' + textStatus);
}

function getNewPageComponent() {
  var paramSet = {};
  paramSet['permissions'] = permissions;
  paramSet['componentType'] = 'newPage';
  paramSet['newPageNo'] = formData.pages.length + 1;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/getStepFormComponentAsJSON.php',
    data: paramSet,
    dataType: 'json',
    error: function(xhr, textStatus, error) { getDataError(xhr, error, textStatus, this.url); },
    success: function(data) { getNewPageDataSuccess(data); }
  });
}

function getNewPageDataSuccess(data) {
  formData.pages.push(data);
  //++formData.pageCount;
  sendStructureAction();  // this causes a reload
}

function getNewEQOptionComponent() {
  var paramSet = {};
  paramSet['permissions'] = permissions;
  paramSet['componentType'] = 'newEQOption';
  paramSet['newOptionNo'] = formData.eligibilityQ.options.length;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/getStepFormComponentAsJSON.php',
    data: paramSet,
    dataType: 'json',
    error: function(xhr, textStatus, error) { getDataError(xhr, error, textStatus, this.url); },
    success: function(data) { getNewEQOptionDataSuccess(data); }
  });
}

function getNewEQOptionDataSuccess(data) {
  formData.eligibilityQ.options.push(data);
  //++formData.eqOptionsCount;
  sendStructureAction();  // this causes a reload
}
