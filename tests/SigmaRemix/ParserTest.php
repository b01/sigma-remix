<?php namespace Kshabazz\SigmaRemix\Tests;

use Kshabazz\SigmaRemix\Parser;

/**
 * Class ParserTest
 *
 * @package \Kshabazz\SigmaRemix\Tests
 * @coversDefaultClass \Kshabazz\SigmaRemix\Parser
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
	private $templateDir;

	public function setUp()
	{
		$this->templateDir = FIXTURES_DIR . DIRECTORY_SEPARATOR;
	}

	/**
	 * @covers ::__construct
	 */
	public function testInitialization()
	{
		$this->assertInstanceOf( '\\Kshabazz\\SigmaRemix\\Parser', new Parser() );
	}

	/**
	 * @covers ::replaceTag
	 * @uses \Kshabazz\SigmaRemix\Parser::__construct
	 */
	public function test2ReplaceTagsShouldReplaceCorrespondingBlocks()
	{
		$template = \file_get_contents(
			$this->templateDir . 'layout-1-user.html'
		);

		$parser = new Parser( $template );

		$replacements = [];
		$actual = $parser->replaceTag( $template, $replacements );

		$this->assertEquals( '<!-- include layout-1.html -->', \trim($actual) );
	}

	/**
	 * @covers ::block
	 * @covers ::replaceBlock
	 * @uses \Kshabazz\SigmaRemix\Parser::__construct
	 */
	public function testParseASingleBlockHavingOnlyTextToForeachStatement()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'block-1.html'
		);

		$parser = new Parser();
		$data = $parser->block( $template );

		$expected = "foreach (\$BLOCK_1_ary as \$BLOCK_1_vars):\n"
			. "\textract(\$BLOCK_1_vars); ?>";
		$this->assertContains( $expected, $data );
		$this->assertContains( 'endforeach; // END BLOCK_1', $data );
	}

	/**
	 * @covers ::block
	 * @covers ::replaceBlock
	 * @uses \Kshabazz\SigmaRemix\Parser::__construct
	 */
	public function testParse2ConsecutiveBlocksToForeachStatements()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'blocks-2.html'
		);

		$parser = new Parser();

		$data = $parser->block( $template );

		$expected = "foreach (\$BLOCK_1_ary as \$BLOCK_1_vars):\n"
			. "\textract(\$BLOCK_1_vars); ?>";
		$this->assertContains( $expected, $data );
		$this->assertContains( 'endforeach; // END BLOCK_1', $data );

		$expected = "foreach (\$BLOCK_2_ary as \$BLOCK_2_vars):\n"
			. "\textract(\$BLOCK_2_vars); ?>";
		$this->assertContains( $expected, $data );
		$this->assertContains( 'endforeach; // END BLOCK_2', $data );
	}

	/**
	 * @covers ::block
	 * @covers ::replaceBlock
	 * @covers ::getBlocks
	 * @uses \Kshabazz\SigmaRemix\Parser::__construct
	 */
	public function testCompileNestedBlocksToNestedForeachStatements()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'nested-blocks.html'
		);

		$parser = new Parser();

		$data = $parser->block( $template );

		$blocks = $parser->getBlocks();

		$this->assertEquals( 'NESTED_BLOCK_1', $blocks[0] );
		$this->assertEquals( 'BLOCK_1', $blocks[1] );
		$this->assertEquals( 'BLOCK_2', $blocks[2] );

		$expected = "foreach (\$BLOCK_1_ary as \$BLOCK_1_vars):\n"
			. "\textract(\$BLOCK_1_vars); ?>";
		$this->assertContains( $expected, $data );
		$this->assertContains( 'endforeach; // END BLOCK_1', $data );

		$expected = "foreach (\$BLOCK_2_ary as \$BLOCK_2_vars):\n"
			. "\textract(\$BLOCK_2_vars); ?>";
		$this->assertContains( $expected, $data );
		$this->assertContains( 'endforeach; // END BLOCK_2', $data );

		$expected = "foreach (\$NESTED_BLOCK_1_ary as \$NESTED_BLOCK_1_vars):\n"
			. "\textract(\$NESTED_BLOCK_1_vars); ?>";
		$this->assertContains( $expected, $data );
		$this->assertContains( 'endforeach; // END NESTED_BLOCK_1', $data );
	}
}
?>