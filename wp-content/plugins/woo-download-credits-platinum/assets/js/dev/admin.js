


// function addRow(tableID,tableID1) {
//     var table = document.getElementById(tableID);
//     var table1 = document.getElementById(tableID1);
//     var rowCount = table.rows.length;
//     var row = table.insertRow(rowCount);
//     var colCount = table.rows[0].cells.length;
//     for(var i=0; i<colCount; i++) {
//         var newcell = row.insertCell(i);
//         newcell.innerHTML = table1.rows[0].cells[i].innerHTML;
//     }
//
// }

function addRow(tableID,tableID1) {
    var table = jQuery('#' +tableID );
    var table1 = jQuery('#' +tableID1 );
    var row = jQuery(table1).find('tbody').html();
    jQuery(table).find('tbody').append(row);
}

function deleteRow(tableID) {
    var table = document.getElementById(tableID);
    var rowCount = table.rows.length;
    for(var i=0; i<rowCount; i++) {
        var row = table.rows[i];
        var chkbox = row.cells[0].childNodes[0];
        if(null != chkbox && true == chkbox.checked) {
            if(rowCount <= 1) { 						// limit the user from removing all the fields
                alert("Cannot Remove all the Tier.");
                break;
            }
            table.deleteRow(i);
            rowCount--;
            i--;
        }
    }
}


(function($){

    var searching = false;

    function wdcp_show_search_result(searchTerm){
              if(!searching){
                    $.ajax({
                        type:"POST",
                        url:MyAjax.ajaxurl,
                        data:{action:'wdcp_user_searchcredit_post',search:searchTerm},
                        before:function(){ searching = true;},
                        success:function(rData){
                           // if(rData.status == 'success'){
                                if(rData.user_credit_list){
                                    $('.user-credits-list-row').html(rData.user_credit_list);
                                }else{
                                    $('.user-credits-list-row').html('');
                                }
                                if(rData.pagination){
                                    $('.credits-pagination').html(rData.pagination);
                                }else{
                                    $('.credits-pagination').html('');
                                }                                
                           // }
                            searching = false;
                        },
                        dataType: 'json'
                    });
              }        
    }

    function wdcp_change_user_result(){
        var searchTerm = $('#cuser-search').val();
        searchTerm = $.trim(searchTerm);
        wdcp_show_search_result(searchTerm);         
    }    


    function wdcpUserSearch(ul_i,searchTerm){
          $("#cuser-search").autocomplete({
              minLength: 3,
              source: function(req, add) {
                    $.ajax({
                        type: "POST",
                        url: MyAjax.ajaxurl,
                        data: {action:'wdcp_search_userterm_post',searchTerm :searchTerm,term:req.term},
                        dateType: 'json',
                        before : function(){

                        },
                        success: function(rData) {
                             var suggestions = [];
                             $.each(rData, function(i, val){
                                   val.label = val;
                                   val.value = val;
                                   suggestions.push(val);
                             });
                             add(suggestions);
                        },
                        complete: function() {

                        },                       
                    });
              },
                change: function(e, ui) {
                  wdcp_change_user_result();
                } 

       });
    }





    $('.remove-tiers').live('click',function(e){
        var deleting = false;
        var tableID = 'dataTable';
        var row = $(this).closest('tr');
        var credit_id = $(row).attr('data-creditid');

        if(credit_id){
            if(confirm('are you sure? want to delete this item')){
                if(!deleting){
                    $.ajax({
                        type:"POST",
                        url:MyAjax.ajaxurl,
                        data:{action:'wdcp_delete_credit_post',credit_id:credit_id},
                        before:function(){ deleting = true;},
                        success:function(rData){
                            if(rData.status == 'success'){
                                $(row).remove();
                            }

                            deleting = false;
                        },
                        dataType: 'json'
                    });
                }
            }
        }
        else{
            $(row).remove();
        }
    });


    $('#cuser-search').on('change',function(e){
        e.preventDefault();
        wdcp_change_user_result();
     });    



    $('#searchcredits-submit').on('click',function(e){
        e.preventDefault();
        wdcp_change_user_result();
    });    

      $("#cuser-search").on('keyup',function(e){
         var searchTerm = $('#cuser-search').val();
         if(typeof searchTerm == "undefined"){
             return false;
         }
         searchTerm = $.trim(searchTerm);
          if(searchTerm.length >= 2){
                return false;
          }
          var ul_i=0;
          wdcpUserSearch(ul_i,searchTerm);
       });




    


})(jQuery);

jQuery(document).ready(function($){
    var custom_uploader;
    $('#credits_form #dataTable').on('click','.product-thumbnail',function(e) {
        var obj = $(this);
        e.preventDefault();
        custom_uploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Credit Image',
            button: {
                text: 'Choose Credit Image'
            },
            multiple: false
        });
        custom_uploader.on('select', function() {
            var  attachment = custom_uploader.state().get('selection').first().toJSON();
            // console.log(attachment);
            var att_id = attachment.id;
            $(obj).closest('.creditrow').find('.credit_image').val(att_id);
            $('img',obj).attr('src',attachment.url);
        });
        custom_uploader.open();
    });
});
