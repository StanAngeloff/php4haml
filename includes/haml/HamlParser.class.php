<?php
/**
 * Haml parser.
 *
 * @link http://haml.hamptoncatlin.com/ Original Haml parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Haml
 */

require_once dirname(__FILE__) . '/../common/CommonCache.class.php';

/**
 * End of line character
 */
defined('HAMLPARSER_TOKEN_LINE') OR define('HAMLPARSER_TOKEN_LINE', "\n");

/**
 * Indention token
 */
defined('HAMLPARSER_TOKEN_INDENT') OR define('HAMLPARSER_TOKEN_INDENT', ' ');

/**
 * Create tag (%strong, %div)
 */
defined('HAMLPARSER_TOKEN_TAG') OR define('HAMLPARSER_TOKEN_TAG', '%');

/**
 * Set element ID (#foo, %strong#bar)
 */
defined('HAMLPARSER_TOKEN_ID') OR define('HAMLPARSER_TOKEN_ID', '#');

/**
 * Set element class (.foo, %strong.lorem.ipsum)
 */
defined('HAMLPARSER_TOKEN_CLASS') OR define('HAMLPARSER_TOKEN_CLASS', '.');

/**
 * Start the options (attributes) list
 */
defined('HAMLPARSER_TOKEN_OPTIONS_LEFT') OR define('HAMLPARSER_TOKEN_OPTIONS_LEFT', '{');

/**
 * End the options list
 */
defined('HAMLPARSER_TOKEN_OPTIONS_RIGHT') OR define('HAMLPARSER_TOKEN_OPTIONS_RIGHT', '}');

/**
 * Options separator
 */
defined('HAMLPARSER_TOKEN_OPTIONS_SEPARATOR') OR define('HAMLPARSER_TOKEN_OPTIONS_SEPARATOR', ',');

/**
 * Start option name
 */
defined('HAMLPARSER_TOKEN_OPTION') OR define('HAMLPARSER_TOKEN_OPTION', ':');

/**
 * Start option value
 */
defined('HAMLPARSER_TOKEN_OPTION_VALUE') OR define('HAMLPARSER_TOKEN_OPTION_VALUE', '=>');

/**
 * Begin PHP instruction (without displaying)
 */
defined('HAMLPARSER_TOKEN_INSTRUCTION_PHP') OR define('HAMLPARSER_TOKEN_INSTRUCTION_PHP', '-');

/**
 * Parse PHP (and display)
 */
defined('HAMLPARSER_TOKEN_PARSE_PHP') OR define('HAMLPARSER_TOKEN_PARSE_PHP', '=');

/**
 * Set DOCTYPE or XML header (!!! 1.1, !!!, !!! XML)
 */
defined('HAMLPARSER_TOKEN_DOCTYPE') OR define('HAMLPARSER_TOKEN_DOCTYPE', '!!!');

/**
 * Include file (!! tpl2)
 */
defined('HAMLPARSER_TOKEN_INCLUDE') OR define('HAMLPARSER_TOKEN_INCLUDE', '!!');

/**
 * Comment code (block and inline)
 */
defined('HAMLPARSER_TOKEN_COMMENT') OR define('HAMLPARSER_TOKEN_COMMENT', '/');

/**
 * Translate content (%strong$ Translate)
 */
defined('HAMLPARSER_TOKEN_TRANSLATE') OR define('HAMLPARSER_TOKEN_TRANSLATE', '$');

/**
 * Mark level (%strong?3, !! foo?3)
 */
defined('HAMLPARSER_TOKEN_LEVEL') OR define('HAMLPARSER_TOKEN_LEVEL', '?');

/**
 * Create single, closed tag (%meta{ :foo => 'bar'}/)
 */
defined('HAMLPARSER_TOKEN_SINGLE') OR define('HAMLPARSER_TOKEN_SINGLE', '/');

/**
 * Break line
 */
defined('HAMLPARSER_TOKEN_BREAK') OR define('HAMLPARSER_TOKEN_BREAK', '|');

/**
 * Begin automatic id and classes naming (%tr[$model])
 */
defined('HAMLPARSER_TOKEN_AUTO_LEFT') OR define('HAMLPARSER_TOKEN_AUTO_LEFT', '[');

/**
 * End automatic id and classes naming
 */
defined('HAMLPARSER_TOKEN_AUTO_RIGHT') OR define('HAMLPARSER_TOKEN_AUTO_RIGHT', ']');

/**
 * Insert text block (:textile)
 */
defined('HAMLPARSER_TOKEN_TEXT_BLOCKS') OR define('HAMLPARSER_TOKEN_TEXT_BLOCKS', ':');

/**
 * Number of TOKEN_INDENT to indent
 */
defined('HAMLPARSER_INDENT') OR define('HAMLPARSER_INDENT', 2);

/**
 * One time constructor, is executed??
 *
 * @var boolean
 */
$GLOBALS['_HAMLPARSER_otc'] = false;

/**
 * Debug mode
 *
 * @see HamlParser::isDebug()
 * @var boolean
 */
$GLOBALS['_HAMLPARSER_bDebug'] = false;

/**
 * Doctype definitions
 *
 * @var string
 */
$GLOBALS['_HAMLPARSER_aDoctypes'] = array
(
	'1.1' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
	'Strict' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
	'Transitional' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
	'Frameset' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
	'XML' => "<?php echo '<?xml version=\"1.0\" encoding=\"utf-8\"?>'; ?>\n"
);

/**
 * List of inline tags
 *
 * @var array
 */
$GLOBALS['_HAMLPARSER_aInlineTags'] = array
(
	'a', 'strong', 'b', 'em', 'i', 'h1', 'h2', 'h3', 'h4',
	'h5', 'h6', 'span', 'title', 'li', 'dt', 'dd', 'code',
	'cite', 'td', 'th', 'abbr', 'acronym', 'legend', 'label'
);

/**
 * List of closed tags (like br, link...)
 *
 * @var array
 */
