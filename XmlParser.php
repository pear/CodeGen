<?php
/**
 * Yet another XML parsing class 
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
 * Yet another XML parsing class 
 *
 * This is similar to the "func" mode of XML_Parser but it borrows
 * some concepts from DSSSL.
 * The tag handler method to call is not only determined by the tag
 * name but also potentially by the name of its parent tags, and the
 * most specific handler method (that is the one including the
 * maximum number of matching parent tags in its name) wins.
 * This way it is possible to have e.g. tagstart_name as a general
 * handler for a <name> tag while tagstart_function_name handles the
 * more special case of a <name> tag within a <function> tag.
 * Character data within a tag is collected and passed to the end
 * tag handler.
 * Tag names and attributes are managed using stack arrays.
 * Attributes are not only passed to both the start and end tag 
 * handlers.
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */

    class CodeGen_XmlParser 
    {
        /**
         * XML parser resource
         *
         * @var resource
         */
        private $parser = NULL;

        /**
         * Parser stack for <include> management
         *
         * @var array
         */
        private $parserStack = array();

        /**
         * We collect cData in here
         *
         * @var    string
         */
        private $data = "";

        /**
         * We also try to remember where cData started
         *
         * @access private
         * @var    int
         */
        var $dataLine = 0;

        /** 
         * We maintain the current tag nesting structure here
         *
         * @access private
         * @var    array
         */
        var $tagStack = array();

        /** 
         * We keep track of tag attributes so that we can also provide them to the end tag handlers
         *
         * @access private
         * @var    array
         */
        var $attrStack = array();

        /**
         * There is no clean way to terminate parsing from within a handler ...
         *
         * @access private
         * @var    bool
         */
        var $error = false;

        /**
         * Input Filename
         * 
         * @access public
         * @var    string
         */
        var $filename = false;

        /**
         * Input stream
         *
         * @access public
         * @var    resource
         */
        var $fp = null;

		/**
		 * Verbatim indicator
		 *
		 * @access public
		 * @var    string
		 */
		var $verbatim = false;

		/**
		 * Verbatim taglevel depth
		 *
		 * @access public
		 * @var string
		 */
		var $verbatimDepth = 0;

        /**
         * The constructor 
         *
         * @access public
         */
        function __construct()
        { 
            $this->parser = $this->newParser();
        }

        function newParser()
        {
            $parser = @xml_parser_create_ns(null, ' ');

            if (is_resource($parser)) {
                xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);

                xml_set_object($parser, $this);
                xml_set_element_handler($parser, 'startHandler', 'endHandler');
                xml_set_character_data_handler($parser, 'cDataHandler');
                xml_set_external_entity_ref_handler($parser, 'extEntityHandler');
                xml_set_processing_instruction_handler($parser, 'piHandler');
            }

            return $parser;
        }

        function pushParser()
        {
            if ($this->parser) {
                $entry = array($this->parser, $this->filename, $this->fp);    
                array_push($this->parserStack, $entry);
            }
        }

        function popParser()
        {
            xml_parser_free($this->parser);
            list($this->parser, $this->filename, $this->fp) = array_pop($this->parserStack);
        }


        function posString()
        {
             return "in {$this->filename} on line ".
                 xml_get_current_line_number($this->parser).
                 ":".
                 xml_get_current_column_number($this->parser);
        }
        
        function extEntityHandler($parser, $openEntityNames, $base, $systemId, $publicId) {
            $this->pushParser();
            $this->parser = $this->newParser();
            $stat = $this->setInputFile($systemId);
            if (!$stat) {
                $this->popParser();
                $this->error = PEAR::raiseError("Can't open XInclude file '$systemId' ".$this->posString());
            }
            $this->parse();
            $this->popParser();
            return;
        }

        /**
         * Set file to parse
         *
         * @access public
         * @param string
         */
        function setInputFile($filename) {
            $this->filename = $filename;
            
            $this->fp = @fopen($filename, "r");

            return is_resource($this->fp);
        }

        /**
         * Perform the actual parsing
         *
         * @access public         * @return boolean true on success
         */
        function parse() 
        {
            if (!is_resource($this->parser)) {
                return PEAR::raiseError("Can't create XML parser");
            }

            if (!is_resource($this->fp)) {
                return PEAR::raiseError("No valid input file");
            }


            while (($data = fread($this->fp, 4096))) {
                if (!xml_parse($this->parser, $data, feof($this->fp)) || $this->error) {
                    return $this->error;
                }
            }

            return true;
        }

		/**
		 * Start verbatim mode
		 *
		 */
		function verbatim()
		{
			$this->verbatim = true;
			$this->verbatimDepth = 1;
		}

        /**
         * Try to find best matching tag handler for current tag nesting
         * 
         * @access private
         * @param  string  handler method prefix
         * @return string  hndler method name or false if no handler found
         */
        function findHandler($prefix)
        {
            for ($tags = $this->tagStack; count($tags); array_shift($tags)) {
                $method = "{$prefix}_".join("_", $tags);
                if (method_exists($this, $method)) {
                    return $method;
                }
            }

            return false;
        }


        /**
         * Try to find a tagstart handler for this tag and call it
         *
         * @access private
         * @param  resource internal parser handle
         * @param  string   tag name
         * @param  array    tag attributes         */
        function startHandler($XmlParser, $tag, $attr)
        {
            if ($this->error) return;

            $pos = strrpos($tag, " ");
            
            $ns  = $pos ? substr($tag, 0, $pos)  : "";
            $tag = $pos ? substr($tag, $pos + 1) : $tag;

            // XInclude handling
            if ($ns === "http://www.w3.org/2001/XInclude") {
                // TODO better error checking
                $path = isset($attr['href']) ? $attr['href'] : $attr['http://www.w3.org/2001/XInclude href'];

                if ($tag === "include") {
                    if (isset($attr["parse"]) && $attr["parse"] == "text") {
                        $this-cDataHandler($XmlParser, get_file_contents($path));
                    } else {
                        $this->pushParser();
                        $this->parser = $this->newParser();
                        $stat = $this->setInputFile($path);
                        if (!$stat) {
                            $this->popParser();
                            $this->error = PEAR::raiseError("Can't open XInclude file '$path' ".$this->posString());
                        }
                        $this->parse();
                        $this->popParser();
                        return;
                    }
                }
            }

			if ($this->verbatim) {
				$this->verbatimDepth++;
				$this->data .= "<$tag";
				foreach ($attr as $key => $value) {
					$this->data .= " $key='$value'";
				}
				$this->data .= ">";
				return;
			}

            $this->data = "";
            $this->dataLine = 0;
            array_push($this->tagStack, $tag);
            array_push($this->attrStack, $attr);

            $method = $this->findHandler("tagstart");
            if ($method) {
                $err = $this->$method($attr);
                if (PEAR::isError($err)) {
                    $this->error = $err;
                    $this->error->addUserInfo($this->posString());
                }
            } else if (!$this->findHandler("tagend")) {
                $this->error = PEAR::raiseError("no matching tag handler for ".join(":",$this->tagStack));
                $this->error->addUserInfo($this->posString());              
            }
        }

        /**
         * Try to find a tagend handler for this tag and call it
         *
         * @access private
         * @param  resource internal parser handle
         * @param  string   tag name
         */
        function endHandler($XmlParser, $tag)
        {
            if ($this->error) return;

            $pos = strrpos($tag, " ");
            
            $ns  = $pos ? substr($tag, 0, $pos)  : "";
            $tag = $pos ? substr($tag, $pos + 1) : $tag;

            // XInclude handling
            if ($ns === "http://www.w3.org/2001/XInclude") {
                return;
            }

			if ($this->verbatim) {
				if (--$this->verbatimDepth > 0) {
					$this->data .= "</$tag>";
					return;
				} else { 
					$this->verbatim = false;
				}				
			}

            $method = $this->findHandler("tagend");
            array_pop($this->tagStack);
            $attr = array_pop($this->attrStack);
            if ($method) {
                $err = $this->$method($attr, $this->data, $this->dataLine, $this->filename);
                if (PEAR::isError($err)) {
                    $this->error = $err;
                    $this->error->addUserInfo($this->posString());                                   
                }
            }
            $this->data = "";
            $this->dataLine = 0;
        }


        /**
         * Just collect cData for later use in tag end handlers
         *
         * @access private
         * @param  resource internal parser handle
         * @param  string   cData to collect
         */
        function cDataHandler($XmlParser, $data)
        {
            $this->data.= $data;
            if (!$this->dataLine) {
                $this->dataLine = xml_get_current_line_number($XmlParser);
            }
        }

        /**
         * Delegate processing instructions
         *
         * @access private
         * @param  resource internal parser handle
         * @param  string   PI name
         * @param  string   PI content data
         */
        function piHandler($XmlParser, $name, $data)
        {
            $methodName = $name."PiHandler";

            if (method_exists($this, $methodName)) {
                $this->$methodName($XmlParser, $data);
            } else {
                $this->error = PEAR::raiseError("unknown processing instruction '<$name'");
            }
        }

        /**
         * Tread <?data PI sections like <![CDATA[
         *
         * @access private
         * @param  resource internal parser handle
         * @param  string   cData to collect
         */
        function dataPiHandler($XmlParser, $data) 
        {
            $this->cDataHandler($XmlParser, $data);
        }

        /**
         * A helper stack for collecting stuff 
         *
         * @access private
         * @var    array
         */
        var $helperStack = array();

        /**
         * The current helper (top of stack) 
         *
         * @access private
         * @var    mixed
         */
        var $helper = false;

        /**
         * The previous helper (top-1 of stack) 
         *
         * @access private
         * @var    mixed
         */
        var $helperPrev = false;
        
        /**
         * Push something on the helper stack
         *
         * @access private
         * @param mixed
         */
        function pushHelper($helper)
        {
            array_push($this->helperStack, $this->helper);
            $this->helperPrev = $this->helper;
            $this->helper = $helper;
        }


        /**
         * Pop something from the helper stack
         *
         * @access private
         */
        function popHelper()
        {
            $this->helper = array_pop($this->helperStack);
            if (count($this->helperStack)) {
                end($this->helperStack);
                $this->helperPrev = current($this->helperStack); 
            } else {
                $this->helperPrev = false;
            }
        }

    }

?>