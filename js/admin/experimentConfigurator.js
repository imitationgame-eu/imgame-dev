var currentExptName;
var exptId;
var currentStage;
var formType;
var paramSet = {};
var messageType;
var content;
var focusControlId = '';
var blockBlur = false;
var focusId;
var formName;

// <editor-fold defaultstate="collapsed" desc=" global experiment settings & step1 sessions    [configStage class='step1Configure']">

function setEditedStep1Detail(exptDetail, cType, focusId) {
  $('#adminSectionOne').html(exptDetail);
  setUI();
  blockBlur = false;
  if (cType == 'select') {
    // select change, so make selector the focus element
    focusControlId = focusId;
    //console.log('selectFocus: '+focusControlId);
  }
  if (cType != 'check') { redoFocus(); }  // don't refocus on checkbox as it will retoggle
  furtherStep1Init();
}

function furtherStep1Init() {
  for (var i=0; i<step1FlagsCnt; i++) {
    var tmp = step1ViewState[i];
    var jqId = '#'+tmp[0];
    if (tmp[1]) {  
      $(jqId).show(); 
      $(jqId).parent().addClass('active');
    } 
    else { 
      $(jqId).hide(); 
    }
  }
  setStep1HeaderControls();
}

function setStep1HeaderControls() {
  $('#adminSectionOne .currentExperiments').on('click', 'h2', function(e) {
    if ($(this).hasClass('open')) {
      $(this).removeClass('open').addClass('closed');
      $(this).next('div').hide();
      setStep1ViewState($(this).next('div').attr('id'), false);
    }
    else {
      $(this).removeClass('closed').addClass('open');
      $(this).next('div').show();
      setStep1ViewState($(this).next('div').attr('id'), true);
    }
  });  
}

function firstStep1Init() {
  $('#overview').click( function(e) {
    messageType = 'configConnect';    
    content = 'S1config';
    configStage = 'S1config';
    sendAction(messageType, content);    
  });
  $('#adminSectionOne .currentExperiments').find('h2').each( function(e) {
    $(this).removeClass('open');
    $(this).addClass('closed');
    $(this).next('div').hide();
    var tmp = {};
    tmp[0] = $(this).next('div').attr('id');
    tmp[1] = false;
    step1ViewState[step1FlagsCnt++] = tmp;
  });
  setStep1HeaderControls();
}

function setStep1ViewState(id, flag) {
  for (var i=0; i<step1FlagsCnt; i++) {
    var tmp = step1ViewState[i];
    if (tmp[0] == id) { tmp[1]=flag; }
  }
}

function setStep1Controls() {
  $('#experimentSection').unbind();
  $('#experimentDetailSection').unbind();
  $('.formRow').on('click', '.addLocation', function(event){
    $('#aLocation').fadeToggle(500);
    return false;
  }); 
  $('#alSaveB').click(function() {
    if ($('#alBox').val()>'') {
      messageType = 'newLocation';
      content = $('#alBox').val();
      sendAction(messageType, content);
      $('#alBox').val('');
    }
  });
  $('.formRow').on('click', '.addSubject', function(event){
    $('#aSubject').fadeToggle(500);
    return false;
  }); 
  $('#aSSaveB').click(function() {
    if ($('#aSBox').val()>'') {
      messageType = 'newSubject';
      content = $('#aSBox').val();
      sendAction(messageType, content);
      $('#aSBox').val('');
    }
  });
  // attach events to dynamic controls on expt config pages
  $('#adminSectionOne').unbind();
  $('#adminSectionOne').on({
      change: function sendSelectToListener(e) {
        //console.log('change' + $(this).attr('id'));
        focusControlId = $(this).attr('id');
        messageType = 'ecSelect';
        var contentArray = {};
        contentArray[0] = focusControlId;
        contentArray[1] = $(this).val();
        content = contentArray;
        sendAction(messageType, content);
        }
    },
    'select'
  );
  // all labels
  $('#adminSectionOne').on({  // focusout          
      focusout : function sendTextToListener(e) {
        //console.log('focusOut' + $(this).attr('id'));
        if (!blockBlur) {
          focusControlId = $(this).attr('id');
          messageType = 'ecText';
          var contentArray = {};
          contentArray[0] = focusControlId;
          contentArray[1] = $(this).val();
          content = contentArray;
          sendAction(messageType, content);         
        }
        else {
          blockBlur = false;
        }
      }
    },
    'input.text'
  );
  // checkboxes
  $('#adminSectionOne').on({
      click: function sendStateToListener(e) {
        if (!blockBlur) {
          //console.log('check' + $(this).attr('id'));
          focusControlId = $(this).attr('id');
          messageType = 'ecCheck';
          var contentArray = {};
          contentArray[0] = focusControlId;
          contentArray[1] = $(this).val();
          content = contentArray;
          sendAction(messageType, content);
        }
      }
    },
    'input.checkboxButton'
  ); 
  $('#nextTabB').click(function(e) {
    var contentArray = {};
    contentArray[0] = 'S1config';
    content = contentArray;
    sendAction('getS1ContentSummary', content);
  });
  
}

