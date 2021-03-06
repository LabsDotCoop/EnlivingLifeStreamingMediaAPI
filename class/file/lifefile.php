<?php
/**
 * Chronolabs Digital Signature Generation & API Services
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       Chronolabs Cooperative http://labs.coop
 * @license         General Software Licence (https://web.labs.coop/public/legal/general-software-license/10,3.html)
 * @package         life
 * @since           1.0.1
 * @author          Simon Roberts <wishcraft@users.sourceforge.net>
 * @subpackage		file
 * @description		Digital Signature Generation & API Services
 * @link			https://life.labs.coop Digital Signature Generation & API Services
 */

/**
 * lifeFile
 *
 * @package
 * @author Taiwen Jiang <phppp@users.sourceforge.net>
 * @access public
 */
class lifeFile
{
    /**
     * lifeFile::__construct()
     */
    function __construct()
    {
    }

    /**
     * lifeFile::lifeFile()
     */
    function lifeFile()
    {
        $this->__construct();
    }

    /**
     * lifeFile::getInstance()
     *
     * @return
     */
    function getInstance()
    {
        static $instance;
        if (!isset($instance)) {
            $class = __CLASS__;
            $instance = new $class();
        }
        return $instance;
    }

    /**
     * lifeFile::load()
     *
     * @param string $name
     * @return
     */
    static function load($name = 'file')
    {
        switch ($name) {
            case 'folder':
                if (!class_exists('lifeFolderHandler')) {
                    if (file_exists($folder = dirname(__FILE__) . '/folder.php')) {
                        include $folder;
                    } else {
                        trigger_error('Require Item : ' . str_replace(_PATH_ROOT, '', $folder) . ' In File ' . __FILE__ . ' at Line ' . __LINE__, E_USER_WARNING);
                        return false;
                    }
                }
                break;
            case 'file':
            default:
                if (!class_exists('lifeFileHandler')) {
                    if (file_exists($file = dirname(__FILE__) . '/file.php')) {
                        include $file;
                    } else {
                        trigger_error('Require File : ' . str_replace(_PATH_ROOT, '', $file) . ' In File ' . __FILE__ . ' at Line ' . __LINE__, E_USER_WARNING);
                        return false;
                    }
                }
                break;
        }

        return true;
    }

    /**
     * lifeFile::getHandler()
     *
     * @param string $name
     * @param mixed $path
     * @param mixed $create
     * @param mixed $mode
     * @return
     */
    static function getHandler($name = 'file', $path = false, $create = false, $mode = null)
    {
        $handler = null;
        lifeFile::load($name);
        $class = 'life' . ucfirst($name) . 'Handler';
        if (class_exists($class)) {
            $handler = new $class($path, $create, $mode);
        } else {
            trigger_error('Class ' . $class . ' not exist in File ' . __FILE__ . ' at Line ' . __LINE__, E_USER_WARNING);
        }
        return $handler;
    }
}

?>