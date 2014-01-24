<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2007-2010
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package ZoteroImport
 */

/**
 * Represents a zotero_import_imports database table object.
 * 
 * @package ZoteroImport
 */
class ZoteroImportImportTable extends Omeka_Db_Table
{
    /**
     * Finds all the records in this table.
     * 
     * @return array
     */
    public function findAll()
    {
        $db = $this->getDb();
        $sql = "
        SELECT *
        FROM `{$db->prefix}zotero_import_imports` AS `z`
        JOIN `{$db->prefix}collections` AS `c` ON c.id = z.collection_id
        JOIN `{$db->prefix}processes` AS `p` ON p.id = z.process_id
        ORDER BY `z`.`id` ASC";
        return $this->fetchObjects($sql);
    }
}
