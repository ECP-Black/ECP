(function() {
	// Load plugin specific language pack
	//tinymce.PluginManager.requireLangPack('example');

	tinymce.create('tinymce.plugins.ECP', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
			ed.addCommand('ecpphpfk', function() {
			/*	ed.windowManager.open({
					file : url + '/dialog.htm',
					width : 320,
					height : 120,
					inline : 1
				}, {
					plugin_url : url // Plugin absolute URL
				});*/ alert('Hallo');
			});
			// Register example button
			ed.addButton('ecpphp', {
				title : 'test',
				cmd : 'ecpphpfk',
				image : 'inc/javascript/tinymce/plugins/ecp/img/php_button.gif'
			});
		},

		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
				longname : 'ECP PHP Code insert',
				author : 'Dennis Pfenning',
				authorurl : 'http://www.easy-clanpage.de',
				infourl : 'http://wiki.moxiecode.com/index.php/TinyMCE:Plugins/example',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('ecpphpfk', tinymce.plugins.ECP);
})();