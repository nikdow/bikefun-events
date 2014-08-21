jQuery(document).ready(function($)
{
$(".tfdate").datepicker({
    dateFormat: 'D, d M yy',
    showOn: 'button',
    buttonImage: stylesheetUri + '/img/calendar.gif',
    buttonImageOnly: true,
    numberOfMonths: 3
 
    });
});

