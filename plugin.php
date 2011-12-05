<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2007-2010
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package ZoteroImport
 */

add_plugin_hook('install', 'ZoteroImportPlugin::install');
add_plugin_hook('uninstall', 'ZoteroImportPlugin::uninstall');
add_plugin_hook('upgrade', 'ZoteroImportPlugin::upgrade');
add_plugin_hook('admin_append_to_plugin_uninstall_message', 'ZoteroImportPlugin::adminAppendToPluginUninstallMessage');

add_filter('admin_navigation_main', 'ZoteroImportPlugin::adminNavigationMain');

add_plugin_hook('admin_append_to_advanced_search', 'ZoteroImportPlugin::advancedSearch');
add_plugin_hook('item_browse_sql', 'ZoteroImportPlugin::itemBrowseSql');
add_plugin_hook('define_acl', 'ZoteroImportPlugin::defineAcl');

/**
 * Contains code used to integrate Zotero Import into Omeka.
 * 
 * @package ZoteroImport
 */
class ZoteroImportPlugin
{
    const ZOTERO_ELEMENT_SET_NAME = 'Zotero';
    
    /**
     * Zotero-to-Omeka mapping table.
     * 
     * [Zotero field name] = [Omeka element name], 
     * [Zotero field name] = array(
     *     [Omeka element name], 
     *     [Zotero locale field name / API <th> name]
     * )
     * 
     * @var array
     */
    public static $zoteroFields = array(
        // Creators
        'creator'=>array(
            'artist'         => 'Artist',
            'attorneyAgent'  => array('Attorney Agent', 'Attorney/Agent'),
            'author'         => 'Author',
            'bookAuthor'     => 'Book Author',
            'cartographer'   => 'Cartographer',
            'castMember'     => 'Cast Member',
            'commenter'      => 'Commenter',
            'composer'       => 'Composer',
            'contributor'    => 'Contributor',
            'cosponsor'      => 'Cosponsor',
            'counsel'        => 'Counsel',
            'director'       => 'Director',
            'editor'         => 'Editor',
            'guest'          => 'Guest',
            'interviewee'    => array('Interviewee', 'Interview With'),
            'interviewer'    => 'Interviewer',
            'inventor'       => 'Inventor',
            'performer'      => 'Performer',
            'podcaster'      => 'Podcaster',
            'presenter'      => 'Presenter',
            'producer'       => 'Producer',
            'programmer'     => 'Programmer',
            'recipient'      => 'Recipient',
            'reviewedAuthor' => 'Reviewed Author',
            'scriptwriter'   => 'Scriptwriter',
            'seriesEditor'   => 'Series Editor',
            'sponsor'        => 'Sponsor',
            'translator'     => 'Translator',
            'wordsBy'        => 'Words By'
        ),
        // Item Type
        'itemType'             => array('Item Type', 'Type'),
        // Note
        'note'                 => 'Note',
        // Fields
        'DOI'                  => 'DOI',
        'ISBN'                 => 'ISBN',
        'ISSN'                 => 'ISSN',
        'abstractNote'         => array('Abstract Note', 'Abstract'),
        'accessDate'           => array('Access Date', 'Accessed'),
        'applicationNumber'    => 'Application Number',
        'archive'              => 'Archive',
        'archiveLocation'      => array('Archive Location', 'Loc. in Archive'),
        'artworkMedium'        => array('Artwork Medium', 'Medium'),
        'artworkSize'          => 'Artwork Size',
        'assignee'             => 'Assignee',
        'audioFileType'        => array('Audio File Type', 'File Type'),
        'audioRecordingFormat' => array('Audio Recording Format', 'Format'),
        'billNumber'           => 'Bill Number',
        'blogTitle'            => 'Blog Title',
        'bookTitle'            => 'Book Title',
        'callNumber'           => 'Call Number',
        'caseName'             => 'Case Name',
        'code'                 => 'Code',
        'codeNumber'           => 'Code Number',
        'codePages'            => 'Code Pages',
        'codeVolume'           => 'Code Volume',
        'committee'            => 'Committee',
        'company'              => 'Company',
        'conferenceName'       => 'Conference Name',
        'country'              => 'Country',
        'court'                => 'Court',
        'date'                 => 'Date',
        'dateDecided'          => 'Date Decided',
        'dateEnacted'          => 'Date Enacted',
        'dictionaryTitle'      => 'Dictionary Title',
        'distributor'          => 'Distributor',
        'docketNumber'         => 'Docket Number',
        'documentNumber'       => 'Document Number',
        'edition'              => 'Edition',
        'encyclopediaTitle'    => 'Encyclopedia Title',
        'episodeNumber'        => 'Episode Number',
        'extra'                => 'Extra',
        'filingDate'           => 'Filing Date',
        'firstPage'            => 'First Page',
        'forumTitle'           => array('Forum Title', 'Forum/Listserv Title'),
        'genre'                => 'Genre',
        'history'              => 'History',
        'institution'          => 'Institution',
        'interviewMedium'      => array('Interview Medium', 'Medium'),
        'issue'                => 'Issue',
        'issueDate'            => 'Issue Date',
        'issuingAuthority'     => 'Issuing Authority',
        'journalAbbreviation'  => array('Journal Abbreviation', 'Journal Abbr'),
        'label'                => 'Label',
        'language'             => 'Language',
        'legalStatus'          => 'Legal Status',
        'legislativeBody'      => 'Legislative Body',
        'letterType'           => array('Letter Type', 'Type'),
        'libraryCatalog'       => 'Library Catalog',
        'manuscriptType'       => array('Manuscript Type', 'Type'),
        'mapType'              => array('Map Type', 'Type'),
        'medium'               => 'Medium',
        'meetingName'          => 'Meeting Name',
        'nameOfAct'            => 'Name of Act',
        'network'              => 'Network',
        'numPages'             => array('Num Pages', '# of Pages'),
        'number'               => 'Number',
        'numberOfVolumes'      => array('Number of Volumes', '# of Volumes'),
        'pages'                => 'Pages',
        'patentNumber'         => 'Patent Number',
        'place'                => 'Place',
        'postType'             => 'Post Type',
        'presentationType'     => array('Presentation Type', 'Type'),
        'priorityNumbers'      => 'Priority Numbers',
        'proceedingsTitle'     => 'Proceedings Title',
        'programTitle'         => 'Program Title',
        'programmingLanguage'  => array('Programming Language', 'Language'),
        'publicLawNumber'      => 'Public Law Number',
        'publicationTitle'     => array('Publication Title', 'Publication'),
        'publisher'            => 'Publisher',
        'references'           => 'References',
        'reportNumber'         => 'Report Number',
        'reportType'           => 'Report Type',
        'reporter'             => 'Reporter',
        'reporterVolume'       => 'Reporter Volume',
        'rights'               => 'Rights',
        'runningTime'          => 'Running Time',
        'scale'                => 'Scale',
        'section'              => 'Section',
        'series'               => 'Series',
        'seriesNumber'         => 'Series Number',
        'seriesText'           => 'Series Text',
        'seriesTitle'          => 'Series Title',
        'session'              => 'Session',
        'shortTitle'           => 'Short Title',
        'studio'               => 'Studio',
        'subject'              => 'Subject',
        'system'               => 'System',
        'thesisType'           => array('Thesis Type', 'Type'),
        'title'                => 'Title',
        //'type'=>'', // Not used, I guess
        'university'           => 'University',
        'url'                  => 'URL',
        'version'              => 'Version',
        'videoRecordingFormat' => array('Video Recording Format', 'Format'),
        'volume'               => 'Volume',
        'websiteTitle'         => 'Website Title',
        'websiteType'          => 'Website Type',
        // Custom elements that don't exist in Zotero data model.
        'attachmentTitle'      => 'Attachment Title', 
        'attachmentUrl'        => 'Attachment URL'
    );
    
