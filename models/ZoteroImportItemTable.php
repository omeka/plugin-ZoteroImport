<?php
class ZoteroImportItemTable extends Omeka_Db_Table
{
    public function findByImportId($importId)
    {
        $select = $this->getSelect();
        $select->where('import_id = ?');
        return $this->fetchObjects($select, array($importId));
    }
}