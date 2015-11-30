<?php namespace Kshabazz\Web\SigmaRemix\Tests;

use Kshabazz\Web\SigmaRemix\Parser;

/**
 * Class ParserTest
 *
 * @package \Kshabazz\Web\SigmaRemix\Tests
 * @coversDefaultClass \Kshabazz\Web\SigmaRemix\Parser
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
	private $templateDir;

	public function setUp()
	{
		$this->templateDir = FIXTURES_DIR;
	}

	/**
	 * @covers ::__construct
	 */
	public function test_initialization()
	{
		$parser = new Parser( '{TEST_1}', NULL );

		$this->assertInstanceOf( '\\Kshabazz\\Web\\SigmaRemix\\Parser' , $parser );
	}

	/**
	 * @covers ::__construct
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage fake-dir is not a valid directory.
	 */
	public function test_should_throw_exception_when_second_parameters_is_an_invalid_dir()
	{
		$parser = new Parser( '{TEST_1}', 'fake-dir' );
	}

	/**
	 * @covers ::process
	 * @covers ::setPlaceholders
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::replaceIncludes
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::setFunctions
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::setBlocks
	 */
	public function test_parsing_placeholders()
	{
		$parser = new Parser('{TEST_1}', NULL);
		$data = $parser->process();

		$this->assertEquals('$TEST_1', $data );
		$this->assertNotContains('{TEST_1}', $data );
	}

	/**
	 * @covers ::process
	 * @covers ::setBlocks
	 * @covers ::replaceBlock
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::replaceIncludes
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::setPlaceholders
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::setFunctions
	 */
	public function test_parsing_block_with_just_text()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'block-1.html'
		);

		$parser = new Parser( $template, NULL );

		$data = $parser->process();

		$expected = "\$BLOCK_1_ary = [ \$BLOCK_1_vals ];\n"
			. "foreach (\$BLOCK_1_ary as \$BLOCK_1_vars):\n"
			. "\textract(\$BLOCK_1_vars); ?>";
		$this->assertContains( $expected, $data );
		$this->assertContains( 'endforeach; // END BLOCK_1', $data );
	}

	/**
	 * @covers ::process
	 * @covers ::setBlocks
	 * @covers ::replaceBlock
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::replaceIncludes
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::setPlaceholders
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::setFunctions
	 */
	public function test_should_parse_two_consecutive_blocks()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'blocks-2.html'
		);

		$parser = new Parser( $template, NULL );

		$data = $parser->process();

		$expected = "\$BLOCK_1_ary = [ \$BLOCK_1_vals ];\n"
				. "foreach (\$BLOCK_1_ary as \$BLOCK_1_vars):\n"
				. "\textract(\$BLOCK_1_vars); ?>";
		$this->assertContains( $expected, $data );
		$this->assertContains( 'endforeach; // END BLOCK_1', $data );

		$expected = "\$BLOCK_2_ary = [ \$BLOCK_2_vals ];\n"
				. "foreach (\$BLOCK_2_ary as \$BLOCK_2_vars):\n"
				. "\textract(\$BLOCK_2_vars); ?>";
		$this->assertContains( $expected, $data );
		$this->assertContains( 'endforeach; // END BLOCK_1', $data );
	}

	/**
	 * @covers ::process
	 * @covers ::setBlocks
	 * @covers ::replaceBlock
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::replaceIncludes
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::setPlaceholders
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::setFunctions
	 */
	public function test_should_parse_nested_blocks()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'nested-blocks.html'
		);

		$parser = new Parser( $template, NULL );

		$data = $parser->process();

		$expected = "\$BLOCK_1_ary = [ \$BLOCK_1_vals ];\n"
				. "foreach (\$BLOCK_1_ary as \$BLOCK_1_vars):\n"
				. "\textract(\$BLOCK_1_vars); ?>";
		$this->assertContains( $expected, $data );
		$this->assertContains( 'endforeach; // END BLOCK_1', $data );

		$expected = "\$BLOCK_2_ary = [ \$BLOCK_2_vals ];\n"
				. "foreach (\$BLOCK_2_ary as \$BLOCK_2_vars):\n"
				. "\textract(\$BLOCK_2_vars); ?>";
		$this->assertContains( $expected, $data );
		$this->assertContains( 'endforeach; // END BLOCK_1', $data );

		$expected = "\$NESTED_BLOCK_1_ary = [ \$NESTED_BLOCK_1_vals ];\n"
				. "foreach (\$NESTED_BLOCK_1_ary as \$NESTED_BLOCK_1_vars):\n"
				. "\textract(\$NESTED_BLOCK_1_vars); ?>";
		$this->assertContains( $expected, $data );
		$this->assertContains( 'endforeach; // END NESTED_BLOCK_1', $data );
	}

	/**
	 * @covers ::replaceIncludes
	 * @covers ::replaceInclude
	 * @covers ::process
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::setPlaceholders
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::setFunctions
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::setBlocks
	 */
	public function test_parsing_an_include_tag()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'include-placeholders-1.html'
		);

		$parser = new Parser( $template, $this->templateDir );

		$data = $parser->process();

		$this->assertContains( 'Including placeholders-1.html was', $data );
		$this->assertContains( '$TEST_1', $data );
	}
}
?>