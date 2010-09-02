<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2007-2010
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package ZoteroImport
 */

/**
 * Represents a zotero_import_items database table object.
 * 
 * @package ZoteroImport
 */
class ZoteroImportItemTable extends Omeka_Db_Table
{
    /**
     * Finds rows by import ID.
     * 
     * @return array
     */
    public function findByImportId($importId)
    {
        $select = $this->getSelect();
        $select->where('import_id = ?');
        return $this->fetchObjects($select, array($importId));
    }
}