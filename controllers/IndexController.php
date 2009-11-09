<?php
class ZoteroImport_IndexController extends Omeka_Controller_Action
{    
    public function indexAction()
    {
        $this->view->assign('form', $this->_getFeedForm());
    }
    
    public function importAction()
    {
        $form = $this->_getFeedForm();
    }
    
    protected function _getFeedForm()
    {
        require_once 'Zend/Form.php';
        $form = new Zend_Form;
        return $form;
    }
}