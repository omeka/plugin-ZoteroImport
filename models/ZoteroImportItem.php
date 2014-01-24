<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2007-2010
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package ZoteroImport
 */

/**
 * Represents a zotero_import_items record object.
 * 
 * @package ZoteroImport
 */
class ZoteroImportItem extends Omeka_Record_AbstractRecord
{
    public $id;
    public $import_id;
    public $item_id;
    public $zotero_item_key;
    public $zotero_item_parent_key;
    public $zotero_item_type;
    public $zotero_updated;
}
