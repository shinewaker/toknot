<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot;

use Toknot\Boot\FileObject;
use Toknot\Boot\DataCacheServerInterface;

class DataCacheControl extends Object{

    /**
     * Data cache file name, without extension name, if use server , must set one 
     * server connect handle instance,the object be supposed set($key, $data, $expire)
     * and get($key) method, and set method should recvie array type parameter for $data, get 
     * method should return a array or boolean
     *
     * @var string
     */
    private $cacheHandle = '';

    /**
     * Current data modify seconds
     * @var integer
     * @access public
     */
    public $dataModifyTime = 0;

    /**
     * if cache type be seted CACHE_FILE, must set and is application root path
     *
     * @var string
     * @access public
     * @static
     */
    public static $appRoot = '';

    /**
     * Set use cache type
     *
     * @var integer
     */
    private $cacheType = self::CACHE_FILE;
    private $expire = 0;

    const CACHE_FILE = '1001';
    const CACHE_SERVER = '1002';
    /**
     * <code>
     * //file
     * $config = 'var/cache.php';
     * $config = 'file:/var/cache.php';
     * $config = 'file:var/cache.php';
     * $config = 'file:/var/cache.php;3600';
     * 
     * //class instance
     * //Toknot\Db\Memcache class
     * $config = 'Toknot\Db\Memcache:host=127.0.0.1;port=112211;dataModifyTime=3600';
     * $config = 'var/cache.php;3600';
     *
     * </code>
     * 
     * @param string $config
     * @throws \RuntimeException
     */
    public function __init(string $config) {
        if(strpos($config, ':') === false) {
            $this->cacheType = self::CACHE_FILE;
            $split = explode(',', $config);
            $this->cacheHandle = $split[0];
            if(isset($split[1])) {
                $this->dataModifyTime = $split[1];
            }
        } elseif(strpos($config, 'file:')) {
            $this->cacheType = self::CACHE_FILE;
            $split = explode(';',explode(':', $config,2)[1]);
            $this->cacheHandle = $split[0];
            if(isset($split[1])) {
                $this->dataModifyTime = $split[1];
            }
        } else {
            $this->cacheType = self::CACHE_SERVER;
            list($class,$argc) = explode(':', $config,2);
            $params = explode(';', $argc);
            
            $server = new $class;
            if(!$server instanceof DataCacheServerInterface) {
                throw new \RuntimeException('Cache Handle instance need implement Toknot\Boot\DataCacheServerInterface');
            }
            foreach($params as $p) {
                list($pn, $pv) = explode('=', $p,2);
                $server->$pn = $pv;
            }
            $server->connect();
            $this->cacheHandle = $server;
        }
    }


    private function getFileName($key = '') {
        return FileObject::getRealPath(self::$appRoot, "{$this->cacheHandle}{$key}.php");
    }
    
    /**
     * Get current cache data save seconds
     * 
     * @return int
     */
    public function cacheTime($key = '') {
        if ($this->cacheType == self::CACHE_SERVER || empty($this->cacheHandle)) {
            return 0;
        }
        $file = $this->getFileName($key);
        if (file_exists($file)) {
            return filemtime($file);
        } else {
            return 0;
        }
    }

    public static function createCachePath($path) {
        $path = FileObject::getRealPath(self::$appRoot, $path);
        if (file_exists($path)) {
            return;
        }
        return mkdir($path, 0777, true);
    }

    /**
     * Use expire time control data modify time
     * 
     * @param int $expire
     */
    public function useExpire($expire) {
        $this->expire = $expire;
    }

    /**
     * store data
     * 
     * @param mixed $data
     * @param string $key If not use file store, must set
     * @return boolean  if data not change return false
     */
    public function save($data, $key = '') {
        if ($this->cacheType == DataCacheControl::CACHE_SERVER) {
            $key = md5(self::$appRoot .$key);
            $this->cacheHandle->set($key, $data, time() + $this->expire);
            return true;
        }
        if (empty($this->cacheHandle)) {
            return false;
        }
        
        $dataString = '<?php return '.var_export($data, true) .';';
        $file = $this->getFileName($key);
        $fileObject = FileObject::saveContent($file, $dataString);
        
        if($fileObject === false) {
            return false;
        }
        return true;
    }

    /**
     * Get cache data
     * 
     * @param string $key data key
     * @return boolean|array  if cache data is old return false
     */
    public function get($key = '') {
        if ($this->cacheType == self::CACHE_SERVER) {
            $key = md5(self::$appRoot .$key);
            return $this->cacheHandle->get($key);
        }
        if ($this->expire > 0 && ($this->cacheTime($key) + $this->expire) < time()) {
            return false;
        } elseif ($this->expire == 0 && $this->cacheTime($key) < $this->dataModifyTime) {
            return false;
        }

        $file = $this->getFileName($key);

        if (file_exists($file)) {
            return include $file;    
        } else {
            return false;
        }
    }

    /**
     * Delete cache data
     * 
     * @param sting $key
     * @return boolean
     */
    public function del($key = '') {
        if ($this->cacheType == self::CACHE_SERVER) {
            return $this->cacheHandle->del($key);
        }
        $file = $this->getFileName($key);
        if (file_exists($file)) {
            return unlink($file);
        }
    }

    public function exists($key = '') {
        if ($this->cacheType == self::CACHE_SERVER) {
            return $this->cacheHandle->exist($key);
        }
        $file = $this->getFileName($key);
        if ($this->expire > 0 && ($this->cacheTime($key) + $this->expire) < time()) {
            return false;
        } elseif ($this->expire == 0 && $this->cacheTime($key) < $this->dataModifyTime) {
            return false;
        }
        return file_exists($file);
    }
    
    public function rename($oldKey,$newKey) {
        if($this->cacheType == self::CACHE_SERVER) {
            return $this->cacheHandle->rename($oldKey, $newKey);
        }
        $newfile = $this->getFileName($newKey);
        if(file_exists($newfile)) {
            return false;
        }
        $oldfile = $this->getFileName($oldKey);
        if(!file_exists($oldfile)) {
            return false;
        }
        return rename($oldfile, $newfile);
    }
}

