<?php
/**
 * Returns items of a particular Zotero item type. Uses the Item Type element in 
 * the Zotero element set.
 *
 * @param string $typeName
 * @param int $collectionId 
 * @return void
 */
function get_items_by_zotero_item_type($typeName, $collectionId = null, $limit = 10)
{
    $db = get_db();
    
    // Get the Zotero:Item Type element
    $element = $db->getTable('Element')->findByElementSetNameAndElementName('Zotero', 'Item Type');
    
    // Using the advanced search interface, get a limited set of items that have 
    // the provided Zotero:Item Type.
    $items = get_items(array('collection' => $collectionId, 
                             'recent' => true, 
                             'advanced_search' => array(array('type' => 'contains', 
                                                              'element_id' => $element->id, 
                                                              'terms' => $typeName))), 
                       $limit);
    
    return $items;
}