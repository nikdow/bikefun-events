jQuery(document).ready(function($) {

    $('#wp-calendar span').hover( function () {
                    $(this).next().show();
                      },
                      function () {
                          $(this).next().hide();
                      });

    $('#wp-calendar div').hover( function () {
                            $(this).show();
                      },
                      function () {
                            $(this).hide();
                      });
    
    $('#embedClick').click( showEmbedCode );
    
});

function showEmbedCode() {
    $ = jQuery;
  $('#embedCalCode').addClass('removed');
  $('#embedCode').removeClass('removed'); 
}


function newSecret(post_id) { // request for email to event lister with new secret code
    var formdata = { 'action':'sendSecret', 'post_id':post_id };
    $ = jQuery;
    $.post ( data.ajaxurl, formdata, function(response) {
        var ajaxdata = $.parseJSON(response);
             if( ajaxdata.error ) {
                 $('#returnMessage').html( ajaxdata.error );    
             } else if( ajaxdata.success ) {
                 $('#returnMessage').html( ajaxdata.success );
             } else {
                 $('#returnMessage').html ( ajaxdata );
             }
    });
    return false;
}
