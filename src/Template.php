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
		/** @var array Block replacements. */
		$replaceBlocks,
		/** @var string File path to the template. */
		$templateFile;

	/**
	 * Template constructor.
	 *
	 * @param string $pTemplateFile
	 * @param TemplateParser|NULL $pParser Will be used to parse the template.
	 * @throws TemplateException
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
	 * @param array $pPlaceholders Values to fill in placeholders.
	 * @return string
	 *
	 * TODO Implement loading from cache
	 */
	private function build( array $pPlaceholders = [] )
	{
		// Load the template.
		$template = \file_get_contents( $this->templateFile );

		// Allow dependency injection.
		if ( !isset($this->parser) )
		{
			$this->parser = new Parser( $template, self::$rootDir );
		}

		$this->parser->setBlockReplacements( $this->replaceBlocks );

		// 1. Convert Sigma tags to PHP.
		if ( !isset($this->compiledTemplate) )
		{
			$this->compiledTemplate = '// Template ?>'
				. \PHP_EOL . $this->parser->process()
				. \PHP_EOL . '<?php'
				. \PHP_EOL;
		}

		// 2. Build block placeholder arrays for blocks that have not been parsed.
		$blocks = $this->parser->getBlocks();

		foreach ($blocks as $block )
		{
			$this->parseBlock($block, []);
		}

		// Placeholder values can change every time render is called. So do not add them to compiled template that is
		// saved.

		// 3. Convert placeholders to PHP code string.
		$code = '// placeholders' . \PHP_EOL . $this->compilePlaceholders( $pPlaceholders ) . \PHP_EOL;

		// 4. Convert block placeholders to PHP code as a string.
		$code .= '// block placeholders' . \PHP_EOL . $this->compilePlaceholders( $this->blockPlaceholders ) . \PHP_EOL;

		// 5. Put it all together.
		$code .= $this->compiledTemplate;

		return $code;
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
			// Initialize the block placeholder variable.
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
		// 1. Get the compile template.
		$code = $this->build( $pPlaceholders );

		// 2. Start a new buffer to capture template output.
		\ob_start();

		// 3. Evaluate the PHP code generated.
		eval( $code );

		// 4. Get the contents of the template output.
		$render = \ob_get_contents();

		// 5. Remove the template output buffer.
		\ob_end_clean();

		return $render;
	}

	/**
	 * Replace block content with new content.
	 */
	public function replaceBlock( $pBlock, $pContent )
	{
		$this->replaceBlocks[ $pBlock ] = $pContent;

		return $this;
	}

	/**
	 * Save a compiled template to cache.
	 *
	 * @return bool TRUE on success, or FALSE otherwise. May also emit an error if cannot saves
	 * @see file_put_contents
	 */
	public function save()
	{
		// TODO: Complete
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
		if ( !\is_dir($pCacheDir) && !empty($pCacheDir) )
		{
			throw new TemplateException(TemplateException::BAD_CACHE_DIR, [$pCacheDir]);
		}

		self::$cacheDir = empty($pCacheDir) ? $pCacheDir : $pCacheDir . DIRECTORY_SEPARATOR;

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
		if ( !\is_dir($pTemplateRoot) && !empty($pTemplateRoot) )
		{
			throw new TemplateException(TemplateException::BAD_TEMPLATE_ROOT_DIR, [$pTemplateRoot]);
		}

		self::$rootDir = empty($pTemplateRoot) ? $pTemplateRoot : $pTemplateRoot . DIRECTORY_SEPARATOR;

		return TRUE;
	}
}
?>