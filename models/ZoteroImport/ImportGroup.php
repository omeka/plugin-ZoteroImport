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
            $feed = $zotero->groupItemsTop($this->_id, array('content' => 'full', 'start' => $start));
            
            // Set the start parameter for the next page iteration.
            if ($feed->link('next')) {
                $query = parse_url($feed->link('next'), PHP_URL_QUERY);
                parse_str($query, $query);
                $start = $query['start'];
            }
            
            // Iterate through this page's entries/items.
            foreach ($feed->entry as $entry) {
                
                // Set default insert_item() arguments.
                $itemMetadata = array();
                $elementTexts = array();
                $fileMetadata = array();
                
                // Map Zotero fields to Omeka element texts (Dublin Core)
                foreach ($entry->content->item->field as $field) {
                    if ($fieldName = $this->_fieldMap($field['name'])) {
                        $elementTexts['Dublin Core'][$fieldName][] = array('text' => (string) $field, 'html' => false);
                    }
                }
                
                // Map Zotero item type to dc:type.
                $elementTexts['Dublin Core']['Type'][] = array('text' => $entry->content->item['itemType'], 'html' => false);
                
                // map Zotero creators to dc:creator.
                if (is_array($entry->content->item->creator)) {
                    foreach ($entry->content->item->creator as $creator) {
                        if ($creator = $this->_getCreatorName($creator)) {
                            $elementTexts['Dublin Core']['Creator'][] = array('text' => $creator, 'html' => false);
                        }
                    }
                } else {
                    if ($creator = $this->_getCreatorName($entry->content->item->creator)) {
                        $elementTexts['Dublin Core']['Creator'][] = array('text' => $creator, 'html' => false);
                    }
                }
                
                // Map Zotero children (notes & attachments) to Omeka ??? and files
                // item 67826721 is an example of an item with an attachment and a note
                if ($entry->numChildren()) {
                    $children = $zotero->groupItemChildren($this->_id, $entry->itemID(), array('content' => 'full'));
                    foreach ($children->entry as $child) {
                        switch ($child->itemType) {
                            case 'note':
                                // map note to what?
                                break;
                            case 'attachment':
                                // get location and metadata, then map to item file
                                // $zotero->groupItemFile($this->_id, $child->itemID());
                                break;
                            default:
                                break;
                        }
                    }
                }
                
                // Map Zotero tags to Omeka tags, comma-delimited.
                if ($entry->numTags()) {
                    $tags = $zotero->groupItemTags($this->_id, $entry->itemID(), array('content' => 'full'));
                    $tagArray = array();
                    foreach ($tags->entry as $tag) {
                        // Remove commas from Zotero tag, or Omeka will bisect it.
                        $tagArray[] = str_replace(',', ' ', $tag->title);
                    }
                    $itemMetadata['tags'] = join(',', $tagArray);
                }
                
                //insert_item($itemMetadata, $elementTexts, $fileMetadata);
            }
            
        } while ($feed->link('self') != $feed->link('last'));
    }
    
    protected function _getCreatorName($creator)
    {
        if (isset($creator->creator->name)) {
            return $creator->creator->name();
        }
        
        if (isset($creator->creator->firstName) && isset($creator->creator->lastName)) {
            return $creator->creator->firstName() . " " . $creator->creator->lastName();
        }
        
        return false;
    }
}