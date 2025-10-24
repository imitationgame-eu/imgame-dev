$(document).ready(function() {
  $('#loginForm').validate();
  $('#registerForm').validate();
  $('#resetForm').validate();
  var restartUID = $('#hiddenRestartUID').text();
  $('#restartUID').val(restartUID); // this value only exists when passed to login page from pre-step1 survey but needs to be injected into the form
  $.mobile.ajaxEnabled = false;
});
