<?php
class ZoteroImport_IndexController extends Omeka_Controller_Action
{    
    const PROCESS_CLASS = 'ZoteroImport_ImportLibraryProcess';
    
    protected $_feedForm;
    protected $_imports;
    
    public function indexAction()
    {
        $this->_assignFeedForm();
        $this->_assignImports();
    }
    
    public function importLibraryAction()
    {
        $form = $this->_getFeedForm();
        
        if (!$form->isValid($_POST)) {
            $this->flashError('There are errors in the form. Please check below and resubmit.');
            $this->_assignFeedForm($form);
            $this->_assignImports();
            return $this->render('index');
        }
        
        $libraryId           = $this->_getLibraryId();
        $libraryType         = $this->_getLibraryType();
        $libraryCollectionId = $this->_getLibraryCollectionId();
        
        // Verify that there are no errors when requesting this group.
        if (!$this->_verifyLibrary($libraryId, 
                                   $libraryType, 
                                   $libraryCollectionId, 
                                   $this->_getParam('private_key'))) {
            $this->_assignFeedForm($form);
            $this->_assignImports();
            return $this->render('index');
        }
        
        // Create the collection.
        $collection = $this->_createCollection($libraryId, 
                                               $libraryType, 
                                               $libraryCollectionId, 
                                               $this->_getParam('private_key'));
        
        // Save a row in Zotero import.
        require_once 'ZoteroImportImport.php';
        $zoteroImport = new ZoteroImportImport;
        $zoteroImport->collection_id = $collection->id;
        $zoteroImport->save();
        
        // Dispatch the background process.
        $args = array('libraryId'           => $libraryId, 
                      'libraryType'         => $libraryType, 
                      'libraryCollectionId' => $libraryCollectionId, 
                      'privateKey'          => $this->_getParam('private_key'), 
                      'collectionId'        => $collection->id, 
                      'zoteroImportId'      => $zoteroImport->id);
        $process = ProcessDispatcher::startProcess(self::PROCESS_CLASS, null, $args);
        
        // Set the zotero import process id.
        $zoteroImport->process_id = $process->id;
        $zoteroImport->save();
        
        $this->flashSuccess("Importing the $libraryType library. This may take a while.");
        $this->redirect->goto('index');
    }
    
    public function stopImportAction()
    {
        $process = $this->getTable('Process')->find($this->_getParam('processId'));
        if (ProcessDispatcher::stopProcess($process)) {
            $this->flashSuccess('The import process has been stopped.');
            $this->redirect->goto('index');
        } else {
            $this->flashError('The import process could not be stopped.');
            $this->_assignFeedForm();
            $this->_assignImports();
            return $this->render('index');
        }
    }
    
    protected function _createCollection($libraryId, $libraryType, $collectionId, $privateKey)
    {
        require_once 'ZoteroApiClient/Service/Zotero.php';
        $z = new ZoteroApiClient_Service_Zotero($privateKey);
        if ($collectionId) {
            $method = "{$libraryType}CollectionItems";
            $feed = $z->$method($libraryId, $collectionId);
            $name = trim(preg_replace('#.+/.+/.+‘(.+)’$#', '$1', $feed->title()));
        } else {
            $method = "{$libraryType}Items";
            $feed = $z->$method($libraryId);
            $name = trim(preg_replace('#.+/(.+)/.+#', '$1', $feed->title()));
        }
        $collectionMetadata = array('public' => true, 
                                    'name'   => $name);
        return insert_collection($collectionMetadata);
    }
    
    protected function _assignFeedForm($feedForm = null)
    {
        if ($feedForm) {
            $this->view->assign('form', $feedForm);
            return;
        }
        if (!$this->_feedForm) {
            $this->_feedForm = $this->_getFeedForm();
        }
        $this->view->assign('form', $this->_feedForm);
    }
    
    protected function _assignImports($imports = null)
    {
        if ($imports) {
            $this->view->assign('imports', $imports);
            return;
        }
        if (!$this->_imports) {
            $this->_imports = $this->getTable('ZoteroImportImport')->findAll();
        }
        $this->view->assign('imports', $this->_imports);
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
    
    protected function _getLibraryCollectionId()
    {
        if (!preg_match('#/collections/(\d+)/#', $this->_getParam('feedUrl'), $match)) {
            return null;
        }
        return $match[1];
    }
    
    protected function _verifyLibrary($libraryId, $libraryType, $collectionId, $privateKey)
    {
        try {
            require_once 'ZoteroApiClient/Service/Zotero.php';
            $z = new ZoteroApiClient_Service_Zotero($privateKey);
            if ($collectionId) {
                $method = "{$libraryType}CollectionItems";
                $feed = $z->$method($libraryId, $collectionId);
            } else {
                $method = "{$libraryType}Items";
                $feed = $z->$method($libraryId);
            }
            if (0 == $feed->count()) {
                throw new Exception('No items found for this library');
            }
            return true;
        } catch (Exception $e) {
            $this->flashError($e->getMessage().'. This may indicate that the library or collection does not exist, you do not have access to the library, or the private key is invalid.');
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
            'description' => 'Enter the Atom feed URL of the Zotero user or group library or collection you want to import. This URL can be found on the library or collection page of the Zotero website, under "Subscribe to this feed."', 
            'class'       => 'textinput', 
            'size'        => '40', 
            'required'    => true, 
            'validators'  => array(array('zoteroapiurl', false, array(array('groupItems', 'userItems', 'groupCollectionItems', 'userCollectionItems')))),
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
            'description' => 'If this is a user library or collection, enter your Zotero private key. This is not required, but is necessary to access private user libraries and to download user attachments (files and web snapshots). Warning: private keys for group libraries and collections are currently not supported. You will not be able to import private group libraries and collections or download group attachments (published or private).', 
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