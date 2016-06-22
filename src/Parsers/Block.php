<?php namespace Kshabazz\SigmaRemix\Parsers;


use Kshabazz\SigmaRemix\ParserException;

class Block
{
	use ParserTraits;

	private
		/** @var array A list of block found and replaced within a template. */
		$blocks,
		/** @var string Regular expression to parse blocks. */
		$filter,
		/** @var array Blocks to remove from the template. */
		$removals,
		/** @var array Blocks to replace with new content. */
		$replacements;

	/**
	 * Parser constructor.
	 */
	public function __construct()
	{
		$this->blocks = [];
		$this->filter =
			'@<!--\s+\[((?!\/|\]).+)\]\s+-->'
			. '(.*)?'
			. '<!--\s+\[\/\1\]\s+-->@sm';
		$this->removals = [];
		$this->replacements = [];
	}

	/**
	 * Replace blocks in a template with the PHP counter part.
	 *
	 * A block MUST have a begin and end tag, or it will be skipped.
	 * WARNING: Having block tags that are opened but not closed, and visa vera, can cause strange side effects.
	 * <code>
	 * <!-- [my_block] -->
	 *   Place content here.
	 * <!-- [/my_block] -->
	 * </code>
	 *
	 * @param string|array $pTemplate A string containing block syntax to parse.
	 * @return mixed
	 */
	public function parse( $pTemplate )
	{
		// Reset to remove state data.
		$this->blocks = [];

		// Perform the actual block replacements.
		$parsed = \preg_replace_callback(
			$this->filter,
			[$this, 'replace'],
			$pTemplate
		);

		return $parsed;
	}

	/**
	 * Set blocks to replace with other content.
	 *
	 * @param $pReplacements
	 * @return $this
	 */
	public function setReplacements( $pReplacements )
	{
		$this->replacements = $pReplacements;

		return $this;
	}

	/**
	 * Perform block PHP substitution.
	 *
	 * @param array $pMatch
	 * @return string
	 * @throws ParserException
	 */
	private function replace( array $pMatch )
	{
		if ( static::$debug )
		{
			print_r( $pMatch );
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
			$this->filter,
			[$this, __FUNCTION__],
			$pMatch[ 2 ]
		);

		// Decrement since we have returned.
		$recursionCount--;

		if ( static::$debug )
		{
			print \PHP_EOL . 'REPLACEMENTS:' . \PHP_EOL;
			print_r( $this->replacements );
		}

		// Replace a blocks content on demand.
		if ( \array_key_exists($block, $this->replacements) )
		{
			$blockContent = $this->replacements[ $block ];
		}

		// Removed a block on demand.
		if ( \in_array($block, $this->removals) )
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
}
?>