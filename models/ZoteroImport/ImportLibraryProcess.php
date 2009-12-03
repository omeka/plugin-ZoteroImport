<?php
class ZoteroImport_ImportLibraryProcess extends ProcessAbstract
{
    protected $_client;
    protected $_libraryId;
    protected $_libraryType;
    protected $_userId;
    
    public function run($args)
    {
        ini_set('memory_limit', '500M');
        
        $this->_libraryId   = $args['libraryId'];
        $this->_libraryType = $args['libraryType'];
        
        require_once 'ZoteroApiClient/Service/Zotero.php';
        $this->_client = new ZoteroApiClient_Service_Zotero($args['username'], $args['password']);
        
        $this->_import();
    }
    
    protected function _import()
    {
        do {
            
            // Initialize the start parameter on the first group feed iteration.
            if (!isset($start)) {
                $start = 0;
            }
            
            // Get the group feed.
            $method = "{$this->_libraryType}ItemsTop";
            $feed = $this->_client->$method($this->_libraryId, array('start' => $start));
            
            // Set the start parameter for the next page iteration.
            if ($feed->link('next')) {
                $query = parse_url($feed->link('next'), PHP_URL_QUERY);
                parse_str($query, $query);
                $start = $query['start'];
            }
            
            // Iterate through this page's entries/items.
            foreach ($feed->entry as $item) {
                
                // Set default insert_item() arguments.
                $itemMetadata = array();
                $elementTexts = array();
                $fileMetadata = array('file_transfer_type'  => 'Url', 
                                      'file_ingest_options' => array('ignore_invalid_files' => true));
                
                // Map the title.
                $elementTexts['Dublin Core']['Title'][] = array('text' => $item->title(), 'html' => false);
                
                // Map Zotero fields to Omeka element texts (Dublin Core)
                foreach ($item->content->div->table->tr as $tr) {
                    if ($elementName = $this->_getElementName($tr['class'])) {
                        $elementTexts['Dublin Core'][$elementName][] = array('text' => $tr->td(), 'html' => false);
                    }
                }
                
                // Map Zotero tags to Omeka tags, comma-delimited.
                if ($item->numTags()) {
                    $method = "{$this->_libraryType}ItemTags";
                    $tags = $this->_client->$method($this->_libraryId, $item->itemID());
                    $tagArray = array();
                    foreach ($tags->entry as $tag) {
                        // Remove commas from Zotero tag, or Omeka will bisect it.
                        $tagArray[] = str_replace(',', ' ', $tag->title);
                    }
                    $itemMetadata['tags'] = join(',', $tagArray);
                }
                
                // Map Zotero children (notes & attachments).
                if ($item->numChildren()) {
                    $method = "{$this->_libraryType}ItemChildren";
                    $children = $this->_client->$method($this->_libraryId, $item->itemID());
                    foreach ($children->entry as $child) {
                        switch ($child->itemType()) {
                            case 'note':
                                $noteXpath = '//default:tr[@class="note"]/default:td/default:p';
                                $note = (string) $this->_contentXpath($child->content, $noteXpath, true);
                                // Map note to what?
                                break;
                            case 'attachment':
                                $urlXpath = '//default:tr[@class="url"]/default:td';
                                $url = $this->_contentXpath($child->content, $urlXpath, true);
                                if ($url) {
                                    $elementTexts['Dublin Core']['Identifier'][] = array('text' => (string) $url, 'html' => false);
                                }
                                $method = "{$this->_libraryType}ItemFile";
                                $location = $this->_client->$method($this->_libraryId, $child->itemID());
                                if ($location) {
                                    $fileMetadata['files'][] = array('source' => $location, 'name' => $child->title());
                                }
                                break;
                            default:
                                break;
                        }
                    }
                }
                
                // Insert the item.
                insert_item($itemMetadata, $elementTexts, $fileMetadata);
            }
            
        } while ($feed->link('self') != $feed->link('last'));
    }
    
