function addRow(e,t){var r=jQuery("#"+e),a=jQuery("#"+t),i=jQuery(a).find("tbody").html();jQuery(r).find("tbody").append(i)}function deleteRow(e){for(var t=document.getElementById(e),r=t.rows.length,a=0;r>a;a++){var i=t.rows[a],c=i.cells[0].childNodes[0];if(null!=c&&1==c.checked){if(1>=r){alert("Cannot Remove all the Tier.");break}t.deleteRow(a),r--,a--}}}!function(e){function t(t){i||e.ajax({type:"POST",url:MyAjax.ajaxurl,data:{action:"wdcp_user_searchcredit_post",search:t},before:function(){i=!0},success:function(t){t.user_credit_list?e(".user-credits-list-row").html(t.user_credit_list):e(".user-credits-list-row").html(""),t.pagination?e(".credits-pagination").html(t.pagination):e(".credits-pagination").html(""),i=!1},dataType:"json"})}function r(){var r=e("#cuser-search").val();r=e.trim(r),t(r)}function a(t,a){e("#cuser-search").autocomplete({minLength:3,source:function(t,r){e.ajax({type:"POST",url:MyAjax.ajaxurl,data:{action:"wdcp_search_userterm_post",searchTerm:a,term:t.term},dateType:"json",before:function(){},success:function(t){var a=[];e.each(t,function(e,t){t.label=t,t.value=t,a.push(t)}),r(a)},complete:function(){}})},change:function(){r()}})}var i=!1;e(".remove-tiers").live("click",function(){var t=!1,r=e(this).closest("tr"),a=e(r).attr("data-creditid");a?confirm("are you sure? want to delete this item")&&(t||e.ajax({type:"POST",url:MyAjax.ajaxurl,data:{action:"wdcp_delete_credit_post",credit_id:a},before:function(){t=!0},success:function(a){"success"==a.status&&e(r).remove(),t=!1},dataType:"json"})):e(r).remove()}),e("#cuser-search").on("change",function(e){e.preventDefault(),r()}),e("#searchcredits-submit").on("click",function(e){e.preventDefault(),r()}),e("#cuser-search").on("keyup",function(){var t=e("#cuser-search").val();if("undefined"==typeof t)return!1;if(t=e.trim(t),t.length>=2)return!1;var r=0;a(r,t)})}(jQuery),jQuery(document).ready(function(e){var t;e("#credits_form #dataTable").on("click",".product-thumbnail",function(r){var a=e(this);r.preventDefault(),t=wp.media.frames.file_frame=wp.media({title:"Choose Credit Image",button:{text:"Choose Credit Image"},multiple:!1}),t.on("select",function(){var r=t.state().get("selection").first().toJSON(),i=r.id;e(a).closest(".creditrow").find(".credit_image").val(i),e("img",a).attr("src",r.url)}),t.open()})});
//# sourceMappingURL=admin-min.js.map