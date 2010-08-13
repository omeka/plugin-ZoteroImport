<?php
require_once 'ZoteroApiClient/Service/Zotero.php';
require_once 'ZoteroImportItem.php';

class ZoteroImport_ImportProcess extends ProcessAbstract
{
    protected $_libraryId;
    protected $_libraryType;
    protected $_libraryCollectionId;
    protected $_privateKey;
    protected $_collectionId;
    protected $_zoteroImportId;
    
    protected $_collection;
    protected $_zoteroImport;
    protected $_client;
    
    protected $_itemMetadata;
    protected $_elementTexts;
    protected $_fileMetadata;
    
    public function run($args)
    {
        ini_set('memory_limit', '500M');
        
        $this->_libraryId           = $args['libraryId'];
        $this->_libraryType         = $args['libraryType'];
        $this->_libraryCollectionId = $args['libraryCollectionId'];
        $this->_privateKey          = $args['privateKey'];
        $this->_collectionId        = $args['collectionId'];
        $this->_zoteroImportId      = $args['zoteroImportId'];
        
        $this->_client = new ZoteroApiClient_Service_Zotero($this->_privateKey);
        
        $this->_import();
    }
    
    protected function _import()
    {
        do {
            
            // Initialize the start parameter on the first library feed iteration.
            if (!isset($start)) {
                $start = 0;
            }
            
            // Get the library feed.
            if ($this->_libraryCollectionId) {
                $method = "{$this->_libraryType}CollectionItemsTop";
                $feed = $this->_client->$method($this->_libraryId, $this->_libraryCollectionId, array('start' => $start));
            } else {
                $method = "{$this->_libraryType}ItemsTop";
                $feed = $this->_client->$method($this->_libraryId, array('start' => $start));
            }
            
            // Set the start parameter for the next page iteration.
            if ($feed->link('next')) {
                $query = parse_url($feed->link('next'), PHP_URL_QUERY);
                parse_str($query, $query);
                $start = $query['start'];
            }
            
            // Iterate through this page's entries/items.
            foreach ($feed->entry as $item) {
                
                // Set default insert_item() arguments.
                $this->_itemMetadata = array('collection_id' => $this->_collectionId, 
                                             'public'        => true);
                $this->_elementTexts = array();
                $this->_fileMetadata = array('file_transfer_type'  => 'Url', 
                                             'file_ingest_options' => array('ignore_invalid_files' => true));
                
                // Map the title.
                $this->_elementTexts['Dublin Core']['Title'][] = array('text' => $item->title(), 'html' => false);
                $this->_elementTexts['Zotero']['Title'][] = array('text' => $item->title(), 'html' => false);
                
                // Map top-level attachment item.
                if ('attachment' == $item->itemType()) {
                    $this->_mapAttachment($item, true);
                }
                
                // Map the Zotero API field nodes to Omeka elements.
                if (is_array($item->content->div->table->tr)) {
                    foreach ($item->content->div->table->tr as $tr) {
                        $this->_mapFields($tr);
                    }
                } else {
                    $this->_mapFields($item->content->div->table->tr);
                }
                
                // Map Zotero tags to Omeka tags, comma-delimited.
                if ($item->numTags()) {
                    $method = "{$this->_libraryType}ItemTags";
                    $tags = $this->_client->$method($this->_libraryId, $item->itemID());
                    $tagArray = array();
                    foreach ($tags->entry as $tag) {
                        // Remove commas from Zotero tags, or Omeka will assume 
                        // they are separate tags.
                        $tagArray[] = str_replace(',', ' ', $tag->title);
                    }
                    $this->_itemMetadata['tags'] = join(',', $tagArray);
                }
                
                // Map Zotero children (notes & attachments).
                if ($item->numChildren()) {
                    $method = "{$this->_libraryType}ItemChildren";
                    $children = $this->_client->$method($this->_libraryId, $item->itemID());
                    foreach ($children->entry as $child) {
                        
                        // Map a Zotero child note to an Omeka item.
                        if ('note' == $child->itemType()) {
                            $noteXpath = '//default:tr[@class="note"]/default:td/default:p';
                            $note = $this->_contentXpath($child->content, $noteXpath, true);
                            $this->_elementTexts['Zotero']['Note'][] = array('text' => (string) $note, 'html' => false);
                        
                        // Map a Zotero child attachment (file) to a file 
                        // assigned to the Omeka parent item.
                        } else if ('attachment' == $child->itemType()) {
                            $this->_mapAttachment($child);
                        
                        // Unknown child. Do not map.
                        } else {
                            continue;
                        }
                        
                        // Save the Zotero child item.
                        $this->_insertZoteroImportItem(null, 
                                                       $child->itemID(), 
                                                       $item->itemID(), 
                                                       $child->itemType(), 
                                                       $child->updated());
                    }
                }
                
                // Insert the item.
                $omekaItem = insert_item($this->_itemMetadata, 
                                         $this->_elementTexts, 
                                         $this->_fileMetadata);
                
                // Save the Zotero item.
                $this->_insertZoteroImportItem($omekaItem->id, 
                                               $item->itemID(), 
                                               null, 
                                               $item->itemType(), 
                                               $item->updated());
                
                // Zotero stores web snapshots in ZIP files containing base64 
                // encoded filenames. Decode these filenames if able.
                if (class_exists('ZipArchive')) {
                    $this->_base64DecodeZip($omekaItem);
                }
                
                release_object($item);
                release_object($omekaItem);
            }
            
        } while ($feed->link('self') != $feed->link('last'));
    }
    
