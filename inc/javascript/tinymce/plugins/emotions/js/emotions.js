tinyMCEPopup.requireLangPack();

var EmotionsDialog = {
	init : function(ed) {
		tinyMCEPopup.resizeToInnerSize();
	},

	insert : function(file, title) {
		var ed = tinyMCEPopup.editor, dom = ed.dom;
		title = title.replace(/&/g, '&amp;');
		title = title.replace(/\"/g, '&quot;');
		title = title.replace(/</g, '&lt;');
		title = title.replace(/>/g, '&gt;');
		tinyMCEPopup.execCommand('mceInsertContent', false, dom.createHTML('img', {
			src : 'images/smilies/' + file,
			alt : title,
			title : title,
			border : 0
		}));

		tinyMCEPopup.close();
	}
};

tinyMCEPopup.onInit.add(EmotionsDialog.init, EmotionsDialog);
