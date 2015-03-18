<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2007-2010
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package ZoteroImport
 */

require_once 'Zend/Rest/Client.php';
require_once 'Zend/Feed/Atom.php';
require_once 'Zend/Uri.php';

/**
 * Contains code used to interface with the Zotero API.
 * 
 * @package ZoteroImport
 */
class ZoteroApiClient_Service_Zotero extends Zend_Rest_Client
{
    const URI = 'https://api.zotero.org';
    
    protected $_privateKey;
    protected $_requestAttempts = 1;
    
    /**
     * Constructs the object.
     * 
     * @todo May have to suppress the frequency of request attempts when Zotero 
     *       implements request throttling.
     * @param string|null $privateKey The Zotero private key needed for the 
     * requests.
     * @param int $requestAttempts The number of HTTP request attempts to make 
     * before throwing an Exception. This is to compensate for transient HTTP error 
     * responses.
     */
    public function __construct($privateKey = null, $requestAttempts = 1)
    {
        $this->_privateKey = $privateKey;
        
        // Set the request attempts.
        if (0 < (int) $requestAttempts) {
            $this->_requestAttempts = $requestAttempts;
        }
        
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
     * @param int The collection key.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function userCollectionItems($userId, $collectionKey, array $params = array())
    {
        $path = "/users/$userId/collections/$collectionKey/items";
        return $this->_getFeed($path, $params);
    }
    
    /**
     * Gets a user collection top items feed.
     * 
     * @param int The user ID.
     * @param int The collection key.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function userCollectionItemsTop($userId, $collectionKey, array $params = array())
    {
        $path = "/users/$userId/collections/$collectionKey/items/top";
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
     * @param int The item key.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function userItem($userId, $itemKey, array $params = array())
    {
        $path = "/users/$userId/items/$itemKey";
        return $this->_getFeed($path, $params);
    }
    
    /**
     * Gets a user item tags feed.
     * 
     * @param int The user ID.
     * @param int The item key.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function userItemTags($userId, $itemKey, array $params = array())
    {
        $path = "/users/$userId/items/$itemKey/tags";
        return $this->_getFeed($path, $params);
    }
    
    /**
     * Gets a user item children feed.
     * 
     * @param int The user ID.
     * @param int The item key.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function userItemChildren($userId, $itemKey, array $params = array())
    {
        $path = "/users/$userId/items/$itemKey/children";
        return $this->_getFeed($path, $params);
    }
    
    /**
     * Gets the Zotero API and Amazon S3 URLs of a user item file.
     * 
     * Returns an array containing the Zotero API file method URL and the Amazon 
     * S3 URL to the file, if one exists in S3. Recommended use is to download 
     * the file using the Zotero URL and to extract the original file name from 
     * the S3 URL, since S3 URLs expire in 30 seconds. Not all Zotero 
     * attachments have corresponding files.
     * 
     * @param int The user ID.
     * @param int The item key.
     * @param array Additional parameters for the request.
     * @return array array('zotero' => string, 's3' => string|null)
     */
    public function userItemFile($userId, $itemKey, array $params = array())
    {
        $path = "/users/$userId/items/$itemKey/file";
        $params = $this->_filterParams($params);
        $this->_setConfig(array('maxredirects' => 0));
        $attempt = 0;
        while (true) {
            try {
                $attempt++;
                $s3 = $this->restGet($path, $params)->getHeader('Location');
                break;
            } catch (Exception $e) {
                if ($this->_requestAttempts > $attempt) {
                    continue;
                }
                throw $e;
            }
        }
        return array(
            's3'     => $s3, 
            'zotero' => $this->_getUri($path, $params)
        );
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
     * @param int The collection key.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function groupCollectionItems($groupId, $collectionKey, array $params = array())
    {
        $path = "/groups/$groupId/collections/$collectionKey/items";
        return $this->_getFeed($path, $params);
    }
    
    /**
     * Gets a group collection top items feed.
     * 
     * @param int The group ID.
     * @param int The collection key.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function groupCollectionItemsTop($groupId, $collectionKey, array $params = array())
    {
        $path = "/groups/$groupId/collections/$collectionKey/items/top";
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
     * @param int The item key.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function groupItem($groupId, $itemKey, array $params = array())
    {
        $path = "/groups/$groupId/items/$itemKey";
        $feed = $this->_getFeed($path, $params);
        return $feed->current();
    }
    
    /**
     * Gets the Zotero API and Amazon S3 URLs of a group item file.
     * 
     * @see ZoteroApiClient_Service_Zotero::userItemFile()
     * @param int The group ID.
     * @param int The item key.
     * @param array Additional parameters for the request.
     * @return array array('zotero' => string, 's3' => string|null)
     */
    public function groupItemFile($groupId, $itemKey, array $params = array())
    {
        $path = "/groups/$groupId/items/$itemKey/file";
        $params = $this->_filterParams($params);
        $this->_setConfig(array('maxredirects' => 0));
        $attempt = 0;
        while (true) {
            try {
                $attempt++;
                $s3 = $this->restGet($path, $params)->getHeader('Location');
                break;
            } catch (Exception $e) {
                if ($this->_requestAttempts > $attempt) {
                    continue;
                }
                throw $e;
            }
        }
        return array(
            's3'     => $s3, 
            'zotero' => $this->_getUri($path, $params)
        );
    }
    
    /**
     * Gets a group item children feed.
     * 
     * @param int The group ID.
     * @param int The item key.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function groupItemChildren($groupId, $itemKey, array $params = array())
    {
        $path = "/groups/$groupId/items/$itemKey/children";
        return $this->_getFeed($path, $params);
    }
    
    /**
     * Gets a group item tags feed.
     * 
     * @param int The group ID.
     * @param int The item key.
     * @param array Additional parameters for the request.
     * @return Zend_Feed_Atom
     */
    public function groupItemTags($groupId, $itemKey, array $params = array())
    {
        $path = "/groups/$groupId/items/$itemKey/tags";
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
        $client = Zend_Feed::getHttpClient();
        $client->setHeaders('Accept-Encoding', '');
        $client->setConfig(array('httpversion' => Zend_Http_Client::HTTP_0));
        
        $uri = $this->_getUri($path, $this->_filterParams($params));
        $attempt = 0;
        while (true) {
            try {
                $attempt++;
                return new Zend_Feed_Atom($uri);
            } catch (Exception $e) {
                if ($this->_requestAttempts > $attempt) {
                    continue;
                }
                throw $e;
            }
        }
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
        return array('v' => 2) + $params;
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
        $uri = Zend_Uri::factory(self::URI);
        $uri->setPath($path);
        $uri->setQuery($params);
        return $uri->getUri();
    }
}
