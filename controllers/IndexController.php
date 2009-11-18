<?php
class ZoteroImport_IndexController extends Omeka_Controller_Action
{    
    public function indexAction()
    {
        $this->view->assign('form', $this->_getFeedForm());
    }
    
    public function importLibraryAction()
    {
        $form = $this->_getFeedForm();
        if (!$form->isValid($_POST)) {
            $this->view->assign('form', $form);
            return $this->render('index');
        }
        
        $this->view->assign('type', $this->_getLibraryType($this->_getParam('feed')));
    }
    
    protected function _getLibraryType($feed)
    {
        preg_match('/groups|users/', $feed, $match);
        return $match[0];
    }
    
    protected function _getFeedForm()
    {
        require_once 'Zend/Form.php';
        $form = new Zend_Form;
        $form->setAction($this->_helper->url('import-library', 'index', 'zotero-import'))
             ->setMethod('post')
             ->addElementPrefixPath('ZoteroApiClient_Validate', 'ZoteroApiClient/Validate/', 'validate')
             ->removeDecorator('HtmlTag');
        
        $form->addElement('text', 'feed', array(
            'label' => 'Zotero Atom Feed URL', 
            'description' => 'Enter the Atom Feed URL of the Zotero library you want to import.', 
            'class' => 'textinput', 
            'required' => true, 
            'validators' => array(array('zoteroapiurl', false, array(array('groupItems', 'userItems')))),
            'decorators' => array(
                'ViewHelper', 
                array('Description', array('tag' => 'p', 'class' => 'explanation')), 
                'Errors', 
                array(array('InputsTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'inputs')), 
                'Label', 
                array(array('FieldTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'field'))
            )
        ));
        
        $form->addElement('submit', 'submit', array(
            'label' => 'Continue', 
            'class' => 'submit', 
            'decorators' => array(
                'ViewHelper'
            )
        ));
        
        return $form;
    }
}