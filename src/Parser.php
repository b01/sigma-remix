<?php namespace Kshabazz\Web\SigmaRemix;

/**
 * Class Parser
 *
 * @package Kshabazz\Web\SigmaRemix
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
		/** @var strig Directory where load include templates */
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

		$this->template = $pTemplate;
		$this->includeTemplatesDir = $pIncludeTemplatesDir;

		$functionNameChars = '[_a-zA-Z][A-Za-z_0-9]*';

		$this->blockRegExp = '@<!--\s+BEGIN\s+([0-9A-Za-z_-]+)\s+-->'
			. '(.*)'
			. '<!--\s+END\s+\1\s+-->@sm';

		$this->functionRegEx = \sprintf(
			'@func_(%s)\s*\(@sm',
			$functionNameChars
		);

		$this->includeRegEx = '#<!--\s+INCLUDE\s+(\S+)\s+-->#im';

		$this->placeholderRegEx = \sprintf(
			'@{([0-9A-Za-z._-]+)(:(%s))?}@sm',
			$functionNameChars
		);
	}

	/**
	 *
	 */
	public function process()
	{
		$parsed = $this->template;

		// 1. Replace all INCLUDE tags first, then process the whole template.
		$parsed = $this->replaceIncludes( $parsed );

		// 2. Convert all placeholders to variables.
		$parsed = $this->setPlaceholders( $parsed );

		// TODO: Implement parsing functions.
		// 3. Parse functions.
		$parsed = $this->setFunctions( $parsed );

		// TODO: Take into consideration blocks that were added, removed, or replaced.

		// 4. Replace all block tags. At this point all adding, removing, replacing blocks should have been done.
		$parsed = $this->setBlocks( $parsed );

		return $parsed;
	}

	/**
	 * Recursively build a list of all blocks within the template.
	 *
	 * @param string $string template to be scanned
	 *
	 * @access private
	 * @return mixed array of block names on success or error object on failure
	 * @throws PEAR_Error
	 * @see    $_blocks
	 */
	function _buildBlocks($string)
	{
		$blocks = array();
		if (preg_match_all($this->blockRegExp, $string, $regs, PREG_SET_ORDER)) {
			foreach ($regs as $match) {
				$blockname    = $match[1];
				$blockcontent = $match[2];
				if (isset($this->_blocks[$blockname]) || isset($blocks[$blockname])) {
					return new \Exception(
						$this->errorMessage(SIGMA_BLOCK_DUPLICATE, $blockname), SIGMA_BLOCK_DUPLICATE
					);
				}
				$this->_blocks[$blockname] = $blockcontent;
				$blocks[$blockname] = true;
				$inner              = $this->_buildBlocks($blockcontent);
				if (is_a($inner, 'PEAR_Error')) {
					return $inner;
				}
				foreach ($inner as $name => $v) {
					$pattern     = sprintf('@<!--\s+BEGIN\s+%s\s+-->(.*)<!--\s+END\s+%s\s+-->@sm', $name, $name);
					$replacement = $this->openingDelimiter.'__'.$name.'__'.$this->closingDelimiter;
					$this->_children[$blockname][$name] = true;
					$this->_blocks[$blockname]          = preg_replace(
						$pattern, $replacement, $this->_blocks[$blockname]
					);
				}
			}
		}
		return $blocks;
	}

	/**
	 * Get function calls withing template.
	 */
	private function setFunctions( $pTemplate )
	{
		// TODO: Implement parsing functions.

		return $pTemplate;
	}

	/**
	 *
	 */
	private function replaceBlock( $pMatches )
	{
		$block = $pMatches[1];
		$this->blocks[ $pMatches[1] ];
		$output = "<?php \${$block}_ary = [ \${$pMatches[1]}_vals ];\n"
				. "foreach (\${$block}_ary as \${$block}_vars):\n"
				. "\textract(\${$block}_vars); ?>"
				. "{$pMatches[2]}"
				. "<?php endforeach; // END {$pMatches[1]} ?>";

		return $output;
	}

	/**
	 * Replace all includes with corresponding PHP.
	 *
	 * @param string $pTemplate Template to parse.
	 * @return string
	 */
	private function replaceIncludes( $pTemplate )
	{
		$output = \preg_replace_callback(
				$this->includeRegEx,
				[ $this, 'replaceInclude' ],
				$pTemplate
		);

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
		preg_match_all( $this->blockRegExp, $pTemplate, $regs, PREG_SET_ORDER );

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
		\preg_match_all( $this->placeholderRegEx, $pTemplate, $matches, \PREG_SET_ORDER );

		$output = \preg_replace( $this->placeholderRegEx, '\$$1', $pTemplate );

		return $output;
	}
}
?>