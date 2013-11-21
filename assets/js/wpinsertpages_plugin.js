function isRetinaDisplay() {
	if (window.matchMedia) {
		var mq = window.matchMedia("only screen and (-moz-min-device-pixel-ratio: 1.3), only screen and (-o-min-device-pixel-ratio: 2.6/2), only screen and (-webkit-min-device-pixel-ratio: 1.3), only screen  and (min-device-pixel-ratio: 1.3), only screen and (min-resolution: 1.3dppx)");
		if (mq && mq.matches || (window.devicePixelRatio > 1)) {
			return true;
		} else {
			return false;
		}
	}
}

(function() { // Start a new namespace to avoid collisions

	// Create plugin
	tinymce.create('tinymce.plugins.wpInsertPages', {

		// Initializes the plugin
		init: function(ed, url) {
			//var disabled = true;
			var toolbar_icon = isRetinaDisplay() ?
				url + '/../img/insertpages_toolbar_icon-2x.png' :
				url + '/../img/insertpages_toolbar_icon.png';

			// Register the command
			ed.addCommand('WP_InsertPages', function() {
				//if (disabled) return;
				ed.windowManager.open({
					id: 'wp-insertpage',
					width: 480,
					height: 'auto',
					wpDialog: true,
					title: 'Insert Page'
				}, {
					plugin_url: url // Plugin absolute url
				});
			});

			// Register the button
			ed.addButton('wpInsertPages_button', {
				title: 'Insert Page',
				image: toolbar_icon,
				cmd: 'WP_InsertPages'
			});

			// enable when something other than an anchor element is selected
			//ed.onNodeChange.add(function(ed, cm, n, co) {
			//  disabled = co && n.nodeName != 'A';
			//});
		},

		// Create control
		createControl: function(id, controlManager) {
			return null;
		},

		// Returns info about the plugin as a name/value array
		getInfo: function() {
			return {
				longname: 'WordPress Insert Page Dialog',
				author: 'Paul Aumer-Ryan',
				authorurl: 'http://combinelabs.com/paul',
				infourl: 'http://www.hawaii.edu/coe/dcdc/wordpress/insert-pages',
				version: '1.0'
			};
		}

	});

	// Register plugin
	tinymce.PluginManager.add('wpInsertPages', tinymce.plugins.wpInsertPages);

})();
