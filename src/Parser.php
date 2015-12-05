<?php namespace Kshabazz\SigmaRemix;

/**
 * Class Parser
 *
 * @package Kshabazz\SigmaRemix
 */
class Parser
{
	private
		$blocks,
		$functions,
		$functionRegEx,
		$includeRegEx,
		$placeholders,
		$placeholderRegEx,
		/** @var string Directory where load include templates */
		$includeTemplatesDir;

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

		$this->blockRegExp = '@<!--\s+BEGIN\s+([0-9A-Za-z_-]+)\s+-->'
			. '(.*)'
			. '<!--\s+END\s+\1\s+-->@sm';
		$this->blocks = [];
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
		$this->template = $pTemplate;
	}

	/**
	 * Get block names parsed.
	 * @return mixed
	 */
	public function getBlocks()
	{
		return $this->blocks;
	}

	/**
	 *Parse the template, converting various parts to PHP.
	 *
	 * @return string
	 */
	public function process()
	{
		$parsed = $this->template;

		// 1. Replace all INCLUDE tags first, then process the whole template.
		$parsed = $this->setIncludes( $parsed );

		// 2. Convert all placeholders to variables.
		$parsed = $this->setPlaceholders( $parsed );

		// 3. Parse functions.
		$parsed = $this->setFunctions( $parsed );

		// TODO: Take into consideration blocks that were added, removed, or replaced.
		// 4. Replace all block tags. At this point all adding, removing, replacing blocks should have been done.
		$parsed = $this->setBlocks( $parsed );

		return $parsed;
	}

	/**
	 * Get function calls withing template.
	 *
	 * @param string $pTemplate
	 * @return string
	 */
	private function setFunctions( $pTemplate )
	{
		// TODO: Implement parsing functions.

		return $pTemplate;
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

		// Build a list of all blocks found.
		$this->blocks[] = $block;

		$output = "<?php foreach (\${$block}_ary as \${$block}_vars):\n"
				. "\textract(\${$block}_vars); ?>"
				. "{$blockContent}"
				. "<?php endforeach; // END {$block} ?>";

		return $output;
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
	 * Replace all includes with corresponding PHP.
	 *
	 * @param string $pTemplate Template to parse.
	 * @return string
	 */
	private function setIncludes( $pTemplate )
	{
		$output = \preg_replace_callback(
				$this->includeRegEx,
				[ $this, 'replaceInclude' ],
				$pTemplate
		);

		return $output;
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
	 * @param string $pTemplate Template to parse.
	 * @return string
	 */
	private function setBlocks( $pTemplate )
	{
		$output = \preg_replace_callback( $this->blockRegExp, [$this, 'replaceBlock'], $pTemplate );

		return $output;
	}

	/**
	 * Get placeholder in a template.
	 *
	 * @param string $pTemplate
	 * @return string
	 */
	private function setPlaceholders( $pTemplate )
	{
		$output = \preg_replace_callback(
			$this->placeholderRegEx,
			[$this, 'replacePlaceholder'],
			$pTemplate
		);

		return $output;
	}
}
?>