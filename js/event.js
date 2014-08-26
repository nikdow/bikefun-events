jQuery(document).ready(function($){
    $('#name').focus();
    $.each(['', '_popup'], function(index, str) {
        $('#country'+str).bind('change', function () {
        if($('#country'+str).val()==="AU") {
          $('.state'+str).each(function(index, el) {
              $(el).removeClass('removed');
          });
       } else {
         $('.state'+str).each(function(index, el) {
             $(el).addClass('removed');
         });
        }
      });

    $('#saveButton'+str).bind('click', function (event) {
        var err = false;
        var errmsg = "";
        var field = $('#simpleTuring'+str);
        if(field && !field.prop("checked") ) {
          errmsg += 'You must tick the box that asks if you are not a robot.\n';
          if(!err) field.focus();
          err = true;
        }
        field = $('#email'+str);
        if(!checkEmail(field.val())) {
          errmsg += 'You must provide a valid email address.\n';
          if(!err) field.focus();
          err = true;
        }
        if(err) {
          alert(errmsg);
          return false;
        }
        if($('#name'+str).val()==="") $('#public'+str).prop('checked', false);
        var senddata = $(document.forms['register'+str]).serialize();
        $('#ajax-loading'+str).removeClass('farleft');
        $('#returnMessage'+str).html('&nbsp;');
        $('#saveButton'+str).prop('disabled', true);
        $.post(data.ajaxUrl, senddata, function( response ){
             var ajaxdata = $.parseJSON(response);
             if( ajaxdata.error ) {
                 $('#returnMessage'+str).html( ajaxdata.error );
                 $('#saveButton'+str).prop('disabled', false);
             } else if( ajaxdata.success ) {
                 $('#returnMessage'+str).html( ajaxdata.success );
             } else {
                 $('#returnMessage'+str).html ( ajaxdata );
             }
             $('#ajax-loading'+str).addClass('farleft');
          });
        ga('send', 'event', 'Registration', data.action);
      } );
    });
});

(function($) {
      

    function duplicate(responseJSON) {
      alert('sorry we already have that email address');
    }
    function afterSave() {
         $('#register').find('table').each(function(index, el) {
             el.empty();
         });
    }
    
    $(".tfdate").datepicker({
        dateFormat: 'D, d M yy',
        showOn: 'button',
        buttonImage: data.stylesheetUri + '/img/calendar.gif',
        buttonImageOnly: true,
        numberOfMonths: 3

        });

})(jQuery);

function checkEmail(inputvalue){	
var pattern=/^([a-zA-Z0-9_.-])+@([a-zA-Z0-9_.-])+\.([a-zA-Z])+([a-zA-Z])+/;
var bool = pattern.test(inputvalue);
return bool;
}
