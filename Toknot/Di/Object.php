<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Di;

use \ReflectionObject;
use \ReflectionMethod;
use \ReflectionProperty;
use \Iterator;
use \Countable;
use \ReflectionClass;

abstract class Object implements Iterator, Countable {

	/**
	 * For iterator of propertie list
	 *
	 * @var array
	 * @access protected
	 */
	protected $interatorArray = array();

	/**
	 * whether object instance propertie change status
	 *
	 * @var bool
	 * @access private
	 */
	private $propertieChange = false;

	/**
	 * Object instance of the child class
	 * 
	 * @var object
	 * @access private 
	 */
	private static $instance = array();

	/**
	 * provide singleton pattern for Object child class
	 * 
	 * @param mixed $_ options,  instance of  for construct parameters
	 * @static
	 * @access public
	 * @return object
	 */
	final protected static function &__singleton() {
		$className = get_called_class();
		if (isset(self::$instance[$className]) && is_object(self::$instance[$className]) && self::$instance[$className] instanceof $className) {
			return self::$instance[$className];
		}
		$argc = func_num_args();
		if ($argc > 0) {
			$args = func_get_args();
			self::$instance[$className] = new $className($args);		
		} else {
			self::$instance[$className] = new $className;
		}
		return self::$instance[$className];
	}


	/**
	 * __set 
	 * set propertie value and save changed status and baned child cover __set method
	 * 
	 * @param mixed $propertie 
	 * @param mixed $value 
	 * @final
	 * @access public
	 * @return void
	 */
	final public function __set($propertie, $value) {
		$this->propertieChange = true;
		$this->setPropertie($propertie, $value);
	}

	/**
	 * 
	 * @param string $propertie
	 * @param mixed $value
	 * @access public
	 * @return void 
	 */
	protected function setPropertie($propertie, $value) {
		//$this->$propertie = $value;
	}

	/**
	 * isChange 
	 * check class propertie whether change default value or set new propertie and it value;
	 * 
	 * @final
	 * @access public
	 * @return boolean
	 */
	final public function isChange() {
		if ($this->propertieChange)
			return true;
		$ref = new ReflectionObject($this);
		$list = $ref->getDefaultProperties();
		$staticList = $ref->getStaticProperties();
		foreach ($list as $key => $value) {
			if (isset($staticList[$key])) {
				if (self::$$key != $value)
					return true;
			} else {
				if ($this->$key != $value)
					return true;
			}
		}
		return false;
	}

	final public function getDocComment($method = null) {
		if (is_null($method)) {
			$ref = new ReflectionObject($this);
			return $ref->getDocComment();
		} else {
			try {
				$mRef = new ReflectionMethod($this, $method);
				return $mRef->getDocComment();
			} catch (ReflectionException $e) {
				try {
					$pRef = new ReflectionProperty($this, $method);
					return $pRef->getDocComment();
				} catch (ReflectionException $e) {
					return false;
				}
			}
		}
	}

	public function rewind() {
		$ref = new ReflectionObject($this);
		$propertiesList = $ref->getProperties();
		$constantsList = $ref->getConstants();
		$this->interatorArray = array_merge($constantsList, $propertiesList);
		reset($this->interatorArray);
	}

	public function current() {
		return current($this->interatorArray);
	}

	public function key() {
		return key($this->interatorArray);
	}

	public function next() {
		next($this->interatorArray);
	}

	public function valid() {
		$key = $this->key();
		return isset($this->interatorArray[$key]);
	}

	public function count() {
		return count($this->interatorArray);
	}

}

