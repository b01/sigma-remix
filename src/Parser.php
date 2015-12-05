<?php namespace Kshabazz\SigmaRemix;

/**
 * Class Parser
 *
 * @package Kshabazz\SigmaRemix
 */
class Parser
{
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
		$placeholderRegEx,
		/** @var string A regular expression to parse REPLACE tags. */
		$replaceBlockRegEx;

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
	 *
	 * @param string $pTemplate Template to be parsed.
	 * @param string $pIncludeTemplatesDir This path will be prefixed to the path in any INCLUDE tag during parsing.
	 */
	public function __construct( $pTemplate, $pIncludeTemplatesDir = NULL )
	{
		if ( !\is_null($pIncludeTemplatesDir) && !is_dir($pIncludeTemplatesDir) )
		{
			// TODO: Implement ParserException, and store this message there, or refactor this.
			throw new \InvalidArgumentException( $pIncludeTemplatesDir . ' is not a valid directory.' );
		}

		$this->blocks = [];
		// TODO: Change to match replaceBlockRegEx with BLOCK .* /BLOCK
		$this->blockRegExp = '@<!--\s+BEGIN\s+([0-9A-Za-z_-]+)\s+-->'
			. '(.*)'
			. '<!--\s+END\s+\1\s+-->@sm';
		$this->blockRemovals = [];
		$this->blockReplacements = [];
		$this->includeRegEx = '#<!--\s+INCLUDE\s+(\S+)\s+-->#im';
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
		$this->replaceBlockRegEx = '@<!--\s+REPLACE\s+([0-9A-Za-z_-]+)\s+-->'
			. '(.*)'
			. '<!--\s+/REPLACE\s+-->@sm';
		$this->template = $pTemplate;
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
	 */
	public function removeBlocks( array $pBlockRemovals, $pMerge = TRUE )
	{
		$this->blockRemovals = $pMerge ? \array_merge( $pBlockRemovals ) : $pBlockRemovals;

		return $this;
	}

	/**
	 * Set new content to replace existing block content.
	 *
	 * @param array $pReplacements
	 */
	public function setBlockReplacements( array $pReplacements = NULL )
	{
		if ( \is_array($pReplacements) )
		{
			// Compile each element as a template.
			$this->blockReplacements = $this->compile( $pReplacements );
		}

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

		// 1. Replace all INCLUDE tags first, then process the whole template.
		$parsed = $this->setIncludes( $parsed );

		// 2. Must happen after include, so we can perform in-template replacements request.
		$parsed = $this->setReplaceBlocks( $parsed );

		// 2. Parse functions.
		$parsed = $this->setFunctions( $parsed );

		// TODO: Take into consideration blocks that were added and removed.
		// 3. Replace all block tags. At this point all adding, removing, replacing blocks should have been done.
		$parsed = $this->setBlocks( $parsed );

		// 4. Convert all placeholders to variables.
		$parsed = $this->setPlaceholders( $parsed );

		return $parsed;
	}

	/**
	 * Perform block PHP substitution.
	 *
	 * @param array $pMatches
	 * @return string
	 */
	private function replaceBlock( array $pMatches )
	{
		$block = $pMatches[1];

		// Recursively parse nested blocks.
		$blockContent = \preg_replace_callback(
			$this->blockRegExp,
			[ $this, 'replaceBlock' ],
			$pMatches[2]
		);

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

		// Build a list of all blocks found.
		$this->blocks[] = $block;

		return "<?php foreach (\${$block}_ary as \${$block}_vars):\n"
				. "\textract(\${$block}_vars); ?>"
				. "{$blockContent}"
				. "<?php endforeach; // END {$block} ?>";
	}

	/**
	 * Replace INCLUDE tag with content from the file path it provides.
	 *
	 * @param $matches
	 * @return string
	 */
	private function replaceInclude( $matches )
	{
		$content = '';
		$includeFile = $this->includeTemplatesDir . DIRECTORY_SEPARATOR . $matches[ 1 ];

		if ( \file_exists( $includeFile ) )
		{
			$content = \file_get_contents( $includeFile );
		}
		else if ( static::isStrict() )
		{
			throw new ParserException( ParserException::BAD_INCLUDE, [$includeFile] );
		}

		return $content;
	}

	/**
	 * Perform placeholder substitution.
	 *
	 * @param array $match
	 * @return string
	 */
	private function replacePlaceholder( array $match )
	{
		$placeholder = $match[1];

		// Build a list of all placeholders found.
		$this->placeholders[] = $placeholder;

		return '<?= $' . $placeholder . '; ?>';
	}

	/**
	 * Perform REPLACE tag substitution.
	 *
	 * @param array $pMatches
	 * @return string
	 */
	private function replaceReplaceTag( array $pMatches )
	{
		$block = $pMatches[1];

		// TODO: Limit the amount of recursion.
		// Recursively parse nested blocks.
		$blockContent = \preg_replace_callback(
			$this->replaceBlockRegEx,
			[ $this, 'replaceReplaceTag' ],
			$pMatches[2]
		);

		$this->blockReplacements[ $block ] = $blockContent;

		// REPLACE blocks are removed from the compiled template.
		return '';
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
	 * @return string
	 */
	private function setBlocks( $pTemplate )
	{
		$output = \preg_replace_callback( $this->blockRegExp, [$this, 'replaceBlock'], $pTemplate );

		return $output;
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
		return \preg_replace_callback(
			$this->replaceBlockRegEx,
			[$this, 'replaceReplaceTag'],
			$pTemplate
		);
	}
}
?>