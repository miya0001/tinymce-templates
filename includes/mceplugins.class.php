<?php
/*
Add TinyMCE plugis easily

Parameters
$plugin_url: url of editor_plugin.js
$lang_path: path of langs.php
$plugin_name: name of plugin
*/
if (!class_exists('mcePlugins')) {
class mcePlugins{

    private $name = null;
    private $url = null;
    private $lang_path = null;
    private $inits = array();

    function __construct($plugin_name, $plugin_url,
        $lang_path = null, $button_callback = null, $inits = array())
    {
        $this->name = $plugin_name;
        $this->url = $plugin_url;
        add_filter('mce_external_plugins', array(&$this, 'external_plugins'));
        if ($lang_path) {
            $this->lang_path = $lang_path;
            add_filter('mce_external_languages', array(&$this, 'external_languages'));
        }
        if ($inits) {
            $this->inits = $inits;
            add_filter('tiny_mce_before_init', array(&$this, 'before_init'));
        }
        if ($button_callback) {
            add_filter('mce_buttons', $button_callback);
        }
    }

    public function before_init($inits){
        foreach ($this->inits as $key => $value) {
            $inits[$key] = $value;
        }
        return $inits;
    }

    public function external_plugins($plugins = array())
    {
        $plugins[$this->name] = $this->url;
        return $plugins;
    }

    public function external_languages($langs)
    {
        $langs[$this->name] = $this->lang_path;
        return $langs;
    }

}
}

?>
