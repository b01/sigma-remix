<?php namespace Kshabazz\SigmaRemix;

use \Kshabazz\Web\SigmaRemix\TemplateParser;

/**
 * Class Processor
 *
 * @package \Kshabazz\SigmaRemix
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
		/** @var array Placeholders exclusively set for within a block */
		$blockPlaceholders,
		/** @var string Compiled PHP template. */
		$compiledTemplate,
		/** @var Parser */
		$parser,
		/** @var array Placeholder values. */
		$placeholders,
		/** @var string File path to the template. */
		$templateFile;

	/**
	 * Template constructor.
	 *
	 * @param $pTemplateFile
	 * @param null $pParser Will be used to parse the template.
	 */
	public function __construct( $pTemplateFile, TemplateParser $pParser = NULL )
	{
		$this->templateFile = static::$rootDir . $pTemplateFile;

		if ( !\file_exists($this->templateFile) )
		{
			throw new TemplateException( TemplateException::BAD_TEMPLATE_FILE, [$this->templateFile] );
		}

		$this->blockPlaceholders = [];
		$this->parser = $pParser;
	}

	/**
	 * Compile the template to PHP.
	 *
	 * @return string
	 * TODO: Make private.
	 */
	private function build()
	{
		// Load the template.
		$template = \file_get_contents( $this->templateFile );

		// Allow dependency injection.
		if ( !isset($this->parser) )
		{
			$this->parser = new Parser( $template, self::$rootDir );
		}

		// Compile the template.
		$compileTemplate = $this->parser->process();

		// Build block placeholder arrays which are used when looping over a block.
		$blocks = $this->parser->getBlocks();

		foreach ($blocks as $block )
		{
			$this->parseBlock($block, []);
		}

		return $compileTemplate;
	}

	/**
	 * Convert placeholders to PHP code.
	 *
	 * Will be prefixed to compiled template. The result of which will fill in placeholders when render is called.
	 *
	 * @see ::render
	 * @param array $pPlaceholders
	 * @return string
	 */
	private function compilePlaceholders( array $pPlaceholders )
	{
		$placeholders = \var_export( $pPlaceholders, TRUE );

		return "extract(\n" . $placeholders . "\n);\n";
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
	 * Iterate over a block, setting optional placeholders.
	 *
	 * @param $pBlock
	 * @param array $pVars
	 * @return bool TRUE
	 */
	public function parseBlock( $pBlock, array $pPlaceholders = [] )
	{
		$blockCName = $pBlock . '_ary';

		if ( !\array_key_exists($blockCName, $this->blockPlaceholders) )
		{
			$this->blockPlaceholders[ $blockCName ] = [];
		}

		$this->blockPlaceholders[ $blockCName ][] = $pPlaceholders;

		return TRUE;
	}

	/**
	 * Render the template.
	 *
	 * @param array $pPlaceholders
	 * @return string Template with all blocks and placeholders parsed.
	 */
	public function render( array $pPlaceholders = [] )
	{
		$code = '';

		// 1. Convert the template to PHP.
		if ( !isset($this->compiledTemplate) )
		{
			$this->compiledTemplate = '// Template ?>' . \PHP_EOL . $this->build() . \PHP_EOL . '<?php' . \PHP_EOL;
		}

		// 2. Convert placeholders to PHP code string.
		$code .= '// placeholders' . \PHP_EOL . $this->compilePlaceholders( $pPlaceholders ) . \PHP_EOL;

		// 3. Convert block placeholders to PHP code as a string.
		$code .= '// block placeholders' . \PHP_EOL . $this->compilePlaceholders( $this->blockPlaceholders ) . \PHP_EOL;

		$code .= $this->compiledTemplate;

		// 1. Start a new buffer to capture template output.
		\ob_start();
		// 2. Evaluate the PHP code generated.
		eval( $code );
		// 3. Get the contents of the template output.
		$render = \ob_get_contents();
		// 4. Remove the template output buffer.
		\ob_end_clean();

		return $render;
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
	 * @throws \Kshabazz\SigmaRemix\TemplateException
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
	 * @throws \Kshabazz\SigmaRemix\TemplateException
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