$GLOBALS['_HAMLPARSER_aClosedTags'] = array('br', 'hr', 'link', 'meta', 'img', 'input');

/**
 * List of tags which can't be indented
 *
 * @var array
 */
$GLOBALS['_HAMLPARSER_aNoIndentTags'] = array('pre', 'textarea');

/**
 * List of PHP blocks
 *
 * @var array
 *
 */
$GLOBALS['_HAMLPARSER_aPhpBlocks'] = array('if', 'else', 'elseif', 'while', 'switch', 'for', 'do');

/**
 * Template variables
 *
 * @var array
 */
$GLOBALS['_HAMLPARSER_aVariables'] = array();

/**
 * List of text processing blocks
 *
 * @var array
 */
$GLOBALS['_HAMLPARSER_aBlocks'] = array();

/**
 * Eval embedded PHP code
 *
 * @see HamlParser::embedCode()
 * @var boolean
 */
$GLOBALS['_HAMLPARSER_bEmbed'] = true;

/**
 * Instance for getInstance
 *
 * @see HamlParser::getInstance()
 * @var HamlParser
 */
$GLOBALS['_HAMLPARSER_oInstance'] = null;

/**
 * Translation function name.
 *
 * @var string
 */
$GLOBALS['_HAMLPARSER_sTranslate'] = 'fake_translate';

/**
 * Haml parser.
 *
 * Haml is templating language. It is very simple and clean.
 * Example Haml code
 * <code>
 * !!! 1.1
 * %html
 *   %head
 *     %title= $title ? $title : 'none'
 *     %link{ :rel => 'stylesheet', :type => 'text/css', :href => "$uri/tpl/$theme.css" }
 *   %body
 *     #header
 *       %h1.sitename example.com
 *     #content
 *       / Table with models
 *       %table.config.list
 *         %tr
 *           %th ID
 *           %th Name
 *           %th Value
 *         - foreach ($models as $model)
 *           %tr[$model]
 *             %td= $model->ID
 *             %td= $model->name
 *             %td= $model->value
 *     #footer
 *       %address.author Random Hacker
 * </code>
 * Comparing to original Haml language I added:
 * <ul>
 *   <li>
 *     Support to translations - use '$'
 * <code>
 * %strong$ Log in
 * </code>
 *   </li>
 *   <li>
 *     Including support ('!!') and level changing ('?')
 * <code>
 * !! html
 * !! page.header?2
 * %p?3
 *   Foo bar
 * !! page.footer?2
 * </code>
 *   </li>
 * </ul>
 *
 * @link http://haml.hamptoncatlin.com/ Original Haml parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Haml
 */
class HamlParser
{
	/**
	 * Haml source
	 *
	 * @var string
	 */
	var $sSource = '';

	/**
	 * Files path
	 *
	 * @var string
	 */
	var $sPath = '';

	/**
	 * Compile templates??
	 *
	 * @var boolean
	 */
	var $bCompile = true;

	/**
	 * Filename
	 *
	 * @var string
	 */
	var $sFile = '';

	/**
	 * Parent parser
	 *
	 * @var object
	 */
	var $oParent = null;

	/**
	 * Children parsers
	 *
	 * @var array
	 */
	var $aChildren = array();

	/**
	 * Indent level
	 *
	 * @var integer
	 */
	var $iIndent = -1;

	/**
	 * Translation function
	 *
	 * You can use it in templates.
	 * <code>
	 * %strong= $foo.$this->translate('to translate')
	 * </code>
	 */
	function translate()
	{
		$a = func_get_args();
		return call_user_func_array($GLOBALS['_HAMLPARSER_sTranslate'], $a);
	}

	/**
	 * Set translation callback.
	 *
	 * @param string Translation callback
	 * @return void
	 */
	function setTranslate($mCallable)
	{
		$GLOBALS['_HAMLPARSER_sTranslate'] = $mCallable;
	}

	/**
	 * Temporary directory
	 *
	 * @var string
	 */
	var $sTmp = '';

	/**
	 * Block of PHP code
	 *
	 * @var boolean
	 */
	var $bBlock = false;

	/**
	 * Current tag name
	 *
	 * @var string
	 */
	var $sTag = 'div';

	/**
	 * The constructor.
	 *
	 * Create Haml parser. Second argument can be path to
	 * temporary directory or boolean if true then templates
	 * are compiled to templates path else if false then
	 * templates are compiled on every run
	 * <code>
	 * <?php
	 * require_once './includes/haml/HamlParser.class.php';
	 *
	 * $parser = new HamlParser('./tpl', './tmp');
	 * $foo = 'bar';
	 * $parser->assign('foo', $foo);
	 * $parser->display('mainpage.haml');
	 * ?>
	 * </code>
	 *
	 * @param string Path to files
	 * @param boolean/string Compile templates (can be path)
	 * @param object Parent parser
	 * @param array Array with debug informations
	 * @param boolean Is used dynamic including
	 */
	function HamlParser($sPath = false, $bCompile = true, $aDebug = null, $bInside = false)
	{
		if ($sPath)
			$this->setPath($sPath);
		$this->bCompile = $bCompile;
		if (is_string($bCompile))
			$this->setTmp($bCompile); else
		if ($sPath)
			$this->setTmp($sPath);
		else
			$this->setTmp(ini_get('session.save_path'));
		if ($aDebug)
			$this->aDebug = $aDebug;
		$this->bInside = $bInside;
		if (!$GLOBALS['_HAMLPARSER_otc']) {
			HamlParser::__otc();
		}
	}

	/**
	 * Is used dynamic including
	 *
	 * @var boolean
	 */
	var $bInside = false;

	/**
	 * Debugging informations
	 *
	 * @var array
	 */
	var $aDebug = null;

