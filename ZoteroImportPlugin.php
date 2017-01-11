<?php
class ZoteroImportPlugin extends Omeka_Plugin_AbstractPlugin
{
    const ELEMENT_SET_NAME = 'Zotero';
    
    protected $_hooks = array('install', 'uninstall', 'upgrade', 'define_acl',
        'admin_items_search', 'items_browse_sql');

    protected $_filters = array('admin_navigation_main');

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
     * Install the plugin.
     */
    public function hookInstall()
    {
        if ($this->_db->getTable('ElementSet')->findByName(self::ELEMENT_SET_NAME)) {
            throw new Omeka_Plugin_Installer_Exception(
                __('An element set by the name "%s" already exists. You must delete that element set to install this plugin.', self::ELEMENT_SET_NAME)
            );
        }

        // Insert the Zotero element set.
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
        insert_element_set(self::ELEMENT_SET_NAME, $elements);

        // Create the plugin's tables.
        $sql = "
        CREATE TABLE IF NOT EXISTS `{$this->_db->prefix}zotero_import_imports` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `process_id` int(10) unsigned DEFAULT NULL,
          `collection_id` int(10) unsigned DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $this->_db->query($sql);

        $sql = "
        CREATE TABLE IF NOT EXISTS `{$this->_db->prefix}zotero_import_items` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `import_id` int(10) unsigned NOT NULL,
          `item_id` int(10) unsigned DEFAULT NULL,
          `zotero_item_key` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
          `zotero_item_parent_key` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
          `zotero_item_type` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
          `zotero_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $this->_db->query($sql);
    }

    /**
     * Uninstall the plugin.
     */
    public function hookUninstall()
    {
        // Drop all tables created during installation.
        $sql = "DROP TABLE IF EXISTS `{$this->_db->prefix}zotero_import_imports`";
        $this->_db->query($sql);
        $sql = "DROP TABLE IF EXISTS `{$this->_db->prefix}zotero_import_items`";
        $this->_db->query($sql);
    }

    /**
     * Upgrade the plugin.
     */
    public function hookUpgrade($oldVersion, $newVersion)
    {
        if (version_compare($oldVersion, '1.1', '<=')) {
            // Zotero changed the way it identifies items from a numeric ID
            // to an alphanumeric key. These changes fix this.
            $sql = "
            ALTER TABLE `{$this->_db->prefix}zotero_import_items`
            CHANGE `zotero_item_id` `zotero_item_key` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
            CHANGE `zotero_item_parent_id` `zotero_item_parent_key` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL";
            $this->_db->query($sql);
        }
    }

    public function hookDefineAcl($args)
    {
        // Restrict access to super and admin users.
        $args['acl']->addResource('ZoteroImport_Index');
    }

    /**
     * Filter admin navigation.
     */
    public function filterAdminNavigationMain($nav)
    {
        $nav[] = array(
            'label' => __('Zotero Import'),
            'uri' => url('zotero-import'),
            'resource' => ('ZoteroImport_Index'),
        );
        return $nav;
    }

    /**
     * Gets all the Zotero Item Types in a format designed to be used by
     * Zend_View_Helper_FormSelect.
     *
     * @return array
     */
    public function getZoteroItemTypes()
    {
        $sql = "
        SELECT DISTINCT(et.text), e.id
        FROM `{$this->_db->prefix}element_texts` et
        JOIN `{$this->_db->prefix}elements` e
        ON et.element_id = e.id
        JOIN `{$this->_db->prefix}element_sets` es
        ON e.element_set_id = es.id
        WHERE e.name = '" . self::$zoteroFields['itemType'][0] . "'
        AND es.name = '" . self::ELEMENT_SET_NAME . "'
        ORDER BY et.text";
        $results = $this->_db->fetchAll($sql);
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
        $formSelect = get_view()->formSelect(
            'zotero_item_type', null, null,
            label_table_options(self::getZoteroItemTypes())
        );
        echo '
        <div class="field">
            <div class="two columns alpha">
                <label for="zotero_item_type">Zotero Item Type</label>
            </div>
            <div class="five columns omega inputs">';
        echo $formSelect;
        echo '
            </div>
        </div>';
    }

    /**
     * Narrows a search by Zotero Item Type.
     *
     * @return Zend_Db_Select
     */
    public function hookItemsBrowseSql($args)
    {
        if (!empty($args['params']['zotero_item_type'])) {
            $args['select']
                ->join(array('element_texts' => $this->_db->prefix.'element_texts'), 'element_texts.record_id = items.id', array())
                ->join(array('elements' => $this->_db->prefix.'elements'), 'element_texts.element_id = elements.id', array())
                ->join(array('element_sets' => $this->_db->prefix.'element_sets'), 'elements.element_set_id = element_sets.id', array())
                ->where('element_sets.name = ?', self::ELEMENT_SET_NAME)
                ->where('elements.name = ?', self::$zoteroFields['itemType'][0])
                ->where('element_texts.text = ?', $args['params']['zotero_item_type']);
        }
    }

    /**
     * Decode ZIP filenames.
     *
     * Zotero stores web snapshots in ZIP files containing base64 encoded
     * filenames
     *
     * @param File $file
     */
    public static function beforeSaveFile($args)
    {
        if (!$args['insert']) {
            return;
        }

        $file = $args['record'];

        // Return if the file does not have a ".zip" file extension. This is
        // needed because ZipArchive::open() sometimes opens files that are not
        // ZIP archives.
        if (!preg_match('/\.zip$/', $file->filename)) {
            return;
        }

        $za = new ZipArchive;

        // Skip this file if an error occurs. ZipArchive::open() will return
        // true if valid, error codes otherwise.
        if (true !== $za->open($file->getPath())) {
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
