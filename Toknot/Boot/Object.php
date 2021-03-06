<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot;

use \Reflection;
use \ReflectionObject;
use \ReflectionMethod;
use \ReflectionProperty;
use \ReflectionException;
use \Iterator;
use \Countable;
use \SplObjectStorage;
use \BadMethodCallException;
use \Toknot\Exception\BadPropertyGetException;
use \Toknot\Exception\BadClassCallException;
use \Closure;

abstract class Object implements Iterator, Countable {

    /**
     * For iterator of propertie list
     *
     * @var array
     * @access protected
     */
    protected $interatorArray = [];

    /**
     * whether object instance propertie change status
     *
     * @var bool
     * @access private
     */
    private $propertieChange = false;

    /**
     * Object instance of the child class when singleton mode
     *
     * @var object
     * @access private
     */
    private static $singletonInstanceStorage = [];
    private static $thisInstance = [];
    private $counter = 0;
    private $countNumber = 0;
    private $extendsClass = null;
    private $currentCallClass = 'Object';

    /**
     *  late extends method of parent class
     *  <code>
     *   class A {
     *      public am() {}
     *   }
     *   class B extends Object {}
     *   $aobj = new A;
     *   $bobj = new B($aobj);
     *   $bobj->am();
     *
     *  </code>
     *
     */
    final public function __construct(...$argv) {
        $this->currentCallClass = get_called_class();
        self::$thisInstance[$this->currentCallClass] = $this;
        $this->extendsClass = new SplObjectStorage();
        if (count($argv) > 0) {
            foreach ($argv as $param) {
                if (is_object($param)) {
                    $this->extendsClass->attach($arg);
                }
            }
        }
        $this->invokeMethod('__init', $argv);
    }

    /**
     * Constructor of class instead @__construct()
     * 
     * @param mixed $argc 
     */
    protected function __init() {
        
    }

    /**
     * the function utilized for reading data from inaccessible properties. 
     * instead __get()
     * 
     * @param string $name
     * @throws BadPropertyGetException
     */
    protected function getPropertie(string $name) {
        throw new BadPropertyGetException($this->currentCallClass, $name);
    }

    /**
     * the method is triggered when invoking inaccessible methods in an object context. 
     * instead __call()
     * 
     * @param string $name
     * @param array $arguments
     * @throws BadMethodCallException
     */
    protected function __callMethod(string $name, array $arguments = []) {
        throw new BadMethodCallException("Call undefined Method $name in object {$this->currentCallClass}");
    }

    /**
     * the function run when writing data to inaccessible properties. 
     * instead __set()
     *
     * @param string $propertie
     * @param mixed $value
     * @access public
     * @return void
     */
    protected function setPropertie($propertie, $value) {
        throw new BadPropertyGetException("{$this->currentCallClass}::setPropertie() not defined,so dynamic set {$this->currentCallClass}", "$propertie is inhibit");
    }

    /**
     * late add parent class to current class
     * 
     * @param object $param
     * @throws \InvalidArgumentException
     */
    final protected function addExtendObject($param) {
        if (is_object($param)) {
            $this->extendsClass->attach($param);
        } else {
            throw \InvalidArgumentException('add extend object must is object instance');
        }
    }

    /**
     * __call be invoked when self class is not public function and outer call
     * so fist call parent class of method, if self class call not exists method
     */
    final public function __call(string $name, array $arguments = []) {
        if ($this->extendsClass->count()) {
            foreach ($this->extendsClass as $obj) {
                if ($obj instanceof Closure) {
                    $fn = $obj->bindTo($this);
                    return self::invokeFunction($fn, $arguments);
                }
                if (method_exists($obj, $name)) {
                    return $obj->invokeMethod($name, $arguments);
                }
            }
        }

        return $this->__callMethod($name, $arguments);
    }

    final public function __get($name) {
        try {
            return $this->getPropertie($name);
        } catch (BadPropertyGetException $e) {
            if ($this->extendsClass->count()) {
                foreach ($this->extendsClass as $obj) {
                    if (property_exists($obj, $name)) {
                        return $obj->$name;
                    }
                }
            }
            throw $e;
        }
    }

