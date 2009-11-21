<?php
// question: what exactly are the "API key" and "shared secret"? And where can I find them?

class ZoteroImport_ImportGroupProcess extends ProcessAbstract
{
    protected $_feed = array();
    protected $_entries = array();
    
    public function run($args)
    {
        // /usr/bin/php /var/www/omekatag/application/core/background.php -p 30 -l initializeRoutes
        // getting a 500 Internal Server Error
        require_once 'ZoteroApiClient/Service/Zotero.php';
        $z = new ZoteroApiClient_Service_Zotero($args['username'], $args['password']);
        $location = $z->userItemFile(66453, 75201954);
        exit($location);
        
/******************************************************************************/
        
        // /usr/bin/php /var/www/omekatag/application/core/background.php -p 29 -l initializeRoutes
        require_once 'ZoteroApiClient/Service/Zotero.php';
        $z = new ZoteroApiClient_Service_Zotero;
        $feed = $z->groupItemsTop($args['id'], array('content' => 'full'));
        
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
        
        
        print_r($this->_entries);
        /*
        $z = new ZoteroImport_Service_Zotero();
        
        //$result = $z->userItemsTop(66453, array('content' => 'full'));
        //$result = $z->groupItems(3113, array('start' => 300, 'content' => 'full'));
        //print_r($result);
        
        $groupId = 3113;
        
        do {
            
            // Start iteration at 0.
            if (!isset($start)) {
                $start = 0;
            }
            
            // Get the request response.
            $xml = $z->groupItems($groupId, array('start' => $start, 'content' => 'full'))->getIterator();
            
            // Register XPath namespaces.
            $xml->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
            $xml->registerXPathNamespace('zapi', 'http://zotero.org/ns/api');
            $xml->registerXPathNamespace('zxfer', 'http://zotero.org/ns/transfer');
            
            // Set the self, next, and last page URLs.
            $links = array('self', 'next', 'last');
            foreach ($links as $link) {
                $$link = $xml->xpath("atom:link[@rel='$link']/attribute::href");
                $$link = (string) ${$link}[0];
            }
            
            // Set the start parameter for the next page iteration.
            if ($next) {
                $query = parse_url($next, PHP_URL_QUERY);
                parse_str($query);
            }
            
            //echo "self: $self\nnext: $next\nlast: $last\n";
            
            // MAP ZOTERO ENTRY TO OMEKA ITEM...
            $entries = $xml->entry;
            foreach ($entries as $entry) {
                
                //print_r($entry);exit;
                
                $title      = (string) $entry->title;
                $authorName = (string) $entry->name;
                $authorUri  = (string) $entry->uri;
                $id         = (string) $entry->id;
                $published  = (string) $entry->published;
                $updated    = (string) $entry->updated;
                
                $links = $entry->link;
                foreach ($links as $link) {
                    // get links
                }
                
                $zapi = $entry->children('http://zotero.org/ns/api');
                $itemId   = $zapi->itemId;
                $itemType = $zapi->itemType;
                $numTags  = $zapi->numTags;
                
                $content = $entry->content;
                    
                    $item = $content->item;
                        
                        $fields = $item->field;
                        foreach ($fields as $field) {
                            // get fields
                        }
                        
                        $creators = $item->creator;
                        foreach ($creators as $creator) {
                            // get creators
                        }
            }
            
            //$totalResults = $xml->xpath('zapi:totalResults');
            //print_r($totalResults);exit;
        
        // Stop iteration if the current and last pages are identical.
        } while ($self != $last); // !$next ?
        */
    }
}