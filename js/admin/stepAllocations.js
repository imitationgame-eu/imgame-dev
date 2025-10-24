var Server;
var uid;
var firstName;
var sName;
var permissions;
var currentExptName;
var hostname;
var exptId;
var stepId;
var currentStage;
var formType;
var paramSet = {};
var messageType;
var content;
var oldControlTabId = '';
var _isFocusBlurEvent = false;

function redoFocus() {
  if (oldControlTabId > '') {
    // find the id of the previous control, and then do focus on next
    _isFocusBlurEvent = true;
    var tabIndex = parseInt($('[id = '+oldControlTabId + ']').attr('tabindex')) + 1;
    $('[tabindex=' + tabIndex + ']').focus();
    _isFocusBlurEvent = false; 
    oldControlTabId = '';
  }
}

function setUI() {
  if ($('#configStage').hasClass('exptMain')) {
    $('#dialog-confirm').hide();
    $('#usersInfo').hide();
    $('#summaryHeaderDetails').html('<ul><li>Welcome: '+firstName+' </li><li><a href="#"> Logout </a></li></ul>');
    $('#headerDetails').show();
    $('#experimentSection').show();
    $('#experimentDetailSection').hide();
    $('#experimentSessionSection').hide();
    setExptSummaryControls(); 
  }
  if ($('#configStage').hasClass('exptConfigure')) {
    $('#messagePlace').hide();
    $('#summaryHeader').hide();
    $('#configHeader').show();
    $('#loginSection').hide();
    $('#experimentSection').hide();
    $('#experimentDetailSection').show();
    $('#tabOne').addClass('active');
    $('#configureExperiment').addClass('active');
    $('#experimentSessionSection').hide();
    $('.addLocationBox').hide();	
    setExptConfigControls();
  }
  if ($('#configStage').hasClass('configSaved')) {
    $('#configSaveB').hide();
    $('#messagePlace').show();
    $('#messagePlace').fadeOut(1200, function() {
      $('#configStage').removeClass('configSaved').addClass('configForms');
      getFormsData();
      setUI();
    });
  }
  if ($('#configStage').hasClass('configForms')) {
    $('#tabOne').removeClass('active notCompleted').addClass('completed');
    $('#tabTwo').addClass('active');
    $('#configureExperiment').hide();
    $('#configureFormsSection').show();
    redoFocus();
  }
  if ($('#configStage').hasClass('content')) {
    $('#tabTwo').removeClass('active notCompleted').addClass('completed');
    $('#tabThree').addClass('active');
    $('#contentEdit').show();        
  }
  if ($('#configStage').hasClass('usersPage')) {
    $('#tabThree').removeClass('active notCompleted').addClass('completed');
    $('#tabFour').addClass('active');
    $('#contentEdit').hide();
    $('#usersInfo').show();
  }
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

function setEListControls() {
  $('.buttonBlue').click( function () {
    var id = $(this).attr('id');
    var details = id.split('_');
    paramSet = {};
    paramSet['uid'] = uid;
    paramSet['permissions'] = permissions;
    paramSet['stepId'] = details[1];
    stepId = details[1];
    paramSet['exptId'] = details[2];
    exptId = details[2];
    paramSet['messageType'] = 'getExptAllocationView';
    $.ajax({
      type: 'GET',
      url: '/webServices/admin/step1AllocationView.php',
      data: paramSet,
      dataType: 'text',
      success: function(data) { processData(data); }
    });      
    
  });
}

function setAllocationsControls() {
  $('.registrant').click( function () {
    if ($(this).hasClass('closed')) {
      $(this).removeClass('closed').addClass('open');
      $(this).parent().next('div').show();
    }
    else {
      $(this).removeClass('open').addClass('closed');
      $(this).parent().next('div').hide();      
    }
  });
  $('.allocate').click( function() {
    var regDetails = $(this).attr('id').split('_');
    sendAction('allocateReg', regDetails);   
  });
  $('.deallocate').click( function() {
    var regDetails = $(this).attr('id').split('_');
    sendAction('deallocateReg', regDetails);       
  });
  
}

function processData(data) {
  var xmlDoc = txtToXmlDoc(data);
  var messageType=xmlDoc.getElementsByTagName("messageType")[0].firstChild.nodeValue;
  switch (messageType)
  {
    case 'listExperiments' :
      var eHtml = xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
      $('#allocationSection').html(eHtml);
      $('#exptList').show();
      setEListControls();
      break;
    case 'getExptAllocationView':
      var vHtml = xmlDoc.getElementsByTagName("content")[0].firstChild.nodeValue;
      $('#allocationSection').html(vHtml);  
      $('#allocations').show();
      setAllocationsControls();
      break;
  }
}

function sendAction(messageType, content) {
  // content can be single value or array
  paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['exptId'] = exptId;
  paramSet['stepId'] = stepId;
  paramSet['messageType'] = messageType;
  paramSet['content'] = content;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/step1AllocationView.php',
    data: paramSet,
    dataType: 'text',
    success: function(data) { processData(data); }
  });      
}

//------------------------------------------------------------------------------
//  DOM ready
//------------------------------------------------------------------------------
$(document).ready(function() {
  setUI();
  uid=$('#hiddenUID').text();
  fName=$('#hiddenfName').text();
  sName=$('#hiddensName').text();
  permissions=$('#hiddenPermissions').text();
  email = $('#hiddenEmail').text();
  stepId = $('#hiddenStepId').text();

  // get experiment list
  $('#name').html(fName);
  messageType = 'listExperiments';
  content = '';
  sendAction(messageType, content);
});

