<?php
/**
 * Returns items of a particular Zotero item type. Uses the Item Type element in 
 * the Zotero element set.
 *
 * @param string $typeName
 * @param int $collectionId 
 * @return void
 */
function zotero_import_get_items_by_zotero_item_type($typeName, $collectionId = null, $limit = 10)
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

/**
 * Returns custom text built from elements from the Zotero element set.
 * 
 * @param array $parts The parts of the output text, mapped from Zotero elements.
 * array(
 *     'element'   => {Zotero element name, text, required}, 
 *     'prefix'    => {part prefix, text, optional}, 
 *     'suffix'    => {part suffix, text, optional}, 
 *     'all'       => {get all element texts?, boolean, optional}, 
 *     'delimiter' => {element text delimiter, text, optional}
 * )
 * @return string The output text.
 */
function zotero_import_build_zotero_output(array $parts = array())
{
    $output = '';
    foreach ($parts as $part) {
        
        if (!isset($part['element']) || !is_string($part['element'])) {
            throw new Exception('Zotero output parts must include an element name.');
        }
        
        // Set the options.
        $options = array();
        if (isset($part['all']) && $part['all']) {
            $options['all'] = true;
        }
        if (isset($part['delimiter'])) {
            $options['delimiter'] = $part['delimiter'];
        }
        
        // Set the element text.
        $elementText = item(ZoteroImportPlugin::ZOTERO_ELEMENT_SET_NAME, $part['element'], $options);
        if (!$elementText) {
            continue;
        }
        
        // Build the output.
        $output .= isset($part['prefix']) ? $part['prefix'] : '';
        $output .= is_array($elementText) ? implode(', ', $elementText) : $elementText;
        $output .= isset($part['suffix']) ? $part['suffix'] : '';
    }
    return $output;
}