	/**
	 * Render Haml. Append globals variables
	 *
	 * Simple way to use Haml
	 * <code>
	 * echo HamlParser::haml('%strong Hello, World!'); // <strong>Hello, World!</strong>
	 * $foo = 'bar'; // This is global variable
	 * echo Haml::haml('%strong= "Foo is $foo"'); // <strong>Foo is bar</strong>
	 * </code>
	 *
	 * @param string Haml source
	 * @return string xHTML
	 */
	function haml($sSource)
	{
		static $__haml_parser;
		if (!$__haml_parser)
			$__haml_parser =& new HamlParser();
		$__haml_parser->setSource($sSource);
		$__haml_parser->append($GLOBALS);
		return $__haml_parser->fetch();
	}

	/**
	 * One time constructor. Register Textile block
	 * if exists Textile class and Markdown block if
	 * exists Markdown functions
	 */
	function __otc()
	{
		$GLOBALS['_HAMLPARSER_otc'] = true;
		if (class_exists('Textile'))
			HamlParser::registerBlock(array(new Textile, 'TextileThis'), 'textile');
		if (function_exists('Markdown'))
			HamlParser::registerBlock('Markdown', 'markdown');
	}

	/**
	 * Set parent parser. Used internally.
	 *
	 * @param object Parent parser
	 * @return void
	 */
	function setParent(&$oParent)
	{
		$this->oParent =& $oParent;
		$this->bRemoveBlank = $oParent->bRemoveBlank;
	}

	/**
	 * Set files path.
	 *
	 * @param string File path
	 * @return void
	 */
	function setPath($sPath)
	{
		$this->sPath = realpath($sPath);
	}

	/**
	 * Set filename.
	 *
	 * Filename can be full path to file or
	 * filename (then file is searched in path)
	 * <code>
	 * // We have file ./foo.haml and ./tpl/bar.haml
	 * // ...
	 * $parser->setPath('./tpl');
	 * $parser->setFile('foo.haml'); // Setted to ./foo.haml
	 * $parser->setFile('bar.haml'); // Setted to ./tpl/bar.haml
	 * </code>
	 *
	 * @param string Filename
	 * @return void
	 */
	function setFile($sPath)
	{
		if (file_exists($sPath))
			$this->sFile = $sPath;
		else
			$this->sFile = "{$this->sPath}/$sPath";
		$this->setSource(file_get_contents($this->sFile));
	}

	/**
	 * Return filename to include
	 *
	 * You can override this function.
	 *
	 * @param string Name
	 * @return string
	 */
	function getFilename($sName)
	{
		return "{$this->sPath}/".trim($sName).'.haml';
	}

	/**
	 * Real source
	 *
	 * @var string
	 */
	var $sRealSource = '';

	/**
	 * Set source code
	 *
	 * <code>
	 * // ...
	 * $parser->setFile('foo.haml');
	 * echo $parser->setSource('%strong Foo')->fetch(); // <strong>Foo</strong>
	 * </code>
	 *
	 * @param string Source
	 * @return void
	 */
	function setSource($sHaml)
	{
		$this->sSource = preg_replace('/(\r\n|\r|\n){2,}/', '\\1', trim($sHaml, HAMLPARSER_TOKEN_INDENT));
		$this->sRealSource = $sHaml;
		$this->sTag = null;
		$this->aChildren = array();
	}

	/**
	 * Set temporary directory
	 *
	 * @param string Directory
	 * @return void
	 */
	function setTmp($sTmp)
	{
		$this->sTmp = realpath($sTmp);
	}

	/**
	 * Set and check debug mode. If is set
	 * debugging mode to generated source are
	 * added comments with debugging mode and
	 * Haml source is not cached.
	 *
	 * @param boolean Debugging mode (if null, then only return current state)
	 * @return boolean
	 */
	function isDebug($bDebug = null)
	{
		if (!is_null($bDebug))
			$GLOBALS['_HAMLPARSER_bDebug'] = $bDebug;
		if ($GLOBALS['_HAMLPARSER_bDebug'])
			$this->bCompile = false;
		return $GLOBALS['_HAMLPARSER_bDebug'];
	}

	/**
	 * Render the source or file
	 *
	 * @see HamlParser::fetch()
	 * @return string
	 */
	function render()
	{
		$__aSource = explode(HAMLPARSER_TOKEN_LINE, $this->sRealSource = $this->sSource = $this->parseBreak($this->sSource));
		$__sCompiled = '';
		if (is_a($this->oParent, 'HamlParser'))
			$__sCompiled = $this->parseLine($__aSource[0]);
		else
		{
			$__oCache =& new CommonCache($this->sTmp, 'hphp', $this->sSource);
			$this->aChildren = array();
			if ($__oCache->isCached() && $this->bCompile && !$this->isDebug())
				$__sCompiled = $__oCache->getFilename();
			else
			{
				$__sGenSource = $this->sSource;
				do
				{
					$__iIndent = 0;
					$__iIndentLevel = 0;
					foreach ($__aSource as $__iKey => $__sLine)
					{
						$__iLevel = $this->countLevel($__sLine);
						if ($__iLevel <= $__iIndentLevel)
						$__iIndent = $__iIndentLevel = 0;
						if (preg_match('/\\'.HAMLPARSER_TOKEN_LEVEL.'([0-9]+)$/', $__sLine, $__aMatches))
						{
							$__iIndent = (int)$__aMatches[1];
							$__iIndentLevel = $__iLevel;
							$__sLine = preg_replace('/\\'.HAMLPARSER_TOKEN_LEVEL."$__iIndent$/", '', $__sLine);
						}
						$__sLine = str_repeat(HAMLPARSER_TOKEN_INDENT, $__iIndent * HAMLPARSER_INDENT) . $__sLine;
						$__aSource[$__iKey] = $__sLine;
						if (preg_match('/^(\s*)'.HAMLPARSER_TOKEN_INCLUDE.' (.+)/', $__sLine, $aMatches))
						{
							$__sISource = file_get_contents($__sIFile = $this->getFilename($aMatches[2]));
							if ($this->isDebug())
								$__sISource = "// Begin file $__sIFile\n$__sISource\n// End file $__sIFile";
							$__sIncludeSource = $this->sourceIndent($__sISource, $__iIndent ? $__iIndent : $__iLevel);
							$__sLine = str_replace($aMatches[1] . HAMLPARSER_TOKEN_INCLUDE . " {$aMatches[2]}", $__sIncludeSource, $__sLine);
							$__aSource[$__iKey] = $__sLine;
						}
						$this->sSource = implode(HAMLPARSER_TOKEN_LINE, $__aSource);
					}
					$__aSource = explode(HAMLPARSER_TOKEN_LINE, $this->sSource = $__sGenSource = $this->parseBreak($this->sSource));
				} while (preg_match('/(\\'.HAMLPARSER_TOKEN_LEVEL.'[0-9]+)|(\s*[^!]'.HAMLPARSER_TOKEN_INCLUDE.' .+)/', $this->sSource));
				$this->sSource = $this->sRealSource;
				$__oCache->setCached($this->parseFile($__aSource));
				$__oCache->cacheIt();
				$__sCompiled = $__oCache->getFilename();
			}
			// Expand compiled template
			// set up variables for context
			extract($GLOBALS['_HAMLPARSER_aVariables']);
			ob_start();		// start a new output buffer
			require $__sCompiled;
			if ($this->isDebug())
				@unlink($__sCompiled);
			$__c = rtrim(ob_get_clean()); // capture the result, and discard ob
			// Call filters
			foreach ($this->aFilters as $mFilter)
				$__c = call_user_func($mFilter, $__c);
			if ($this->isDebug())
			{
				header('Content-Type: text/plain');
				$__a = "\nFile $this->sFile:\n";
				foreach (explode("\n", $__sGenSource) as $iKey => $sLine)
					$__a .= 'F' . ($iKey + 1) . ":\t$sLine\n";
				$__c .= rtrim($__a);
			}
			return $__c;
		}
		return $__sCompiled;
	}

