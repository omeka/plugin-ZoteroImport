<?php
require_once 'Zend/Rest/Client.php';

class ZoteroApiClient_Service_Zotero extends Zend_Rest_Client
{
    const URI = 'https://api.zotero.org';
    
    protected $_privateKey;
    
    public function __construct($privateKey = null)
    {
        $this->_privateKey = $privateKey;
        $this->setUri(self::URI);
    }
    
    public function setPrivateKey($privateKey)
    {
        $this->_privateKey = $privateKey;
    }
    
    public function userItems($userId, array $params = array())
    {
        $path = "/users/$userId/items";
        return $this->_getFeed($path, $params);
    }
    
    public function userItemsTop($userId, array $params = array())
    {
        $path = "/users/$userId/items/top";
        return $this->_getFeed($path, $params);
    }
    
    public function userItem($userId, $itemId, array $params = array())
    {
        $path = "/users/$userId/items/$itemId";
        return $this->_getFeed($path, $params);
    }
    
    public function userItemTags($userId, $itemId, array $params = array())
    {
        $path = "/users/$userId/items/$itemId/tags";
        return $this->_getFeed($path, $params);
    }
    
    public function userItemChildren($userId, $itemId, array $params = array())
    {
        $path = "/users/$userId/items/$itemId/children";
        return $this->_getFeed($path, $params);
    }
    
    public function userItemFile($userId, $itemId, array $params = array())
    {
        $path = "/users/$userId/items/$itemId/file";
        $this->_setConfig(array('maxredirects' => 0));
        return $this->restGet($path, $this->_filterParams($params))->getHeader('Location');
    }
    
    public function group($groupId, array $params = array())
    {
        $path = "/groups/$groupId";
        return $this->_getFeed($path, $params);
    }
    
    public function groupItems($groupId, array $params = array())
    {
        $path = "/groups/$groupId/items";
        return $this->_getFeed($path, $params);
    }
    
    public function groupItemsTop($groupId, array $params = array())
    {
        $path = "/groups/$groupId/items/top";
        return $this->_getFeed($path, $params);
    }
    
    public function groupItem($groupId, $itemId, array $params = array())
    {
        $path = "/groups/$groupId/items/$itemId";
        $feed = $this->_getFeed($path, $params);
        return $feed->current();
    }
    
    public function groupItemFile($groupId, $itemId, array $params = array())
    {
        $path = "/groups/$groupId/items/$itemId/file";
        $this->_setConfig(array('maxredirects' => 0));
        return $this->restGet($path, $this->_filterParams($params))->getHeader('Location');
    }
    
    public function groupItemChildren($groupId, $itemId, array $params = array())
    {
        $path = "/groups/$groupId/items/$itemId/children";
        return $this->_getFeed($path, $params);
    }
    
    public function groupItemTags($groupId, $itemId, array $params = array())
    {
        $path = "/groups/$groupId/items/$itemId/tags";
        return $this->_getFeed($path, $params);
    }
    
    protected function _setConfig(array $config)
    {
        self::getHttpClient()->setConfig($config);
    }
    
    protected function _getFeed($path, $params)
    {
        require_once 'Zend/Feed/Atom.php';
        return new Zend_Feed_Atom($this->_getUri($path, $this->_filterParams($params)));
    }
    
    protected function _filterParams(array $params = array())
    {
        if (!isset($params['key']) && $this->_privateKey) {
            $params['key'] = $this->_privateKey;
        }
        return $params;
    }
    
    protected function _getUri($path, $params)
    {
        require_once 'Zend/Uri.php';
        $uri = Zend_Uri::factory(self::URI);
        $uri->setPath($path);
        $uri->setQuery($params);
        return $uri->getUri();
    }
}