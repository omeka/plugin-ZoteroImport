<?php

add_plugin_hook('install', 'ZoteroImportPlugin::install');
add_plugin_hook('uninstall', 'ZoteroImportPlugin::uninstall');
add_plugin_hook('admin_append_to_plugin_uninstall_message', 'ZoteroImportPlugin::adminAppendToPluginUninstallMessage');

add_filter('admin_navigation_main', 'ZoteroImportPlugin::adminNavigationMain');

add_plugin_hook('admin_append_to_advanced_search', 'ZoteroImportPlugin::advancedSearch');
add_plugin_hook('item_browse_sql', 'ZoteroImportPlugin::itemBrowseSql');

class ZoteroImportPlugin
{
    const ZOTERO_ELEMENT_SET_NAME = 'Zotero';
    
    public static $zoteroFields = array(
        'creator' => array(
            'Author', 
            'Contributor', 
            'Editor', 
            'Translator', 
            'Series Editor', 
            'Interviewee', 
            'Interviewer', 
            'Director', 
            'Scriptwriter', 
            'Producer', 
            'Cast Member', 
            'Sponsor', 
            'Counsel', 
            'Inventor', 
            'Attorney Agent', 
            'Recipient', 
            'Performer', 
            'Composer', 
            'Words By', 
            'Cartographer', 
            'Programmer', 
            'Artist', 
            'Commenter', 
            'Presenter', 
            'Guest', 
            'Podcaster', 
            'Reviewed Author', 
        ), 
        'country'             => 'Country', 
        'number'              => 'Number', 
        'DOI'                 => 'DOI', 
        'ISBN'                => 'ISBN', 
        'ISSN'                => 'ISSN', 
        'abstractNote'        => 'Abstract Note', 
        'accessDate'          => 'Access Date', 
        'applicationNumber'   => 'Application Number', 
        'archiveLocation'     => 'Archive Location', 
        'artworkMedium'       => 'Artwork Medium', 
        'artworkSize'         => 'Artwork Size', 
        'assignee'            => 'Assignee', 
        'audioFileType'       => 'Audio File Type', 
        'audioRecordingType'  => 'Audio Recording Type', 
        'billNumber'          => 'Bill Number', 
        'blogTitle'           => 'Blog Title', 
        'bookTitle'           => 'Book Title', 
        'callNumber'          => 'Call Number', 
        'caseName'            => 'Case Name', 
        'code'                => 'Code', 
        'codeNumber'          => 'Code Number', 
        'codePages'           => 'Code Pages', 
        'codeVolume'          => 'Code Volume', 
        'committee'           => 'Committee', 
        'company'             => 'Company', 
        'conferenceName'      => 'Conference Name', 
        'court'               => 'Court', 
        'date'                => 'Date', 
        'dateDecided'         => 'Date Decided', 
        'dateEnacted'         => 'Date Enacted', 
        'dictionaryTitle'     => 'Dictionary Title', 
        'distributor'         => 'Distributor', 
        'docketNumber'        => 'Docket Number', 
        'documentNumber'      => 'Document Number', 
        'edition'             => 'Edition', 
        'encyclopediaTitle'   => 'Encyclopedia Title', 
        'episodeNumber'       => 'Episode Number', 
        'extra'               => 'Extra', 
        'firstPage'           => 'First Page', 
        'forumTitle'          => 'Forum Title', 
        'history'             => 'History', 
        'institution'         => 'Institution', 
        'interviewMedium'     => 'Interview Medium', 
        'issue'               => 'Issue', 
        'issueDate'           => 'Issue Date', 
        'itemType'            => 'Item Type', 
        'journalAbbreviation' => 'Journal Abbreviation', 
        'label'               => 'Label', 
        'language'            => 'Language', 
        'legalStatus'         => 'Legal Status', 
        'legislativeBody'     => 'Legislative Body', 
        'letterType'          => 'Letter Type', 
        'manuscriptType'      => 'Manuscript Type', 
        'mapType'             => 'Map Type', 
        'medium'              => 'Medium', 
        'meetingName'         => 'Meeting Name', 
        'nameOfAct'           => 'Name Of Act', 
        'network'             => 'Network', 
        'numPages'            => 'Num Pages', 
        'numberOfVolumes'     => 'Number Of Volumes', 
        'pages'               => 'Pages', 
        'patentNumber'        => 'Patent Number', 
        'place'               => 'Place', 
        'postType'            => 'Post Type', 
        'presentationType'    => 'Presentation Type', 
        'priorityNumbers'     => 'Priority Numbers', 
        'proceedingsTitle'    => 'Proceedings Title', 
        'programmingLanguage' => 'Programming Language', 
        'publicLawNumber'     => 'Public Law Number', 
        'publicationTitle'    => 'Publication Title', 
        'publisher'           => 'Publisher', 
        'references'          => 'References', 
        'reportNumber'        => 'Report Number', 
        'reportType'          => 'Report Type', 
        'reporter'            => 'Reporter', 
        'reporterVolume'      => 'Reporter Volume', 
        'repository'          => 'Repository', 
        'rights'              => 'Rights', 
        'runningTime'         => 'Running Time', 
        'scale'               => 'Scale', 
        'section'             => 'Section', 
        'series'              => 'Series', 
        'seriesNumber'        => 'Series Number', 
        'seriesText'          => 'Series Text', 
        'seriesTitle'         => 'Series Title', 
        'session'             => 'Session', 
        'shortTitle'          => 'Short Title', 
        'studio'              => 'Studio', 
        'subject'             => 'Subject', 
        'system'              => 'System', 
        'thesisType'          => 'Thesis Type', 
        'title'               => 'Title', 
        'type'                => 'Type', 
        'university'          => 'University', 
        'url'                 => 'URL', 
        'version'             => 'Version', 
        'videoRecordingType'  => 'Video Recording Type', 
        'volume'              => 'Volume', 
        'websiteTitle'        => 'Website Title', 
        'websiteType'         => 'Website Type', 
        'note'                => 'Note', 
        // Custom elements that don't exist in Zotero data model.
        'attachmentTitle' => 'Attachment Title', 
        'attachmentUrl'   => 'Attachment URL'
    );
    
