(function() {
    // Load plugin specific language pack
    tinymce.PluginManager.requireLangPack('ecp');

    tinymce.create('tinymce.plugins.EcpPlugin', {
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
            ed.addCommand('mceEcpPHP', function() {
                ed.windowManager.open({
                    file : url + '/php_code.html',
                    width : 640,
                    height : 320 ,
                    inline : 1
                }, {
                    plugin_url : url // Plugin absolute URL
                });
            });
            ed.addCommand('mceEcpQuote', function() {
                ed.windowManager.open({
                    file : url + '/quote.html',
                    width : 640,
                    height : 320 ,
                    inline : 1
                }, {
                    plugin_url : url // Plugin absolute URL
                });
            });	
            ed.addCommand('mceEcpFlags', function() {
                ed.windowManager.open({
                    file : url + '/flags.php',
                    width : 580,
                    height : 150,
                    inline : 1
                }, {
                    plugin_url : url // Plugin absolute URL
                });
            });	
            ed.addCommand('mceEcpUser', function() {
                ed.windowManager.open({
                    file : 'user.php',
                    width : 240,
                    height : 60,
                    inline : 1
                }, {
                    plugin_url : url // Plugin absolute URL
                });
            });	
            ed.addCommand('mceEcpPages', function() {
                ed.windowManager.open({
                    file : url + '/pages.php',
                    width : 400,
                    height : 75,
                    inline : 1
                }, {
                    plugin_url : url // Plugin absolute URL
                });
            });											

            // Register example button
            ed.addButton('ecpphp', {
                title : 'ecp.php_desc',
                cmd : 'mceEcpPHP',
                image : url + '/img/php_button.gif'
            });
            ed.addButton('ecpquote', {
                title : 'ecp.quote_desc',
                cmd : 'mceEcpQuote',
                image : url + '/img/quote.gif'
            });	
            ed.addButton('ecpflags', {
                title : 'ecp.flag_desc',
                cmd : 'mceEcpFlags',
                image : url + '/img/flag.gif'
            });	
            ed.addButton('ecpuser', {
                title : 'ecp.user_desc',
                cmd : 'mceEcpUser',
                image : url + '/img/user.gif'
            });
            ed.addButton('ecppages', {
                title : 'ecp.pages_desc',
                cmd : 'mceEcpPages',
                image : url + '/img/pages.gif'
            });													

            // Add a node change handler, selects the button in the UI when a image is selected
        },
        getInfo : function() {
            return {
                longname : 'ECP Plugin | PHP, Quote, Code',
                author : 'Dennis Pfenning',
                authorurl : 'http://www.easy-clanapge.de',
                infourl : 'http://www.easy-clanapge.de',
                version : "1.0"
            };
        }
    });

    // Register plugin
    tinymce.PluginManager.add('ecp', tinymce.plugins.EcpPlugin);
})();