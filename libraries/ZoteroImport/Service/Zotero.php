<?php
require_once 'Zend/Rest/Client.php';

class ZoteroImport_Service_Zotero extends Zend_Rest_Client
{
    const URI = 'https://api.zotero.org';
    
    public function __construct()
    {
        parent::__construct(self::URI);
    }
    
    protected function _init()
    {
        $client = self::getHttpClient();
        $client->resetParameters();
    }
    
    public function userItems($userId, array $params = array())
    {
        $this->_init();
        
        $userId = (int) $userId;
        $path = "/users/$userId/items";
        
        $response = $this->restGet($path, $params);
        return new Zend_Rest_Client_Result($response->getBody());
    }
    
    public function userItemsTop($userId, array $params = array())
    {
        $this->_init();
        
        $userId = (int) $userId;
        $path = "/users/$userId/items/top";
        
        $response = $this->restGet($path, $params);
        return new Zend_Rest_Client_Result($response->getBody());
    }
    
    public function userItem($userId, $itemId, array $params = array())
    {
        $this->_init();
        
        $userId = (int) $userId;
        $itemId = (int) $itemId;
        $path = "/users/$userId/item/$itemId";
        
        $response = $this->restGet($path, $params);
        return new Zend_Rest_Client_Result($response->getBody());
    }
    
    public function group($groupId, array $params = array())
    {
        $this->_init();
        
        $groupId = (int) $groupId;
        $path = "/groups/$groupId";
        
        $response = $this->restGet($path, $params);
        return new Zend_Rest_Client_Result($response->getBody());
    }
    
    public function groupItems($groupId, array $params = array())
    {
        $this->_init();
        
        $groupId = (int) $groupId;
        $path = "/groups/$groupId/items";
        
        $response = $this->restGet($path, $params);
        return new Zend_Rest_Client_Result($response->getBody());
    }
    
    public function authenticate($username, $password)
    {
        self::getHttpClient()->setAuth($username, $password);
    }
}