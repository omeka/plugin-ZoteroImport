<?php
add_plugin_hook('install', 'ZoteroImportPlugin::install');
add_plugin_hook('uninstall', 'ZoteroImportPlugin::uninstall');

add_filter('admin_navigation_main', 'ZoteroImportPlugin::adminNavigationMain');

class ZoteroImportPlugin
{
    public static $zoteroFields = array(
        'creator' => array('dc' => 'Creator',     'z' => array(
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
        )), 
        //'country' => array('dc' => '', 'z' => 'Country'), // ambiguous
        //'number' => array('dc' => '', 'z' => 'Number'), // ambiguous
        'DOI'                 => array('dc' => 'Identifier',  'z' => 'DOI'), 
        'ISBN'                => array('dc' => 'Identifier',  'z' => 'ISBN'), 
        'ISSN'                => array('dc' => 'Identifier',  'z' => 'ISSN'), 
        'abstractNote'        => array('dc' => 'Description', 'z' => 'Abstract Note'), 
        'accessDate'          => array('dc' => 'Date',        'z' => 'Access Date'), 
        'applicationNumber'   => array('dc' => 'Identifier',  'z' => 'Application Number'), 
        'archiveLocation'     => array('dc' => 'Publisher',   'z' => 'Archive Location'), 
        'artworkMedium'       => array('dc' => 'Format',      'z' => 'Artwork Medium'), 
        'artworkSize'         => array('dc' => 'Format',      'z' => 'Artwork Size'), 
        'assignee'            => array('dc' => 'Contributor', 'z' => 'Assignee'), 
        'audioFileType'       => array('dc' => 'Type',        'z' => 'Audio File Type'), 
        'audioRecordingType'  => array('dc' => 'Type',        'z' => 'Audio Recording Type'), 
        'billNumber'          => array('dc' => 'Identifier',  'z' => 'Bill Number'), 
        'blogTitle'           => array('dc' => 'Source',      'z' => 'Blog Title'), 
        'bookTitle'           => array('dc' => 'Source',      'z' => 'Book Title'), 
        'callNumber'          => array('dc' => 'Identifier',  'z' => 'Call Number'), 
        'caseName'            => array('dc' => 'Title',       'z' => 'Case Name'), 
        'code'                => array('dc' => 'Identifier',  'z' => 'Code'), 
        'codeNumber'          => array('dc' => 'Identifier',  'z' => 'Code Number'), 
        'codePages'           => array('dc' => 'Source',      'z' => 'Code Pages'), 
        'codeVolume'          => array('dc' => 'Source',      'z' => 'Code Volume'), 
        'committee'           => array('dc' => 'Creator',     'z' => 'Committee'), 
        'company'             => array('dc' => 'Creator',     'z' => 'Company'), 
        'conferenceName'      => array('dc' => 'Source',      'z' => 'Conference Name'), 
        'court'               => array('dc' => 'Creator',     'z' => 'Court'), 
        'date'                => array('dc' => 'Date',        'z' => 'Date'), 
        'dateDecided'         => array('dc' => 'Date',        'z' => 'Date Decided'), 
        'dateEnacted'         => array('dc' => 'Date',        'z' => 'Date Enacted'), 
        'dictionaryTitle'     => array('dc' => 'Source',      'z' => 'Dictionary Title'), 
        'distributor'         => array('dc' => 'Publisher',   'z' => 'Distributor'), 
        'docketNumber'        => array('dc' => 'Identifier',  'z' => 'Docket Number'), 
        'documentNumber'      => array('dc' => 'Identifier',  'z' => 'Document Number'), 
        'edition'             => array('dc' => 'Source',      'z' => 'Edition'), 
        'encyclopediaTitle'   => array('dc' => 'Source',      'z' => 'Encyclopedia Title'), 
        'episodeNumber'       => array('dc' => 'Source',      'z' => 'Episode Number'), 
        'extra'               => array('dc' => 'Description', 'z' => 'Extra'), 
        'firstPage'           => array('dc' => 'Source',      'z' => 'First Page'), 
        'forumTitle'          => array('dc' => 'Source',      'z' => 'Forum Title'), 
        'history'             => array('dc' => 'Description', 'z' => 'History'), 
        'institution'         => array('dc' => 'Publisher',   'z' => 'Institution'), 
        'interviewMedium'     => array('dc' => 'Format',      'z' => 'Interview Medium'), 
        'issue'               => array('dc' => 'Source',      'z' => 'Issue'), 
        'issueDate'           => array('dc' => 'Date',        'z' => 'Issue Date'), 
        'itemType'            => array('dc' => 'Type',        'z' => 'Item Type'), 
        'journalAbbreviation' => array('dc' => 'Publisher',   'z' => 'Journal Abbreviation'), 
        'label'               => array('dc' => 'Publisher',   'z' => 'Label'), 
        'language'            => array('dc' => 'Language',    'z' => 'Language'), 
        'legalStatus'         => array('dc' => 'Description', 'z' => 'Legal Status'), 
        'legislativeBody'     => array('dc' => 'Creator',     'z' => 'Legislative Body'), 
        'letterType'          => array('dc' => 'Type',        'z' => 'Letter Type'), 
        'manuscriptType'      => array('dc' => 'Type',        'z' => 'Manuscript Type'), 
        'mapType'             => array('dc' => 'Type',        'z' => 'Map Type'), 
        'medium'              => array('dc' => 'Format',      'z' => 'Medium'), 
        'meetingName'         => array('dc' => 'Source',      'z' => 'Meeting Name'), 
        'nameOfAct'           => array('dc' => 'Title',       'z' => 'Name Of Act'), 
        'network'             => array('dc' => 'Publisher',   'z' => 'Network'), 
        'numPages'            => array('dc' => 'Format',      'z' => 'Num Pages'), 
        'numberOfVolumes'     => array('dc' => 'Source',      'z' => 'Number Of Volumes'), 
        'pages'               => array('dc' => 'Source',      'z' => 'Pages'), 
        'patentNumber'        => array('dc' => 'Identifier',  'z' => 'Patent Number'), 
        'place'               => array('dc' => 'Publisher',   'z' => 'Place'), 
        'postType'            => array('dc' => 'Type',        'z' => 'Post Type'), 
        'presentationType'    => array('dc' => 'Type',        'z' => 'Presentation Type'), 
        'priorityNumbers'     => array('dc' => 'Identifier',  'z' => 'Priority Numbers'), 
        'proceedingsTitle'    => array('dc' => 'Source',      'z' => 'Proceedings Title'), 
        'programmingLanguage' => array('dc' => 'Format',      'z' => 'Programming Language'), 
        'publicLawNumber'     => array('dc' => 'Identifier',  'z' => 'Public Law Number'), 
        'publicationTitle'    => array('dc' => 'Source',      'z' => 'Publication Title'), 
        'publisher'           => array('dc' => 'Publisher',   'z' => 'Publisher'), 
        'references'          => array('dc' => 'Relation',    'z' => 'References'), 
        'reportNumber'        => array('dc' => 'Identifier',  'z' => 'Report Number'), 
        'reportType'          => array('dc' => 'Type',        'z' => 'Report Type'), 
        'reporter'            => array('dc' => 'Contributor', 'z' => 'Reporter'), 
        'reporterVolume'      => array('dc' => 'Source',      'z' => 'Reporter Volume'), 
        'repository'          => array('dc' => 'Publisher',   'z' => 'Repository'), 
        'rights'              => array('dc' => 'Rights',      'z' => 'Rights'), 
        'runningTime'         => array('dc' => 'Format',      'z' => 'Running Time'), 
        'scale'               => array('dc' => 'Format',      'z' => 'Scale'), 
        'section'             => array('dc' => 'Source',      'z' => 'Section'), 
        'series'              => array('dc' => 'Source',      'z' => 'Series'), 
        'seriesNumber'        => array('dc' => 'Source',      'z' => 'Series Number'), 
        'seriesText'          => array('dc' => 'Description', 'z' => 'Series Text'), 
        'seriesTitle'         => array('dc' => 'Source',      'z' => 'Series Title'), 
        'session'             => array('dc' => 'Creator',     'z' => 'Session'), 
        'shortTitle'          => array('dc' => 'Title',       'z' => 'Short Title'), 
        'studio'              => array('dc' => 'Publisher',   'z' => 'Studio'), 
        'subject'             => array('dc' => 'Title',       'z' => 'Subject'), 
        'system'              => array('dc' => 'Publisher',   'z' => 'System'), 
        'thesisType'          => array('dc' => 'Type',        'z' => 'Thesis Type'), 
        'title'               => array('dc' => 'Title',       'z' => 'Title'), 
        'type'                => array('dc' => 'Type',        'z' => 'Type'), 
        'university'          => array('dc' => 'Publisher',   'z' => 'University'), 
        'url'                 => array('dc' => 'Identifier',  'z' => 'URL'), 
        'version'             => array('dc' => 'Relation',    'z' => 'Version'), 
        'videoRecordingType'  => array('dc' => 'Type',        'z' => 'Video Recording Type'), 
        'volume'              => array('dc' => 'Source',      'z' => 'Volume'), 
        'websiteTitle'        => array('dc' => 'Source',      'z' => 'Website Title'), 
        'websiteType'         => array('dc' => 'Type',        'z' => 'Website Type'), 
    );
    
    public static function install()
    {
        // Insert the Zotero element set.
        $elementSetMetadata = 'Zotero';
        $elements = array();
        foreach (self::$zoteroFields as $zoteroFieldName => $map) {
            if ('creator' == $zoteroFieldName) {
                foreach ($map['z'] as $creatorFieldName) {
                    $elements[] = array('name' => $creatorFieldName, 'data_type' => 'Tiny Text');
                }
            } else {
                $elements[] = array('name' => $map['z'], 'data_type' => 'Tiny Text');
            }
        }
        // Add the Note element, which is separate from the separate fields.
        $elements[] = array('name' => 'Note', 'data_type' => 'Tiny Text');
        insert_element_set($elementSetMetadata, $elements);
    }
    
    public static function uninstall()
    {}
    
    public static function adminNavigationMain($nav)
    {
        $nav['Zotero Import'] = uri('zotero-import');
        return $nav;
    }
}