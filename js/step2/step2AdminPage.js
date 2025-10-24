
$(document).ready(function() {
  $('.tab').removeClass("active");
  $('.tabContent').hide();
  $('.tab').click(function(e) {
    $('.tabContent').hide();
    $('.tab').removeClass("active");
    $(this).addClass("active");
    $(this).next('.tabContent').show();
  }); 
  $('#tabOne').addClass("active");
  $('#tabOneContent').show();
});

