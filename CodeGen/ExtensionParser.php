<?php
/**
 * Parser for XML based extension desription files
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
require_once "CodeGen/XmlParser.php";
require_once "CodeGen/Tools/Indent.php";

/**
 * Parser for XML based extension desription files
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
abstract class CodeGen_ExtensionParser 
    extends CodeGen_XmlParser
{
    /**
     * The extension to parse specs into
     *
     * @access private
     * @var    object
     */
    var $extension;
    
    /** 
     * Constructor
     *
     * @access public
     * @param  object the extension to parse specs into
     */
    function __construct($extension) 
    {
        parent::__construct();
        $this->extension = $extension;
    }
    
    /**
     * Handle <extension>
     *
     * @access private
     * @param  array    attribute/value pairs
     * @return bool     success status
     */
    function tagstart_extension($attr)
    {
        $err = $this->checkAttributes($attr, array("name", "prefix", "version"));
        if (PEAR::isError($err)) {
            return $err;
        }
                                      
        if (!isset($attr["name"])) {
            return PEAR::raiseError("needed attribute 'name' for <extension> not given");
        }
        $status = $this->extension->setName(trim($attr["name"]));
        if ($status === true && isset($attr["prefix"])) {
            $status = $this->extension->setPrefix(trim($attr["prefix"]));
        }

        if (isset($attr["version"])) {
            if (version_compare($attr["version"], $this->extension->version(), ">")) {
                return PEAR::raiseError("This is ".get_class($this->extension)." ".$this->extension->version().", extension specification requires at least version $attr[version]\n");
            }
        }

        return $status;
    }
    
    /**
     * Handle <extension><name>
     *
     * @access private
     * @param  array    attribute/value pairs
     * @return bool     success status
     */
    function tagstart_extension_name($attr)
    {
        return PEAR::raiseError("extension <name> tag is no longer supported, use <extension>s 'name=...' attribute instead");
    }
    
    
    /**
     * Handle <extension></summary>
     *
     * @access private
     * @param  array    attribute/value pairs
     * @param  array    tag data content
     * @return bool     success status
     */
    function tagend_extension_summary($attr, $data)
    {
        return $this->extension->setSummary(CodeGen_Tools_Indent::linetrim($data));
    }
    
    /**
     * Handle <extension></description>
     *
     * @access private
     * @param  array    attribute/value pairs
     * @param  array    tag data content
     * @return bool     success status
     */
    function tagend_extension_description($attr, $data)
    {
        return $this->extension->setDescription(CodeGen_Tools_Indent::linetrim($data));
    }

        


    
    function tagstart_maintainer($attr)
    {
        $this->pushHelper(new CodeGen_Maintainer);
        return true;
    }
    
    function tagend_maintainer_user($attr, $data)
    {
        return $this->helper->setUser(trim($data));
    }
    
    function tagend_maintainer_name($attr, $data)
    {
        return $this->helper->setName(trim($data));
    }

    function tagend_maintainer_email($attr, $data)
    {
        return $this->helper->setEmail(trim($data));
    }

    function tagend_maintainer_role($attr, $data)
    {
        return $this->helper->setRole(trim($data));
    }

    function tagend_maintainer($attr, $data)
    {
        $err = $this->extension->addAuthor($this->helper);
        $this->popHelper();
        return $err;
    }

    function tagend_maintainers($attr, $data) 
    {
        return true;
    }

    function tagstart_extension_release($attr)
    {
        $this->pushHelper(new CodeGen_Release);
        return true;
    }

    function tagend_release_version($attr, $data)
    {
        return $this->helper->setVersion(trim($data));
    }

    function tagend_release_date($attr, $data)
    {
        return $this->helper->setDate(trim($data));
    }

    function tagend_release_state($attr, $data)
    {
        return $this->helper->setState(trim($data));
    }

    function tagend_release_notes($attr, $data)
    {
        return $this->helper->setNotes(CodeGen_Tools_Indent::linetrim($data));
    }

    function tagend_extension_release($attr, $data)
    {
        $err = $this->extension->setRelease($this->helper);
        $this->popHelper(new CodeGen_Release);
        return $err;
    }

    function tagend_extension_changelog($attr, $data) 
    {
        return true;
    }

        
    function tagend_license($attr, $data)
    {
        $license = CodeGen_License::factory(trim($data));
        
        if (PEAR::isError($license)) {
            return $err;
        }
        
        return $this->extension->setLicense($license);
    }

}


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode:nil
 * End:
 */
?>
