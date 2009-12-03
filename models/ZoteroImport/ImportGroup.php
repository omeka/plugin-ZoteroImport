<?php
class ZoteroImport_ImportGroup extends ZoteroImport_ImportProcessAbstract
{
    public function import()
    {
        // /usr/bin/php -d memory_limit=500M /var/www/omekatag/application/core/background.php -p 1 -l initializeRoutes
        require_once 'ZoteroApiClient/Service/Zotero.php';
        $zotero = new ZoteroApiClient_Service_Zotero($this->_username, $this->_password);
        
        do {
            
            // Initialize the start parameter on the first group feed iteration.
            if (!isset($start)) {
                $start = 0;
            }
            
            // Get the group feed.
            $feed = $zotero->groupItemsTop($this->_id, array('start' => $start));
            
            // Set the start parameter for the next page iteration.
            if ($feed->link('next')) {
                $query = parse_url($feed->link('next'), PHP_URL_QUERY);
                parse_str($query, $query);
                $start = $query['start'];
            }
            
            //echo $feed->link('self')."\n";
            
            // Iterate through this page's entries/items.
            foreach ($feed->entry as $item) {
                
                //echo $item->itemID();
                
                // Set default insert_item() arguments.
                $itemMetadata = array();
                $elementTexts = array();
                $fileMetadata = array('file_transfer_type'   => 'Url', 
                                      'ignore_invalid_files' => true);
                
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
                    $tags = $zotero->groupItemTags($this->_id, $item->itemID());
                    $tagArray = array();
                    foreach ($tags->entry as $tag) {
                        // Remove commas from Zotero tag, or Omeka will bisect it.
                        $tagArray[] = str_replace(',', ' ', $tag->title);
                    }
                    $itemMetadata['tags'] = join(',', $tagArray);
                }
                
                // Map Zotero children (notes & attachments) to Omeka ??? and files
                if ($item->numChildren()) {
                    $children = $zotero->groupItemChildren($this->_id, $item->itemID());
                    foreach ($children->entry as $child) {
                        if ('note' == $child->itemType()) {
                            $note = (string) $this->_contentXpath($child->content, '//default:tr[@class="note"]/default:td/default:p', true);
                        } else if ('attachment' == $child->itemType()) {
                            // The only kinds of attachments are linked file, imported file, linked URL, imported URL (a.k.a. snapshot)
                            $url = $this->_contentXpath($child->content, '//default:tr[@class="url"]/default:td', true);
                            // If the URL exists the attachment is a linked URL.
                            if ($url) {
                                $elementTexts['Dublin Core']['Identifier'][] = array('text' => (string) $url, 'html' => false);
                            // If the URL does not exist, the attachment is a imported file or imported URL
                            } else {
                                $location = $zotero->groupItemFile($this->_id, $child->itemID());
                                $fileMetadata['files'][] = $location;
                            }
                        }
                    }
                }
                
                //print_r($itemMetadata);exit;
                //print_r($elementTexts);exit;
                //print_r($fileMetadata);exit;
                
                insert_item($itemMetadata, $elementTexts, $fileMetadata);
            }
            
        } while ($feed->link('self') != $feed->link('last'));
    }
}