    /**
     * Installs the Zotero Import plugin.
     */
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
        foreach (self::$zoteroFields as $zoteroFieldName => $fieldMap) {
            if ('creator' == $zoteroFieldName) {
                foreach ($fieldMap as $zoteroCreatorName => $creatorMap) {
                    $creatorName = is_array($creatorMap) ? $creatorMap[0] : $creatorMap;
                    $elements[] = array('name' => $creatorName, 'data_type' => 'Tiny Text');
                }
            } else {
                $fieldName = is_array($fieldMap) ? $fieldMap[0] : $fieldMap;
                $elements[] = array('name' => $fieldName, 'data_type' => 'Tiny Text');
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
  `zotero_item_key` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `zotero_item_parent_key` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `zotero_item_type` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `zotero_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $db->query($sql);
    }
    
    /**
     * Uninstalls the Zotero Import plugin.
     */
    public static function uninstall()
    {
        $db = get_db();
        
        // Delete the "Zotero" element set if it exists.
        $elementSet = $db->getTable('ElementSet')->findByName(self::ZOTERO_ELEMENT_SET_NAME);
        if ($elementSet) {
            $elementSet->delete();
        }
        
        // DROP all tables created during installation.
        $sql = "DROP TABLE IF EXISTS `{$db->prefix}zotero_import_imports`";
        $db->query($sql);
        $sql = "DROP TABLE IF EXISTS `{$db->prefix}zotero_import_items`";
        $db->query($sql);
    }
    
    public static function upgrade($oldVersion, $newVersion)
    {
        $db = get_db();
        switch ($oldVersion) {
            case '1.1':
                // Zotero changed the way it identifies items from a numeric ID 
                // to an alphanumeric key. These changes fix this.
                $sql = "ALTER TABLE `{$db->prefix}zotero_import_items` 
                        CHANGE `zotero_item_id` `zotero_item_key` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
                        CHANGE `zotero_item_parent_id` `zotero_item_parent_key` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL";
                 $db->query($sql);
            default:
                break;
        }
    }
    
    /**
     * Appends a warning message to the uninstall confirmation page.
     */
    public static function adminAppendToPluginUninstallMessage()
    {
        echo '<p><strong>Warning</strong>: This will permanently delete the "' . self::ZOTERO_ELEMENT_SET_NAME . '" element set and all text mapped to it during import. Text mapped to the Dublin Core element set will not be touched. You may deactivate this plugin if you do not want to lose data.</p>';
    }
    
    /**
     * Adds a Zotero Import tab to the admin navigation.
     * 
     * @param array $nav
     * @return array
     */
    public static function adminNavigationMain($nav)
    {
        if(has_permission('ZoteroImport_Index', 'index')) {
            $nav['Zotero Import'] = uri('zotero-import');
        }
        return $nav;
    }
    
    /**
     * Gets all the Zotero Item Types in a format designed to be used by 
     * Zend_View_Helper_FormSelect.
     * 
     * @return array
     */
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
WHERE e.name = '" . self::$zoteroFields['itemType'][0] . "'
AND es.name = '" . self::ZOTERO_ELEMENT_SET_NAME . "'
ORDER BY et.text";
        
        $results = $db->fetchAll($sql);
        $zoteroItemTypes = array();
        foreach($results as $result) {
            $zoteroItemTypes[$result['text']] = $result['text'];
        }
        
        return $zoteroItemTypes;
    }
    
    /**
     * Appends a narrow by Zotero Item Type select menu to the admin advanced 
     * search.
     */
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
    
    /**
     * Narrows a search by Zotero Item Type.
     * 
     * @return Zend_Db_Select
     */
    public static function itemBrowseSql($select, $params)
    {
        if (isset($_GET['zotero_item_type'])) {
            $db = get_db();
            $select->join(array('et' => $db->prefix.'element_texts'), 'et.record_id = i.id', array())
                   ->join(array('e' => $db->prefix.'elements'), 'et.element_id = e.id', array())
                   ->join(array('es' => $db->prefix.'element_sets'), 'e.element_set_id = es.id', array())
                   ->where('es.name = ?', self::ZOTERO_ELEMENT_SET_NAME) 
                   ->where('e.name = ?', self::$zoteroFields['itemType'][0])
                   ->where('et.text = ?', $_GET['zotero_item_type']);
        }
        
        return $select;
    }
    
    public static function defineAcl($acl)
    {
        $acl->loadResourceList(
            array('ZoteroImport_Index' => array('index', 'import-library', 'stop-import', 'delete-import'))
        );
    }
    
    /**
     * Decode ZIP filenames.
     * 
     * Zotero stores web snapshots in ZIP files containing base64 encoded 
     * filenames
     * 
     * @param File $file
     */
    public static function beforeInsertFile($file)
    {
        // Return if the file does not have a ".zip" file extension. This is 
        // needed because ZipArchive::open() sometimes opens files that are not 
        // ZIP archives.
        if (!preg_match('/\.zip$/', $file->archive_filename)) {
            return;
        }
        
        $za = new ZipArchive;
        
        // Skip this file if an error occurs. ZipArchive::open() will return 
        // true if valid, error codes otherwise.
        if (true !== $za->open($file->getPath('archive'))) {
            return;
        }
        
        // Rename each file in the archive.
        for ($i = 0; $i < $za->numFiles; $i++) {
            $stat = $za->statIndex($i);
            // Encoded filenames end with %ZB64, remove prior to decoding.
            $name = preg_replace('/%ZB64$/', '', $stat['name']);
            // Base64 decode the filename.
            $name = base64_decode($name);
            // Some decoded filenames begin with @22, remove prior to renaming.
            $name = preg_replace('/^@22/', '', $name);
            // Some decoded filenames end with @22, remove prior to renaming.
            $name = preg_replace('/@22$/', '', $name);
            $za->renameIndex($i, $name);
        }
        
        $za->close();
    }
}

/**
 * Returns items of a particular Zotero item type. Uses the Item Type element in 
 * the Zotero element set.
 *
 * @param string $typeName Search items with this Zotero item type.
 * @param int|null $collectionId Search only in this collection.
 * @param int $limit Maximum number of items to return.
 * @return array An array containing item results.
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
 * @param array $parts The parts of the output text mapped from Zotero elements.
 * array(
 *     array(
 *         'element'   => {Zotero element name, text, required}, 
 *         'prefix'    => {part prefix, text, optional}, 
 *         'suffix'    => {part suffix, text, optional}, 
 *         'all'       => {get all element texts?, boolean, optional}, 
 *         'delimiter' => {element text delimiter, text, optional}
 *     ), 
 *     array([...])
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