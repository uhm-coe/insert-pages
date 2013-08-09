(function() { // Start a new namespace to avoid collisions
  
  // Create plugin
  tinymce.create('tinymce.plugins.wpInsertPages', {
    
    // Initializes the plugin
    init: function(ed, url) {
      //var disabled = true;

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
        image: url+'/../img/insertpages_toolbar_icon.gif',
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