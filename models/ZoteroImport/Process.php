<?php
class ZoteroImport_Process
{
    protected $_args;
    
    public function run($args)
    {
        $this->_args = $args;
        $this->import();
    }
}