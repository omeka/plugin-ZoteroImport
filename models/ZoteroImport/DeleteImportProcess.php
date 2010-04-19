<?php
class ZoteroImport_DeleteImportProcess extends ProcessAbstract
{
    protected $_db;
    protected $_processId;
    
    public function run($args)
    {
        ini_set('memory_limit', '500M');
        $this->_db = get_db();
        $this->_processId = $args['processId'];
        $this->_deleteImport();
    }
    
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