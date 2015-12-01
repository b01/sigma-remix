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

	private
		/** @var string Compiled PHP template. */
		$compiledTemplate,
		/** @var array Placeholder values. */
		$placeholders,
		/** @var string File path to the template. */
		$templateFile;

	/**
	 * Processor constructor.
	 */
	public function __construct( $pTemplateFile )
	{
		$this->templateFile = static::$rootDir . $pTemplateFile;

		if ( !\file_exists($this->templateFile) )
		{
			throw new TemplateException( TemplateException::BAD_TEMPLATE_FILE, [$this->templateFile] );
		}
	}

	public function compile()
	{
		// Load the template.
		$template = \file_get_contents( $this->templateFile );

		$parser = new Parser($template);

		$this->compiledTemplate = $parser->process();

		return $this->compiledTemplate;
	}

	/**
	 * Get placeholders.
	 *
	 * @return mixed
	 */
	public function getPlaceholders()
	{
		return $this->placeholders;
	}

	/**
	 * Save a compiled template to cache.
	 *
	 * @return bool TRUE on success, or FALSE otherwise. May also emit an error if cannot saves
	 * @see file_put_contents
	 */
	public function save()
	{
		$saveFile = static::$cacheDir
				. DIRECTORY_SEPARATOR . basename( $this->templateFile ) . '.php';

		return FALSE !== \file_put_contents( $saveFile, $this->compiledTemplate );
	}

	/**
	 * Set placeholders in the template.
	 *
	 * These values will be used to fill in values when the template is rendered.
	 *
	 * @param array $pPlaceholders
	 */
	public function setPlaceholders( array $pPlaceholders )
	{
		$this->placeholders = array_merge( $pPlaceholders );
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
	 * @return TRUE
	 * @throws \Kshabazz\Web\SigmaRemix\TemplateException
	 */
	static public function setCacheDir( $pCacheDir )
	{
		// Report when invalid values are passed as an argument.
		if ( !\is_dir($pCacheDir) && !\is_null($pCacheDir) )
		{
			throw new TemplateException(TemplateException::BAD_CACHE_DIR, [$pCacheDir]);
		}

		self::$cacheDir = $pCacheDir;

		return TRUE;
	}

	/**
	 * Sets the directory where to look for templates. This directory is prefixed to all template filename passed in.
	 *
	 * @see ::compile
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