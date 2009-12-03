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
        
        $libraryId   = $this->_getLibraryId();
        $libraryType = $this->_getLibraryType();
                
        // Verify that there are no errors when requesting this group.
        if (!$this->_verifyLibrary($libraryId, $libraryType)) {
            $this->view->assign('form', $form);
            return $this->render('index');
        }
        
        // Dispatch the background process.
        $args = array('libraryId'   => $libraryId, 
                      'libraryType' => $libraryType, 
                      'username'    => $this->_getParam('username'), 
                      'password'    => $this->_getParam('password'));
        ProcessDispatcher::startProcess('ZoteroImport_ImportLibraryProcess', null, $args);
        
        $this->flashSuccess('Importing the library. This may take a while.');
        $this->redirect->goto('index');
        
        // Assume an error occured.
        $this->view->assign('form', $form);
        return $this->render('index');
    }
    
    protected function _getLibraryType()
    {
        preg_match('/groups|users/', $this->_getParam('feedUrl'), $match);
        switch ($match[0]) {
            case 'groups':
                $libraryType = 'group';
                break;
            case 'users':
                $libraryType = 'user';
                break;
            default:
                break;
        }
        return $libraryType;
    }
    
    protected function _getLibraryId()
    {
        preg_match('/\d+/', $this->_getParam('feedUrl'), $match);
        return $match[0];
    }
    
    protected function _verifyLibrary($libraryId, $libraryType)
    {
        try {
            require_once 'ZoteroApiClient/Service/Zotero.php';
            $z = new ZoteroApiClient_Service_Zotero;
            switch ($libraryType) {
                case 'group':
                    $z->group($libraryId); // a thrown exception means error
                    break;
                case 'user':
                    $z->userItems($libraryId);
                    break;
                default:
                    break;
            }
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