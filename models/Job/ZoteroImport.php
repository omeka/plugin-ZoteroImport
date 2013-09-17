<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2007-2010
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package ZoteroImport
 */

require_once 'ZoteroApiClient/Service/Zotero.php';
require_once 'ZoteroImportItem.php';
require_once 'Omeka/Filter/Filename.php';
require_once 'Zend/Uri.php';

/**
 * The Zotero import process.
 * 
 * @package ZoteroImport
 */
class Job_ZoteroImport extends Omeka_Job_AbstractJob
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
    
    /**
     * Runs the import process.
     * 
     * @param array $args Required arguments to run the process.
     */
    public function perform()
    {
        // Raise the memory limit.
        ini_set('memory_limit', '500M');
        
        // Add the before_insert_file hook during import if the required ZIP 
        // library exists.
        if (class_exists('ZipArchive')) {
            get_plugin_broker()->addHook('before_insert_file', 
                                         'ZoteroImportPlugin::beforeInsertFile', 
                                         'ZoteroImport');
        }
        
        // Set the arguments.
        $this->_libraryId           = $this->_options['libraryId'];
        $this->_libraryType         = $this->_options['libraryType'];
        $this->_libraryCollectionId = $this->_options['libraryCollectionId'];
        $this->_privateKey          = $this->_options['privateKey'];
        $this->_collectionId        = $this->_options['collectionId'];
        $this->_zoteroImportId      = $this->_options['zoteroImportId'];
        
        // Set the Zotero client. Make up to 3 request attempts.
        $this->_client = new ZoteroApiClient_Service_Zotero($this->_privateKey, 3);
        
        $this->_import(); 
    }
    
    /**
     * Performs the import by iterating the Zotero API Atom feeds and mapping 
     * each entry to an Omeka item.
     */
    protected function _import()
    {
        do {
            
            // Initialize the start parameter on the first library feed iteration.
            if (!isset($start)) {
                $start = 0;
                $zoteroImportImport->started = Zend_Date::now()->toString('yyyy-MM-dd HH:mm:ss');
                $zoteroImportImport->save();
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
                    $tags = $this->_client->$method($this->_libraryId, $item->key());
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
                    $children = $this->_client->$method($this->_libraryId, $item->key());
                    foreach ($children->entry as $child) {
                        
                        // Map a Zotero child note to an Omeka item.
                        if ('note' == $child->itemType()) {
                            $noteXpath = '//default:tr[@class="note"]/default:td';
                            $note = $this->_contentXpath($child->content, $noteXpath, true);
                            // Prepare the note for import.
                            if ($note instanceof SimpleXMLElement) {
                                $note = trim(preg_replace('#^<td>(.*)</td>$#', '$1', $note->asXML()));
                            }
                            $this->_elementTexts['Zotero']['Note'][] = array('text' => (string) $note, 'html' => true);
                        
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
                                                       $child->key(), 
                                                       $item->key(), 
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
                                               $item->key(), 
                                               null, 
                                               $item->itemType(), 
                                               $item->updated());
                
                release_object($item);
                release_object($omekaItem);
            }
          
 
        } while ($feed->link('self') != $feed->link('last'));
    }
    
    /**
     * Maps Zotero fields to Omeka elements.
     * 
     * @param Zend_Feed_Element $tr
     */
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
        
        // Map to the Zotero element set. Set HTML to true if this is a Note.
        $this->_elementTexts['Zotero'][$elementName][] = array('text' => $elementText, 'html' => 'Note' == $elementName ? true : false);
        
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
   
   /**
    * Maps Zotero attachments to Omeka files, et al. 
    * 
    * @param Zend_Feed_Element
    * @param bool Flag indicating that this is a top-level attachment.
    */
   protected function _mapAttachment(Zend_Feed_Element $element, $topLevelAttachment = false)
   {
        if (!$topLevelAttachment) {
            $this->_elementTexts['Zotero']['Attachment Title'][] = array('text' => $element->title(), 'html' => false);
            
            $urlXpath = '//default:tr[@class="url"]/default:td';
            if ($url = $this->_contentXpath($element->content, $urlXpath, true)) {
                $this->_elementTexts['Zotero']['Attachment URL'][] = array('text' => $url, 'html' => false);
            
            // If a attachment that is not top-level has no URL, still assign it 
            // a placeholder to maintain relationships between the "Attachment 
            // Title" and "Attachment Url" elements.
            } else {
                $this->_elementTexts['Zotero']['Attachment URL'][] = array('text' => '[No URL]', 'html' => false);
            }
        }
        
        // The Zotero API will not return a file unless a private key exists, so 
        // prevent unnecessary requests.
        if (!$this->_privateKey) {
            return;
        }
        
        // Get the file URLs.
        $method = "{$this->_libraryType}ItemFile";
        $urls = $this->_client->$method($this->_libraryId, $element->key());
        
        // Not all attachments have corresponding files in Amazon S3, so return 
        // those that do not.
        if (!$urls['s3']) {
            return;
        }
        
        // Name the file.
        $uri = Zend_Uri::factory($urls['s3']);
        // Hack to work around a bug in Omeka 1.2 concerning Source file 
        // ingests and filenames containing Unicode characters. Omeka 
        // correctly saves Unicode filenames to the archive, but removes 
        // the Unicode characters in the database (in `files`.
        // `archive_filename`). This is fixed in Omeka 1.3.
        if (version_compare(OMEKA_VERSION, '1.3-dev', '<')) {
            $name = md5(mt_rand() + microtime(true)) 
                  . '.' 
                  . pathinfo($uri->getPath(), PATHINFO_EXTENSION);
        // Set the original filename as the basename of the URL path.
        } else {
            $name = urldecode(basename($uri->getPath()));
        }
        
        // Set the file metadata.
        $this->_fileMetadata['files'][] = array(
            'source' => $urls['zotero'], 
            'name' => $name, 
            // Set the title.
            'metadata' => array(
                'Dublin Core' => array(
                    'Title' => array(
                        array('text' => $element->title(), 'html' => false)
                    ),
                    'Identifier' => array(
                        array('text' => $url, 'html' => false)
                    )
                )
            )
        );
    }
   
   /**
    * Inserts a row into zotero_import_items.
    * 
    * @param int $itemId
    * @param int $zoteroItemId
    * @param int $zoteroItemParentId
    * @param string $zoteroItemType
    * @param string $zoteroUpdated
    */
   protected function _insertZoteroImportItem($itemId, 
                                              $zoteroItemKey, 
                                              $zoteroItemParentKey,
                                              $zoteroItemType, 
                                              $zoteroUpdated)
   {
        $zoteroItem = new ZoteroImportItem;
        $zoteroItem->import_id              = $this->_zoteroImportId;
        $zoteroItem->item_id                = $itemId;
        $zoteroItem->zotero_item_key        = $zoteroItemKey;
        $zoteroItem->zotero_item_parent_key = $zoteroItemParentKey;
        $zoteroItem->zotero_item_type       = $zoteroItemType;
        $zoteroItem->zotero_updated         = $zoteroUpdated; 
        $zoteroItem->save();
        release_object($zoteroItem);
   }
    
    /**
     * Gets values via XPath.
     * 
     * @param Zend_Feed_Element $content The XML element object to search.
     * @param string $xpath The XPath.
     * @param bool $fetchOne Fetch all the results or just the first.
     * @return array|SimpleXMLElement|null
     */
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
