jQuery(document).ready(function($){
    $('#name').focus();
    $('#saveButton').click( validate );

    function validate() {
        var err = false;
        var errmsg = "";
        var field = $('#simpleTuring');
        if(field && !field.prop("checked") ) {
          errmsg += 'You must tick the box that asks if you are not a robot.\n';
          if(!err) field.focus();
          err = true;
        }
        field = $('#email');
        if(!checkEmail(field.val())) {
          errmsg += 'You must provide a valid email address.\n';
          if(!err) field.focus();
          err = true;
        }
        if(err) {
          alert(errmsg);
          return false;
        }
        ed = tinyMCE.get(wpActiveEditor)
        $('#editcontent').text(ed.getContent());
        ed.isNotDirty = true;
        $('#ajax-loading').removeClass('farleft');
        $('#returnMessage').html('&nbsp;');
        $('#saveButton').prop('disabled', true);
        $('#register').submit();
    }
    function afterAJAX( response ) {
        var ajaxdata = $.parseJSON(response);
             if( ajaxdata.error ) {
                 $('#returnMessage').html( ajaxdata.error );
                 $('#saveButton').prop('disabled', false);
             } else if( ajaxdata.success ) {
                 $('#returnMessage').html( ajaxdata.success );
             } else {
                 $('#returnMessage').html ( ajaxdata );
             }
             $('#ajax-loading').addClass('farleft');
    }

    var options = {
        success:       afterAJAX,    // post-submit callback 
        url:    data.ajaxUrl         // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php     
    }; 

    // bind form using 'ajaxForm' 
    $('#register').ajaxForm(options); 
    
    $(".bfdate").datepicker({
        dateFormat: 'D, d M yy',
        showOn: 'button',
        buttonImage: data.stylesheetUri + '/img/calendar.gif',
        buttonImageOnly: true,
        numberOfMonths: 3
        });
});


function checkEmail(inputvalue){	
var pattern=/^([a-zA-Z0-9_.-])+@([a-zA-Z0-9_.-])+\.([a-zA-Z])+([a-zA-Z])+/;
var bool = pattern.test(inputvalue);
return bool;
}

