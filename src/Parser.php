<?php namespace Kshabazz\SigmaRemix;

/**
 * Class Parser Provides functions to parsing template tags.
 *
 * @package Kshabazz\SigmaRemix
 */
class Parser
{
	/**
	 * Switch debug messages on/off.
	 *
	 * @var bool
	 */
	static protected $debug = FALSE;

	/**
	 * Limit the number of recursive calls a function can make.
	 *
	 * This is reset once the limit is hit and the function returns. Strict mode will cause an error to be throw when
	 * the limit is reached.
	 *
	 * @var int
	 * @see ::replaceBlock, ::replaceInclude, ::replaceReplaceTag
	 * TODO: Implement and add static functions to set.
	 */
	static private $recursionLimit = 10;

	/** @var bool Throw errors for simple mistakes that are not fatal. */
	static private $strictMode = FALSE;

	private
		/** @var array A list of block found and replaced within a template. */
		$blocks,
		/** @var string Regular expression to parse blocks. */
		$blockRegExp,
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
		$placeholderRegEx,
		/** @var string A regular expression to parse REPLACE tags. */
		$replaceBlockRegEx;

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
	 * Parser constructor.
	 */
	public function __construct()
	{
		$this->blocks = [];
		$this->blockRegExp = '@<!--\s+\[((?!\/|\]).+)\]\s+-->'
			. '(.*)?'
			. '<!--\s+\[\/\1\]\s+-->@sm';
		$this->blockRemovals = [];
		$this->blockReplacements = [];
		$this->includeRegEx = '#<!--\s+INCLUDE\s+(\S+)\s+-->#im';
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
		$this->replaceBlockRegEx = '@<\!\-\-\s+replace' // begin replace tag, being overly cautious by escaping dash chars.
			. '\s+([^\s]+)' // capture the block name
			. '\s+\-\->' // close the begin tag, being overly cautious by escaping dash chars.
			. '(.*)' // capture content withing tag
			. '<\!\-\- /replace \-\->' // end the tag.
			. '@smU';
	}

	/**
	 * Replace blocks in a template with the PHP counter part.
	 *
	 * A block MUST have a begin and end tag, or it will be skipped.
	 * WARNING: Having block tags that are opened but not closed, and visa vera, can cause sideeffect.
	 * <code>
	 * <!-- [a_block] -->
	 *   Place content here.
	 * <!-- [/a_block] -->
	 * </code>
	 *
	 * @param string|array $pTemplate A string containing block syntax to parse.
	 * @return mixed
	 */
	public function block( $pTemplate )
	{
		$this->blocks = [];

		return \preg_replace_callback(
			$this->blockRegExp,
			[ $this, 'replaceBlock' ],
			$pTemplate
		);
	}

	/**
	 * Get names of blocks found when parsing.
	 *
	 * @return array
	 */
	public function getBlocks()
	{
		return $this->blocks;
	}

	/**
	 * Set blocks to remove from the template.
	 *
	 * By default the array passed in is merged with any previous removals. Setting to FALSE will overwrite any
	 * previous removals. Setting to FALSE then passing an empty array will clear all removals.
	 *
	 * @param array $pBlockRemovals
	 * @param bool $pMerge
	 * @return $this
	 */
	public function setRemoveBlocks( array $pBlockRemovals, $pMerge = TRUE )
	{
		$this->blockRemovals = $pMerge ? \array_merge( $pBlockRemovals ) : $pBlockRemovals;

		return $this;
	}

	/**
	 * Set content to replace existing blocks.
	 *
	 * @param array $pReplacements
	 * @param bool $pMerge Set the current array or merge in with previous.
	 * @return $this
	 */
	public function setBlocksReplacement( array $pReplacements, $pMerge = TRUE )
	{
		$this->blockReplacements = $pMerge ? \array_merge( $this->blockReplacements, $pReplacements ) : $pReplacements;

		return $this;
	}

	/**
	 * Perform block PHP substitution.
	 *
	 * @param array $pMatch
	 * @return string
	 * @throws ParserException
	 */
	private function replaceBlock( array $pMatch )
	{
		if ( static::$debug )
		{
			\print_r( $pMatch );
		}

		static $recursionCount = 0;
		$block = $pMatch[ 1 ];

		// Prevent infinite loop via recursion.
		if ( $recursionCount > self::$recursionLimit )
		{
			if ( static::isStrict() )
			{
				$recursionCount = 0;// Reset in cases where the error is caught and execution proceeds.
				throw new ParserException( ParserException::RECURSION, ['BLOCK', __FUNCTION__] );
			}
		}

		// Increment recursion
		$recursionCount++;

		// Recursively parse nested blocks.
		$blockContent = \preg_replace_callback(
			$this->blockRegExp,
			[ $this, __FUNCTION__ ],
			$pMatch[ 2 ]
		);

		// Decrement since we have returned.
		$recursionCount--;

		if ( static::$debug )
		{
			print \PHP_EOL . 'REPLACEMENTS:' . \PHP_EOL;
			print_r( $this->blockReplacements );
		}

		// Replace a blocks content on demand.
		if ( \array_key_exists($block, $this->blockReplacements) )
		{
			$blockContent = $this->blockReplacements[ $block ];
		}

		// Removed a block on demand.
		if ( \in_array($block, $this->blockRemovals) )
		{
			return '';
		}

		// Build a list of all block names found.
		$this->blocks[] = $block;

		return "<?php foreach (\${$block}_ary as \${$block}_vars):\n"
		. "\textract(\${$block}_vars); ?>"
		. "{$blockContent}"
		. "<?php endforeach; // END {$block} ?>";
	}

	/**
	 * Replace INCLUDE tag with content from the file path it provides.
	 *
	 * @param $pMatch
	 * @return string
	 * @throws ParserException
	 */
	public function replaceInclude( $pMatch )
	{
		static $recursionCount = 0;
		$content = '';
		$includeFile = $this->includeTemplatesDir . DIRECTORY_SEPARATOR . $pMatch[ 1 ];

		// Prevent infinite loop via recursion.
		if ( $recursionCount > self::$recursionLimit )
		{
			if ( static::isStrict() )
			{
				$recursionCount = 0;
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
	public function replacePlaceholder( array $pMatch )
	{
		$placeholder = $pMatch[1];

		// Build a list of all placeholders found.
		$this->placeholders[] = $placeholder;

		return '<?= $' . $placeholder . '; ?>';
	}

	/**
	 * Get function calls withing template.
	 *
	 * @param string|array $pTemplate
	 * @return string
	 */
	public function setFunctions( $pTemplate )
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
	public function setIncludes( $pTemplate )
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
	public function setPlaceholders( $pTemplate )
	{
		return \preg_replace_callback(
			$this->placeholderRegEx,
			[$this, 'replacePlaceholder'],
			$pTemplate
		);
	}

	/**
	 * Parse replace tags in a template.
	 *
	 * @param string $pTemplate
	 * @param array $replacements
	 * @return string
	 */
	public function replaceTag( $pTemplate, array & $replacements )
	{
		$content = \preg_replace_callback(
			$this->replaceBlockRegEx,
			function ( array $pMatches ) use ( & $replacements ) {
				$replacements[ $pMatches[1] ] = $pMatches[ 2 ];

				return '';
			},
			$pTemplate
		);

		return $content;
	}
}