    /**
     * Base64 decode the filenames if files are valid ZIP archives.
     * 
     * @param Item $item
     */
    protected function _base64DecodeZip($item)
    {
        // Iterate all the item's files.
        foreach ($item->Files as $file) {
            $za = new ZipArchive;
            // Skip this file if it is not a valid ZIP archive. open() will 
            // return true if valid, error codes otherwise.
            if (true !== $za->open($file->getPath('archive'))) {
                continue;
            }
            // Rename each file in the archive.
            for ($i = 0; $i < $za->numFiles; $i++) {
                $stat = $za->statIndex($i);
                $name = base64_decode(strstr($stat['name'], '%', true));
                $za->renameIndex($i, $name);
            }
            $za->close();
        }
    }
    
    protected function _mapFields(Zend_Feed_Element $tr)
    {
        // Only map those field nodes that exist in the mapping array.
        if (!array_key_exists($tr['class'], ZoteroImportPlugin::$zoteroFields)) {
            return;
        }
        
        $fieldName = $tr['class'];
        $fieldNameLocale = $tr->th();
        $fieldMap = ZoteroImportPlugin::$zoteroFields[$fieldName];
        $elementText = $tr->td();
        
        // Get the element name.
        if ('creator' == $fieldName) {
            foreach ($fieldMap as $zoteroCreatorName => $creatorMap) {
                if (is_array($creatorMap) && $fieldNameLocale == $creatorMap[1]) {
                    $elementName = $creatorMap[0];
                    break;
                } else if ($fieldNameLocale == $creatorMap) {
                    $elementName = $creatorMap;
                    break;
                }
            }
            // Only map those creators that exist in the mapping array.
            if (!isset($elementName)) {
                return;
            }
        } else {
            if (is_array($fieldMap) && $fieldNameLocale == $fieldMap[1]) {
                $elementName = $fieldMap[0];
            } else {
                $elementName = $fieldMap;
            }
        }
        
        // Map to the Zotero element set.
        $this->_elementTexts['Zotero'][$elementName][] = array('text' => $elementText, 'html' => false);
        
        // Map unambiguous fields to the Dublin Core element set.
        switch ($elementName) {
            case 'Subject':
                $this->_elementTexts['Dublin Core']['Subject'][] = array('text' => $elementText, 'html' => false);
                break;
            case 'Publisher':
                $this->_elementTexts['Dublin Core']['Publisher'][] = array('text' => $elementText, 'html' => false);
                break;
            case 'Date':
                $this->_elementTexts['Dublin Core']['Date'][] = array('text' => $elementText, 'html' => false);
                break;
            case 'Rights':
                $this->_elementTexts['Dublin Core']['Rights'][] = array('text' => $elementText, 'html' => false);
                break;
            case 'Language':
                $this->_elementTexts['Dublin Core']['Language'][] = array('text' => $elementText, 'html' => false);
                break;
            case 'Contributor':
                $this->_elementTexts['Dublin Core']['Contributor'][] = array('text' => $elementText, 'html' => false);
                break;
            // Map all the Creator types to DC:Creator (except for Contributor).
            case 'Creator':
            case 'Attorney Agent':
            case 'Author':
            case 'Book Author':
            case 'Cartographer':
            case 'Cast Member':
            case 'Commenter':
            case 'Composer':
            case 'Contributor':
            case 'Cosponsor':
            case 'Counsel':
            case 'Director':
            case 'Editor':
            case 'Guest':
            case 'Interviewee':
            case 'Interviewer':
            case 'Inventor':
            case 'Performer':
            case 'Podcaster':
            case 'Presenter':
            case 'Producer':
            case 'Programmer':
            case 'Recipient':
            case 'Reviewed Author':
            case 'Scriptwriter':
            case 'Series Editor':
            case 'Sponsor':
            case 'Translator':
            case 'Words By':
                $this->_elementTexts['Dublin Core']['Creator'][] = array('text' => $elementText, 'html' => false);
                break;
            // Map all the Item types to DC:Type.
            case 'Item Type':
            case 'Audio File Type':
            case 'Letter Type':
            case 'Manuscript Type':
            case 'Map Type':
            case 'Post Type':
            case 'Presentation Type':
            case 'Report Type':
            case 'Thesis Type':
            case 'Website Type':
                $this->_elementTexts['Dublin Core']['Type'][] = array('text' => $elementText, 'html' => false);
                break;
            default:
                break;
        }
   }
   