    protected function _getElementName($fieldName)
    {
        // Map to Dublin Core.
        switch ($fieldName) {
            
            // Source
            case 'reporterVolume':
            case 'firstPage': // ???
            case 'codeVolume':
            case 'codePages': // ???
            case 'pages': // ???
            case 'series':
            case 'seriesNumber': // concatenate with series?
            case 'volume':
            case 'numberOfVolumes': // concatenate with volume?
            case 'edition':
            case 'issue':
            case 'section':
            case 'dictionaryTitle':
            case 'encyclopediaTitle':
            case 'proceedingsTitle':
            case 'conferenceName':
            case 'meetingName':
            case 'seriesTitle':
            case 'bookTitle':
            case 'websiteTitle':
            case 'publicationTitle':
            case 'forumTitle':
            case 'blogTitle':
            case 'episodeNumber':
               $elementName = 'Source';
                break;
            
            // Identifier
            case 'url':
            case 'ISBN':
            case 'ISSN':
            case 'callNumber':
            case 'DOI':
            case 'reportNumber':
            case 'billNumber':
            case 'docketNumber':
            case 'documentNumber':
            case 'publicLawNumber':
            case 'codeNumber':
            case 'patentNumber':
            case 'applicationNumber':
            case 'priorityNumbers':
            case 'code':
                $elementName = 'Identifier';
                break;
            
            // Publisher
            case 'publisher':
            case 'place': // concatenate with publisher?
            case 'repository':
            case 'archiveLocation':
            case 'system':
            case 'distributor':
            case 'network':
            case 'university':
            case 'institution':
            case 'journalAbbreviation':
            case 'studio':
            case 'label':
                 $elementName = 'Publisher';
                break;
            
            // Type
            case 'itemType':
            case 'thesisType':
            case 'letterType':
            case 'manuscriptType':
            case 'videoRecordingType':
            case 'websiteType':
            case 'reportType':
            case 'audioRecordingType':
            case 'presentationType':
            case 'postType':
            case 'mapType':
            case 'audioFileType':
                $elementName = 'Type';
                break;
            
            // Format
            case 'programmingLanguage':
            case 'interviewMedium':
            case 'numPages':
            case 'runningTime':
            case 'artworkMedium':
            case 'artworkSize':
            case 'scale':
                $elementName = 'Format';
                break;
            
            // Creator
            case 'creator':
            case 'company':
            case 'legislativeBody':
            case 'court':
            case 'committee':
            case 'session':
                $elementName = 'Creator';
                break;
            
            // Date
            case 'date':
            case 'accessDate':
            case 'dateDecided':
            case 'dateEnacted':
            case 'issueDate':
                $elementName = 'Date';
                break;
            
            // Description
            case 'extra':
            case 'abstractNote':
            case 'seriesText':
            case 'history':
            case 'legalStatus':
                $elementName = 'Description';
                break;
            
            // Title
            case 'title':
            case 'shortTitle':
            case 'subject': // email subject
            case 'caseName':
            case 'nameOfAct':
                $elementName = 'Title';
                break;
            
            // Contributor
            case 'reporter':
            case 'assignee':
                $elementName = 'Contributor';
                break;
            
            // Relation
            case 'version':
            case 'references':
                $elementName = 'Relation';
                break;
            
            // Rights
            case 'rights':
                $elementName = 'Rights';
                break;
            
            // Language
            case 'language':
                $elementName = 'Language';
                break;
            
            // [unknown]
            default:
                $elementName = false;
                break;
        }
        
        return $elementName;
    }
    
    protected function _contentXpath(Zend_Feed_Element $content, $xpath, $fetchOne = false)
    {
        $xml = simplexml_load_string($content->div->saveXml());
        $xml->registerXPathNamespace('default', 'http://www.w3.org/1999/xhtml');
        
        // Experimental: automatically namespace each node in the xpath.
        //$xpath = preg_replace('#(/)([a-z])#i', '$1default:$2', $xpath);
        
        $result = $xml->xpath($xpath);
        if ($fetchOne) {
            return $result[0];
        }
        return $result;
    }
}