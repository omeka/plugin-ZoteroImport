<?php
require_once 'Zend/Rest/Client.php';

class ZoteroApiClient_Service_Zotero extends Zend_Rest_Client
{
    const URI = 'https://api.zotero.org';
    
    public function authenticate($username, $password)
    {
        self::getHttpClient()->setAuth($username, $password);
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
    
    public function groupItemChildren($groupId, $itemId, array $params = array())
    {
        $path = "/groups/$groupId/items/$itemId/children";
        return $this->_getFeed($path, $params);
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