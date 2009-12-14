<?php
class ZoteroImport_IndexController extends Omeka_Controller_Action
{    
    const PROCESS_CLASS = 'ZoteroImport_ImportLibraryProcess';
    
    public function indexAction()
    {
        $this->view->assign('form', $this->_getFeedForm());
        $this->view->assign('processes', $this->getTable('Process')->findByClass(self::PROCESS_CLASS));
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
        if (!$this->_verifyLibrary($libraryId, 
                                   $libraryType, 
                                   $this->_getParam('private_key'))) {
            $this->view->assign('form', $form);
            $this->view->assign('processes', $this->getTable('Process')->findByClass(self::PROCESS_CLASS));
            return $this->render('index');
        }
        
        // Create the collection.
        $collection = $this->_getCollection($libraryId, 
                                            $libraryType, 
                                            $this->_getParam('private_key'));
        
        // Dispatch the background process.
        $args = array('libraryId'      => $libraryId, 
                      'libraryType'    => $libraryType, 
                      'collectionName' => $collection->name, 
                      'collectionId'   => $collection->id, 
                      'privateKey'     => $this->_getParam('private_key'));
        ProcessDispatcher::startProcess(self::PROCESS_CLASS, null, $args);
        
        $this->flashSuccess('Importing the library. This may take a while.');
        $this->redirect->goto('index');
    }
    
    // Commented out until this bug is fixed: https://omeka.org/trac/ticket/868
    /*
    public function stopProcessAction()
    {
        $process = $this->getTable('Process')->find($this->_getParam('processId'));
        if (ProcessDispatcher::stopProcess($process)) {
            $this->flashSuccess('The process has been stopped.');
            $this->redirect->goto('index');
        } else {
            $this->flashError('The process could not be stopped.');
            $this->view->assign('form', $this->_getFeedForm());
            $this->view->assign('processes', $this->getTable('Process')->findByClass(self::PROCESS_CLASS));
            return $this->render('index');
        }
    }
    */
    
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
    
    protected function _getCollection($libraryId, $libraryType, $privateKey)
    {
        require_once 'ZoteroApiClient/Service/Zotero.php';
        $z = new ZoteroApiClient_Service_Zotero($privateKey);
        $method = "{$libraryType}Items";
        $feed = $z->$method($libraryId);
        $collectionMetadata = array('public' => true, 
                                    'name'   => trim(preg_replace('#.+/(.+)/.+#', '$1', $feed->title())));
        return insert_collection($collectionMetadata);
    }
    
    protected function _verifyLibrary($libraryId, $libraryType, $privateKey)
    {
        try {
            require_once 'ZoteroApiClient/Service/Zotero.php';
            $z = new ZoteroApiClient_Service_Zotero($privateKey);
            $method = "{$libraryType}Items";
            $feed = $z->$method($libraryId);
            if (0 == $feed->count()) {
                throw new Exception('No items found for this library');
            }
            return true;
        } catch (Exception $e) {
            $this->flashError($e->getMessage().'. This may indicate that the library does not exist, you do not have access to the library, or the private key is invalid.');
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
            'description' => 'Enter the Atom feed URL of the Zotero user or group library you want to import. This URL can be found on the library page of the Zotero website, under "Subscribe to this feed."', 
            'class'       => 'textinput', 
            'size'        => '40', 
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
        
        $form->addElement('password', 'private_key', array(
            'label'       => 'Private Key', 
            'description' => 'If this is a user library, enter your Zotero private key. This is not required, but is necessary to access private user libraries and to download user library attachments (files and web snapshots). Warning: private keys for group libraries are currently not supported. You will not be able to import  private group libraries or download group library attachments (published or private).', 
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