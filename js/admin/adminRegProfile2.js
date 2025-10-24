var uid;
var permissions;
var fName;

function post_to_url(path, params) {
    var method = "post"; // Set method to post by default

    // The rest of this code assumes you are not using a library.
    // It can be made less wordy if you use one.
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

function setProfileControls() {
  $('.tab').removeClass('active');
  $('.tabContent').removeClass('active');
  $('#tabOne').addClass('active');
  $('#tabOne').next('div').addClass('active');
  $('.formRow').css('display','none');
  $('.currentExperiments').on('click', 'h3', function(event){
    //alert($(this).text());
    if ($(this).hasClass('closed')) {
      $(this).parent().children('.formRow').css('display','inline-block');
      $(this).parent().children('p').css('display','inline-block');
      $(this).removeClass('closed').addClass('open');
    } 
    else {
      $(this).parent().children('.formRow').css('display','none');
      $(this).parent().children('p').css('display','none');
      $(this).removeClass('open').addClass('closed');
    }
  });
  $('.deleteFormField').on('click', function(event) {
    var idArray=$(this).parent().attr('id').split('_');
    $('#specialAction').val('1');  // delete
    $('#qNo').val(idArray[0]);
    $('#optionNo').val(idArray[2]);
    $('#amendProfileDef').submit();
  });
  $('.addFormField').on('click', function(event) {
    var idArray=$(this).parent().attr('id').split('_');
    $('#specialAction').val('2');  // add
    $('#qNo').val(idArray[0]);
    $('#optionNo').val(idArray[2]);
    $('#optionValue').val($(this).parent().find('input').val());    
    $('#amendProfileDef').submit();
  });
  $('.select').on('change', function(event) {
    $('#amendProfileDef').submit();    
  });
}

function showProfileAdmin(profileHtml) {
  $('#userProfileSection').html(profileHtml);
  setProfileControls();
}
//------------------------------------------------------------------------------
//  DOM ready
//------------------------------------------------------------------------------
$(document).ready(function() {
  uid = $('#hiddenUID').text();
  permissions = $('#hiddenPermissions').text();
  fName = $('#hiddenfName').text();
  $('#name').html(fName);
  var paramSet = {};
  paramSet['uid'] = uid;
  paramSet['permissions'] = permissions;
  $.ajax({
    type: 'GET',
    url: '/webServices/admin/getProfileControls.php',
    data: paramSet,
    dataType: 'text',
    success: function(data) { showProfileAdmin(data); }
  });
});

