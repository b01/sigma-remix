<?php namespace Kshabazz\Web\SigmaRemix;

/**
 * Class Processor
 *
 * @package \Kshabazz\Web\SigmaRemix
 */
class Template
{
	/**
	 * @var null|string Directory where compiled templates will be stored. Setting to null turns caching off.
	 */
	static private $cacheDir = NULL;

	/**
	 * @var string Root directory for templates. Will be prefixed to templates on load.
	 */
	static private $rootDir = '';

	/**
	 * Processor constructor.
	 */
	public function __construct($pTemplateFile)
	{
		$this->templateFile = static::$rootDir . $pTemplateFile;

		if ( !\file_exists($this->templateFile) )
		{
			throw new TemplateException( TemplateException::BAD_TEMPLATE_FILE, [$this->templateFile] );
		}

//		$this->load();
	}


	/**
	 * Turns cache on and stores compile template in the directory specified.
	 *
	 * The "prepared" templates are just a mix of markup and PHP: essentially all $blocks, $functions, and
	 * $placeholders are converted to PHP. This permits bypassing expensive calls to rebuild template when the source
	 * has not changed.
	 *
	 * The files in this cache do not have any TTL. It is recommended to build templates during deployment, but DO
	 * NOT store the cache in your codebase. That way new source templates are generated with every push.
	 *
	 * NOTE: Caching will be turned off when ::$cacheDir is set to NULL.
	 *
	 * @param string $pCacheDir Template cache directory.
	 * @return bool
	 * @throws \Kshabazz\Web\SigmaRemix\TemplateException
	 */
	static public function setCacheDir( $pCacheDir )
	{
		// Report when invalid values are passed as an argument.
		if ( !\is_dir($pCacheDir) || !\is_null($pCacheDir) )
		{
			throw new TemplateException(TemplateException::BAD_CACHE_DIR, [$pCacheDir]);
		}

		self::$cacheDir = $pCacheDir;

		return TRUE;
	}

	/**
	 * Sets the directory where to look for templates. This directory is prefixed to all template filename passed in.
	 *
	 * @see ::load
	 * @param string $pTemplateRoot Root location to look for templates.
	 * @return bool
	 * @throws \Kshabazz\Web\SigmaRemix\TemplateException
	 */
	static public function setRootDir( $pTemplateRoot )
	{
		// Report when invalid values are passed as an argument.
		if ( !\is_dir($pTemplateRoot) && !\is_null($pTemplateRoot) )
		{
			throw new TemplateException(TemplateException::BAD_TEMPLATE_ROOT_DIR, [$pTemplateRoot]);
		}

		self::$rootDir = $pTemplateRoot;

		return TRUE;
	}
}
?>