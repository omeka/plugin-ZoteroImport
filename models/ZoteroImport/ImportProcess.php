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
                $method = "{$this->_libraryType}CollectionItems";
                $feed = $this->_client->$method($this->_libraryId, $this->_libraryCollectionId, array('start' => $start));
            } else {
                $method = "{$this->_libraryType}Items";
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
                $this->_elementTexts['Zotero']['Title'][]      = array('text' => $item->title(), 'html' => false);
                
                // Map top-level attachment item.
                if ('attachment' == $item->itemType()) {
                    $this->_mapAttachment($item);
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
                
                release_object($item);
                release_object($omekaItem);
            }
            
        } while ($feed->link('self') != $feed->link('last'));
    }
    
    protected function _mapFields(Zend_Feed_Element $tr)
    {
        // Only map those field nodes that exist in the mapping array.
        if ($elementName = $this->_getElementName($tr['class'])) {
            
            if ($elementName['dc']) {
                // Map the field nodes to the correlating Dublin Core element 
                // set field elements.
                $this->_elementTexts['Dublin Core'][$elementName['dc']][] = array('text' => $tr->td(), 'html' => false);
            }
            
            if ($elementName['z']) {
                // The creator node is formatted differently than other field 
                // nodes. Account for this by mapping a creator node to the 
                // correlating Zotero element set creator element.
                if ('creator' == $tr['class'] && in_array($tr->th(), $elementName['z'])) {
                    $this->_elementTexts['Zotero'][$tr->th()][] = array('text' => $tr->td(), 'html' => false);
                
                // Map the field nodes to the correlating Zotero element set 
                // field elements.
                } else {
                    $this->_elementTexts['Zotero'][$elementName['z']][] = array('text' => $tr->td(), 'html' => false);
                }
            }
        }
   }
   
   protected function _mapAttachment(Zend_Feed_Element $element)
   {
        // If not already assigned to the parent item, map the attachment's 
        // title to the parent item's Title element.
        if (!$this->_inElementTexts('Dublin Core', 'Title', $element->title)) {
            $this->_elementTexts['Dublin Core']['Title'][] = array('text' => $element->title(), 'html' => false);
        }
        if (!$this->_inElementTexts('Zotero', 'Title', $element->title)) {
            $this->_elementTexts['Zotero']['Title'][] = array('text' => $element->title(), 'html' => false);
        }
        
        // If not already assigned to the parent item, map the attachment's url 
        // to the parent item's Identifier and URL elements.
        $urlXpath = '//default:tr[@class="url"]/default:td';
        if ($url = $this->_contentXpath($element->content, $urlXpath, true)) {
            if (!$this->_inElementTexts('Dublin Core', 'Identifier', $url)) {
                $this->_elementTexts['Dublin Core']['Identifier'][] = array('text' => $url, 'html' => false);
            }
            if (!$this->_inElementTexts('Zotero', 'URL', $url)) {
                $this->_elementTexts['Zotero']['URL'][] = array('text' => $url, 'html' => false);
            }
        }
        
        // Ignoring the attachment's accessDate becuase adding it to the parent 
        // item's metadata would only confuse matters.
        
        // Set the file if it exists.
        $method = "{$this->_libraryType}ItemFile";
        $location = $this->_client->$method($this->_libraryId, $element->itemID());
        if ($location) {
            $this->_fileMetadata['files'][] = array('source' => $location, 'name' => $element->title());
        }
   }
   
   protected function _inElementTexts($elementSet, $element, $text)
   {
        if (isset($this->_elementTexts[$elementSet][$element])) {
            foreach ($this->_elementTexts[$elementSet][$element] as $elementText) {
                if ($text == $elementText['text']) {
                    return true;
                }
            }
        }
        return false;
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
    
    protected function _getElementName($fieldName)
    {
        foreach (ZoteroImportPlugin::$zoteroFields as $zoteroFieldName => $map) {
            if ($fieldName == $zoteroFieldName) {
                return $map;
            }
        }
        return false;
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