function setStep1FormsControls() {
  $('#experimentSection').unbind();
  $('#experimentDetailSection').unbind();
  $('#adminSectionOne .currentExperiments').on('click', 'h2', function(e) {
    if ($(this).hasClass('open')) {
      $(this).removeClass('open').addClass('closed');
      $(this).next('div').hide();
    }
    else {
      $(this).removeClass('closed').addClass('open');
      $(this).next('div').show();
    }
  });
  $('#overview').click( function(e) {
    messageType = 'configConnect';    
    content = 'S1config';
    configStage = 'S1config';
    sendAction(messageType, content);    
  });
  $('a').click(function(e) {
    var paramItems = {};
    paramItems['process'] = 0;
    paramItems['uid'] = $('#hiddenUID').text();
    paramItems['permissions'] = $('#hiddenPermissions').text();
    paramItems['buttonId'] = $(this).attr('id');
    paramItems['exptId'] = exptId;
    paramItems['currentExptName'] = currentExptName;
    var linkDetails = $(this).attr('id').split('_');
    switch(linkDetails[0]) {
      case 'config': 
        paramItems['action'] = '1_2_5';
        paramItems['messageType'] = 'stepFormConfig';          
      break;
      case 'clone': 
        paramItems['action'] = '1_2_6';
        paramItems['messageType'] = 'stepFormClone';          
      break;
      case 'preview': 
        paramItems['action'] = '1_2_7';
        paramItems['messageType'] = 'stepFormPreview';          
      break;      
    } 
    post_to_url('/index.php', paramItems);
  });
  $('.formRow').on({
      click: function sendStateToListener(e) {
        if (!blockBlur) {
          messageType = 'stepFormToggleCheck';
          var contentArray = {};
          contentArray[0] = $(this).attr('id');
          contentArray[1] = $(this).prop('checked');
          content = contentArray;
          sendAction(messageType, content);
        }
      }
    },
    'input.checkboxButton'
  ); 
}

function setStep1FormsDetail(step1FormsDetail, exptName) {
  currentExptName = exptName;
  $('.experimentSection').removeClass('active');
  $('#defTab').removeClass('active').hide();
  $('#configStage').removeClass('exptMain').addClass('step1FormsList');
  var dhHtml='<h1>Admin: <span>'+exptName+'</span></h1><ul><li class=\"headerli\"><a href="#" id="overview">Return to overview</a>';
  dhHtml=dhHtml+'| </li><li><a href="#">Logout</a></li></ul>';
  $('#experimentHeaderDetails').html(dhHtml);    
  $('#adminSectionOne').html(step1FormsDetail);
  setUI();
  setStep1FormsControls();  
}

