<?php

/**
 * Returns items of a particular Zotero item type. Uses the Item Type
 * field in the Zotero element set.
 *
 * @param string $typeName
 * @param int $collectionId 
 * @return void
 */
function get_items_by_zotero_item_type($typeName, $collectionId = null)
{
   
    // Get the table element
    $db = get_db();
    $element = $db->getTable('Element')->findByElementSetNameAndElementName('Zotero', 'Item Type');
    
    // Get the items th
    $items = get_items(array('collection' => $collectionId, 'recent' => true, 'advanced_search' => array(array('type' => 'contains', 'element_id' => $element->id, 'terms' => $typeName))));
    
    return $items;
}