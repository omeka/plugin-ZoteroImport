<?php
class ZoteroApiClient_Validate_Zoteroapiurl extends Zend_Validate_Abstract
{
    const ZOTERO_API_URL = 'https://api.zotero.org';
    
    const MSG_URI = 'msgUri';
    const MSG_ZOTERO_API_URL = 'msgZoteroApiUri';
    
    protected $_messageTemplates = array(
        self::MSG_URI => "'%value%' is not a valid URI",
        self::MSG_ZOTERO_API_URL => "'%value%' is not a valid call to the Zotero API server"
    );
    
    public function isValid($value)
    {
        $this->_setValue($value);
        
        try {
            require_once 'Zend/Uri.php';
            $uri = Zend_Uri::factory($value);
        } catch (Exception $e) {
            $this->_error(self::MSG_URI);
            return false;
        }
        
        if (self::ZOTERO_API_URL != $uri->getScheme() . '://' . $uri->getHost()) {
            $this->_error(self::MSG_ZOTERO_API_URL);
            return false;
        }
        
        return true;
    }
}