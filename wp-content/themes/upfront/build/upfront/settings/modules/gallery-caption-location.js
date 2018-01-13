upfrontrjs.define(["scripts/upfront/settings/modules/base-module"],function(e){var i=Upfront.Settings.l10n.gallery_element,n=e.extend({className:"settings_module caption_location gallery-caption-location clearfix",group:!1,initialize:function(e){this.options=e||{};var n=this,t=this.options.state;this.options.toggle=!0,this.fields=_([new Upfront.Views.Editor.Field.Toggle({model:this.model,className:"useCaptions checkbox-title upfront-toggle-field",name:"use_captions",label:"",default_value:1,multiple:!1,values:[{label:i.panel.show_caption,value:"yes"}],change:function(e){n.model.set("use_captions",e)},show:function(e,i){var l=i.closest(".upfront-settings-item-content");if("yes"==e){l.find("."+t+"-toggle-wrapper").show();var o=n.model.get("caption-height",e);"fixed"===o&&l.find("."+t+"-caption-height-number").show()}else l.find("."+t+"-toggle-wrapper").hide(),l.find("."+t+"-caption-height-number").hide()}}),new Upfront.Views.Editor.Field.Select({model:this.model,className:t+"-caption-select caption_select",name:"captionType",default_value:"below",label_style:"inline",label:i.panel.caption_location,values:[{value:"over",label:i.panel.over},{value:"below",label:i.panel.under}],change:function(e){n.model.set("captionType",e),"below"==e&&n.model.set("showCaptionOnHover","0")},show:function(e,i){var n=i.closest(".state_modules");"below"===e||"undefined"==typeof e?n.find(".gallery-caption-on-hover").hide():n.find(".gallery-caption-on-hover").show()}}),new Upfront.Views.Editor.Field.Radios_Inline({className:t+"-caption-trigger field-caption_trigger gallery-caption-on-hover upfront-field-wrap upfront-field-wrap-multiple upfront-field-wrap-radios-inline over_image_field",model:this.model,name:"showCaptionOnHover",label:i.panel.caption_show,label_style:"inline",layout:"horizontal-inline",values:[{label:i.panel.always,value:"0"},{label:i.panel.hover,value:"1"}],change:function(e){n.model.set("showCaptionOnHover",e)}}),new Upfront.Views.Editor.Field.Radios_Inline({className:t+"-caption-height field-caption-height upfront-field-wrap upfront-field-wrap-multiple upfront-field-wrap-radios-inline",model:this.model,name:"caption-height",label_style:"inline",label:i.panel.caption_height,layout:"horizontal-inline",values:[{label:i.panel.auto,value:"auto"},{label:i.panel.fixed,value:"fixed"}],change:function(e){n.model.set("caption-height",e)},show:function(e,i){var l=i.closest(".state_modules"),o=n.model.get("use_captions");"yes"===o&&("fixed"===e?l.find("."+t+"-caption-height-number").show():l.find("."+t+"-caption-height-number").hide())}}),new Upfront.Views.Editor.Field.Number_Unit({model:this.model,className:t+"-caption-height-number caption-height-number",name:"thumbCaptionsHeight",label_style:"inline",min:1,label:i.panel.caption_height,default_value:20,values:[{label:"px",value:"1"}],change:function(e){n.model.set("thumbCaptionsHeight",e)}})]),this.listenToOnce(this,"rendered",function(){setTimeout(function(){if("yes"===n.model.get("use_captions")){n.$el.find("."+t+"-toggle-wrapper").show();var e=n.model.get("caption-height",value);"fixed"===e&&n.$el.find("."+t+"-caption-height-number").show()}else n.$el.find("."+t+"-toggle-wrapper").hide(),n.$el.find("."+t+"-caption-height-number").hide()},500)})}});return n});