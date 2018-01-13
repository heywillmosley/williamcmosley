!function(t){upfrontrjs.define(["scripts/redactor/ueditor-insert","scripts/redactor/ueditor-insert-utils","scripts/redactor/ueditor-image-insert-base","text!scripts/redactor/ueditor-templates.html"],function(i,e,r,a){var n=r.ImageInsertBase.extend({className:"ueditor-insert upfront-inserted_image-wrapper upfront-inserted_image-basic-wrapper",create_controlls:function(t){this.controlsData=[{id:"link",type:"dialog",icon:"link",tooltip:"Link image",view:this.getLinkView()},{id:"toggle_caption",type:"simple",icon:"caption",tooltip:"Toggle Caption",active:_.bind(this.get_caption_state,this)}],this.allow_alignment(t)&&this.controlsData.unshift({id:"style",type:"dialog",icon:"style",tooltip:"Alignment",view:this.getStyleView()}),this.createControls()},start:function(t){var i=this,e=Upfront.Media.Manager.open({multiple_selection:!1});return e.done(function(e,r){var a=i.getImageData(r);a.id=i.data.id,i.data.clear({silent:!0}),a.style=i.defaultData.style,a.variant_id="basic-image",i.$editor=t.closest(".redactor-box"),i.data.set(a)}),e},render:function(){var i=_.extend({},this.defaultData,this.data.toJSON()),e=i.style,r=this.data.get("alignment");if(e){i.style.label_id="ueditor-image-style-center",r&&(i.style.label_id="ueditor-image-style-"+r.vid,i.style.group.float=r.vid),i.image=this.get_proper_image(),i.style.group.width_cls=this.get_group_width_cls(i.image),0==i.show_caption&&(i.style.image.width_cls=Upfront.Settings.LayoutEditor.Grid.class+24);var a=(this.$el.find(".ueditor-insert-variant-group"),Upfront.Behaviors.GridEditor),n=t(".upfront-content-marker-contents"),s=parseFloat(t(".upfront-content-marker-contents>*").css("padding-left"))/a.col_size,o=parseFloat(t(".upfront-content-marker-contents>*").css("padding-right"))/a.col_size,l=Upfront.Util.grid.width_to_col(n.width(),!0),d=l-s-o,c=t(".upfront-content-marker-contents>*").width()/d;s=s?parseInt(s):0,o=o?parseInt(o):0,e&&e.group&&e.group.float&&("left"==e.group.float&&s>0?(i.style.group.marginLeft=(s-Math.abs(e.group.margin_left))*c,i.style.group.marginRight=0):"right"==e.group.float&&o>0?(i.style.group.marginRight=(o-Math.abs(e.group.margin_right))*c,i.style.group.marginLeft=0):"none"==e.group.float&&s>0&&(i.style.group.marginLeft=(s-Math.abs(e.group.margin_left)+Math.abs(e.group.left))*c,i.style.group.marginRight=0)),this.$el.html(this.tpl(i)),this.create_controlls(i.style.group.width_cls),this.controls.render(),this.$(".ueditor-insert-variant-group").append(this.controls.$el),this.make_caption_editable(),this.updateControlsPosition(),this.$(".ueditor-insert-variant-group").append('<a href="#" contenteditable="false" class="upfront-icon-button upfront-icon-button-delete ueditor-insert-remove"></a>')}},allow_alignment:function(t){if("undefined"==typeof t)return!1;var i=parseInt(t.replace("c",""),10),e=Upfront.Util.grid.width_to_col(this.$editor.width());return i+2<=e},control_events:function(){this.listenTo(this.controls,"control:ok:style",function(t,i){t._style&&(this.data.set("variant_id",t.variant_id),this.data.set("alignment",t._style),t.data.set("selected",t.variant_id)),i.close()})},get_group_width_cls:function(t){var i=Upfront.Util.grid.width_to_col(t.width),e=Upfront.Util.grid.width_to_col(this.$editor.width());return i+1<=e?Upfront.Settings.LayoutEditor.Grid.class+i:Upfront.Settings.LayoutEditor.Grid.class+e},get_proper_image:function(){var t=this.data.toJSON(),i=t.imageFull;Upfront.Settings.LayoutEditor.Grid,Upfront.Util.grid.width_to_col(this.$editor.width());return _.isEmpty(((t||{}).selectedImage||{}).src)?i:t.selectedImage},importFromImage:function(i){i=i instanceof jQuery?i:t(i);var r=_.extend({},this.defaultData),a={src:i.attr("src"),width:i.width(),height:i.height()},s=t("<a>").attr("href",a.src)[0],o=this.calculateRealSize(a.src),l=i.closest(".ueditor-insert-variant-group"),d=l.attr("class"),c=l.find(".wp-caption-text"),g=c.attr("class"),p=l.find(".uinsert-image-wrapper"),h=p.attr("class"),u=1;s.origin!=window.location.origin&&(r.isLocal=0),r.imageThumb=a,r.imageFull={width:o.width,height:o.height,src:a.src};var f=i.parent();f.is("a")&&(r.linkUrl=f.attr("href"),r.linkType="external");var m=i.attr("class");m?(m=m.match(/wp-image-(\d+)/),m?r.attachmentId=m[1]:r.attachmentId=!1):r.attachmentId=!1,r.title=i.attr("title"),_.isEmpty(c.text())||(r.caption=c.html()),u=c.prev(p).length?1:0,r.show_caption=c.length,l.length?(r.style={caption:{order:1,height:c.css("minHeight")?c.css("minHeight").replace("px",""):c.height(),width_cls:Upfront.Util.grid.derive_column_class(g),left_cls:Upfront.Util.grid.derive_marginleft_class(g),top_cls:Upfront.Util.grid.derive_margintop_class(g),show:c.length},group:{float:l.css("float"),width_cls:Upfront.Util.grid.derive_column_class(d),left_cls:Upfront.Util.grid.derive_marginleft_class(d),height:l&&l.css("minHeight")?l.css("minHeight").replace("px",""):a.height+c.height(),marginRight:0,marginLeft:0},image:{width_cls:Upfront.Util.grid.derive_column_class(h),left_cls:Upfront.Util.grid.derive_marginleft_class(h),top_cls:Upfront.Util.grid.derive_margintop_class(h),src:"",height:0}},r.variant_id=l.data("variant"),r.variant_id&&(r.alignment=e.BasicImageVariants.findWhere({vid:l.data("variant")}))):(r.alignment=e.BasicImageVariants.first(),r.variant_id=r.alignment.vid);var w=new n({data:r});w.render();var v=i.hasClass(".upfront-inserted_image-basic-wrapper")?i:i.closest(".upfront-inserted_image-basic-wrapper");return v.replaceWith(w.$el),w},getStyleView:function(){if(this.styleView)return this.styleView;var t=new e.ImageStylesView(this.data);return this.styleView=t,t}});return{ImageInsert:n}})}(jQuery);