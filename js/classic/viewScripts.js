

function validateRegForm() {
  $('#regB').attr("disabled","disabled").addClass("greyed");
  var rp2Value=$('#rp2').val();
  if ( rp2Value.length >5) {
    if ($('#rp1').val()==$('#rp2').val()) {
      var validate=true;
      if ($('#firstName').val()=='') {validate=false;}
      if ($('#lastName').val()=='') {validate=false;}
      if ($('#rEmail').val()=='') {validate=false;}
      if (validate) {
        $('#regB').removeClass('greyed');
        $('#regB').removeAttr('disabled');
      }
    }
  }    
}

function validateLoginForm() {
  $('#loginB').attr("disabled","disabled").addClass("greyed");
  var validate=true;
  if ($('#p1').val()=='') {validate=false;}
  if ($('#emailT').val()=='') {validate=false;}
  if (validate) {
    $('#loginB').removeClass('greyed');
    $('#loginB').removeAttr('disabled');
  }    
}

$(document).ready(function() {
    var hName = window.location.hostname;
    var hn = hName.split(".");
    if (hn == 'www') {
      hName = hName.substr(3, length(hName)-4);
    }
    var htmlStr='<p>Unfortunately an error has occurred. Please try again (by clicking the email-link you used just now) in 10 minutes or so. If you experience a subsequent error then please contact <a href="mailto:igrtadmin@'+hName+'">the imgame administrator</a>.</p>';
    $('#errorMsg').html(htmlStr);
    // 14. Show/hide registration form
    $('.registerForm h3').addClass('closed');
    $('.registerForm .formRow').hide();
    $('.registerForm .buttonBlue').hide();

    $('.registerForm').on('click', 'h3', function(event) {
        if ($(this).hasClass('closed')) {
        $(this).removeClass('closed').addClass('open').parent().find('.formRow').css('display','inline-block');
                    $(this).parent().parent().find('.buttonBlue').css('display','inline-block');
        }
        else {
        $(this).removeClass('open').addClass('closed').parent().find('.formRow').css('display','none');
                    $(this).parent().parent().find('.buttonBlue').css('display','none');
        }
    });
    // TODO - disable by default on production - this helps iMacros scripts
    // $('#loginB').attr("disabled","disabled").addClass("greyed");
    $('#regB').attr("disabled","disabled").addClass("greyed");


    $('.logF').on ({            
      keyup: function process(e) {
          validateLoginForm();
        }
      },
      'input.password'
    );
    $('.logF').on(
        {            
            keyup: function process(e)
            {
                validateLoginForm();
            }
        },
        'input.email'
    );

    $('.regForm').on(
        {            
            keyup: function process(e)
            {
                validateRegForm();
            }
        },
        'input.password'
    );
    $('.regForm').on(
        {            
            keyup: function process(e)
        {
                validateRegForm();
        }
        },
        'input.text'
    );
    $('.regForm').on(
        {            
            keyup: function process(e)
        {
                validateRegForm();
        }
        },
        'input.email'
    );
    // in case of pre-population
    // TODO - disable by default on production - this helps iMacros scripts
    //validateLoginForm();
    //validateRegForm();
});

