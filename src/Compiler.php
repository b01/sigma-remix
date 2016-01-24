<?php namespace Kshabazz\SigmaRemix;

/**
 * Class Compiler
 *
 * @package Kshabazz\SigmaRemix
 */
class Compiler
{
	/**
	 * Switch debug messages on/off.
	 *
	 * @var bool
	 */
	static private $debug = FALSE;

	/**
	 * Limit the number of recursive calls a function can make.
	 *
	 * This is reset once the limit is hit and the function returns. Strict mode will cause an error to be throw when
	 * the limit is reached.
	 *
	 * @var int
	 * @see ::replaceBlock, ::replaceInclude
	 * TODO: Implement and add static functions to set.
	 */
	static private $recursionLimit = 10;

	/** @var bool Throw errors for simple mistakes that are not fatal. */
	static private $strictMode = FALSE;

	private
		/** @var array A list of block found and replaced within a template. */
		$blocks,
		/** @var array Blocks to remove from the template. */
		$blockRemovals,
		/** @var array Blocks to replace with new content. */
		$blockReplacements,
		/** @var array A list of functions found and replaced within a template. */
		$functions,
		/** @var string A regular expression to parse functions within a template. */
		$functionRegEx,
		/** @var string Regular expression to parse include tags. */
		$includeRegEx,
		/** @var string Directory where load include templates */
		$includeTemplatesDir,
		/** @var array Placeholder within a template. */
		$placeholders,
		/** @var string Regular expression to parse placeholder tags. */
		$placeholderRegEx;

	/**
	 * Turn debugging messages on/off.
	 *
	 * @param bool $pSwitch
	 */
	static public function setDebug( $pSwitch )
	{
		static::$debug = $pSwitch;
	}

	/**
	 * When on, will throw exceptions when:
	 * Cannot find an include file.
	 *
	 * @param bool $pBool TRUE or FALSE, to turn on or off respectively.
	 */
	static public function setStrict( $pBool )
	{
		static::$strictMode = $pBool;
	}

	/**
	 * Get indication if the parser is in strict mode.
	 *
	 * @return bool
	 */
	static public function isStrict()
	{
		return static::$strictMode;
	}

	/**
	 * Compiler constructor.
	 *
	 * @param string $pTemplate Template to be parsed.
	 * @param string $pIncludeTemplatesDir This path will be prefixed to the path in any INCLUDE tag during parsing.
	 */
	public function __construct( $pTemplate, $pIncludeTemplatesDir = NULL, Parser $pParser = NULL )
	{
		if ( !\is_null($pIncludeTemplatesDir) && !is_dir($pIncludeTemplatesDir) )
		{
			// TODO: Implement ParserException, and store this message there, or refactor this.
			throw new \InvalidArgumentException( $pIncludeTemplatesDir . ' is not a valid directory.' );
		}

		$this->blocks = [];
		$this->blockRemovals = [];
		$this->blockReplacements = [];
		$this->includeRegEx = '#<!--\s+include\s+(\S+)\s+-->#im';
		$this->includeTemplatesDir = $pIncludeTemplatesDir;
		$functionNameChars = '[_a-zA-Z][A-Za-z_0-9]*';
		$this->functionRegEx = \sprintf(
			'@func_(%s)\s*\(@sm',
			$functionNameChars
		);
		$this->placeholderRegEx = \sprintf(
			'@{([0-9A-Za-z._-]+)(:(%s))?}@sm',
			$functionNameChars
		);
		$this->placeholders = [];
		$this->template = $pTemplate;

		$this->parser = ( $pParser === NULL ) ? new Parser() : $pParser;
	}

	/**
	 * Get block names parsed.
	 *
	 * @return mixed
	 */
	public function getBlocks()
	{
		return $this->blocks;
	}

	/**
	 * Parse the template, converting various parts to PHP.
	 *
	 * @return string
	 */
	public function process()
	{
		$parsed = $this->template;

		$parsed = $this->compile( $parsed );

		return $parsed;
	}

	/**
	 * Set blocks to remove from the template.
	 *
	 * By default the array passed in is merged with any previous removals. Setting to FALSE will overwrite any
	 * previous removals. Setting to FALSE then passing an empty array will clear all removals.
	 *
	 * @param array $pBlockRemovals
	 * @return $this
	 */
	public function setRemoveBlocks( array $pBlockRemovals, $pMerge = TRUE )
	{
		$this->parser->setRemoveBlocks( $pBlockRemovals, $pMerge );

		return $this;
	}

	/**
	 * Set new content to replace existing block content.
	 *
	 * Each key should be a block name, to replace, it value/content.
	 *
	 * TODO: Make sure each content element gets parsed at compile time, otherwise there will be template elements
	 * in the final output.
	 *
	 * @param array $pReplacements An array of template strings.
	 * @param bool $pMerge Merge in array, or override current values.
	 * @return $this
	 */
	public function setBlockReplacements( array $pReplacements = NULL, $pMerge = TRUE )
	{
		$this->blockReplacements = $pMerge ? \array_merge( $this->blockReplacements, $pReplacements ) : $pReplacements;

		return $this;
	}

