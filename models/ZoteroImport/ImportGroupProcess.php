<?php
class ZoteroImport_ImportGroupProcess extends ProcessAbstract
{
    public function run($args)
    {
        require_once 'ZoteroApiClient/Service/Zotero.php';
        $z = new ZoteroApiClient_Service_Zotero;
        $feed = $z->groupItemsTop($args['id']);
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