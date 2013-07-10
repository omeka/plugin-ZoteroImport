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
        $select = $this->getSelect();
        $db = $this->getDb();
        
           $pAlias = $db->getTable('Process')->getTableAlias();
           $zAlias = $db->getTable('ZoteroImportImport')->getTableAlias();

           $select->join(array($pAlias => $db->Process),
                               "$pAlias.id = $zAlias.process_id", array('pid', 'status', 'started', 'stopped'))
                  ->order(array("$zAlias.id ASC"));      
        return $this->fetchObjects($select);
    }
}