	/**
	 * Parse multiline
	 *
	 * @param string File content
	 * @return string
	 */
	function parseBreak($sFile)
	{
		$sFile = preg_replace('/\\'.HAMLPARSER_TOKEN_BREAK.'\s*/', '', $sFile);
		return $sFile;
	}

	/**
	 * Return source of child
	 *
	 * @param integer Level
	 * @return string
	 */
	function getAsSource($iLevel)
	{
		$x = ($this->iIndent - $iLevel - 1) * HAMLPARSER_INDENT;
		$sSource = '';
		if ($x >= 0)
			$sSource = preg_replace('|^'.str_repeat(HAMLPARSER_TOKEN_INDENT, ($iLevel + 1) * HAMLPARSER_INDENT).'|', '', $this->sRealSource);
		foreach ($this->aChildren as $oChild)
			$sSource .= HAMLPARSER_TOKEN_LINE.$oChild->getAsSource($iLevel);
		return trim($sSource, HAMLPARSER_TOKEN_LINE);
	}

	/**
	 * Create and append line to parent
	 *
	 * @param string Line
	 * @param object Parent parser
	 * @param integer Line number
	 * @return HamlParser
	 */
	function& createLine($sLine, &$parent, $iLine = null)
	{
		$oHaml =& new HamlParser($this->sPath, $this->bCompile, array('line'=>$iLine, 'file'=>$this->sFile));
		$oHaml->setParent($parent);
		$oHaml->setSource(rtrim($sLine, "\r"));
		$oHaml->iIndent = $parent->iIndent + 1;
		$parent->aChildren[] =& $oHaml;
		return $oHaml;
	}

	/**
	 * Parse file
	 *
	 * @param array Array of source lines
	 * @return string
	 */
	function parseFile($aSource)
	{
		$aLevels = array(-1 => &$this);
		$sCompiled = '';
		foreach ($aSource as $iKey => $sSource)
		{
			$iLevel = $this->countLevel($sSource);
			$aLevels[$iLevel] =& $this->createLine($sSource, $aLevels[$iLevel - 1], $iKey + 1);
		}
		foreach ($this->aChildren as $oChild)
			$sCompiled .= $oChild->render();
		$sCompiled = preg_replace('|<\?php \}\s*\?>\s*<\?php else \{\s*\?>|ius', '<?php } else { ?>', $sCompiled);
		return $sCompiled;
	}

	/**
	 * Register block
	 *
	 * Text processing blocks are very usefull stuff ;)
	 * <code>
	 * // ...
	 * %code.checksum
	 * $tpl = <<<__TPL__
	 *   :md5
	 *     Count MD5 checksum of me
	 * __TPL__;
	 * HamlParser::registerBlock('md5', 'md5');
	 * $parser->display($tpl); // <code class="checksum">iejmgioemvijeejvijioj323</code>
	 * </code>
	 *
	 * @param mixed Callable
	 * @param string Name
	 */
	function registerBlock($mCallable, $sName = false)
	{
		if (!$sName)
			$sName = serialize($mCallable);
		$GLOBALS['_HAMLPARSER_aBlocks'][$sName] = $mCallable;
	}

	/**
	 * Unregister block
	 *
	 * @param string Name
	 */
	function unregisterBlock($sName)
	{
		unset($GLOBALS['_HAMLPARSER_aBlocks'][$sName]);
	}

	/**
	 * Parse text block
	 *
	 * @param string Block name
	 * @param string Data
	 * @return string
	 */
	function parseTextBlock($sName, $sText)
	{
		return call_user_func($GLOBALS['_HAMLPARSER_aBlocks'][$sName], $sText);
	}

	/**
	 * Eval embedded PHP code
	 *
	 * @param boolean
	 * @return boolean
	 */
	function embedCode($bEmbed = null)
	{
		if (is_null($bEmbed))
			return $GLOBALS['_HAMLPARSER_bEmbed'];
		else
			return $GLOBALS['_HAMLPARSER_bEmbed'] = $bEmbed;
	}

