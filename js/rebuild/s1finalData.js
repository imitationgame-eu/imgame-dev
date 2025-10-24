$(document).ready(function() {
  $.post("/webServices/rebuild/s1finalData.php",{ msgType: "init"},function(data) {
    process(data);
  });    
});

function process(data) {
  $('#judgeContent').html(data);
  $('.tabContent').show();
}



