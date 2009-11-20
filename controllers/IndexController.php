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
            $this->flashError('There are errors in the form. Please check below and resubmit.');
            $this->view->assign('form', $form);
            return $this->render('index');
        }
        
        $type = $this->_getLibraryType($this->_getParam('feedUrl'));
        $id   = $this->_getLibraryId($this->_getParam('feedUrl'));
        
        switch ($type) {
            case 'groups':
                
                // Verify that there are no errors when requesting this group.
                if (!$this->_verifyGroup($id)) {
                    $this->view->assign('form', $form);
                    return $this->render('index');
                }
                
                // Dispatch the background process.
                $args = array('id'       => $id, 
                              'username' => $this->_getParam('username'), 
                              'password' => $this->_getParam('password'), 
                              'user_id'  => current_user()->id);
                ProcessDispatcher::startProcess('ZoteroImport_ImportGroupProcess', null, $args);
                
                $this->flashSuccess('Importing the group. This may take a while.');
                $this->redirect->goto('index');
                break;
            
            case 'users':
                $this->flashError('Error: user import is not yet supported.');
                break;
            default:
                $this->flashError('Error: unknown import.');
                break;
        }
        
        // Assume an error occured.
        $this->view->assign('form', $form);
        return $this->render('index');
    }
    
    protected function _getLibraryType($feedUrl)
    {
        preg_match('/groups|users/', $feedUrl, $match);
        return $match[0];
    }
    
    protected function _getLibraryId($feedUrl)
    {
        preg_match('/\d+/', $feedUrl, $match);
        return $match[0];
    }
    
    protected function _verifyGroup($id)
    {
        try {
            require_once 'ZoteroApiClient/Service/Zotero.php';
            $z = new ZoteroApiClient_Service_Zotero;
            $feed = $z->group($id); // a thrown exception means error
            return true;
        } catch (Exception $e) {
            $this->flashError($e->getMessage());
            return false;
        }
    }
    
    protected function _getFeedForm()
    {
        require_once 'Zend/Form.php';
        $form = new Zend_Form;
        $form->setAction($this->_helper->url('import-library', 'index', 'zotero-import'))
             ->setMethod('post')
             ->addElementPrefixPath('ZoteroApiClient_Validate', 'ZoteroApiClient/Validate/', 'validate')
             ->removeDecorator('HtmlTag');
        
        $form->addElement('text', 'feedUrl', array(
            'label'       => 'Zotero Atom Feed URL', 
            'description' => 'Enter the Atom Feed URL of the Zotero library you want to import.', 
            'class'       => 'textinput', 
            'size'        => '60', 
            'required'    => true, 
            'validators'  => array(array('zoteroapiurl', false, array(array('groupItems', 'userItems')))),
            'decorators'  => array(
                'ViewHelper', 
                array('Description', array('tag' => 'p', 'class' => 'explanation')), 
                'Errors', 
                array(array('InputsTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'inputs')), 
                'Label', 
                array(array('FieldTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'field'))
            )
        ));
        
        $form->addElement('text', 'username', array(
            'label'       => 'Username', 
            'class'       => 'textinput', 
            'size'        => '30', 
            'decorators'  => array(
                'ViewHelper', 
                array('Description', array('tag' => 'p', 'class' => 'explanation')), 
                'Errors', 
                array(array('InputsTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'inputs')), 
                'Label', 
                array(array('FieldTag' => 'HtmlTag'), array('tag' => 'div', 'class' => 'field'))
            )
        ));
        
        $form->addElement('password', 'password', array(
            'label'       => 'Password', 
            'description' => 'Enter the relevant Zotero username and password. This is not required, but is necessary to download attachments and to access protected libraries.', 
            'class'       => 'textinput', 
            'size'        => '30', 
            'decorators'  => array(
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