	/**
	 * Implements singleton pattern
	 *
	 * @see HamlParser::HamlParser()
	 * @param string Path to files
	 * @param boolean/string Compile templates (can be path)
	 * @return HamlParser
	 */
	function& getInstance($sPath = false, $bCompile = true)
	{
		if (is_null($GLOBALS['_HAMLPARSER_oInstance']))
			$GLOBALS['_HAMLPARSER_oInstance'] =& new HamlParser($sPath, $bCompile, null, true);
		return $GLOBALS['_HAMLPARSER_oInstance'];
	}

	/**
	 * Remove white spaces??
	 *
	 * @var boolean
	 * @access private
	 */
	var $bRemoveBlank = null;

	/**
	 * Remove white spaces
	 *
	 * @param boolean
	 * @return HamlParser
	 */
	function removeBlank($bRemoveBlank)
	{
		$this->bRemoveBlank = $bRemoveBlank;
	}

	/**
	 * Parse line
	 *
	 * @param string Line
	 * @return string
	 */
	function parseLine($sSource)
	{
		$sParsed = '';
		$sRealBegin = '';
		$sRealEnd = '';
		$sParsedBegin = '';
		$sParsedEnd = '';
		$bParse = true;
		// Dynamic including
		if (preg_match('/^'.HAMLPARSER_TOKEN_INCLUDE.HAMLPARSER_TOKEN_PARSE_PHP.' (.*)/', $sSource, $aMatches) && $this->embedCode())
		{
			return ($this->isDebug() ? "{$this->aDebug['line']}:\t{$aMatches[1]} == <?php var_export({$aMatches[1]}) ?>\n\n" : '') . "<?php \$__instance =& HamlParser::getInstance(\$this->sPath, \$this->sTmp); echo \$this->indent(\$__instance->fetch(\$this->getFilename({$aMatches[1]})), $this->iIndent, true, false); ?>";
		} else
		// Doctype parsing
		if (preg_match('/^'.HAMLPARSER_TOKEN_DOCTYPE.'(.*)/', $sSource, $aMatches))
		{
			$aMatches[1] = trim($aMatches[1]);
			if ($aMatches[1] == '')
			  $aMatches[1] = '1.1';
			$sParsed = $GLOBALS['_HAMLPARSER_aDoctypes'][$aMatches[1]]."\n";
		} else
		// Internal comment
		if (preg_match('/^\\'.HAMLPARSER_TOKEN_COMMENT.'\\'.HAMLPARSER_TOKEN_COMMENT.'/', $sSource))
			return '';
		else
		// PHP instruction
		if (preg_match('/^'.HAMLPARSER_TOKEN_INSTRUCTION_PHP.' (.*)/', $sSource, $aMatches))
		{
			if (!$this->embedCode())
				return '';
			$bBlock = false;
			// Check for block
			if (preg_match('/^('.implode('|', $GLOBALS['_HAMLPARSER_aPhpBlocks']).')/', $aMatches[1]))
			  $this->bBlock = $bBlock = true;
			$sParsedBegin = '<?php ' . $this->indent($aMatches[1] . ($bBlock ? ' {' : ';'), -2, false)  . '?>';
			if ($bBlock)
			  $sParsedEnd = '<?php } ?>';
		} else
		// Text block
		if (preg_match('/^'.HAMLPARSER_TOKEN_TEXT_BLOCKS.'(.+)/', $sSource, $aMatches))
		{
			$sParsed = $this->indent($this->parseTextBlock($aMatches[1], $this->getAsSource($this->iIndent)));
			$this->aChildren = array();
		} else
		// Check for PHP
		if (preg_match('/^'.HAMLPARSER_TOKEN_PARSE_PHP.' (.*)/', $sSource, $aMatches))
			if ($this->embedCode())
				$sParsed = $this->indent("<?php echo {$aMatches[1]}; ?>")."\n";
			else
				$sParsed = "\n";
		else
		{
			$aAttributes = array();
			$sAttributes = '';
			$sTag = 'div';
			$sToParse = '';
			$sContent = '';
			$sAutoVar = '';

			// Parse options
			while (preg_match('/\\'.HAMLPARSER_TOKEN_OPTIONS_LEFT.'(.*?)\\'.HAMLPARSER_TOKEN_OPTIONS_RIGHT.'/', $sSource, $aMatches))
			{
				$sSource = str_replace($aMatches[0], '', $sSource);
				$aOptions = explode(HAMLPARSER_TOKEN_OPTIONS_SEPARATOR, $aMatches[1]);
				foreach ($aOptions as $sOption)
				{
					$aOption = explode(HAMLPARSER_TOKEN_OPTION_VALUE, trim($sOption), 2);
					foreach ($aOption as $k => $o)
						$aOption[$k] = trim($o);
					$sOptionName = ltrim($aOption[0], HAMLPARSER_TOKEN_OPTION);
					$aAttributes[$sOptionName] = trim($aOption[1], HAMLPARSER_TOKEN_OPTION);
				}
			}

			$sFirst = '['.HAMLPARSER_TOKEN_TAG.'|'.HAMLPARSER_TOKEN_ID.'|'.HAMLPARSER_TOKEN_CLASS.'|'.HAMLPARSER_TOKEN_PARSE_PHP.']';

			if (preg_match("/^($sFirst.*?) (.*)/", $sSource, $aMatches))
			{
				$sToParse = $aMatches[1];
				$sContent = $aMatches[2];
			} else
			if (preg_match("/^($sFirst.*)/", $sSource, $aMatches))
				$sToParse = $aMatches[1];
			else
			{
				// Check for comment
				if (!preg_match('/^\\'.HAMLPARSER_TOKEN_COMMENT.'(.*)/', $sSource, $aMatches))
				{
					if ($this->canIndent() && $this->bRemoveBlank)
						if ($this->isFirst())
							$sParsed = $this->indent($sSource, 0, false) . ' '; else
						if ($this->isLast())
							$sParsed = "$sSource\n";
						else
							$sParsed = "$sSource ";
					else
						$sParsed = $this->indent($sSource);
					foreach ($this->aChildren as $oChild)
						$sParsed .= $oChild->render();
				}
				else
				{
					$aMatches[1] = trim($aMatches[1]);
					if ($aMatches[1] && !preg_match('/\[.*\]/', $aMatches[1]))
						$sParsed = $this->indent(wordwrap($aMatches[1], 60, "\n"), 1)."\n";
				}
				$bParse = false;
			}

			if ($bParse)
			{
				$bPhp = false;
				$bClosed = false;
				// Match tag
				if (preg_match_all('/'.HAMLPARSER_TOKEN_TAG.'([a-zA-Z0-9:\-_]*)/', $sToParse, $aMatches))
					$this->sTag = $sTag = end($aMatches[1]); // it's stack
				// Match ID
				if (preg_match_all('/'.HAMLPARSER_TOKEN_ID.'([a-zA-Z0-9\-_]*)/', $sToParse, $aMatches))
					$aAttributes['id'] = '\''.end($aMatches[1]).'\''; // it's stack
				// Match classes
				if (preg_match_all('/\\'.HAMLPARSER_TOKEN_CLASS.'([a-zA-Z0-9\-_]*)/', $sToParse, $aMatches))
					$aAttributes['class'] = '\''.implode(' ', $aMatches[1]).'\'';
				// Check for PHP
				if (preg_match('/'.HAMLPARSER_TOKEN_PARSE_PHP.'/', $sToParse))
				{
					if ($this->embedCode())
					{
						$sContentOld = $sContent;
						$sContent = "<?php echo $sContent; ?>\n";
						$bPhp = true;
					}
					else
						$sContent = '';
				}
				// Match translating
				if (preg_match('/\\'.HAMLPARSER_TOKEN_TRANSLATE.'$/', $sToParse, $aMatches))
				{
					if (!$bPhp)
						$sContent = "'$sContent'";
					else
						$sContent = $sContentOld;
					$sContent = "<?php echo {$GLOBALS['_HAMLPARSER_sTranslate']}($sContent); ?>\n";
				}
				// Match single tag
				if (preg_match('/\\'.HAMLPARSER_TOKEN_SINGLE.'$/', $sToParse))
					$bClosed = true;
				// Match brackets
				if (preg_match('/\\'.HAMLPARSER_TOKEN_AUTO_LEFT.'(.*?)\\'.HAMLPARSER_TOKEN_AUTO_RIGHT.'/', $sToParse, $aMatches) && $this->embedCode())
					$sAutoVar = $aMatches[1];

				if (!empty($aAttributes) || !empty($sAutoVar))
					$sAttributes = '<?php $this->writeAttributes('.$this->arrayExport($aAttributes).(!empty($sAutoVar) ? ", \$this->parseSquareBrackets($sAutoVar)" : '' ).'); ?>';
				$this->bBlock = $this->oParent->bBlock;
				$iLevelM = $this->oParent->bBlock || $this->bBlock ? -1 : 0;
				// Check for closed tag
				if ($this->isClosed($sTag) || $bClosed)
					$sParsedBegin = $this->indent("<$sTag$sAttributes />", $iLevelM); else
				// Check for no indent tag
				if (in_array($sTag, $GLOBALS['_HAMLPARSER_aNoIndentTags']))
				{
					$this->bRemoveBlank = false;
					$sParsedBegin = $this->indent("<$sTag$sAttributes>", $iLevelM, false);
					if (!empty($sContent))
						$sParsed = $this->indent($sContent);
					$sParsedEnd = $this->indent("</$sTag>\n", $iLevelM);
				} else
				// Check for block tag
				if (!$this->isInline($sTag))
				{
					$sParsedBegin = $this->indent("<$sTag$sAttributes>", $iLevelM);
					if (!empty($sContent))
						if (strlen($sContent) > 60)
							$sParsed = $this->indent(wordwrap($sContent, 60, "\n"), 1+$iLevelM);
						else
							$sParsed = $this->indent($sContent, 1+$iLevelM);
					$sParsedEnd = $this->indent("</$sTag>", $iLevelM);
				} else
				// Check for inline tag
				if ($this->isInline($sTag))
				{
					if ($this->canIndent() && $this->bRemoveBlank)
						if ($this->isFirst())
							$sParsedBegin = $this->indent("<$sTag$sAttributes>", 0, false); else
						if ($this->isLast())
							$sParsedBegin = "<$sTag$sAttributes>\n";
						else
							$sParsedBegin = "<$sTag$sAttributes>";
					else
						if (!$this->canIndent())
							$sParsedBegin = "\n" . $this->indent("<$sTag$sAttributes>", $iLevelM, false);
						else
							$sParsedBegin = $this->indent("<$sTag$sAttributes>", $iLevelM, false);
					$sParsed = $sContent;
					if ($this->canIndent() && $this->bRemoveBlank)
						if ($this->isLast())
							$sParsedEnd = "</$sTag>\n";
						else
							$sParsedEnd = "</$sTag> ";
					else
						$sParsedEnd = "</$sTag>\n";
				}
			}
		}
		// Children appending
		foreach ($this->aChildren as $oChild)
			$sParsed .= $oChild->fetch();
		// Check for IE comment
		if (preg_match('/^\\'.HAMLPARSER_TOKEN_COMMENT.'\[(.*?)\](.*)/', $sSource, $aMatches))
		{
			$aMatches[2] = trim($aMatches[2]);
			if (count($this->aChildren) == 0)
			{
				$sParsedBegin = $this->indent("<!--[{$aMatches[1]}]> $sParsedBegin", 0, false);
				$sParsed = $aMatches[2];
				$sParsedEnd = "$sParsedEnd <![endif]-->\n";
			}
			else
			{
				$sParsed = $sParsedBegin.$sParsed.$sParsedEnd;
				$sParsedBegin = $this->indent("<!--[{$aMatches[1]}]>");
				$sParsedEnd = $this->indent("<![endif]-->");
			}
		} else
		// Check for comment
		if (preg_match('/^\\'.HAMLPARSER_TOKEN_COMMENT.'(.*)/', $sSource, $aMatches))
		{
			$aMatches[1] = trim($aMatches[1]);
			if (count($this->aChildren) == 0)
			{
				$sParsedBegin = $this->indent("<!-- $sParsedBegin", 0, false);
				$sParsed = $aMatches[1];
				$sParsedEnd = "$sParsedEnd -->\n";
			}
			else
			{
				$sParsed = $sParsedBegin.$sParsed.$sParsedEnd;
				$sParsedBegin = $this->indent("<!--");
				$sParsedEnd = $this->indent("-->");
			}
		}
		if ($this->isDebug() && (count($this->aChildren) > 0))
			$sParsedEnd = "{$this->aDebug['line']}:\t$sParsedEnd";
		$sCompiled = $sRealBegin.$sParsedBegin.$sParsed.$sParsedEnd.$sRealEnd;
		if ($this->isDebug())
			$sCompiled = "{$this->aDebug['line']}:\t$sCompiled";
		return $sCompiled;
	}

