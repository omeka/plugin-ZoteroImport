<?php
class ZoteroImport_IndexController extends Omeka_Controller_Action
{    
    public function indexAction()
    {
        // Zend_Form: text input: user library items feed URL, post submit to importUserAction
        // Zend_Form: text input: group library items feed URL, post submit to importGroupAction
        
        /**********************************************************************/
        
        $groupId = 3113;
        $itemId = 67826733;
        
        require_once 'ZoteroImport/Service/Zotero.php';
        $zotero = new ZoteroImport_Service_Zotero;
        
        $entry = $zotero->groupItem($groupId, $itemId, array('content' => 'full'));
        $this->view->assign('entry', $entry);
        
        if ($entry->numChildren) {
            echo $entry->numChildren;
            $feed = $zotero->groupItemChildren($groupId, $itemId, array('content' => 'full'));
            echo $feed->saveXml();
        }
        
        //$feed = $zotero->groupItems(3113, array('content' => 'full'));
        //echo $feed->totalResults;
        
        /**********************************************************************/
    }
    
    public function importUserAction()
    {
        // extract user ID from passed feed URL
        // check for valid user
        // kick off the background process
        // redirect to indexAction
    }
    
    public function importGroupAction()
    {
        // extract user ID from passed feed URL
        // check for valid group
        // kick off the background process
        // redirect to indexAction
    }
}