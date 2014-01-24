<?php
class ZoteroImport_IndexController extends Omeka_Controller_AbstractActionController
{    
    const PROCESS_CLASS_IMPORT = 'ZoteroImport_ImportProcess';
    const PROCESS_CLASS_DELETE_IMPORT = 'ZoteroImport_DeleteImportProcess';
    
    protected $_feedForm;
    protected $_imports;
    
    /**
     * Process the index action.
     */
    public function indexAction()
    {
        $this->_assignFeedForm();
        $this->_assignImports();
    }
    
    /**
     * Process the import-library action.
     */
    public function importLibraryAction()
    {
        $form = $this->_getFeedForm();
        
        if (!$form->isValid($_POST)) {
            $this->_helper->flashMessenger('There are errors in the form. Please check below and resubmit.', 'error');
            $this->_assignFeedForm($form);
            $this->_assignImports();
            return $this->render('index');
        }
        
        $libraryId = $this->_getLibraryId();
        $libraryType = $this->_getLibraryType();
        $libraryCollectionId = $this->_getLibraryCollectionId();
        
        // Verify that there are no errors when requesting this group.
        if (!$this->_verifyLibrary($libraryId, $libraryType, $libraryCollectionId, 
                $this->_getParam('private_key'))
        ) {
            $this->_assignFeedForm($form);
            $this->_assignImports();
            return $this->render('index');
        }
        
        // Create the collection.
        $collection = $this->_createCollection(
            $libraryId, $libraryType, $libraryCollectionId, $this->_getParam('private_key')
        );
        
        // Save a row in Zotero import.
        require_once 'ZoteroImportImport.php';
        $zoteroImport = new ZoteroImportImport;
        $zoteroImport->collection_id = $collection->id;
        $zoteroImport->save();
        
        // Dispatch the background process.
        $args = array(
            'libraryId'           => $libraryId, 
            'libraryType'         => $libraryType, 
            'libraryCollectionId' => $libraryCollectionId, 
            'privateKey'          => $this->_getParam('private_key'), 
            'collectionId'        => $collection->id, 
            'zoteroImportId'      => $zoteroImport->id
        );
        $process = Omeka_Job_Process_Dispatcher::startProcess(self::PROCESS_CLASS_IMPORT, null, $args);
        
        // Set the zotero import process id.
        $zoteroImport->process_id = $process->id;
        $zoteroImport->save();
        
        $this->_helper->flashMessenger("Importing the $libraryType library. This may take a while.", 'success');
        $this->_helper->redirector->goto('index');
    }
    
    /**
     * Process the stop-import action.
     */
    public function stopImportAction()
    {
        $process = $this->_helper->db->getTable('Process')->find($this->_getParam('processId'));
        if (Omeka_Job_Process_Dispatcher::stopProcess($process)) {
            $this->_helper->flashMessenger('The import process has been stopped.', 'success');
            $this->_helper->redirector->goto('index');
        } else {
            $this->_helper->flashMessenger('The import process could not be stopped.', 'error');
            $this->_assignFeedForm();
            $this->_assignImports();
            return $this->render('index');
        }
    }
    
    /**
     * Process the delete-import action.
     */
    public function deleteImportAction()
    {
        $process = $this->_helper->db->getTable('Process')->find($this->_getParam('processId'));
        $process = Omeka_Job_Process_Dispatcher::startProcess(self::PROCESS_CLASS_DELETE_IMPORT, 
                                                   null, 
                                                   array('processId' => $process->id));
        $this->_helper->flashMessenger('Deleting the import. This may take a while', 'success');
        $this->_helper->redirector->goto('index');
    }
    
    /**
     * Creates an Omeka collection that corresponds to the imported Zotero 
     * library/collection.
     * 
     * @uses insert_collection()
     * @param int $libraryId The library ID.
     * @param string $libraryType The type of library, user or group.
     * @param int|null $collectionId The collection ID.
     * @param string $privateKey The Zotero API private key.
     * @return Collection Omeka collection object.
     */
    protected function _createCollection($libraryId, $libraryType, $collectionId, $privateKey)
    {
        require_once 'ZoteroApiClient/Service/Zotero.php';
        // Get the collection title from the Zotero API.
        $z = new ZoteroApiClient_Service_Zotero($privateKey);
        if ($collectionId) {
            $method = "{$libraryType}CollectionItemsTop";
            $feed = $z->$method($libraryId, $collectionId);
            $name = trim(preg_replace('#.+/.+/.+‘(.+)’$#', '$1', $feed->title()));
        } else {
            $method = "{$libraryType}ItemsTop";
            $feed = $z->$method($libraryId);
            $name = trim(preg_replace('#.+/(.+)/.+#', '$1', $feed->title()));
        }
        $collectionMetadata = array('public' => true);
        $elementTexts = array(
            'Dublin Core' => array(
                'Title' => array(
                    array('text' => $name, 'html' => true)
                )
            )
        );
        return insert_collection($collectionMetadata, $elementTexts);
    }
    
    /**
     * Assigns the feed form to the view.
     * 
     * @param Zend_Form|null
     */
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
    
    /**
     * Assigns the existing Zotero Import imports records to the view.
     * 
     * @param array|null $imports
     */
    protected function _assignImports($imports = null)
    {
        if ($imports) {
            $this->view->assign('imports', $imports);
            return;
        }
        if (!$this->_imports) {
            $this->_imports = $this->_helper->db->getTable('ZoteroImportImport')->findAll();
        }
        $this->view->assign('imports', $this->_imports);
    }
    
    /**
     * Extracts the library type from the feed URL.
     * 
     * @return string The library type, group or user.
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
    
    /**
     * Extracts the library ID from the feed URL.
     * 
     * @return int The library ID.
     */
    protected function _getLibraryId()
    {
        preg_match('/\d+/', $this->_getParam('feedUrl'), $match);
        return $match[0];
    }
    
    /**
     * Extracts the collection ID from the feed URL.
     * 
     * @return int The collection ID.
     */
    protected function _getLibraryCollectionId()
    {
        if (!preg_match('#/collections/([^/]+)/#', $this->_getParam('feedUrl'), $match)) {
            return null;
        }
        return $match[1];
    }
    
    /**
     * Verifies that that requested library or collection is available.
     * 
     * @param int $libraryId
     * @param string $libraryType
     * @param int $collectionId
     * @param string $privateKey
     * @return bool
     */
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
            $this->_helper->flashMessenger($e->getMessage().'. This may indicate that the library or collection does not exist, you do not have access to the library, or the private key is invalid.', 'error');
            return false;
        }
    }
    
    /**
     * Builds the feed form.
     * 
     * @return Zend_Form
     */
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
            'description' => 'Enter your Zotero private key. This is not required, but is necessary to access private libraries and collections and to download attachments (files and web snapshots).', 
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