	/**
	 * Indent line
	 *
	 * @param string Line
	 * @param integer Additional indention level
	 * @param boolean Add new line
	 * @param boolean Count level from parent
	 * @return string
	 */
	function indent($sLine, $iAdd = 0, $bNew = true, $bCount = true)
	{
		if (!is_null($this->oParent) && $bCount)
			if (!$this->canIndent())
				if ($sLine{0} == '<')
					return $sLine;
				else
					return "$sLine\n";
		$aLine = explode("\n", $sLine);
		$sIndented = '';
		$iLevel = ($bCount ? $this->iIndent : 0) + $iAdd;
		foreach ($aLine as $sLine)
			$sIndented .= str_repeat("\t", $iLevel >= 0 ? $iLevel : 0).($bNew ? "$sLine\n" : $sLine);
		return $sIndented;
	}

	/**
	 * Is first child of parent
	 *
	 * @return boolean
	 */
	function isFirst()
	{
		if (!is_a($this->oParent, 'HamlParser'))
			return false;
		foreach ($this->oParent->aChildren as $key => $value)
			if ($value === $this)
				return $key == 0;
	}

	/**
	 * Is last child of parent
	 *
	 * @return boolean
	 */
	function isLast()
	{
		if (!is_a($this->oParent, 'HamlParser'))
			return false;
		$count = count($this->oParent->aChildren);
		foreach ($this->oParent->aChildren as $key => $value)
			if ($value === $this)
				return $key == $count - 1;
	}