    /**
     * check is propertie whether is read only
     * 
     * @param string $pn
     * @param string $scope
     * @return boolean
     */
    final public function checkReadonlyPropertie(string $pn, &$scope = 'public') {
        try {
            $p = new ReflectionProperty($this, $pn);
        } catch (ReflectionException $e) {
            return false;
        }
        $doc = $p->getDocComment();
        $scope = Reflection::getModifierNames($p->getModifiers());
        if ($doc) {
            $doc = explode("\n", $p->getDocComment());
            foreach ($doc as $line) {
                if (preg_match('/@access\s+readonly/i', $line)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * provide singleton pattern for Object child class
     *
     * @param mixed $_ options,  instance of  for construct parameters
     * @static
     * @access public
     * @final
     * @return object
     */
    final protected static function &__singleton(...$argv) {
        $className = get_called_class();
        if (isset(self::$singletonInstanceStorage[$className]) && is_object(self::$singletonInstanceStorage[$className]) && self::$singletonInstanceStorage[$className] instanceof $className) {
            return self::$singletonInstanceStorage[$className];
        }
        $argc = count($argv);
        if ($argc > 0) {
            self::$singletonInstanceStorage[$className] = self::constructArgs($argc, $argv, $className);
        } else {
            self::$singletonInstanceStorage[$className] = new $className;
        }
        return self::$singletonInstanceStorage[$className];
    }

    /**
     * get current class instance of singleton
     * 
     * @param mixed ...$argv params list
     * @access public
     * @static
     * @object
     */
    public static function singleton(...$argv) {
        return static::invokeStaticMethod('__singleton', $argv);
    }

    /**
     * get singletion instance of exists
     *
     * @static
     * @access public
     * @final
     * @return object|null
     */
    final public static function getInstance() {
        $className = get_called_class();
        if (isset(self::$singletonInstanceStorage[$className])) {
            return self::$singletonInstanceStorage[$className];
        } else {
            return null;
        }
    }

    /**
     * Get recent instance of current class
     *
     * @static
     * @access public
     * @final
     * @return object|null
     */
    final public static function getClassInstance() {
        $className = get_called_class();
        if (isset(self::$thisInstance[$className])) {
            return self::$thisInstance[$className];
        }
        throw new BadClassCallException($className);
    }

    /**
     * Creates a new class instance without invoking the constructor.
     *
     * @return object
     */
    public static function newInstanceWithoutConstruct() {
        $className = get_called_class();
        $ser = sprintf('O:%d:"%s":0:{}', strlen($className), $className);
        return unserialize($ser);
    }

    /**
     * create instance of class with use static method
     *
     * @static
     * @access public
     * @final
     * @return object
     */
    final public static function getNewInstance() {
        return new static;
    }

    /**
     * new instance of class
     *
     * @param int $argc
     * @param array $args
     * @param string $className
     * @static
     * @access public
     * @final
     * @return object
     */
    final public static function constructArgs($argc, array $args, $className) {
        if ($argc === 1) {
            return new $className($args[0]);
        } elseif ($argc === 2) {
            return new $className($args[0], $args[1]);
        } elseif ($argc === 3) {
            return new $className($args[0], $args[1], $args[2]);
        } elseif ($argc === 4) {
            return new $className($args[0], $args[1], $args[2], $args[3]);
        } elseif ($argc === 5) {
            return new $className($args[0], $args[1], $args[2], $args[3], $args[4]);
        } elseif ($argc === 6) {
            return new $className($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
        } else {
            $argStr = '';
            foreach ($args as $k => $v) {
                $argStr .= "\$args[$k],";
            }
            $argStr = rtrim($argStr, ',');
            $ins = null;
            eval("\$ins = new {$className}($argStr);");
            return $ins;
        }
    }

    /**
     * call un-static method use static method invoke when the un-static-method name prefixed S char
     *
     * @param string $name
     * @param array $arguments
     * @static
     * @access public
     * @final
     * @return mix
     * @throws \BadMethodCallException
     */
    public static function __callStatic(string $name, array $arguments = []) {
        $className = get_called_class();
        if (substr($name, 0, 1) === 'S') {
            $that = self::getInstance();
            $methodName = substr($name, 1);
            if (!method_exists($className, $methodName)) {
                throw new \BadMethodCallException("Call to undefined method $className::$name()");
            }
            return $that->invokeMethod($methodName, $arguments);
        }
        if (!method_exists($className, $name)) {
            throw new \BadMethodCallException("Call to undefined method $className::$name()");
        }
        return self::invokeStaticMethod($name, $arguments);
    }

    final public static function invokeFunction(callable $func, array $args = []) {
        $argc = count($args);
        if ($argc === 0) {
            return $func();
        } elseif ($argc === 1) {
            return $func($args[0]);
        } elseif ($argc === 2) {
            return $func($args[0], $args[1]);
        } elseif ($argc === 3) {
            return $func($args[0], $args[1], $args[2]);
        } elseif ($argc === 4) {
            return $func($args[0], $args[1], $args[2], $args[3]);
        } elseif ($argc === 5) {
            return $func($args[0], $args[1], $args[2], $args[3], $args[4]);
        } elseif ($argc === 5) {
            return $func($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
        } else {
            $argStr = '';
            foreach ($args as $k => $v) {
                $argStr .= "\$args[$k],";
            }
            $argStr = rtrim($argStr, ',');
            $ret = null;
            eval("\$ret = {$func}($argStr);");
            return $ret;
        }
    }

    /**
     * @param
     *
     */
    final public static function invokeStaticMethod(string $methodName, array $args = []) {
        $argc = count($args);
        if ($argc === 0) {
            return static::$methodName();
        } elseif ($argc === 1) {
            return static::$methodName($args[0]);
        } elseif ($argc === 2) {
            return static::$methodName($args[0], $args[1]);
        } elseif ($argc === 3) {
            return static::$methodName($args[0], $args[1], $args[2]);
        } elseif ($argc === 4) {
            return static::$methodName($args[0], $args[1], $args[2], $args[3]);
        } elseif ($argc === 5) {
            return static::$methodName($args[0], $args[1], $args[2], $args[3], $args[4]);
        } elseif ($argc === 6) {
            return static::$methodName($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
        } else {
            $argStr = '';
            foreach ($args as $k => $v) {
                $argStr .= "\$args[$k],";
            }
            $argStr = rtrim($argStr, ',');
            $ret = null;
            eval("\$ret = static::{$methodName}($argStr);");
            return $ret;
        }
    }

    /**
     * invoke method
     *
     * @param string $methodName
     * @param array $args
     * @access public
     * @final
     * @return mix
     */
    final public function invokeMethod(string $methodName, array $args = array()) {
        $argc = count($args);
        if ($argc === 0) {
            return $this->$methodName();
        } elseif ($argc === 1) {
            return $this->$methodName($args[0]);
        } elseif ($argc === 2) {
            return $this->$methodName($args[0], $args[1]);
        } elseif ($argc === 3) {
            return $this->$methodName($args[0], $args[1], $args[2]);
        } elseif ($argc === 4) {
            return $this->$methodName($args[0], $args[1], $args[2], $args[3]);
        } elseif ($argc === 5) {
            return $this->$methodName($args[0], $args[1], $args[2], $args[3], $args[4]);
        } elseif ($argc === 6) {
            return $this->$methodName($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
        } else {
            $argStr = '';
            foreach ($args as $k => $v) {
                $argStr .= "\$args[$k],";
            }
            $argStr = rtrim($argStr, ',');
            $ret = null;
            eval("\$ret = \$this->{$methodName}($argStr);");
            return $ret;
        }
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
        if ($this->checkReadonlyPropertie($propertie, $scope)) {
            throw new BadPropertyGetException("the $propertie is read only on out $scope");
        }
        $this->setPropertie($propertie, $value);
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

    /**
     *
     * @return object
     */
    public function __invoke() {
        return $this;
    }

    /**
     * @return string current called class name
     */
    public function __toString() {
        return $this->currentCallClass;
    }

    public function rewind() {
        $ref = new ReflectionObject($this);
        $propertiesList = $ref->getProperties();
        $constantsList = $ref->getConstants();
        $this->interatorArray = array_merge($constantsList, $propertiesList);
        reset($this->interatorArray);
        $this->resetCount();
    }

    protected function resetCount() {
        $this->counter = 0;
        $this->countNumber = count($this->interatorArray);
    }

    public function current() {
        return current($this->interatorArray);
    }

    public function key() {
        return key($this->interatorArray);
    }

    public function next() {
        $this->counter++;
        next($this->interatorArray);
    }

    public function valid() {
        if ($this->countNumber == 0) {
            return false;
        }
        if ($this->countNumber <= $this->counter) {
            return false;
        }
        return true;
    }

    public function count() {
        $this->rewind();
        return $this->countNumber;
    }

}
