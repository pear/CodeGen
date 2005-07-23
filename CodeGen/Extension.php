<?php
/**
 * Extension generator class
 *
 * PHP versions 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/CodeGen
 */

/**
 * includes
 */
require_once "CodeGen/Maintainer.php";
require_once "CodeGen/License.php";
require_once "CodeGen/Release.php";
require_once "CodeGen/Tools/Platform.php";
require_once "CodeGen/Tools/Indent.php";

/**
 * Extension generator class
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
abstract class CodeGen_Extension 
{
    /**
     * The extensions basename (C naming rules apply)
     *
     * @var string
     */
    var $name = "unknown";
    

    /**
     * The extensions descriptive name
     *
     * @var string
     */
    var $summary = "The unknown extension";
    
    /**
     * extension description
     *
     * @var    string
     * @access private
     */
    var $description;

    /** 
     * The license for this extension
     *
     * @var object
     */
    var $license  = NULL;
    
    /** 
     * The release info for this extension
     *
     * @var object
     */
    var $release  = NULL;
        
    /** 
     * The implementation language
     *
     * Currently we support "c" and "cpp"
     *
     * @var string
     */
    var $language  = "c";
    
    /**
     * The target platform for this extension
     *
     * Possible values are "unix", "win" and "all"
     * 
     * @var string
     */
    var $platform = null;
    
    
    /**
     * The authors contributing to this extension
     *
     * @var array
     */
    var $authors = array();
    
    
    /**
     * Name prefix for functions etc.
     * 
     * @var string
     */
    var $prefix = "";


    /**
     * Set extension base name
     *
     * @access public
     * @param  string  name
     */
    function setName($name) 
    {
        if (!ereg("^[[:alpha:]_][[:alnum:]_]*$", $name)) {
            return PEAR::raiseError("'$name' is not a valid extension name");
        }
        
        $this->name = $name;
        return true;
    }

    /**
     * Set extension summary text
     *
     * @access public
     * @param  string  short summary
     */
    function setSummary($text) 
    {
        $this->summary = $text;
        return true;
    }

    /** 
     * Set extension documentation text
     *
     * @access public
     * @param  string  long description
     */
    function setDescription($text) 
    {
        $this->description = $text;
        return true;
    }

    /**
     * Set the programming language to produce code for
     *
     * @access public
     * @param  string  programming language name
     */
    function setLanguage($lang)
    {
        switch (strtolower($lang)) {
        case "c":
            $this->language = "c";
            return true;
        case "cpp":
        case "cxx":
        case "c++":
            $this->language = "cpp";
            return true;
        default:
            break;
        }

        return PEAR::raiseError("'$lang' is not a supported implementation language");
    }

    /**
     * Set target platform for generated code
     *
     * @access public
     * @param  string  platform name
     */
    function setPlatform($type)
    {
        $this->platform = new CodeGen_Tools_Platform($type);
        if (PEAR::isError($this->platform)) {
            return $this->platform;
        }
        
        return true;
    }

    /**
     * Add an author or maintainer to the extension
     *
     * @access public
     * @param  object   a maintainer object
     */
    function addAuthor($author)
    {
        if (!is_a($author, "CodeGen_Maintainer")) {
            return PEAR::raiseError("argument is not CodeGen_Maintainer");
        }
        
        $this->authors[$author->user] = $author;
        
        return true;
    }

    /** 
     * Set release info
     * 
     * @access public
     * @var    object
     */
    function setRelease($release)
    {
        $this->release = $release;

        return true;
    }


    /** 
     * Set license 
     * 
     * @access public
     * @param  object
     */
    function setLicense($license)
    {
        if ($license->shortname == "GPL") {
            return PEAR::raiseError("The GPL is no valid choice for PHP extensions due to license incompatibilities");
        }

        $this->license = $license;

        return true;
    }


    /**
     * Set extension name prefix (for functions etc.)
     *
     * @access public
     * @param  string  name
     */
    function setPrefix($prefix) 
    {
        if (! CodeGen_Element::isName($prefix)) {
            return PEAR::raiseError("'$name' is not a valid name prefix");
        }
        
        $this->prefix = $prefix;
        return true;
    }

}

?>