	/**
	 * Can indent (check for parent is NoIndentTag)
	 *
	 * @return boolean
	 */
	function canIndent()
	{
		if (in_array($this->sTag, $GLOBALS['_HAMLPARSER_aNoIndentTags']))
			return false;
		else
			if (is_a($this->oParent, 'HamlParser'))
				return $this->oParent->canIndent();
			else
				return true;
	}

	/**
	 * Indent Haml source
	 *
	 * @param string Source
	 * @param integer Level
	 * @return string
	 */
	function sourceIndent($sSource, $iLevel)
	{
		$aSource = explode(HAMLPARSER_TOKEN_LINE, $sSource);
		foreach ($aSource as $sKey => $sValue)
			$aSource[$sKey] = str_repeat(HAMLPARSER_TOKEN_INDENT, $iLevel * HAMLPARSER_INDENT) . $sValue;
		$sSource = implode(HAMLPARSER_TOKEN_LINE, $aSource);
		return $sSource;
	}

	/**
	 * Count level of line
	 *
	 * @param string Line
	 * @return integer
	 */
	function countLevel($sLine)
	{
		return (strlen($sLine) - strlen(trim($sLine, HAMLPARSER_TOKEN_INDENT))) / HAMLPARSER_INDENT;
	}

	/**
	 * Check for inline tag
	 *
	 * @param string Tag
	 * @return boolean
	 */
	function isInline($sTag)
	{
		return (empty($this->aChildren) && in_array($sTag, $GLOBALS['_HAMLPARSER_aInlineTags'])) || empty($this->aChildren);
	}

	/**
	 * Check for closed tag
	 *
	 * @param string Tag
	 * @return boolean
	 */
	function isClosed($sTag)
	{
		return in_array($sTag, $GLOBALS['_HAMLPARSER_aClosedTags']);
	}

	// Template engine

	/**
	 * Assign variable
	 *
	 * <code>
	 * // ...
	 * $parser->assign('foo', 'bar');
	 * $lorem = 'ipsum';
	 * $parser->assign('example', $lorem);
	 * </code>
	 *
	 * @param string Name
	 * @param mixed Value
	 * @return void
	 */
	function assign($sName, $sValue)
	{
		$GLOBALS['_HAMLPARSER_aVariables'][$sName] = $sValue;
	}

	/**
	 * Assign variable
	 *
	 * <code>
	 * // ...
	 * $parser->assign('foo', 'bar');
	 * $lorem = 'ipsum';
	 * $parser->assign('example', $lorem);
	 * </code>
	 *
	 * @param string Name
	 * @param mixed Value
	 * @return void
	 */
	function assignRef($sName, &$sValue)
	{
		$GLOBALS['_HAMLPARSER_aVariables'][$sName] =& $sValue;
	}

	/**
	 * Assign associative array of variables
	 *
	 * <code>
	 * // ...
	 * $parser->append(array('foo' => 'bar', 'lorem' => 'ipsum');
	 * $data = array
	 * (
	 *   'x' => 10,
	 *   'y' => 5
	 * );
	 * $parser->append($data);
	 * </code>
	 *
	 * @param array Data
	 * @return void
	 */
	function append($aData)
	{
		$GLOBALS['_HAMLPARSER_aVariables'] = array_merge($GLOBALS['_HAMLPARSER_aVariables'], $aData);
	}

	/**
	 * Removes variables
	 *
	 * @return void
	 */
	function clearVariables()
	{
		$GLOBALS['_HAMLPARSER_aVariables'] = array();
	}

