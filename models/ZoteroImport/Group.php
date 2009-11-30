<?php
class ZoteroImport_Group extends ZoteroImport_Abstract
{
    protected $_feed;
    protected $_entries;
    protected $_params = array('content' => 'full', 'start' => 0);
    
    public function import()
    {
        // /usr/bin/php /var/www/omekatag/application/core/background.php -p 1 -l initializeRoutes
        require_once 'ZoteroApiClient/Service/Zotero.php';
        $z = new ZoteroApiClient_Service_Zotero;
        
        do {
            $feed = $z->groupItemsTop($this->_args['id'], $this->_params);
            
            // Set the feed and entries more useable formats.
            $this->_setFeed($feed);
            
            // Map Zotero to Omeka.
            $this->_import();
            
            echo $this->_feed['link']['self']."\n";
            
        } while ($this->_feed['link']['self'] != $this->_feed['link']['last']);
    }
    
    protected function _import()
    {
        foreach ($this->_entries as $entry) {
            $metadata = array();
            $elementTexts = array();
            $fileMetadata = array();
            foreach ($entry['item']['fields'] as $fieldName => $fieldText) {
                $elementTexts['Dublin Core'][$this->_fieldMap($fieldName)][] = array('text' => $fieldText, 'html' => false);
            }
            //insert_item($metadata, $elementTexts);
        }
    }
    
    protected function _setFeed(Zend_Feed_Atom $feed)
    {
        // Reset the feed and entries properties.
        $this->_feed = array();
        $this->_entries = array();
        
        // Set Zotero feed variables.
        $this->_feed['title']        = (string) $feed->title;
        $this->_feed['id']           = (string) $feed->id;
        $this->_feed['totalResults'] = (string) $feed->totalResults;
        $this->_feed['apiVersion']   = (string) $feed->apiVersion;
        $this->_feed['updated']      = (string) $feed->updated;
        foreach ($feed->link as $link) {
            switch ($link['rel']) {
                case 'self':
                    $this->_feed['link']['self'] = $link['href'];
                    break;
                case 'first':
                    $this->_feed['link']['first'] = $link['href'];
                    break;
                case 'next':
                    $this->_feed['link']['next'] = $link['href'];
                    break;
                case 'last':
                    $this->_feed['link']['last'] = $link['href'];
                    break;
                case 'alternate':
                    $this->_feed['link']['alternate'] = $link['href'];
                    break;
                default:
                    break;
            }
        }
        
        // Set the start parameter for the next page iteration.
        if ($this->_feed['link']['next']) {
            $query = parse_url($this->_feed['link']['next'], PHP_URL_QUERY);
            parse_str($query, $query);
            $this->_params['start'] = $query['start'];
        }
        
        // Map Zotero entry to Omeka item.
        foreach ($feed->entry as $entry) {
            
            $itemID = (string) $entry->itemID;
            
            $this->_entries[$itemID]['title']          = (string) $entry->title;
            $this->_entries[$itemID]['authorName']     = (string) $entry->author->name;
            $this->_entries[$itemID]['authorUri']      = (string) $entry->author->uri;
            $this->_entries[$itemID]['id']             = (string) $entry->id;
            $this->_entries[$itemID]['published']      = (string) $entry->published;
            $this->_entries[$itemID]['updated']        = (string) $entry->updated;
            $this->_entries[$itemID]['itemType']       = (string) $entry->itemType;
            $this->_entries[$itemID]['creatorSummary'] = (string) $entry->creatorSummary;
            $this->_entries[$itemID]['numChildren']    = (string) $entry->numChildren;
            $this->_entries[$itemID]['numTags']        = (string) $entry->numTags;
            foreach ($entry->link as $link) {
                switch ($link['rel']) {
                    case 'self':
                        $this->_entries[$itemID]['link']['self'] = $link['href'];
                        break;
                    case 'alternate':
                        $this->_entries[$itemID]['link']['alternate'] = $link['href'];
                        break;
                    default:
                        break;
                }
            }
            
            $item = $entry->content->item;
            $this->_entries[$itemID]['item']['libraryID']       = $item['libraryID'];
            $this->_entries[$itemID]['item']['key']             = $item['key'];
            $this->_entries[$itemID]['item']['itemType']        = $item['itemType'];
            $this->_entries[$itemID]['item']['dateAdded']       = $item['dateAdded'];
            $this->_entries[$itemID]['item']['dateModified']    = $item['dateModified'];
            $this->_entries[$itemID]['item']['createdByUserID'] = $item['createdByUserID'];
            foreach ($item->field as $field) {
                $this->_entries[$itemID]['item']['fields'][$field['name']] = (string) $field;
            }
            
            // If there is more than one creator...
            if (is_array($item->creator)) {
                foreach ($item->creator as $creator) {
                    $this->_entries[$itemID]['item']['creators'][$creator['key']]['creatorType'] = $creator['creatorType'];
                    $this->_entries[$itemID]['item']['creators'][$creator['key']]['index'] = $creator['index'];
                    $this->_entries[$itemID]['item']['creators'][$creator['key']]['dateAdded'] = $creator->creator['dateAdded'];
                    $this->_entries[$itemID]['item']['creators'][$creator['key']]['dateModified'] = $creator->creator['dateModified'];
                    
                    // need all possible creator fields
                    $this->_entries[$itemID]['item']['creators'][$creator['key']]['firstName'] = (string) $creator->creator->firstName;
                    $this->_entries[$itemID]['item']['creators'][$creator['key']]['lastName'] = (string) $creator->creator->lastName;
                    $this->_entries[$itemID]['item']['creators'][$creator['key']]['name'] = (string) $creator->creator->name;
                }
            // If there is only one creator...
            } else {
                $this->_entries[$itemID]['item']['creators'][$creator['key']]['creatorType'] = $creator['creatorType'];
                $this->_entries[$itemID]['item']['creators'][$creator['key']]['index'] = $creator['index'];
                $this->_entries[$itemID]['item']['creators'][$creator['key']]['dateAdded'] = $creator->creator['dateAdded'];
                $this->_entries[$itemID]['item']['creators'][$creator['key']]['dateModified'] = $creator->creator['dateModified'];
                
                // need all possible creator fields
                $this->_entries[$itemID]['item']['creators'][$creator['key']]['firstName'] = (string) $creator->creator->firstName;
                $this->_entries[$itemID]['item']['creators'][$creator['key']]['lastName'] = (string) $creator->creator->lastName;
                $this->_entries[$itemID]['item']['creators'][$creator['key']]['name'] = (string) $creator->creator->name;
            }
        }
    }
}