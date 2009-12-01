<?php
class ZoteroImport_ImportGroup extends ZoteroImport_ImportProcessAbstract
{
    protected $_params = array('content' => 'full', 'start' => 0);
    
    public function import()
    {
        // /usr/bin/php -d memory_limit=500M /var/www/omekatag/application/core/background.php -p 1 -l initializeRoutes
        require_once 'ZoteroApiClient/Service/Zotero.php';
        $z = new ZoteroApiClient_Service_Zotero;
        
        do {
            $feed = $z->groupItemsTop($this->_args['id'], $this->_params);
            
            echo $feed->link('self')."\n";
            
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
                
                // Get attachments (files & notes) via $entry->numChildren(), groups/<groupID>/items/<itemID>/children
                
                // Get tags via $entry->numTags(), groups/<groupID>/items/<itemID>/tags
                
                //print_r($elementTexts);
                //insert_item($itemMetadata, $elementTexts, $fileMetadata);
            }
            
            // Set the start parameter for the next page iteration.
            if ($feed->link('next')) {
                $query = parse_url($feed->link('next'), PHP_URL_QUERY);
                parse_str($query, $query);
                $this->_params['start'] = $query['start'];
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