    public static function install()
    {
        $db = get_db();
        
        // Don't install if an element set by the name "Zotero" already exists.
        if ($db->getTable('ElementSet')->findByName(self::ZOTERO_ELEMENT_SET_NAME)) {
            throw new Exception('An element set by the name "' . self::ZOTERO_ELEMENT_SET_NAME . '" already exists. You must delete that element set to install this plugin.');
        }
        
        // Insert the Zotero element set.
        $elementSetMetadata = self::ZOTERO_ELEMENT_SET_NAME;
        $elements = array();
        foreach (self::$zoteroFields as $zoteroFieldName => $elementName) {
            if ('creator' == $zoteroFieldName) {
                foreach ($elementName as $creatorFieldName) {
                    $elements[] = array('name' => $creatorFieldName, 'data_type' => 'Tiny Text');
                }
            } else {
                $elements[] = array('name' => $elementName, 'data_type' => 'Tiny Text');
            }
        }
        insert_element_set($elementSetMetadata, $elements);
        
        // Create the plugin's tables.
        $sql = "
CREATE TABLE IF NOT EXISTS `{$db->prefix}zotero_import_imports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `process_id` int(10) unsigned DEFAULT NULL,
  `collection_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $db->query($sql);

        $sql = "
CREATE TABLE IF NOT EXISTS `{$db->prefix}zotero_import_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `import_id` int(10) unsigned NOT NULL,
  `item_id` int(10) unsigned DEFAULT NULL,
  `zotero_item_id` int(10) unsigned NOT NULL,
  `zotero_item_parent_id` int(10) unsigned DEFAULT NULL,
  `zotero_item_type` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `zotero_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $db->query($sql);
    }
    
    public static function uninstall()
    {
        $db = get_db();
        
        // Delete the "Zotero" element set.
        $elementSet = $db->getTable('ElementSet')->findByName(self::ZOTERO_ELEMENT_SET_NAME);
        $elementSet->delete();
        
        $sql = "DROP TABLE IF EXISTS `{$db->prefix}zotero_import_imports`";
        $db->query($sql);
        $sql = "DROP TABLE IF EXISTS `{$db->prefix}zotero_import_items`";
        $db->query($sql);
    }
    
    public static function adminAppendToPluginUninstallMessage()
    {
        echo '<p><strong>Warning</strong>: This will permanently delete the "' . self::ZOTERO_ELEMENT_SET_NAME . '" element set and all text mapped to it during import. Text mapped to the Dublin Core element set will not be touched. You may deactivate this plugin if you do not want to lose data.</p>';
    }
    