function setConfigStep1Detail(exptDetail, exptName) {
  currentExptName = exptName;
  $('.experimentSection').removeClass('active');
  $('#defTab').removeClass('active').hide();
  $('#configStage').removeClass('exptMain').addClass('step1Configure');
  var dhHtml='<h1>Admin: <span>'+exptName+'</span></h1><ul><li class=\"headerli\"><a href="#" id="overview">Return to overview</a>';
  dhHtml=dhHtml+'| </li><li><a href="#">Logout</a></li></ul>';
  $('#configHeaderDetails').html(dhHtml);    
  $('#adminSectionOne').html(exptDetail);
  setUI();
  firstStep1Init();
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" step1 users tab     [configStage class='usersPage']">

function setUsersTab(usersDetail) {
  $('#configStage').removeClass('content').addClass('usersPage');
  $('#usersInfo').html(usersDetail);
  setUI();
}

function setUserControls() {
  $('#exptListB').click( function(e) {
    $('#configStage').removeClass('usersPage').addClass('exptMain');   
    messageType = 'exptList';
    content = '';
    sendAction(messageType, content)    
  });
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" global page furniture    [configStage class='content']">

function setContentTab(formDetail, focusId) {
  $('#configStage').removeClass('step1Configure').addClass('content');
  $('#contentEdit').html(formDetail);
  setUI();
  $('#contentEdit .formRow').unbind();
  $('#contentEdit .formRow').find('h2').each( function(e) {
    $(this).parent().next('.contentDef').hide();    
  });
  
  find('h2').click
  $('#contentEdit .formRow').find('h2').click( function(e){
    var h2parentId = $(this).parent().attr('id');
    
    if (h2parentId == 'jcId') {
      if ($('#jcId').hasClass('active'))
      {      
        $('#jcId').removeClass('active');
        $('#jcHolder').hide();
      }
      else {
        $('#jcId').addClass('active');
        $('#jcHolder').show();
      }
    }
    else {
      if ($('#rcId').hasClass('active'))
      {      
        $('#rcId').removeClass('active');
        $('#rcHolder').hide();
      }
      else {
        $('#rcId').addClass('active');
        $('#rcHolder').show();
      }      
    }
  });
   
  $('.contentDef').on( {            
    focusout: function sendTextToListener(e) {
      if (!blockBlur) {
        messageType = 'cdText';
        focusControlId = $(this).attr('id');
        var contentArray = {};
        contentArray[0] = focusControlId;
        contentArray[1] = $(this).val();
        content = contentArray;
        sendAction(messageType, content);      
      }
      else {
        blockBlur = false;
      }
    }},
    'input.text'
  );
  $('.contentDef').on( {            
    focusout: function sendTextToListener(e) {
      if (!blockBlur) {
        focusControlId = $(this).attr('id');
        messageType = 'cdText';
        var contentArray = {};
        contentArray[0] = focusControlId;
        contentArray[1] = $(this).val();
        content = contentArray;
        sendAction(messageType, content);
      }
      else {
        blockBlur = false;
      }
    }},
    'textarea.text'
  );
  $('#saveContentB').click(function(e) {
      messageType = 'saveContentData';
      content = '';
      sendAction(messageType, content);      
    }
  );
  focusControlId = focusId;
  redoFocus();
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" jqm initial experiment list page">

function setExptSummaryControls() {
  $('.archivedExperiments table').hide();
  $('.currentExperiments table').hide();
  $('.currentExperiments, .archivedExperiments').unbind();
  $('.currentExperiments h2, .archivedExperiments h2').addClass('closed');  
  $('.currentExperiments, .archivedExperiments').on('click', 'h2', function(event) {
    if ($(this).hasClass('open')) {
      $(this).removeClass('open').addClass('closed');
      $(this).parent().find('table').hide();
    }
    else {
      $(this).removeClass('closed').addClass('open');
      $(this).parent().find('table').show();
    }
  });
  $('.checkboxButton').click(function(e) {
    var buttonID=this.id;
    var buttonDetails=buttonID.split('_');
    switch (buttonDetails[0]) {
      case 'mInactive':
        exptId = buttonDetails[1];
        var currentActiveStatus = buttonDetails[2];
        messageType = 'toggleActiveStatus';
        var contentArray = {};
        contentArray[0] = currentActiveStatus;
        content = contentArray;
        sendAction(messageType, content)    
      break;
    }
  });
  //attach all <a href> to function
  $('#experimentSection').find('.button').click(function(e) {
    var buttonID=this.id;
    var buttonDetails=buttonID.split('_');
    switch (buttonDetails[0]) {
      case 'deleteExpt' : 
        if (!$(this).hasClass('greyed')) {
          $( "#dialog:ui-dialog" ).dialog( "destroy" );
          $( "#dialog-confirm" ).dialog({
              resizable: false,
              height:140,
              modal: true,
              buttons: {
                "Delete experiment" : function() {
                  messageType = buttonDetails[0];
                  content = buttonDetails[1];
                  sendAction(messageType, content);
                  $(this).dialog("close");
                },
                Cancel: function() {
                  $(this).dialog("close");
                }
              }
          });
        }
      break;
      case 'cloneExpt' : 
        if (!$(this).hasClass('greyed')) {
          $( "#dialog:ui-dialog" ).dialog( "destroy" );
          $( "#dialog-clone" ).dialog({
              resizable: false,
              height:140,
              modal: true,
              buttons: {
                "Clone experiment" : function() {
                  messageType = buttonDetails[0];
                  var contentArray = {};
                  contentArray[0] = buttonDetails[1];
                  contentArray[1] = $('#cloneName').val();
                  content = contentArray;
                  sendAction(messageType, content);
                  $(this).dialog("close");
                },
                Cancel: function() {
                  $(this).dialog("close");
                }
              }
          });
        }
      break;  
      case 'dataclone' : 
        if (!$(this).hasClass('greyed')) {
          var paramItems = {};
          paramItems['process'] = 0;
          paramItems['uid'] = $('#hiddenUID').text();
          paramItems['permissions'] = $('#hiddenPermissions').text();
          paramItems['buttonId'] = $(this).attr('id');
          paramItems['action'] = '1_2_9';
          post_to_url('/index.php', paramItems);
        }
      break;  
      default : 
      if (!$(this).hasClass('greyed')) {
        $('.button').addClass('greyed');
        messageType = buttonDetails[0];
        exptId = buttonDetails[2];
        switch(messageType) {
          case 's1forms' :
          case 's2config':
          case 's4config':
          case 's1config': {
            var contentArray = {};
            contentArray[0] = buttonDetails[0]; // not used, but need a dummy content payload
//              contentArray[1] = buttonDetails[2];
            content = contentArray;
            sendAction(messageType, content);
            break;
          }
        }
      }
    }
  });    
}

function renameClonedExpt(sourceExptId) {
  $( "#dialog:ui-dialog" ).dialog( "destroy" );
  $( "#dialog-cloneRename" ).dialog({
    resizable: false,
    height:140,
    modal: true,
    buttons: {
      "Clone experiment" : function() {
        messageType = "cloneExpt";
        var contentArray = {};
        contentArray[0] = sourceExptId;
        contentArray[1] = $('#cloneReName').val();
        content = contentArray;
        sendAction(messageType, content);
        $(this).dialog("close");
      },
      Cancel: function() {
        $(this).dialog("close");
      }
    }
  });

}

function showDeletionDetails(ndc, nar) {
  $("#dialog:ui-dialog" ).dialog( "destroy" );
  $('#ndc').html(ndc + ' system-generated users were deleted.');
  $('#nar').html(nar + ' data rows were archived.');
  $("#dialog-delExpt" ).dialog({
    resizable: false,
    height:160,
    modal: true,
    buttons: {
      "OK" : function() {
        messageType = "exptList";
        content = uid;
        sendAction(messageType, content);
        $(this).dialog("close");
      },
    }
  });
}

function setConfigExperiment(activeStandardList, activeInjectedList, inactiveStandardList, inactiveInjectedList) {
  $('#configStage').removeClass().addClass('exptMain');
  $('#activeStandardList').html(activeStandardList);
  $('#activeInjectedList').html(activeInjectedList);
  $('#inactiveStandardList').html(inactiveStandardList);
  $('#inactiveInjectedList').html(inactiveInjectedList);
  setUI();
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" Step2 & 4 forms/content setup">

function setStepConfigDetail(exptDetail, exptName, step) {
  currentExptName = exptName;
  $('.experimentSection').removeClass('active');
  $('#defTab').removeClass('active').hide();
  // show config section
  var dhHtml='<h1>'+step+': <span>'+exptName+'</span></h1><ul><li class=\"overviewli\"><a href="#" id="overview">Return to overview</a>';
  dhHtml=dhHtml+'| </li><li><a href="#">Logout</a></li></ul>';
  $('#experimentHeaderDetails').html(dhHtml);    
  $('#adminSectionOne').html(exptDetail);
  $('#messagePlace').hide();
  $('#summaryHeader').hide();
  $('#experimentHeader').show();
  $('#loginSection').hide();
  $('#experimentSection').hide();
  $('#experimentDetailSection').show();
  $('#tabOne').addClass('active');
  $('#configureExperiment').addClass('active');
  $('#configureExperiment').show();
  $('#experimentSessionSection').hide();
  $('.addOptionBox').hide();	  
  $('#tabTwo').hide();
  $('#tabThree').hide();
  $('#tabOne').hide();
  $('#nextTabB').hide();
  $('#overview').click( function(e) {
    messageType = 'configConnect';    
    content = 'S1config';
    configStage = 'S1config';
    sendAction(messageType, content);    
  });
  $('#adminSectionOne').on({  // focusout          
      focusout : function sendTextToListener(e) {
        //console.log('focusOut' + $(this).attr('id'));
        if (!blockBlur) {
          focusControlId = $(this).attr('id');
          messageType = 'stepConfigText';
          var contentArray = {};
          contentArray[0] = focusControlId;
          contentArray[1] = $(this).val();
          console.log(contentArray[1]);
          content = contentArray;
          sendAction(messageType, content);         
        }
        else {
          blockBlur = false;
        }
      }
    },
    'input.text'
  );
  $('h2').click(function(e) {
    if ($(this).hasClass('open')) {
      $(this).removeClass('open');
      $(this).addClass('closed');
      $(this).next('div').hide();
    }
    else {
      $(this).removeClass('closed');
      $(this).addClass('open');
      $(this).next('div').show();      
    }
  });
  $('a').click(function(e) {
    var paramItems = {};
    paramItems['process'] = 0;
    paramItems['uid'] = $('#hiddenUID').text();
    paramItems['permissions'] = $('#hiddenPermissions').text();
    paramItems['buttonId'] = $(this).attr('id');
    paramItems['exptId'] = exptId;
    paramItems['currentExptName'] = currentExptName;
    var linkDetails = $(this).attr('id').split('_');
    switch(linkDetails[0]) {
      case 'config': 
        paramItems['action'] = '1_2_5';
        paramItems['messageType'] = 'stepFormConfig';          
      break;
      case 'clone': 
        paramItems['action'] = '1_2_6';
        paramItems['messageType'] = 'stepFormClone';          
      break;
      case 'preview': 
        paramItems['action'] = '1_2_7';
        paramItems['messageType'] = 'stepFormPreview';          
      break;
    } 
    post_to_url('/index.php', paramItems);
  });
  $('.formRow').on({
      click: function sendStateToListener(e) {
        if (!blockBlur) {
          messageType = 'stepFormToggleCheck';
          var contentArray = {};
          contentArray[0] = $(this).attr('id');
          contentArray[1] = $(this).prop('checked');
          content = contentArray;
          configStage = step;
          sendAction(messageType, content);
        }
      }
    },
    'input.checkboxButton'
  ); 
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" UI & controls">

function redoFocus() {
  if (focusControlId != 'noFocus') {
    blockBlur = true;
    //console.log('manual reFocus: ', focusControlId);
//    var jqId = '#'+focusControlId;
//    $(jqId).focus();
    blockBlur = false;
  }
}

function setUI() {
  if ($('#configStage').hasClass('exptMain')) {
    $('#dialog-confirm').hide();
    $('#dialog-clone').hide();
    $('#dialog-cloneRename').hide();
    $('#dialog-delExpt').hide();
    $('#usersInfo').hide();
    $('#summaryHeaderDetails').html('<ul><li>Welcome: '+firstName+' </li><li><a href="#"> Logout </a></li></ul>');
    $('#headerDetails').show();
    $('#experimentHeader').hide();
    $('#tabOne').removeClass('completed');
    $('#tabOne').removeClass('active');
    $('#tabOne').addClass('notCompleted');
    $('#tabTwo').removeClass('completed');
    $('#tabTwo').removeClass('active');
    $('#tabTwo').addClass('notCompleted');
    $('#tabThree').removeClass('completed');
    $('#tabThree').removeClass('active');
    $('#tabThree').addClass('notCompleted');
    $('#configureExperiment').removeClass('active');    
    $('#configureExperiment').hide();
    $('#configureFormsSection').hide();
    $('#experimentSection').show();
    $('#experimentDetailSection').hide();
    $('#experimentSessionSection').hide();
    setExptSummaryControls(); 
  }
  if ($('#configStage').hasClass('step1FormsList')) {
    $('#messagePlace').hide();
    $('#experimentHeader').show();
    $('#summaryHeader').hide();
    $('#configHeader').show();
    $('#loginSection').hide();
    $('#experimentSection').hide();
    $('#experimentDetailSection').show();
    $('#tabOne').addClass('active').hide();
    $('#configureExperiment').show();
    $('#experimentSessionSection').hide();
    $('.addOptionBox').hide();
    $('#nextTabB').hide();
    $('#tabTwo').hide();
    $('#tabThree').hide();
    $('#tabOne').html('Form use');
  }
  if ($('#configStage').hasClass('step1Configure')) {
    $('#messagePlace').hide();
    $('#summaryHeader').show();
    $('#configHeader').show();
    $('#loginSection').hide();
    $('#experimentSection').hide();
    $('#experimentDetailSection').show();
    $('#tabOne').addClass('active');
    //$('#configureExperiment').addClass('active');
    $('#configureExperiment').show();
    $('#experimentSessionSection').hide();
    $('.addOptionBox').hide();	
    setStep1Controls();
  }
  if ($('#configStage').hasClass('content')) {
    $('#tabOne').removeClass('active notCompleted').addClass('completed');
    $('#tabTwo').addClass('active notCompleted');
    $('#adminSectionOne').hide();
    $('#configureExperiment').hide();
    $('#contentEdit').show();        
  }
  if ($('#configStage').hasClass('usersPage')) {
    $('#tabTwo').removeClass('active notCompleted').addClass('completed');
    $('#tabThree').addClass('active completed');
    $('#contentEdit').hide();
    $('#usersInfo').show();
  }
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" helpers and comms/ajax">

function sendAction(messageType, content) {
  // content can be single value or array
  paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['exptId'] = exptId;
  paramSet['messageType'] = messageType;
  paramSet['content'] = content;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/experimentConfiguration.php',
    data: paramSet,
    dataType: 'text',
    success: function(data) { processData(data); }
  });      
}

function txtToXmlDoc(txt) {
  // check for spurious characters at beginning of message string
  if (txt.substring(0,1) != '<') {
    var tl = txt.length;
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

function post_to_url(path, params) {
  var method = "post"; // Set method to post by default
  var form = document.createElement("form");
  form.setAttribute("method", method);
  form.setAttribute("action", path);
  for (var key in params) {
    if (params.hasOwnProperty(key)) {
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

// <editor-fold defaultstate="collapsed" desc=" message processing">

function processData(data) {
  var xmlDoc = txtToXmlDoc(data);
  var messageType=xmlDoc.getElementsByTagName("messageType")[0].firstChild.nodeValue;
  console.log(data);
  switch (messageType) {
    case 'delUsers':
      var ndc = xmlDoc.getElementsByTagName("ndc")[0].firstChild.nodeValue;
      var nar = xmlDoc.getElementsByTagName("nar")[0].firstChild.nodeValue;
      showDeletionDetails(ndc, nar);
      break;
    case 'configStep1Detail' :
      var exptDetail=xmlDoc.getElementsByTagName("exptDetail")[0].firstChild.nodeValue;
      var exptName=xmlDoc.getElementsByTagName("exptTitle")[0].firstChild.nodeValue;
      setConfigStep1Detail(exptDetail, exptName);
      break;
    case 'editedStep1Detail' :
      var e_exptDetail=xmlDoc.getElementsByTagName("exptDetail")[0].firstChild.nodeValue;
      focusId = xmlDoc.getElementsByTagName("focusId")[0].firstChild.nodeValue;
      setEditedStep1Detail(e_exptDetail, focusId);
      break;
    case 'step1Forms' :
      var step1FormsDetail = xmlDoc.getElementsByTagName("step1FormsDetail")[0].firstChild.nodeValue;
      var exptName = xmlDoc.getElementsByTagName("exptTitle")[0].firstChild.nodeValue;
      setStep1FormsDetail(step1FormsDetail, exptName);
      break;
    case 'stepConfigDetail' : // used for Step2 AND Step4 configuration
      var exptDetail=xmlDoc.getElementsByTagName("exptDetail")[0].firstChild.nodeValue;
      var exptName=xmlDoc.getElementsByTagName("exptTitle")[0].firstChild.nodeValue;
      var step = xmlDoc.getElementsByTagName("step")[0].firstChild.nodeValue;
      setStepConfigDetail(exptDetail, exptName, step);
      break;
    case 'formDef':
      var formsHtml = xmlDoc.getElementsByTagName("fHtml")[0].firstChild.nodeValue;
      formName = xmlDoc.getElementsByTagName("formName")[0].firstChild.nodeValue;
      setFormDefPage(formsHtml);
      createFormDefViewState();
      $('#configStage').removeClass().addClass('formDef');
      setUI();
    break;
    case 'editedFormsData':
      var eformsHtml=xmlDoc.getElementsByTagName("fHtml")[0].firstChild.nodeValue;
      focusControlId = xmlDoc.getElementsByTagName("focusId")[0].firstChild.nodeValue;
      setFormDefPage(eformsHtml);
      applyFormDefViewState();
      redoFocus();
      break;
    case 'contentSummary':
      var formDetail=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
      setContentTab(formDetail, '');
      break;
    case 'dupName' :
      var sourceExptId=xmlDoc.getElementsByTagName("sourceExptId")[0].firstChild.nodeValue;
      renameClonedExpt(sourceExptId);
      break;
    case 'NOOP' :
      // non operational message
      break;
    // to be refactored below
    case 'contentUpdate':    
      var uformDetail=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
      focusId = xmlDoc.getElementsByTagName("focusId")[0].firstChild.nodeValue;
      setContentTab(uformDetail, focusId);
      break;
    case 'contentSave':    // move to users tab
      var usersDetail=xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
      setUsersTab(usersDetail);
      break;
  }
  blockEvents = false;
}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" DOM ready">

$(document).ready(function() {
  $('#createB').click(function() {
    if ($('#exptName').val()>'') {  
      sendAction('createExpt', $('#exptName').val());
      $('#exptName').val('');
    }
  });
  setUI();
  uid=$('#hiddenUID').text();
  fName=$('#hiddenfName').text();
  sName=$('#hiddensName').text();
  permissions=$('#hiddenPermissions').text();
  email = $('#hiddenEmail').text();
  var configStage = $('#hiddenStepId').text();
  exptId = $('#hiddenExptId').text();
  // get experiment list
  $('#name').html(fName);
  if (configStage == '') {
    messageType = 'configConnect';    
    content = 'S1config';
    sendAction(messageType, content);
  }
  else {
    messageType  = configStage;
    content = '';
    sendFormAction(messageType, content);
  }
});

// </editor-fold>

