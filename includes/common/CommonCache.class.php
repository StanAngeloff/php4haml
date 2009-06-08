<?php
/**
 * Cache engine
 *
 * @link http://haml.hamptoncatlin.com/ Original Sass parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Common
 */

/**
 * Cache engine
 *
 * @link http://haml.hamptoncatlin.com/ Original Sass parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Common
 */
class CommonCache
{
	/**
	 * The constructor
	 *
	 * @param string Path to cached data
	 * @param string Extension of cache files
	 * @param string Data to cache
	 */
	function CommonCache($path, $extension = 'ccd', &$data)
	{
		$this->setPath($path);
		$this->setExtension($extension);
		$this->setData($data);
		$this->setHash($this->createHash($data));
		if (file_exists($this->getFilename()))
			$this->setCached(file_get_contents($this->getFilename()));
	}

	/**
	 * Extension
	 *
	 * @var string
	 */
	var $extension;

	/**
	 * Get the extension. Extension
	 *
	 * @return string
	 */
	function getExtension()
	{
		return $this->extension;
	}

	/**
	 * Set the extension. Extension
	 *
	 * @param string Extension data
	 * @return void
	 */
	function setExtension($extension)
	{
		$this->extension = $extension;
	}

	/**
	 * Cached data
	 *
	 * @var string
	 */
	var $cached = null;

	/**
	 * Get the cached. Cached data
	 *
	 * @return string
	 */
	function getCached()
	{
		return $this->cached;
	}

	/**
	 * Set the cached. Cached data
	 *
	 * @param string Cached data
	 * @return void
	 */
	function setCached($cached)
	{
		$this->cached = ltrim($cached);
	}

	/**
	 * Check for cached data
	 *
	 * @return unknown
	 */
	function isCached()
	{
		return !is_null($this->cached);
	}

	/**
	 * Data
	 *
	 * @var string
	 */
	var $data;

	/**
	 * Get the data. Data
	 *
	 * @return string
	 */
	function& getData()
	{
		return $this->data;
	}

	/**
	 * Set the data. Data
	 *
	 * @param string Data data
	 * @return void
	 */
	function setData(&$data)
	{
		$this->data =& $data;
	}

	/**
	 * Path
	 *
	 * @var string
	 */
	var $path;

	/**
	 * Get the path. Path
	 *
	 * @return string
	 */
	function getPath()
	{
		return $this->path;
	}

	/**
	 * Set the path. Path
	 *
	 * @param string Path data
	 * @return void
	 */
	function setPath($path)
	{
		$this->path = $path;
	}

	/**
	 * Hash of data
	 *
	 * @var string
	 */
	var $hash;

	/**
	 * Get the hash. Hash of data
	 *
	 * @return string
	 */
	function getHash()
	{
		return $this->hash;
	}

	/**
	 * Set the hash. Hash of data
	 *
	 * @param string Hash data
	 * @return void
	 */
	function setHash($hash)
	{
		$this->hash = $hash;
		$this->setFilename($this->getPath() . "/$hash." . $this->getExtension());
	}

	/**
	 * Create hash of data
	 *
	 * @param string Data
	 * @return string
	 */
	function createHash(&$data)
	{
		if (is_null($data))
			$data =& $this->getData();
		return md5(serialize($data));
	}

	/**
	 * Cache filename
	 *
	 * @var string
	 */
	var $filename;

	/**
	 * Get the filename. Cache filename
	 *
	 * @return string
	 */
	function getFilename()
	{
		return $this->filename;
	}

	/**
	 * Set the filename. Cache filename
	 *
	 * @param string Filename data
	 * @return void
	 */
	function setFilename($filename)
	{
		$this->filename = $filename;
	}

	/**
	 * Cache data
	 *
	 * @return CommonCache
	 */
	function cacheIt()
	{
		$fp = fopen($this->getFilename(), 'w');
		if ($fp !== false) {
			$fl = flock($fp, LOCK_EX);
			if ($fl !== false) {
				fputs($fp, $this->getCached());
				flock($fp, LOCK_UN);
			}
			fclose($fp);
		}
	}
}
