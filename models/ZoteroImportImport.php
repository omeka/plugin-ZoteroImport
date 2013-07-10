<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2007-2010
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package ZoteroImport
 */

/**
 * Represents a zotero_import_imports record object.
 * 
 * @package ZoteroImport
 */
class ZoteroImportImport extends Omeka_Record_AbstractRecord
{
    public $id;
    public $process_id;
    public $collection_id;
}
