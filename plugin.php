<?php
add_plugin_hook('install', 'ZoteroImportPlugin::install');
add_plugin_hook('uninstall', 'ZoteroImportPlugin::uninstall');
add_plugin_hook('config_form', 'ZoteroImportPlugin::configForm');
add_plugin_hook('config', 'ZoteroImportPlugin::config');

add_filter('admin_navigation_main', 'ZoteroImportPlugin::adminNavigationMain');

class ZoteroImportPlugin
{
    public static function install()
    {}
    
    public static function uninstall()
    {}
    
    public static function configForm()
    {}
    
    public static function config()
    {}
    
    public static function adminNavigationMain($nav)
    {
        $nav['Zotero Import'] = uri('zotero-import');
        return $nav;
    }
}