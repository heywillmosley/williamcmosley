!function(){Upfront.Settings&&Upfront.Settings.l10n?Upfront.Settings.l10n.global.views:Upfront.mainData.l10n.global.views;upfrontrjs.define(["scripts/upfront/upfront-views-editor/fields","scripts/upfront/upfront-views-editor/commands/commands","scripts/upfront/upfront-views-editor/commands/command","scripts/upfront/upfront-views-editor/commands/command-cancel-post-layout","scripts/upfront/upfront-views-editor/commands/command-delete","scripts/upfront/upfront-views-editor/commands/command-edit-background-area","scripts/upfront/upfront-views-editor/commands/command-general-edit-custom-css","scripts/upfront/upfront-views-editor/commands/command-edit-custom-css","scripts/upfront/upfront-views-editor/commands/command-edit-global-regions","scripts/upfront/upfront-views-editor/commands/command-edit-layout-background","scripts/upfront/upfront-views-editor/commands/command-exit","scripts/upfront/upfront-views-editor/commands/command-export-history","scripts/upfront/upfront-views-editor/commands/command-go-to-type-preview-page","scripts/upfront/upfront-views-editor/commands/command-load-layout","scripts/upfront/upfront-views-editor/commands/command-logo","scripts/upfront/upfront-views-editor/commands/command-merge","scripts/upfront/upfront-views-editor/commands/command-new-page","scripts/upfront/upfront-views-editor/commands/command-new-post","scripts/upfront/upfront-views-editor/commands/command-open-font-manager","scripts/upfront/upfront-views-editor/commands/command-preview-layout","scripts/upfront/upfront-views-editor/commands/command-publish-layout","scripts/upfront/upfront-views-editor/commands/command-redo","scripts/upfront/upfront-views-editor/commands/command-reset-everything","scripts/upfront/upfront-views-editor/commands/command-save-layout","scripts/upfront/upfront-views-editor/commands/command-save-layout-as","scripts/upfront/upfront-views-editor/commands/command-save-post-layout","scripts/upfront/upfront-views-editor/commands/command-select","scripts/upfront/upfront-views-editor/commands/command-toggle-grid","scripts/upfront/upfront-views-editor/commands/command-toggle-mode","scripts/upfront/upfront-views-editor/commands/command-toggle-mode-small","scripts/upfront/upfront-views-editor/commands/command-trash","scripts/upfront/upfront-views-editor/commands/command-undo","scripts/upfront/upfront-views-editor/commands/responsive/command-create-responsive-layouts","scripts/upfront/upfront-views-editor/commands/responsive/command-start-responsive-mode","scripts/upfront/upfront-views-editor/commands/responsive/command-stop-responsive-mode","scripts/upfront/upfront-views-editor/commands/responsive/command-responsive-redo","scripts/upfront/upfront-views-editor/commands/responsive/command-responsive-undo","scripts/upfront/upfront-views-editor/commands/breakpoint/command-add-custom-breakpoint","scripts/upfront/upfront-views-editor/commands/breakpoint/command-breakpoint-dropdown","scripts/upfront/upfront-views-editor/commands/command-open-media-gallery","scripts/upfront/upfront-views-editor/commands/command-popup-list","scripts/upfront/upfront-views-editor/commands/command-menu"],function(o,n,m,t,s,e,d,r,i,a,p,c,u,f,l,v,w,C,g,y,h,b,L,S,k,P,R,E,M,U,T,x,G,B,D,A,N,O,j,z,F,H){var n=n.extend({tagName:"ul",initialize:function(){this.commands=_([new w({model:this.model}),new C({model:this.model}),new S({model:this.model}),new k({model:this.model}),new x({model:this.model}),new b({model:this.model}),new s({model:this.model}),new R({model:this.model}),new E({model:this.model}),new L({model:this.model})]),Upfront.Settings.Debug.transients&&this.commands.push(new c({model:this.model}))}});return{Command:m,Commands:n,Command_CancelPostLayout:t,Command_Delete:s,Command_EditBackgroundArea:e,Command_GeneralEditCustomCss:d,Command_EditCustomCss:r,Command_EditGlobalRegions:i,Command_EditLayoutBackground:a,Command_Exit:p,Command_ExportHistory:c,Command_GoToTypePreviewPage:u,Command_LoadLayout:f,Command_Logo:l,Command_Merge:v,Command_NewPage:w,Command_NewPost:C,Command_OpenFontManager:g,Command_PreviewLayout:y,Command_PublishLayout:h,Command_Redo:b,Command_ResetEverything:L,Command_SaveLayout:S,Command_SaveLayoutAs:k,Command_SavePostLayout:P,Command_Select:R,Command_ToggleGrid:E,Command_ToggleMode:M,Command_ToggleModeSmall:U,Command_Trash:T,Command_Undo:x,Command_CreateResponsiveLayouts:G,Command_StartResponsiveMode:B,Command_StopResponsiveMode:D,Command_ResponsiveRedo:A,Command_ResponsiveUndo:N,Command_AddCustomBreakpoint:O,Command_BreakpointDropdown:j,Command_OpenMediaGallery:z,Command_PopupList:F,Command_Menu:H}})}();