//------------------------------------------------------------------------------
//  Section (Tab) Two (Forms definition) 
//  [configStage class='configSaved'] 
//------------------------------------------------------------------------------

function createFormsSectionViewState() {
  formsFlagCnt = 0;
  $('#formsList .currentExperiments').find('.formAccordion').each( function(e) {
    var formTemp = new Array();
    var formId = $(this).parent().attr('id');
    formTemp[0] = formId;
    formTemp[1] = false;
    var pageCnt = 0;
    var pageList = new Array();
    var formId = '#' + $(this).parent().attr('id');
    $(formId).find('.pageAccordionControl').each( function(e) {
      var pageTemp = new Array();
      pageTemp[0] = $(this).attr('id');
      pageTemp[1] = false;
      var pageId = '#' + pageTemp[0];
      var qCnt = 0;
      var qList = new Array();
      $(pageId).next('.pageWrapper').find('.questionAccordionControl').each(function(e) {
        var qTemp = new Array();
        qTemp[0] = $(this).attr('id');
        qTemp[1] = false;
        qList[qCnt++] = qTemp;
      });
      pageTemp[2] = qCnt;
      pageTemp[3] = qList;
      pageList[pageCnt++] = pageTemp;
    });
    formTemp[2] = pageCnt;
    formTemp[3] = pageList;
    formsViewState[formsFlagCnt++] = formTemp;
  });
}

function setFormViewState(fId, flag) {
  for (var i=0; i<formsFlagCnt; i++) {
    var formTemp = formsViewState[i];
    if (formTemp[0] == fId) { 
      formTemp[1] = flag; 
    }
    formsViewState[i] = formTemp;
  }
}

function setFormPagesViewState(pId, flag) {
  for (var i=0; i<formsFlagCnt; i++) {
    var formTemp = formsViewState[i];
    var pageList = formTemp[3];
    for (var j=0; j<formTemp[2]; j++) {
      var pageTemp = pageList[j];
      if (pageTemp[0] == pId) { 
        pageTemp[1] = flag;
      }
      pageList[j] = pageTemp;
    }
    formTemp[3] = pageList;
    formsViewState[i] = formTemp;
  }
}

function setFormQuestionsViewState(qId, flag) {
  for (var i=0; i<formsFlagCnt; i++) {
    var formTemp = formsViewState[i];
    var pageList = formTemp[3];
    for (var j=0; j<formTemp[2]; j++) {
      var pageTemp = pageList[j];
      for (var k=0; k<pageTemp[2]; k++) {
        var questionTemp = new Array();
        questionTemp = pageTemp[3][k];
        if (questionTemp[0] == qId) { 
          questionTemp[1] = flag;
          pageTemp[3][k] = questionTemp;
        }
      }
      pageList[j] = pageTemp;
    }
    formTemp[3] = pageList;
    formsViewState[i] = formTemp;
  }
}

function applyFormsSectionViewState() {
  for (var i=0; i<formsFlagCnt; i++) {
    var formTemp = formsViewState[i];
    var fId = '#' + formTemp[0];
    if (formTemp[1]) {
      $(fId).find('.formAccordion').removeClass('closed');
      $(fId).find('.formAccordion').addClass('open');
      $(fId).find('.formWrapper').show();
    }
    else {
      $(fId).find('.formAccordion').removeClass('open');
      $(fId).find('.formAccordion').addClass('closed');
      $(fId).find('.formWrapper').hide();      
    }
    var pageList = formTemp[3];
    for (var j=0 ; j<formTemp[2]; j++) {
      var pageTemp = pageList[j];
      var pId = '#' + pageTemp[0];
      if (pageTemp[1]) {
        $(pId).removeClass('closed');
        $(pId).addClass('open');
        $(pId).parent().next('.pageWrapper').show();
      }
      else {
        $(pId).removeClass('open');
        $(pId).addClass('closed');
        $(pId).parent().next('.pageWrapper').hide();
      }
      for (var k=0; k<pageTemp[2]; k++) {
        var questionTemp = new Array();
        questionTemp = pageTemp[3][k];
        var qId = '#' + questionTemp[0];
        if (questionTemp[1]) {
          $(qId).removeClass('closed');
          $(qId).addClass('open');
          $(qId).parent().next('.questionWrapper').show();         
        }
        else {
          $(qId).removeClass('open');
          $(qId).addClass('closed');
          $(qId).parent().next('.questionWrapper').hide();                   
        }
      }
    }
  }
}

