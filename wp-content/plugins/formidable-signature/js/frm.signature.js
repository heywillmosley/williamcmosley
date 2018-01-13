function frmSignatureJS(){
    'use strict';

    function loadSig(){
        if(typeof __FRMSIG == 'undefined'){
            return;
        }
        var sig = __FRMSIG;
        var sigCount = sig.length;
        for ( var i = 0, l = sigCount; i < l; i++ ) {
            sigInit(sig[i]);
        }
    }

    function sigInit(opts){
        var frmsigopts = {
            name:false,bgColour:opts.bg_color,
            penColour:opts.text_color,lineColour:opts.bg_color,
            defaultAction:'drawIt',validateFields:false
        };
        var w = opts.width;
        var sigField = jQuery(document.getElementById('frm_field_'+opts.id+'_container'));
        if ( sigField.length < 1 ) {
            return;
        }

        var sigPad = sigField.find('.sigPad');
        if(sigPad.is(':visible')){
            w = sigPad.width();
        }
        if( 0 < w < parseInt(opts.width)){
            sigPad.attr('style','max-width:'+ w +'px !important;');
            sigField.find('.typed input').attr('style','max-width:'+(w-11)+'px !important;');
            sigPad.find('canvas').attr('width',w-2);
            jQuery('input[name="item_meta['+opts.id+'][width]"]').val(w-2);
        }
        sigPad.signaturePad(frmsigopts);
    }

	return{
		init: function(){
            jQuery('.sigWrapper .typed').click(function(){
                jQuery(this).find('input').focus();
            });
            loadSig();
        }
    };
}

var frmSignature = frmSignatureJS();

jQuery(document).ready(function(){
	frmSignature.init();
});