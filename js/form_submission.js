jQuery(document).ready(function($) {
    // make code pretty
    window.prettyPrint && prettyPrint();

    // hack for iPhone 7.0.3 multiselects bug
    if(navigator.userAgent.match(/iPhone/i)) {
        $('select[multiple]').each(function(){
            var select = $(this).on({
                "focusout": function(){
                    var values = select.val() || [];
                    setTimeout(function(){
                        select.val(values.length ? values : ['']).change();
                    }, 1000);
                }
            });
            var firstOption = '<option value="" disabled="disabled"';
            firstOption += (select.val() || []).length > 0 ? '' : ' selected="selected"';
            firstOption += '>Select ' + (select.attr('title') || 'Options') + '';
            firstOption += '</option>';
            select.prepend(firstOption);
        });
    }

    $('#multiselect1').multiselect();

    $('.hide_attributes_form').submit( function () { 
    	$('#loadingmessage').show();
    	var ajax_url = object.ajaxurl;
    	var selected_hide_attributes = jQuery('#multiselect1_to').val();
    	console.log(selected_hide_attributes);
        $.ajax({
			url:ajax_url,
			data: {
		        'action': "save_sel_hidden_attributes",
		        'attr':  selected_hide_attributes
		    },
			type:"post", // POST
			success:function(data){
				console.log(data);
				$('#loadingmessage').hide();
			}
		});
		return false;              
    });
    
});