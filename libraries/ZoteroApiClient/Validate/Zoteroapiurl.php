<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2007-2010
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package ZoteroImport
 */

/**
 * Contains code that validates a Zotero API URL.
 * 
 * @package ZoteroImport
 */
class ZoteroApiClient_Validate_Zoteroapiurl extends Zend_Validate_Abstract
{
    const ZOTERO_API_URL = 'https://api.zotero.org';
    
    const INVALID_URI = 'invalidUri';
    const INVALID_ZOTERO_API_URL = 'invalidZoteroApiUri';
    const INVALID_ZOTERO_API_METHOD = 'invalidZoteroApiMethod';
    
    protected $_method;
    protected $_methods = array(
        'groupItems' => '/groups/\d+/items(/top)?/?$', 
        'userItems' => '/users/\d+/items(/top)?/?$', 
        'groupCollectionItems' => '/groups/\d+/collections/.+/items(/top)?/?$', 
        'userCollectionItems' => '/users/\d+/collections/.+/items(/top)?/?$'
    );
    
    protected $_messageTemplates = array(
        self::INVALID_URI => "'%value%' is not a valid URI", 
        self::INVALID_ZOTERO_API_URL => "'%value%' is not a valid call to the Zotero API server", 
        self::INVALID_ZOTERO_API_METHOD => "Unexpected Zotero API method: '%value%'"
    );
    
    public function __construct($method = null)
    {
        $this->setMethod($method);
    }
    
    public function setMethod($method)
    {
        if (is_array($method)) {
            foreach ($method as $meth) {
                $this->checkValidMethod($meth);
            }
        } else if (is_string($method)) {
            $this->checkValidMethod($method);
        } else {
            $method = null;
        }
        
        // Assume valid method(s) if everything checks out.
        $this->_method = $method;
    }
    
    public function checkValidMethod($method)
    {
        if (!array_key_exists($method, $this->_methods)) {
            require_once 'Zend/Validate/Exception.php';
            throw new Zend_Validate_Exception("Invalid method \"$method\" send to the Zotero API URL validator.");
        }
        return true;
    }
    
    public function isValid($value)
    {
        $this->_setValue($value);
        
        // Check for valid URI.
        try {
            require_once 'Zend/Uri.php';
            $uri = Zend_Uri::factory($value);
        } catch (Exception $e) {
            $this->_error(self::INVALID_URI);
            return false;
        }
        
        // Check for valid Zotero API host URL.
        if (self::ZOTERO_API_URL != $uri->getScheme() . '://' . $uri->getHost()) {
            $this->_error(self::INVALID_ZOTERO_API_URL);
            return false;
        }
        
        // Check for valid method URL.
        if ($this->_method) {
            if (is_array($this->_method)) {
                $isValid = false;
                foreach ($this->_method as $method) {
                    if (preg_match('#'.$this->_methods[$method].'#', $uri->getPath())) {
                        $isValid = true;
                        break;
                    }
                }
                if (!$isValid) {
                    $this->_error(self::INVALID_ZOTERO_API_METHOD);
                    return false;
                }
            } else if (is_string($this->_method)) {
                if (!preg_match('#'.$this->_methods[$this->_method].'#', $uri->getPath())) {
                    $this->_error(self::INVALID_ZOTERO_API_METHOD);
                    return false;
                }
            }
        }
        
        return true;
    }
}