    public static function adminNavigationMain($nav)
    {
        $nav['Zotero Import'] = uri('zotero-import');
        return $nav;
    }
    
    public static function getZoteroItemTypes()
    {
        $db = get_db();
        
        $sql = "
SELECT DISTINCT(et.text), e.id 
FROM `{$db->prefix}element_texts` et 
JOIN `{$db->prefix}elements` e 
ON et.element_id = e.id 
JOIN `{$db->prefix}element_sets` es 
ON e.element_set_id = es.id
WHERE e.name = '" . self::$zoteroFields['itemType'] . "'
AND es.name = '" . self::ZOTERO_ELEMENT_SET_NAME . "'";
        
        $results = $db->fetchAll($sql);
        $zoteroItemTypes = array();
        foreach($results as $result) {
            $zoteroItemTypes[$result['text']] = $result['text'];
        }
        
        return $zoteroItemTypes;
    }
    
    public static function advancedSearch()
    {
        // The array of Zotero Item Types
        $zoteroItemTypes = self::getZoteroItemTypes();
        
        $html .= '<div class="field">';
        $html .= label('zotero_item_type','Zotero Item Type');
        $html .= '<div class="inputs">';
        $html .= select(array('name' => 'zotero_item_type', 'id' => 'zotero_item_type'), $zoteroItemTypes);
        $html .= '</div>';
        $html .= '</div>';
        
        echo $html;
    }
    
    public static function itemBrowseSql($select, $params)
    {
        if (strlen($_GET['zotero_item_type'])) {
            $db = get_db();
            $select->join(array('et' => $db->prefix.'element_texts'), 'et.record_id = i.id', array())
                   ->join(array('e' => $db->prefix.'elements'), 'et.element_id = e.id', array())
                   ->join(array('es' => $db->prefix.'element_sets'), 'e.element_set_id = es.id', array())
                   ->where('es.name = ?', self::ZOTERO_ELEMENT_SET_NAME) 
                   ->where('e.name = ?', self::$zoteroFields['itemType'])
                   ->where('et.text = ?', $_GET['zotero_item_type']);
        }
        
        return $select;
    }
}

/**
 * Returns items of a particular Zotero item type. Uses the Item Type element in 
 * the Zotero element set.
 *
 * @param string $typeName
 * @param int $collectionId 
 * @return void
 */
function zotero_import_get_items_by_zotero_item_type($typeName, $collectionId = null, $limit = 10)
{
    $db = get_db();
    
    // Get the Zotero:Item Type element
    $element = $db->getTable('Element')->findByElementSetNameAndElementName('Zotero', 'Item Type');
    
    // Using the advanced search interface, get a limited set of items that have 
    // the provided Zotero:Item Type.
    $items = get_items(array('collection' => $collectionId, 
                             'recent' => true, 
                             'advanced_search' => array(array('type' => 'contains', 
                                                              'element_id' => $element->id, 
                                                              'terms' => $typeName))), 
                       $limit);
    
    return $items;
}

/**
 * Returns custom text built from elements from the Zotero element set.
 * 
 * @param array $parts The parts of the output text, mapped from Zotero elements.
 * array(
 *     'element'   => {Zotero element name, text, required}, 
 *     'prefix'    => {part prefix, text, optional}, 
 *     'suffix'    => {part suffix, text, optional}, 
 *     'all'       => {get all element texts?, boolean, optional}, 
 *     'delimiter' => {element text delimiter, text, optional}
 * )
 * @return string The output text.
 */
function zotero_import_build_zotero_output(array $parts = array())
{
    $output = '';
    foreach ($parts as $part) {
        
        if (!isset($part['element']) || !is_string($part['element'])) {
            throw new Exception('Zotero output parts must include an element name.');
        }
        
        // Set the options.
        $options = array();
        if (isset($part['all']) && $part['all']) {
            $options['all'] = true;
        }
        if (isset($part['delimiter'])) {
            $options['delimiter'] = $part['delimiter'];
        }
        
        // Set the element text.
        $elementText = item(ZoteroImportPlugin::ZOTERO_ELEMENT_SET_NAME, $part['element'], $options);
        if (!$elementText) {
            continue;
        }
        
        // Build the output.
        $output .= isset($part['prefix']) ? $part['prefix'] : '';
        $output .= is_array($elementText) ? implode(', ', $elementText) : $elementText;
        $output .= isset($part['suffix']) ? $part['suffix'] : '';
    }
    return $output;
}