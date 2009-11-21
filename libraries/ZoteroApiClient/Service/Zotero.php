<?php
require_once 'Zend/Rest/Client.php';

class ZoteroApiClient_Service_Zotero extends Zend_Rest_Client
{
    const URI = 'https://api.zotero.org';
    
    protected $_username;
    protected $_password;
    
    public function __construct($username = null, $password = null)
    {
        $this->_username = $username;
        $this->_password = $password;
        $this->setUri(self::URI);
    }
    
    public function setUsername($username)
    {
        $this->_username = $username;
    }
    
    public function setPassword($password)
    {
        $this->_password = $password;
    }
    
    public function userItems($userId, array $params = array())
    {
        $path = "/users/$userId/items";
    }
    
    public function userItemsTop($userId, array $params = array())
    {
        $path = "/users/$userId/items/top";
    }
    
    public function userItem($userId, $itemId, array $params = array())
    {
        $path = "/users/$userId/items/$itemId";
    }
    
    public function userItemFile($userId, $itemId)
    {
        $path = "/users/$userId/items/$itemId/file";
        $this->_setAuth();
        $this->_setConfig(array('maxredirects' => 0));
        return $this->restGet($path)->getHeader('Location');
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
    
    public function groupItemFile($groupId, $itemId)
    {
        $path = "/groups/$groupId/items/$itemId/file";
        $this->_setAuth();
        $this->_setConfig(array('maxredirects' => 0));
        return $this->restGet($path)->getHeader('Location');
    }
    
    public function groupItemChildren($groupId, $itemId, array $params = array())
    {
        $path = "/groups/$groupId/items/$itemId/children";
        return $this->_getFeed($path, $params);
    }
    
    protected function _setAuth()
    {
        if (!is_string($this->_username) || !is_string($this->_password)) {
            throw new ZoteroApiClient_Service_Exception('Cannot set authentication without a username and password.');
        }
        self::getHttpClient()->setAuth($this->_username, $this->_password);
    }
    
    protected function _setConfig(array $config)
    {
        self::getHttpClient()->setConfig($config);
    }
    
    protected function _getFeed($path, $params)
    {
        require_once 'Zend/Feed/Atom.php';
        return new Zend_Feed_Atom($this->_getUri($path, $params));
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