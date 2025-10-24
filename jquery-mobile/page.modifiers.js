/*
 * make content full-height, after jquery-mobile has worked the magic
 *   
 */
$(document).on( "pagecontainershow", function(){
    ScaleContentToDevice();    
    $(window).on("resize orientationchange", function(){
        ScaleContentToDevice();
    })
});

function ScaleContentToDevice(){
    scroll(0, 0);
    var content = $.mobile.getScreenHeight() - $(".ui-header").outerHeight() - $(".ui-footer").outerHeight() - $(".ui-content").outerHeight() + $(".ui-content").height();
    $(".ui-content").height(content);
}
