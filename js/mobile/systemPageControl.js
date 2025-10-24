/*
 * make back-button/footer float to bottom of page even after a scroll
 *
 */

function setPageFunctions() {
  scaleContentToDevice();
  $(window).on("resize orientationchange scroll", function(){
    scaleContentToDevice();
  })

}

function scaleContentToDevice(){
  var h = window.innerHeight + 18;
  var pageYOffset = window.pageYOffset;


  var footerTop = pageYOffset + h - ($(".systemHeader").outerHeight() + $(".systemFooter").outerHeight())
  $('#footer').offset({top : footerTop, width: window.innerWidth});
}
