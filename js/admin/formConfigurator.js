// many global variables already in adminNavigate.js
var activeControl;

// <editor-fold defaultstate="collapsed" desc=" data, ajax and UI flow-control">

$(document).ready(function() {
  activeControl = $('#hiddenActiveControl').text();
  // now update UI DOM with jQM
  $('#container').trigger('create');
  // set UI bindings after jQM decoration to avoid unwanted event firings when decorating the DOM
  setUIBindings();
});

function setUIBindings() {
  $('.accordionControl').on('collapsibleexpand', function (event) {
    var id = $(this).attr('id');
    var status = 0;
    uiUpdate(id, status);
    event.stopPropagation();
  });
  $('.accordionControl').on('collapsiblecollapse', function (event) {
    var id = $(this).attr('id');
    var status = 1;
    uiUpdate(id, status);
    event.stopPropagation();
  });
  $('.selector').on('change', function(event) {
    var id = $(this).attr('id');
    storeActiveControl(id);
    var selection = $(this).val();
    selectValueUpdate(id, selection);
    event.stopPropagation();
  });
  $('.classFS').on('change', function(event){
    var id = $(this).attr('id');
    storeActiveControl(id);
    var selection = $(this).val();
    toggleFSValue(id, selection);
    event.stopPropagation();
  });
  $('.classTA').on('keyup', function(event){
    var id = $(this).attr('id');
    storeActiveControl(id);
    var textValue = encodeURIComponent($(this).val());
    taValueUpdate(id, textValue);
    event.stopPropagation();
  });
  $('.optionsButton').on('click', function(event) {
    var id = $(this).attr('id');
    storeActiveControl(id);
    buttonClick(id);
    event.stopPropagation();
  });  
  $('#reloadB').on('click', function(event) {
    reloadPage(); // in adminNavigate.js
  });
  $('#'+activeControl).focus();
}

function storeActiveControl(controlName) {
  activeControl = controlName;
  paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['controlName'] = controlName;
  paramSet['exptId'] = exptId;
  paramSet['formType'] = formType;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/formStoreActiveControl.php',
    data: paramSet,
    dataType: 'text',
    error: function(xhr, textStatus, error) { uiDataError(xhr, error, textStatus, this.url); },
    success: function(data) { uiDataSuccess(data); }
  });  
}

function buttonClick(controlName) {
  paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['controlName'] = controlName;
  paramSet['exptId'] = exptId;
  paramSet['formType'] = formType;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/formButtonClickOperation.php',
    data: paramSet,
    dataType: 'text',
    error: function(xhr, textStatus, error) { uiDataError(xhr, error, textStatus, this.url); },
    success: function(data) { reloadPage(); }
  });  
}


function taValueUpdate(controlName, textValue) {
  paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['controlName'] = controlName;
  paramSet['textValue'] = textValue;
  paramSet['exptId'] = exptId;
  paramSet['formType'] = formType;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/formTAValueUpdate.php',
    data: paramSet,
    dataType: 'text',
    error: function(xhr, textStatus, error) { uiDataError(xhr, error, textStatus, this.url); },
    success: function(data) { uiDataSuccess(data); }
  });
}

function toggleFSValue(controlName, selection) {
  paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['controlName'] = controlName;
  paramSet['selection'] = selection;
  paramSet['exptId'] = exptId;
  paramSet['formType'] = formType;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/formToggleFSValue.php',
    data: paramSet,
    dataType: 'text',
    error: function(xhr, textStatus, error) { uiDataError(xhr, error, textStatus, this.url); },
    success: function(data) { uiDataSuccess(data); }
  });          
}

function selectValueUpdate(controlName, selection) {
  paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['controlName'] = controlName;
  paramSet['selection'] = selection;
  paramSet['exptId'] = exptId;
  paramSet['formType'] = formType;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/formSelectValueUpdate.php',
    data: paramSet,
    dataType: 'text',
    error: function(xhr, textStatus, error) { uiDataError(xhr, error, textStatus, this.url); },
    success: function(data) { uiDataSuccess(data); }
  });        
}

function uiUpdate(controlName, status) {
  paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  paramSet['controlName'] = controlName;
  paramSet['status'] = status;
  paramSet['exptId'] = exptId;
  paramSet['formType'] = formType;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/uiFormControlUpdate.php',
    data: paramSet,
    dataType: 'text',
    error: function(xhr, textStatus, error) { uiDataError(xhr, error, textStatus, this.url); },
    success: function(data) { uiDataSuccess(data); }
  });      
}

function uiDataSuccess(data) {
  // not expecting data 
}

function uiDataError(xhr, error, textStatus, referer) {  
}


// </editor-fold>