	/**
	 * @param string $pTemplate Convert template tags into PHP.
	 *
	 * Compile can all take an array, treating each element as a template.
	 *
	 * @param string|array $pTemplate
	 * @return string
	 */
	private function compile( $pTemplate )
	{
		$parsed = $pTemplate;

		if ( static::$debug )
		{
			print "\n${parsed}\n";
		}

		// 1. Replace all INCLUDE tags first, then process the whole template.
		$parsed = $this->setIncludes( $parsed );

		// 2. Must happen after include, so we can perform in-template replacements request.
		$parsed = $this->setReplaceBlocks( $parsed );

		// 2. Parse functions.
		$parsed = $this->setFunctions( $parsed );

		// TODO: Take into consideration blocks that were added and removed.
		// 3. Replace all block tags. At this point all adding, removing, replacing blocks should have been done.
		$parsed = $this->setBlocks( $parsed, $this->blocks );

		// 4. Convert all placeholders to variables.
		$parsed = $this->setPlaceholders( $parsed );

		return $parsed;
	}

	/**
	 * Replace INCLUDE tag with content from the file path it provides.
	 *
	 * @param $pMatch
	 * @return string
	 * @throws \Kshabazz\SigmaRemix\ParserException
	 */
	private function replaceInclude( $pMatch )
	{
		static $recursionCount = 1;
		$content = '';
		$includeFile = $this->includeTemplatesDir . DIRECTORY_SEPARATOR . $pMatch[ 1 ];

		// Prevent infinite loop via recursion.
		if ( $recursionCount >= self::$recursionLimit )
		{
			if ( static::isStrict() )
			{
				$recursionCount = 1;
				throw new ParserException( ParserException::RECURSION, ['INCLUDE', __FUNCTION__] );
			}

			return $content;
		}

		if ( !\file_exists($includeFile) )
		{
			if ( static::isStrict() )
			{
				throw new ParserException( ParserException::BAD_INCLUDE, [$includeFile] );
			}

			return $content;
		}

		$content = \file_get_contents( $includeFile );

		// Increment recursion
		$recursionCount++;

		// Recursively parse include tags.
		$content = \preg_replace_callback(
			$this->includeRegEx,
			[ $this, 'replaceInclude' ],
			$content
		);

		// Decrement since we have returned.
		$recursionCount--;


		return $content;
	}

	/**
	 * Perform placeholder substitution.
	 *
	 * @param array $pMatch
	 * @return string
	 */
	private function replacePlaceholder( array $pMatch )
	{
		$placeholder = $pMatch[1];

		// Build a list of all placeholders found.
		$this->placeholders[] = $placeholder;

		return '<?= $' . $placeholder . '; ?>';
	}

	/**
	 * Replace blocks in a template with the PHP counter part.
	 *
	 * A block MUST have a begin and end tag, or it will be ignored.
	 * <code>
	 * <!-- BEGIN MY_BLOCK -->
	 *   Place content here
	 * <!-- END MY_BLOCK -->
	 * </code>
	 *
	 * @param string|array $pTemplate Template to parse.
	 * @param array $pBlocks Return all parsed block names as a reference array.
	 * @return string
	 */
	private function setBlocks( $pTemplate, array & $pBlocks )
	{
		$this->parser->setBlocksReplacement( $this->blockReplacements );
		$parsedTemplate = $this->parser->block( $pTemplate );
		$pBlocks = $this->parser->getBlocks();

		return $parsedTemplate;
	}

	/**
	 * Get function calls withing template.
	 *
	 * @param string|array $pTemplate
	 * @return string
	 */
	private function setFunctions( $pTemplate )
	{
		// TODO: Implement parsing functions.

		return $pTemplate;
	}

	/**
	 * Replace all includes with corresponding PHP.
	 *
	 * @param string|array $pTemplate Template to parse.
	 * @return string
	 */
	private function setIncludes( $pTemplate )
	{
		return \preg_replace_callback(
				$this->includeRegEx,
				[ $this, 'replaceInclude' ],
				$pTemplate
		);
	}

	/**
	 * Get placeholder in a template.
	 *
	 * @param string|array $pTemplate
	 * @return string
	 */
	private function setPlaceholders( $pTemplate )
	{
		return \preg_replace_callback(
			$this->placeholderRegEx,
			[$this, 'replacePlaceholder'],
			$pTemplate
		);
	}

	/**
	 * Get placeholder in a template.
	 *
	 * @param string|array $pTemplate
	 * @return string
	 */
	private function setReplaceBlocks( $pTemplate )
	{
		if ( static::$debug )
		{
			print $pTemplate;
		}

		return $this->parser->replaceTag( $pTemplate, $this->blockReplacements );
	}
}
?>