#!/bin/env php
<?php
/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

/**
 * Generate password of encriyption string
 */
class GeneratePassword {

    public function __construct($argv) {
        $this->toknotDir = dirname(__DIR__);
        $this->workDir = getcwd();
        require_once $this->toknotDir . '/Control/Application.php';
        define('DEVELOPMENT', true);
        new Toknot\Control\Application;
        $config = false;
        
        if(!empty($argv[1])) {
            $config = $this->checkIni($argv[1]);
        }
        if (!$config) {
            while (true) {
                Toknot\Di\Log::colorMessage('Enter path of config file:', null, false);
                $config = trim(fgets(STDIN));
                if (!empty($config)) {
                    $config = $this->checkIni($config);
                }
            }
        }

        while (($password = $this->enterPass()) === false) {
            Toknot\Di\Log::colorMessage('Twice password not same, enter again:', 'red');
        }
        Toknot\Config\ConfigLoader::singleton();
        $cfg = Toknot\Config\ConfigLoader::importCfg($config);

        if (empty($cfg->User->userPasswordEncriyptionAlgorithms)) {
            Toknot\Di\Log::colorMessage('config of Algorithm is empty', 'red');
        }
        if (empty($cfg->User->userPasswordEncriyptionSalt)) {
            Toknot\Di\Log::colorMessage('config of salt is empty', 'red');
        }
         \Toknot\Control\StandardAutoloader::importToknotModule('User', 'UserAccessControl');
        $password = Toknot\User\Root::getTextHashCleanSalt($password, $cfg->User->userPasswordEncriyptionAlgorithms, $cfg->User->userPasswordEncriyptionSalt);
        Toknot\Di\Log::colorMessage($password,'green');
    }

    public function checkIni($file) {
        $config = realpath($file);
        if ($config) {
            return $config;
        }
        Toknot\Di\Log::colorMessage("$file not exits", 'red');
        return false;
    }

    public function enterPass() {
        Toknot\Di\Log::colorMessage('Enter password:', null, false);
        $password = trim(fgets(STDIN));
        while (strlen($password) < 6) {
            Toknot\Di\Log::colorMessage('password too short,enter again:', 'red', false);
            $password = trim(fgets(STDIN));
        }
        Toknot\Di\Log::colorMessage('Enter password again:', null, false);
        $repassword = trim(fgets(STDIN));
        while (empty($password)) {
            Toknot\Di\Log::colorMessage('must enter password again:', 'red', false);
            $repassword = trim(fgets(STDIN));
        }
        if ($repassword != $password) {
            return false;
        } else {
            return $password;
        }
    }

}

return new GeneratePassword($argv);