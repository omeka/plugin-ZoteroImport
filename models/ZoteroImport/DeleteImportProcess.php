<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2007-2010
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package ZoteroImport
 */

/**
 * The Zotero delete import process.
 * 
 * @package ZoteroImport
 */
class ZoteroImport_DeleteImportProcess extends Omeka_Job_Process_AbstractProcess
{
    protected $_db;
    protected $_processId;
    
    /**
     * Runs the delete import process.
     * 
     * @param array $args Required arguments to run the process.
     */
    public function run($args)
    {
        ini_set('memory_limit', '500M');
        $this->_db = get_db();
        $this->_processId = $args['processId'];
        $this->_deleteImport();
    }
    
    /**
     * Deletes all the items imported from an import process.
     */
    protected function _deleteImport()
    {
        $process = $this->_db->getTable('Process')->find($this->_processId);
        $args = $process->getArguments();
        
        // Delete the items.
        $zoteroImportItems = $this->_db->getTable('ZoteroImportItem')->findByImportId($args['zoteroImportId']);
        foreach ($zoteroImportItems as $zoteroImportItem) {
            if ($zoteroImportItem->item_id) {
                $item = $this->_db->getTable('Item')->find($zoteroImportItem->item_id);
                $item->delete();
            }
            $zoteroImportItem->delete();
        }
        
        // Delete the collection.
        $collection = $this->_db->getTable('Collection')->find($args['collectionId']);
        $collection->delete();
        
        // Delete the import.
        $import = $this->_db->getTable('ZoteroImportImport')->find($args['zoteroImportId']);
        $import->delete();
    }
}