	/**
	 * Remove all compiled templates (*.hphp files)
	 *
	 * @return void
	 */
	function clearCompiled()
	{
		$oDirs = dir($this->sTmp);
		while (($oDir = $oDirs->read()) !== false)
			if ($oDir{0} !== '.' && preg_match('/\.hphp/', $oDir)) {
				unlink($this->sTmp . '/' . $oDir);
			}
		$oDirs->close();
	}

	/**
	 * Return compiled template
	 *
	 * <code>
	 * // ...
	 * echo $parser->setSource('%strong Foo')->fetch(); // <strong>Foo</strong>
	 * $parser->setSource('%strong Bar')->display(); // <strong>Bar</strong>
	 * echo $parser->setSource('%em Linux'); // <strong>Linux</strong>
	 *
	 * echo $parser->fetch('bar.haml'); // Compile and display bar.haml
	 * </code>
	 *
	 * @param string Filename
	 * @return string
	 */
	function fetch($sFilename = false)
	{
		if ($sFilename)
			$this->setFile($sFilename);
		return $this->render();
	}

	/**
	 * Display template
	 *
	 * @see HamlParser::fetch()
	 * @param string Filename
	 */
	function display($sFilename = false)
	{
		echo $this->fetch($sFilename);
	}

	/**
	 * List of registered filters
	 *
	 * @var array
	 */
	var $aFilters = array();

	/**
	 * Register output filter.
	 *
	 * Filters are next usefull stuff. For example if
	 * you want remove <em>all</em> whitespaces (blah) use this
	 * <code>
	 * // ...
	 * function fcw($data)
	 * {
	 *   return preg_replace('|\s*|', '', $data);
	 * }
	 * $parser->registerFilter('fcw');
	 * echo $parser->fetch('foo.haml');
	 * </code>
	 *
	 * @param callable Filter
	 * @param string Name
	 * @return void
	 */
	function registerFilter($mCallable, $sName = false)
	{
		if (!$sName)
			$sName = serialize($mCallable);
		$this->aFilters[$sName] = $mCallable;
	}

	/**
	 * Unregister output filter
	 *
	 * @param string Name
	 * @return void
	 */
	function unregisterFilter($sName)
	{
		unset($this->aFilters[$sName]);
	}

	/**
	 * Return array of template variables
	 *
	 * @return array
	 */
	function getVariables()
	{
		return $GLOBALS['_HAMLPARSER_aVariables'];
	}

	/**
	 * Parse variable in square brackets
	 *
	 * @param mixed Variable
	 * @return array Attributes
	 */
	function parseSquareBrackets($mVariable)
	{
		$sType = gettype($mVariable);
		$aAttr = array();
		$sId = '';
		if ($sType == 'object')
		{
			static $__objectNamesCache;
			if (!is_array($__objectNamesCache))
				$__objectNamesCache = array();
			$sClass = get_class($mVariable);
			if (!array_key_existS($sClass, $__objectNamesCache))
				$__objectNamesCache[$sClass] = $sType = trim(preg_replace('/([A-Z][a-z]*)/', '$1_', $sClass), '_');
			else
				$sType = $__objectNamesCache[$sClass];
			if (method_exists($mVariable, 'getID'))
				$sId = $mVariable->getID(); else
			if (!empty($mVariable->ID))
				$sId = $mVariable->ID;
		}
		if ($sId == '')
			$sId = substr(md5(uniqid(serialize($mVariable).rand(), true)), 0, 8);
		$aAttr['class'] = strtolower($sType);
		$aAttr['id'] = "{$aAttr['class']}_$sId";
		return $aAttr;
	}

	/**
	 * Write attributes
	 */
	function writeAttributes()
	{
		$aAttr = array();
		foreach (func_get_args() as $aArray)
			$aAttr = array_merge($aAttr, $aArray);
		ksort($aAttr);
		foreach ($aAttr as $sName => $sValue)
			if ($sValue)
				echo " $sName=\"".htmlentities($sValue).'"';
	}

	/**
	 * Export array
	 *
	 * @return string
	 */
	function arrayExport()
	{
		$sArray = 'array (';
		$aArray = $aNArray = array();
		foreach (func_get_args() as $aArg)
			$aArray = array_merge($aArray, $aArg);
		foreach ($aArray as $sKey => $sValue)
		{
			if (!preg_match('/[\'$"()]/', $sValue))
				$sValue = "'$sValue'";
			$aNArray[] = "'$sKey' => $sValue";
		}
		$sArray .= implode(', ', $aNArray).')';
		return $sArray;
	}
}

if (!function_exists('fake_translate'))
{
	/**
	 * Fake translation function used
	 * as default translation function
	 * in HamlParser
	 *
	 * @param string
	 * @return string
	 */
	function fake_translate($s)
	{
		return $s;
	}
}

/**
 * This is the simpliest way to use Haml
 * templates. Global variables are
 * automatically assigned to template.
 *
 * <code>
 * $x = 10;
 * $y = 5;
 * display_haml('my.haml'); // Simple??
 * </code>
 *
 * @param string Haml parser filename
 * @param array Associative array of additional variables
 * @param string Temporary directory (default is directory of Haml templates)
 * @param boolean Register get, post, session, server and cookie variables
 */
function display_haml($sFilename, $aVariables = array(), $sTmp = true, $bGPSSC = false)
{
	global $__oHaml;
	$sPath = realpath($sFilename);
	if (!is_object($__oHaml))
		$__oHaml =& new HamlParser(dirname($sPath), $sTmp);
	$__oHaml->append($GLOBALS);
	if ($bGPSSC)
	{
		$__oHaml->append($_GET);
		$__oHaml->append($_POST);
		$__oHaml->append($_SESSION);
		$__oHaml->append($_SERVER);
		$__oHaml->append($_COOKIE);
	}
	$__oHaml->append($aVariables);
	$__oHaml->display($sFilename);
}
