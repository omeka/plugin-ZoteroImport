<?php
// @todo: Maybe there should be an option in ItemBuilder::addFiles() that's much 
// like 'ignore_invalid_files', but would allow all files through, e.g. 
// 'allow_invalid_files'. Developers using insert_item() would not have to worry 
// about the "Allowed File Extensions" and "Allowed File Types" settings when 
// ingesting files.

// @todo: Maybe the 'file_transfer_type' should be associated with one source at 
// a time, in case, for example, you're assigning a URL and Filesystem file to 
// the same item.

add_filter('admin_navigation_main', 'ZoteroImportPlugin::adminNavigationMain');

class ZoteroImportPlugin
{
    public static function adminNavigationMain($nav)
    {
        $nav['Zotero Import'] = uri('zotero-import');
        return $nav;
    }
}