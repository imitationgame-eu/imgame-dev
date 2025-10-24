/* 
  override core jquery-mobile to support 'normal' web page view on mobile registrationViews
  when viewed on e.g iPad landscape and above
 */

$(document).bind('mobileinit', function(){
  $.extend(  $.mobile , {
    defaultPageTransition: "none"
  });
  $.mobile.selectmenu.prototype.options.initSelector = ".mobileSelect"; // for selects and flip switches
});


