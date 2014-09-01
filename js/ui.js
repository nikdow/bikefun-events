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
