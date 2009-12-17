<?php
class ZoteroImportImportTable extends Omeka_Db_Table
{
    public function findAll()
    {
        $select = $this->getSelect();
        $db = $this->getDb();
        
        $select->join(array('c' => $db->Collection), 'c.id = z.collection_id', array('name'))
               ->join(array('p' => $db->Process), 'p.id = z.process_id', array('pid', 'status', 'started', 'stopped'));
        
        return $this->fetchObjects($select);
    }
}