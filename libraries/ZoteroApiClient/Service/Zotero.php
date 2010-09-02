<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2007-2010
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package ZoteroImport
 */

require_once 'Zend/Rest/Client.php';

/**
 * Contains code used to interface with the Zotero API.
 * 
 * @package ZoteroImport
 */
class ZoteroApiClient_Service_Zotero extends Zend_Rest_Client
{
    const URI = 'https://api.zotero.org';
    
    protected $_privateKey;
    
    /**
     * Constructs the object.
     * 
     * @param string|null The Zotero private key needed for the requests.
     */
    public function __construct($privateKey = null)
    {
        $this->_privateKey = $privateKey;
        $this->setUri(self::URI);
    }
    
    /**
     * Sets the Zotero private key.
     * 
     * @param string|null The Zotero private key needed for the requests.
     */
    public function setPrivateKey($privateKey)
    {
        $this->_privateKey = $privateKey;
    }
    
    /**
     * Gets a user items feed.
     * 
     * @param int The user ID.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function userItems($userId, array $params = array())
    {
        $path = "/users/$userId/items";
        return $this->_getFeed($path, $params);
    }
    
    /**
     * Gets a user collection items feed.
     * 
     * @param int The user ID.
     * @param int The collection ID.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function userCollectionItems($userId, $collectionId, array $params = array())
    {
        $path = "/users/$userId/collections/$collectionId/items";
        return $this->_getFeed($path, $params);
    }
    
    /**
     * Gets a user collection top items feed.
     * 
     * @param int The user ID.
     * @param int The collection ID.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function userCollectionItemsTop($userId, $collectionId, array $params = array())
    {
        $path = "/users/$userId/collections/$collectionId/items/top";
        return $this->_getFeed($path, $params);
    }
    
    /**
     * Gets a user top items feed.
     * 
     * @param int The user ID.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function userItemsTop($userId, array $params = array())
    {
        $path = "/users/$userId/items/top";
        return $this->_getFeed($path, $params);
    }
    
    /**
     * Gets a user item feed.
     * 
     * @param int The user ID.
     * @param int The item ID.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function userItem($userId, $itemId, array $params = array())
    {
        $path = "/users/$userId/items/$itemId";
        return $this->_getFeed($path, $params);
    }
    
    /**
     * Gets a user item tags feed.
     * 
     * @param int The user ID.
     * @param int The item ID.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function userItemTags($userId, $itemId, array $params = array())
    {
        $path = "/users/$userId/items/$itemId/tags";
        return $this->_getFeed($path, $params);
    }
    
    /**
     * Gets a user item children feed.
     * 
     * @param int The user ID.
     * @param int The item ID.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function userItemChildren($userId, $itemId, array $params = array())
    {
        $path = "/users/$userId/items/$itemId/children";
        return $this->_getFeed($path, $params);
    }
    
    /**
     * Gets the location of a user item file.
     * 
     * @param int The user ID.
     * @param int The item ID.
     * @param array Additional parameters for the request.
     * @return string
     */
    public function userItemFile($userId, $itemId, array $params = array())
    {
        $path = "/users/$userId/items/$itemId/file";
        $this->_setConfig(array('maxredirects' => 0));
        return $this->restGet($path, $this->_filterParams($params))->getHeader('Location');
    }
    
    /**
     * Gets a group feed.
     * 
     * @param int The group ID.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function group($groupId, array $params = array())
    {
        $path = "/groups/$groupId";
        return $this->_getFeed($path, $params);
    }
    
    /**
     * Gets a group items feed.
     * 
     * @param int The group ID.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function groupItems($groupId, array $params = array())
    {
        $path = "/groups/$groupId/items";
        return $this->_getFeed($path, $params);
    }
    
    /**
     * Gets a group collection items feed.
     * 
     * @param int The group ID.
     * @param int The collection ID.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function groupCollectionItems($groupId, $collectionId, array $params = array())
    {
        $path = "/groups/$groupId/collections/$collectionId/items";
        return $this->_getFeed($path, $params);
    }
    
    /**
     * Gets a group collection top items feed.
     * 
     * @param int The group ID.
     * @param int The collection ID.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function groupCollectionItemsTop($groupId, $collectionId, array $params = array())
    {
        $path = "/groups/$groupId/collections/$collectionId/items/top";
        return $this->_getFeed($path, $params);
    }
    
    /**
     * Gets a group top items feed.
     * 
     * @param int The group ID.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function groupItemsTop($groupId, array $params = array())
    {
        $path = "/groups/$groupId/items/top";
        return $this->_getFeed($path, $params);
    }
    
    /**
     * Gets a group item feed.
     * 
     * @param int The group ID.
     * @param int The item ID.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function groupItem($groupId, $itemId, array $params = array())
    {
        $path = "/groups/$groupId/items/$itemId";
        $feed = $this->_getFeed($path, $params);
        return $feed->current();
    }
    
    /**
     * Gets the location of a group item file.
     * 
     * @param int The group ID.
     * @param int The item ID.
     * @param array Additional parameters for the request.
     * @return string
     */
    public function groupItemFile($groupId, $itemId, array $params = array())
    {
        $path = "/groups/$groupId/items/$itemId/file";
        $this->_setConfig(array('maxredirects' => 0));
        return $this->restGet($path, $this->_filterParams($params))->getHeader('Location');
    }
    
    /**
     * Gets a group item children feed.
     * 
     * @param int The group ID.
     * @param int The item ID.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function groupItemChildren($groupId, $itemId, array $params = array())
    {
        $path = "/groups/$groupId/items/$itemId/children";
        return $this->_getFeed($path, $params);
    }
    
    /**
     * Gets a group item tags feed.
     * 
     * @param int The group ID.
     * @param int The item ID.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function groupItemTags($groupId, $itemId, array $params = array())
    {
        $path = "/groups/$groupId/items/$itemId/tags";
        return $this->_getFeed($path, $params);
    }
    
    /**
     * Sets HTTP configuration for the next request.
     * 
     * @param array
     */
    protected function _setConfig(array $config)
    {
        self::getHttpClient()->setConfig($config);
    }
    
    /**
     * Requests an Atom feed from the Zotero API.
     * 
     * @param string The Zotero API path for the desired action.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    protected function _getFeed($path, $params)
    {
        require_once 'Zend/Feed/Atom.php';
        return new Zend_Feed_Atom($this->_getUri($path, $this->_filterParams($params)));
    }
    
    /**
     * Filters the request parameters to include the private key if it exists.
     * 
     * @param array Additional parameters for the request.
     * @return array
     */
    protected function _filterParams(array $params = array())
    {
        if (!isset($params['key']) && $this->_privateKey) {
            $params['key'] = $this->_privateKey;
        }
        return $params;
    }
    
    /**
     * Builds a valid URI for a Zotero API request.
     * 
     * @param string The Zotero API path for the desired action.
     * @param array Additional parameters for the request.
     * @return string
     */
    protected function _getUri($path, $params)
    {
        require_once 'Zend/Uri.php';
        $uri = Zend_Uri::factory(self::URI);
        $uri->setPath($path);
        $uri->setQuery($params);
        return $uri->getUri();
    }
}