function SetFormsPage(formsHtml) {
  $('#formsList').html(formsHtml);
  $('#formsList').unbind();
  //close all forms
  $('#formsList .currentExperiments').find('.formAccordion').each( function(e) {
//    $(this).parent().removeClass('active');
    $(this).removeClass('open');
    $(this).addClass('closed');
    $(this).next('.formWrapper').hide();    
  });
  // close all form-registrationViews
  $('#formsList .currentExperiments').find('.pageAccordion').each( function(e) {
//    $(this).parent().removeClass('active');
    $(this).removeClass('open');
    $(this).addClass('closed');
    $(this).next('.pageWrapper').hide();    
  });
  // close all questions
  $('#formsList .currentExperiments').find('.questionAccordionControl').each( function(e) {
//    $(this).parent().removeClass('active');
    $(this).removeClass('open');
    $(this).addClass('closed');
    $(this).parent().next('.questionWrapper').hide();    
  });  
  // now hook up accordion controls
  $('#formsList .currentExperiments').find('.formAccordion').click( function(e) {
    if ($(this).hasClass('open')) {
      $(this).removeClass('open').addClass('closed');
      $(this).next('.formWrapper').hide();
      setFormViewState($(this).parent().attr('id'), false);
    }
    else {
      $(this).removeClass('closed').addClass('open');
      $(this).next('.formWrapper').show();
      setFormViewState($(this).parent().attr('id'), true);
    }
  });
  $('#formsList .currentExperiments').find('.pageAccordionControl').click (function(e){
    if ($(this).parent().hasClass('open')) {
      $(this).parent().removeClass('open').addClass('closed');
      $(this).parent().next('.pageWrapper').hide();
      setFormPagesViewState($(this).attr('id'), false);
    }
    else {
      $(this).parent().removeClass('closed').addClass('open');
      $(this).parent().next('.pageWrapper').show();
      setFormPagesViewState($(this).attr('id'), true);
    }
  });
  $('#formsList .currentExperiments').find('.questionAccordionControl').click(function(e){
    if ($(this).hasClass('open')) {
      $(this).removeClass('open').addClass('closed');
      $(this).parent().next('.questionWrapper').hide();
      setFormQuestionsViewState($(this).attr('id'), false);
    }
    else {
      $(this).parent().next('.questionWrapper').show();
      $(this).removeClass('closed').addClass('open');
      setFormQuestionsViewState($(this).attr('id'), true);
    }
  });
  $('.formRow').on('click', 'input', function(e) {
    var buttonDetails=$(this).attr('id').split('_');
    if (buttonDetails[1]=='SaveB') {
      $('#formList').unbind();
      $('#configureFormsSection').hide();
      messageType = 'saveForms';
      content = '';
      sendAction(messageType, content);      
    }
  });
  // checkboxes
  $('.formRow').on({
      click: function sendStateToListener(e) {
        if (!blockBlur) {
          //console.log('check' + $(this).attr('id'));
          messageType = 'formCheck';
          var contentArray = {};
          contentArray[0] = $(this).attr('id');
          contentArray[1] = $(this).prop('checked');
          content = contentArray;
          sendFormAction(messageType, content);
        }
      }
    },
    'input.checkboxButton'
  ); 
  // question and option modifiers  
  $('#formsList .currentExperiments').find('.addQuestion').click( function processAQM(e) {    
    var aID=$(this).attr('id');
    var aDetails=aID.split('_');
    var formName = aDetails[2];
    var pageNo = aDetails[3];
    var newQNo = aDetails[4];
    // adjust front-end structure to track accordions etc
    switch (aDetails[0]) {
      case 'add':
        for (var i=0; i<formsFlagCnt; i++) {
          var formTemp = formsViewState[i];
          if (formTemp[0] == formName) {
            var pageList = formTemp[3];
            var pageTemp = pageList[pageNo];
            var qCnt = pageTemp[2];
            var qList = pageTemp[3];
            var qTemp = {};
            qTemp[0] = formName + '_q_' + pageNo + '_' +newQNo;
            qTemp[1] = true; // adding, so must want to be able to see it!
            focusControlId = qTemp[0];  //focus on new question
            qList[newQNo] = qTemp;
            pageTemp[3] = qList;
            pageTemp[2] = qCnt + 1;            
            pageList[pageNo] = pageTemp;
            formTemp[3] = pageList;
            formsViewState[i] = formTemp;
            messageType = 'addFormQuestion';
            var contentArray = {};
            contentArray[0] = 'add';
            contentArray[1] = pageNo;
            contentArray[2] = newQNo;            
            contentArray[3] = formName;
            contentArray[4] = focusControlId;
            content = contentArray;
            sendFormAction(messageType, content);      
          }
        }        
      break;
      case 'ins':
        for (var i=0; i<formsFlagCnt; i++) {
          var formTemp = formsViewState[i];
          if (formTemp[0] == formName) {
            var pageList = formTemp[3];
            var pageTemp = pageList[pageNo];
            var qCnt = pageTemp[2];
            var qList = pageTemp[3];
            var tempQList = [];
            var qTemp = new Array();            
            var oldListItem = {};
            for (var k=0; k<=newQNo; k++) {
              tempQList[k] = qList[k];             
            }
            var nextQNo = +newQNo; // important to force as int
            ++nextQNo;
            qTemp[0] = formName + '_q_' + pageNo + '_' + nextQNo;
            qTemp[1] = true;
            tempQList[nextQNo] = qTemp; // new insertion
            // now build tail of list
            var remainingCnt = qCnt - nextQNo; 
            for (var k=0; k<remainingCnt; k++) {
              var oldPtr = +nextQNo;
              oldPtr = oldPtr + k;
              var newPtr = oldPtr + 1;
              oldListItem = qList[oldPtr]; 
              qTemp = new Array();
              qTemp[0] = formName + '_q_' + pageNo + '_' + newPtr;
              qTemp[1] = oldListItem[1];
              tempQList[newPtr] = qTemp;
            }
            focusControlId = formName + '_' + pageNo + '_qType_' + nextQNo ;   // focus on new question type selector  
            pageTemp[3] = tempQList;
            pageTemp[2] = qCnt + 1;
            pageList[pageNo] = pageTemp;
            formTemp[3] = pageList;
            formsViewState[i] = formTemp;
            messageType = 'addFormQuestion';
            var contentArray = {};
            contentArray[0] = 'ins';
            contentArray[1] = pageNo;
            contentArray[2] = newQNo; 
            contentArray[3] = formName;
            contentArray[4] = focusControlId;
            content = contentArray;
            sendFormAction(messageType, content);      
          }
        }
      break;
    }  
  });
  $('#formsList .currentExperiments').find('.delQuestion').click( function processDQM(e) {    
    var aID=$(this).attr('id');
    var aDetails=aID.split('_');
    var formName = aDetails[2];
    var pageNo = aDetails[3];
    var delQNo = aDetails[4];
    // adjust front-end structure to track accordions etc
//    for (var i=0; i<formsFlagCnt; i++) {
//      var formTemp = formsViewState[i];
//      if (formTemp[0] == formName) {
//        var pageList = formTemp[3];
//        var pageTemp = pageList[pageNo];
//        var qCnt = pageTemp[2];
//        var qList = pageTemp[3];
//        var tempQList = [];
//        for (var k=0; k<delQNo; k++) {
//          tempQList[k] = qList[k];             
//        }
//        // now build tail of list ignoring deleted item
//        // shuffle visibilities down due to deletion but account for id renumbering
//        var remainingCnt = qCnt - delQNo - 1; 
//        for (var k=0; k<remainingCnt; k++) { 
//          var ptr = +delQNo+k;  // int
//          var oldPtr = +ptr;
//          ++oldPtr;          
//          oldListItem = qList[oldPtr]; 
//          qTemp = new Array();
//          qTemp[0] = formName + '_q_' + pageNo + '_' + ptr;
//          qTemp[1] = oldListItem[1];
//          tempQList[ptr] = qTemp;
//        }
//        var qTemp = new Array();
//        // make  Q after the deleted one visible, unless it's the last question, in which case the last remaining is made visible
//        --qCnt;
//        if (delQNo < qCnt) {
//          ptr = +delQNo;
//        }
//        else {
//          ptr = +qCnt;
//          --ptr;
//        }
//        qTemp = tempQList[ptr];
//        qTemp[1] = true;
//        tempQList[ptr] = qTemp;
//        focusControlId = formName + '_' + pageNo + '_qType_' + ptr ;   // focus on next and visible question type selector            
//        pageTemp[3] = tempQList;
//        pageTemp[2] = qCnt;
//        pageList[pageNo] = pageTemp;
//        formTemp[3] = pageList;
//        formsViewState[i] = formTemp;
//      }
//    } 
    // now send to back-end
    messageType = 'delFormQuestion';
    var contentArray = {};
    contentArray[0] = pageNo;
    contentArray[1] = delQNo; 
    contentArray[2] = formName;
    contentArray[3] = focusControlId;
    content = contentArray;
    sendFormAction(messageType, content);      
  });
  $('#formsList .currentExperiments').find('.addPage').click( function processAPM(e) {    
    var aID=$(this).attr('id');
    var aDetails=aID.split('_');
    var formName = aDetails[2];
    var pageNo = aDetails[3];
    // adjust front-end structure to track accordions etc
    for (var i=0; i<formsFlagCnt; i++) {
      var formTemp = formsViewState[i];
      if (formTemp[0] == formName) {
        // NB: as header page is page0, treat add and delete as 1-indexed not zero-indexed
        var pageList = formTemp[3];
        var pageCnt = pageList.length;
        var tempPageList = new Array();
        var addPtr = +pageNo;
        ++addPtr;
        for (var k=0; k<=addPtr; k++) {
          tempPageList[k] = pageList[k];
        }
        var pageTemp = new Array();
  //      var newPageNo = +addPtr;
  //      ++newPageNo;
        pageTemp[0]= formName+'_page_'+addPtr;
        pageTemp[1]=true; //make new one visible
        pageTemp[2]=0;
        pageTemp[3]=new Array();
        tempPageList[addPtr] = pageTemp;
        var remainingCnt = pageCnt - addPtr - 1;
        if (remainingCnt > 0) {
          for (var k=0; k<remainingCnt; k++) {
            var ptr=addPtr + k;
            var tempPage = pageList[ptr];
            var newPtr = +ptr;
            ++newPtr;
            pageTemp = new Array();
            pageTemp[0]= formName+'_page_'+newPtr;
            pageTemp[1]=tempPage[1];  // shuffle visibility from existing stack
            pageTemp[2]=tempPage[2];
            pageTemp[3]=tempPage[3];
            tempPageList[newPtr] = pageTemp;          
          }
        }
        // now get footer page
        tempPage = new Array();
        tempPageList[pageCnt] = pageList[pageCnt - 1];
        pageList = tempPageList;
        ++formTemp[2];
        formTemp[3] = pageList;
        formsViewState[i] = formTemp;
      }
    }
    // now send to back-end
    messageType = 'addFormPage';
    var contentArray = {};
    contentArray[0] = formName;
    contentArray[1] = pageNo;
    focusControlId = 'noFocus';
    contentArray[2] = focusControlId;
    content = contentArray;
    sendFormAction(messageType, content);      
  });
  $('#formsList .currentExperiments').find('.delPage').click( function processDPM(e) {    
    var aID=$(this).attr('id');
    var aDetails=aID.split('_');
    var formName = aDetails[2];
    var delPageNo = aDetails[3];
    // adjust front-end structure to track accordions etc
    for (var i=0; i<formsFlagCnt; i++) {
      var formTemp = formsViewState[i];
      if (formTemp[0] == formName) {
        // NB: as header page is page0, treat add and delete as 1-indexed not zero-indexed
        var pageList = formTemp[3];
        var pageCnt = pageList.length;
        var tempPageList = new Array();
        for (var k=0; k<delPageNo; k++) {
          tempPageList[k] = pageList[k];
        }
        var remainingCnt = pageCnt - delPageNo - 2; // -2 accounts for (final message page and dec pageCnt) [final msg page has different id format]
        if (remainingCnt > 0) {
          for (var j=0; j<remainingCnt; j++) {
            var ptr=+delPageNo + j;
            var oldPtr = +ptr;
            ++oldPtr;
            var tempPage = pageList[oldPtr];
            pageTemp = new Array();
            pageTemp[0]= formName+'_page_'+ptr;
            pageTemp[1]=tempPage[1];  // shuffle visibility from existing stack
            pageTemp[2]=tempPage[2];
            pageTemp[3]=tempPage[3];
            tempPageList[ptr] = pageTemp;          
          }
        }
        // now get footer page
        tempPage = new Array();
        tempPageList[pageCnt - 2] = pageList[pageCnt - 1];
        pageList = tempPageList;
        --formTemp[2];
        formTemp[3] = pageList;
        formsViewState[i] = formTemp;
       }
    }
    // now send to back-end
    messageType = 'delFormPage';
    var contentArray = {};
    contentArray[0] = formName;
    contentArray[1] = delPageNo;
    focusControlId = 'noFocus';
    contentArray[2] = focusControlId;
    content = contentArray;
    sendFormAction(messageType, content);      
  });
  $('.form_t_single').on( {            
    focusout: function sendTextToListener(e) {
      if (!blockBlur) {
        messageType = 'fdText';
        var contentArray = {};
        contentArray[0] = $(this).attr('id'); 
        contentArray[1] = $(this).val(); 
        content = contentArray;
        sendFormAction(messageType, content);
      }
      else {
        blockBlur = false;
      }
    }
    },
    'input.text'
  );
  $('.form_t_multi').on( {            
    focusout: function sendTextToListener(e) {
      if (!blockBlur) {
        messageType = 'fdText';
        var contentArray = {};
        contentArray[0] = $(this).attr('id'); 
        contentArray[1] = $(this).val(); 
        content = contentArray;
        sendFormAction(messageType, content);
      }
      else {
        blockBlur = false;
      }
    }
    },
    'textarea.text'
  );
  $('.questionWrapper').on( {
    change: function sendSelectToListener(e) {
        var details=$(this).attr('id').split('_');
        messageType = 'qdSelect';
        focusControlId = $(this).attr('id');
        var contentArray = {};
        contentArray[0] = details[0];
        contentArray[1] = details[1];
        contentArray[2] = details[2];
        contentArray[3] = details[3];
        contentArray[4] = $(this).val();
        contentArray[5] = (focusControlId > '') ? focusControlId : 'unset';
        content = contentArray;
        sendFormAction(messageType, content);
      }
    },
    'select'
  );
  $('.questionWrapper').on( {
      focusout: function sendTextToListener(e) {
        //console.log('focusOut optionLabel');
        if (!blockBlur) {
          var details=$(this).attr('id').split('_');
          messageType = 'qdText';
          focusControlId = $(this).attr('id');
          var contentArray = {};
          contentArray[0] = details[0];
          contentArray[1] = details[1];
          contentArray[2] = details[2];
          contentArray[3] = details[3];
          contentArray[4] = $(this).val();
          contentArray[5] = (focusControlId > '') ? focusControlId : 'unset';
          content = contentArray;
          sendFormAction(messageType, content);
          //console.log(messageType);
        }
        else {
          blockBlur = false;
        }
      }
    },
    'textarea.text'
  );
  $('.optionLabel').on( {            
      focusout: function sendTextToListener(e) {
        //console.log('focusOut optionLabel');
        if (!blockBlur) {
          var details=$(this).attr('id').split('_');
          messageType = 'qdText';
          focusControlId = $(this).attr('id');
          var contentArray = {};
          contentArray[0] = details[0];
          contentArray[1] = details[1];
          contentArray[2] = details[2];
          contentArray[3] = details[3];
          contentArray[4] = details[4];
          contentArray[5] = $(this).val();
          contentArray[6] = (focusControlId > '') ? focusControlId : 'unset';
          content = contentArray;
          sendFormAction(messageType, content);
          //console.log(messageType);
        }
        else {
          blockBlur = false;
        }
      }
    },
    'input.text'
  );
  $('.optionLabel').on( {            
      focusout: function sendTextToListener(e) {
        //console.log('focusOut optionLabel');
        if (!blockBlur) {
          var details=$(this).attr('id').split('_');
          messageType = 'qdText';
          focusControlId = $(this).attr('id');
          var contentArray = {};
          contentArray[0] = details[0];
          contentArray[1] = details[1];
          contentArray[2] = details[2];
          contentArray[3] = details[3];
          contentArray[4] = details[4];
          contentArray[5] = $(this).val();
          contentArray[6] = (focusControlId > '') ? focusControlId : 'unset';
          content = contentArray;
          sendFormAction(messageType, content);
          //console.log(messageType);
        }
        else {
          blockBlur = false;
        }
      }
    },
    'textarea.text'
  );

  // use survey/form checkboxes
  $('.currentExperiments').on({
    click: function toggle(e) {
      var contentArray = {};
      var buttonDetails = $(this).attr('id').split('_');
      if (buttonDetails[1] == 'use') {
        messageType = 'fdToggleUse';
        contentArray[0] = buttonDetails[0]; 
        contentArray[1] = $(this).prop('checked'); 
        content = contentArray;
        sendFormAction(messageType, content);    
      }     
    }
  },'input.checkboxButton');
  
  $('.aff').on('click', 'a', function(e){
    var aID=$(this).parent().attr('id');
    var contentArray = {};
    var details=aID.split('_');
    contentArray[0] = details[0];
    contentArray[1] = details[1];
    contentArray[2] = details[2];
    contentArray[3] = details[3];    
    contentArray[4] = (focusControlId > '') ? focusControlId : 'unset';
    content = contentArray;
    messageType = 'aff';
    sendFormAction(messageType, content);
  });
  $('.deleteOptionField').on('click', function(e){
    var aID=$(this).parent().attr('id');
    var contentArray = {};
    var details=aID.split('_');
    contentArray[0] = details[0];
    contentArray[1] = details[1];
    contentArray[2] = details[2];
    contentArray[3] = details[3];    
    contentArray[4] = (focusControlId > '') ? focusControlId : 'unset';
    content = contentArray;
    messageType = 'rff';
    sendFormAction(messageType, content);
  });
  
  
  blockBlur = false;
}


