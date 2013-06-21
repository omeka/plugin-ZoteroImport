<?php
/**
 * ZoteroImport
 *
 * @copyright Copyright 2008-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */


/**
 * Contains code used to integrate Zotero Import into Omeka.
 *
 * @package ZoteroImport
 */
class ZoteroImportPlugin extends Omeka_Plugin_AbstractPlugin
{
	/**
     * @var array Hooks for the plugin.
     */
    protected $_hooks = array('install', 'uninstall', 'upgrade',
        'uninstall_message', 'admin_items_search',
        'item_browse_sql','define_acl');

    /**
     * @var array Filters for the plugin.
     */
    protected $_filters = array('admin_navigation_main');

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
public function hookInstall()
    {
        $db = $this->_db;
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
		CREATE TABLE IF NOT EXISTS `$db->ZoteroImportImport` (
  		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  		`process_id` int(10) unsigned DEFAULT NULL,
  		`collection_id` int(10) unsigned DEFAULT NULL,
  		PRIMARY KEY (`id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $db->query($sql);

        $sql = "
		CREATE TABLE IF NOT EXISTS `$db->ZoteroImportItem` (
  		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  		`import_id` int(10) unsigned NOT NULL,
  		`item_id` int(10) unsigned DEFAULT NULL,
  		`zotero_item_key` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  		`zotero_item_parent_key` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  		`zotero_item_type` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  		`zotero_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 		 PRIMARY KEY (`id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $db->query($sql);

        $this->_installOptions();
    }

	/**
 	* Uninstall the plugin.
 	*/
    public function hookUninstall()
    {        
        // Drop the table.
        $db = $this->_db;
        $sql = "DROP TABLE IF EXISTS `$db->ZoteroImportImport`";
        $db->query($sql);
        $sql = "DROP TABLE IF EXISTS `$db->ZoteroImportItem`";
        $db->query($sql);

        $this->_uninstallOptions();
    }

	/**
     * Upgrade the plugin.
     *
     * @param array $args contains: 'old_version' and 'new_version'
     */
    public function hookUpgrade($args)
    {
 		$oldVersion = $args['old_version'];
        $newVersion = $args['new_version'];
        $db = $this->_db;

        if ($oldVersion == '1.1') {
        	// Zotero changed the way it identifies items from a numeric ID
            // to an alphanumeric key. These changes fix this.
                $sql = "ALTER TABLE `$db->ZoteroImportItem`
                        CHANGE `zotero_item_id` `zotero_item_key` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
                        CHANGE `zotero_item_parent_id` `zotero_item_parent_key` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL ENGINE = InnoDB";
                 $db->query($sql);
        }

    }

    /**
     * Appends a warning message to the uninstall confirmation page.
     */
    public function hookUninstallMessage()
    {
        echo '<p><strong>Warning</strong>: This will permanently delete the "' . self::ZOTERO_ELEMENT_SET_NAME . '" element set and all text mapped to it during import. Text mapped to the Dublin Core element set will not be touched. You may deactivate this plugin if you do not want to lose data.</p>';
    }

 	/**
     * Gets all the Zotero Item Types in a format designed to be used by
     * Zend_View_Helper_FormSelect.
     *
     * @return array
     */
   
    public static function getZoteroItemTypes()
    {
        $db = $this->_db;

        $sql = "
SELECT DISTINCT(et.text), e.id
FROM {$db->ElementTexts} et
JOIN {$db->Elements} e
ON et.element_id = e.id
JOIN {$db->ElementSets} es
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
    public function hookAdminItemsSearch()
    {
        // The array of Zotero Item Types
        $zoteroItemTypes = self::getZoteroItemTypes();

        $html = '<div class="field">';
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
    public function hookItemBrowseSql($args)
    {
    	$select = $args['select'];
    	$params = $args['params'];
    
        if (!empty($_GET['zotero_item_type'])) {
            $db = $this->_get_db();
            $select->join(array('et' => $db->ElementTexts), 'et.record_id = i.id', array())
                   ->join(array('e' => $db->Elements), 'et.element_id = e.id', array())
                   ->join(array('es' => $db->ElementSets), 'e.element_set_id = es.id', array())
                   ->where('es.name = ?', self::ZOTERO_ELEMENT_SET_NAME)
                   ->where('e.name = ?', self::$zoteroFields['itemType'][0])
                   ->where('et.text = ?', $_GET['zotero_item_type']);
        }

        return $select;
    }

    public function hookDefineAcl($args)
    {
    	$acl = $args['acl'];

    	$indexResource = new Zend_Acl_Resource('ZoteroImport_Index');
        if (version_compare(OMEKA_VERSION, '2.0-dev', '>=')) {
            $acl->add($indexResource);
        } else {
            $acl->add(
                array('ZoteroImport_Index' => array('index', 'import-library', 'stop-import', 'delete-import'))
            );
        }

    }

    /**
     * Adds a Zotero Import tab to the admin navigation.  
     * Uses is_allowed instead of deprecated has_permission
     * @param array $nav
     * @return array
     */
    public function filterAdminNavigationMain($nav)
    {
        if(is_allowed('ZoteroImport_Index', 'index')) {
            $nav[] = array(
            	          'label' => 'Zotero Import',
            	           'uri' => url('zotero-import')
            	           );
        }
        return $nav;
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

        // Base64 decode each file in the archive if needed.
        for ($i = 0; $i < $za->numFiles; $i++) {
            $stat = $za->statIndex($i);
            // Filenames that end with "%ZB64" are Base64 encoded.
            if (preg_match('/%ZB64$/', $stat['name'])) {
                // Remove "%ZB64" prior to decoding.
                $name = preg_replace('/%ZB64$/', '', $stat['name']);
                // Base64 decode the filename and rename the file.
                $name = base64_decode($name);
                $za->renameIndex($i, $name);
            }
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
     $db = $this->_db;

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