   protected function _mapAttachment(Zend_Feed_Element $element, $topLevelAttachment = false)
   {
        $titleElement = $topLevelAttachment ? 'Title' : 'Attachment Title';
        $this->_elementTexts['Zotero'][$titleElement][] = array('text' => $element->title(), 'html' => false);
        
        // If not already assigned to the parent item, map the attachment's url 
        // to the parent item's Identifier and URL elements.
        $urlXpath = '//default:tr[@class="url"]/default:td';
        if ($url = $this->_contentXpath($element->content, $urlXpath, true)) {
            $urlElement = $topLevelAttachment ? 'URL' : 'Attachment URL';
            $this->_elementTexts['Zotero'][$urlElement][] = array('text' => $url, 'html' => false);
        // If a attachment that is not top-level has no URL, still assign it a 
        // placeholder to maintain relationships between the "Attachment Title" 
        // and "Attachment Url" elements.
        } else if (!$topLevelAttachment) {
            $this->_elementTexts['Zotero']['Attachment URL'][] = array('text' => '[No URL]', 'html' => false);
        }
        
        // Ignoring the attachment's accessDate becuase adding it to the parent 
        // item's metadata would only confuse matters.
        
        // Set the file if it exists. The Zotero API will not return a file 
        // unless a private key exists, so prevent unnecessary requests.
        if ($this->_privateKey) {
            $method = "{$this->_libraryType}ItemFile";
            $location = $this->_client->$method($this->_libraryId, $element->itemID());
            if ($location) {
                $this->_fileMetadata['files'][] = array('source' => $location, 'name' => $element->title());
            }
        }
   }
   
   protected function _insertZoteroImportItem($itemId, 
                                              $zoteroItemId, 
                                              $zoteroItemParentId,
                                              $zoteroItemType, 
                                              $zoteroUpdated)
   {
        $zoteroItem = new ZoteroImportItem;
        $zoteroItem->import_id             = $this->_zoteroImportId;
        $zoteroItem->item_id               = $itemId;
        $zoteroItem->zotero_item_id        = $zoteroItemId;
        $zoteroItem->zotero_item_parent_id = $zoteroItemParentId;
        $zoteroItem->zotero_item_type      = $zoteroItemType;
        $zoteroItem->zotero_updated        = $zoteroUpdated;
        $zoteroItem->save();
        release_object($zoteroItem);
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
