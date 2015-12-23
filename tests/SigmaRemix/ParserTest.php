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
		$this->templateDir = FIXTURES_DIR;
		Parser::setStrict( FALSE );
		Parser::setDebug( FALSE );
	}

	/**
	 * @covers ::__construct
	 */
	public function testInitializingAParserObject()
	{
		$parser = new Parser( '{TEST_1}', NULL );

		$this->assertInstanceOf( '\\Kshabazz\\SigmaRemix\\Parser' , $parser );
	}

	/**
	 * @covers ::__construct
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage fake-dir is not a valid directory.
	 */
	public function testShouldThrowAnExceptionWhenTheSecondParameterIsAnInvalidDirectory()
	{
		( new Parser('{TEST_1}', 'fake-dir') );
	}

	/**
	 * @covers ::setPlaceholders
	 * @covers ::replacePlaceholder
	 * @uses \Kshabazz\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\SigmaRemix\Parser::process
	 * @uses \Kshabazz\SigmaRemix\Parser::compile
	 * @uses \Kshabazz\SigmaRemix\Parser::setIncludes
	 * @uses \Kshabazz\SigmaRemix\Parser::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Parser::setBlocks
	 * @uses \Kshabazz\SigmaRemix\Parser::setReplaceBlocks
	 */
	public function testCompileASinglePlaceholderToAPhpEchoStatement()
	{
		$parser = new Parser('{TEST_1}', NULL);
		$data = $parser->process();

		$this->assertEquals('<?= $TEST_1; ?>', $data );
		$this->assertNotContains('{TEST_1}', $data );
	}

	/**
	 * @covers ::process
	 * @covers ::compile
	 * @covers ::setBlocks
	 * @covers ::replaceBlock
	 * @uses \Kshabazz\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\SigmaRemix\Parser::setIncludes
	 * @uses \Kshabazz\SigmaRemix\Parser::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Parser::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Parser::setReplaceBlocks
	 */
	public function testCompileASingleBlockHavingOnlyTextToForeachStatement()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'block-1.html'
		);

		$parser = new Parser( $template, NULL );

		$data = $parser->process();

		$expected = "foreach (\$BLOCK_1_ary as \$BLOCK_1_vars):\n"
			. "\textract(\$BLOCK_1_vars); ?>";
		$this->assertContains( $expected, $data );
		$this->assertContains( 'endforeach; // END BLOCK_1', $data );
	}

	/**
	 * @covers ::process
	 * @covers ::compile
	 * @covers ::setBlocks
	 * @covers ::replaceBlock
	 * @uses \Kshabazz\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\SigmaRemix\Parser::setIncludes
	 * @uses \Kshabazz\SigmaRemix\Parser::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Parser::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Parser::setReplaceBlocks
	 */
	public function testCompile2ConsecutiveBlocksToForeachStatements()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'blocks-2.html'
		);

		$parser = new Parser( $template, NULL );

		$data = $parser->process();

		$expected = "foreach (\$BLOCK_1_ary as \$BLOCK_1_vars):\n"
				. "\textract(\$BLOCK_1_vars); ?>";
		$this->assertContains( $expected, $data );
		$this->assertContains( 'endforeach; // END BLOCK_1', $data );

		$expected = "foreach (\$BLOCK_2_ary as \$BLOCK_2_vars):\n"
				. "\textract(\$BLOCK_2_vars); ?>";
		$this->assertContains( $expected, $data );
		$this->assertContains( 'endforeach; // END BLOCK_1', $data );
	}

	/**
	 * @covers ::process
	 * @covers ::compile
	 * @covers ::setBlocks
	 * @covers ::replaceBlock
	 * @uses \Kshabazz\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\SigmaRemix\Parser::setIncludes
	 * @uses \Kshabazz\SigmaRemix\Parser::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Parser::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Parser::setReplaceBlocks
	 */
	public function testCompileNestedBlocksToNestedForeachStatements()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'nested-blocks.html'
		);

		$parser = new Parser( $template, NULL );

		$data = $parser->process();

		$expected = "foreach (\$BLOCK_1_ary as \$BLOCK_1_vars):\n"
				. "\textract(\$BLOCK_1_vars); ?>";
		$this->assertContains( $expected, $data );
		$this->assertContains( 'endforeach; // END BLOCK_1', $data );

		$expected = "foreach (\$BLOCK_2_ary as \$BLOCK_2_vars):\n"
				. "\textract(\$BLOCK_2_vars); ?>";
		$this->assertContains( $expected, $data );
		$this->assertContains( 'endforeach; // END BLOCK_1', $data );

		$expected = "foreach (\$NESTED_BLOCK_1_ary as \$NESTED_BLOCK_1_vars):\n"
				. "\textract(\$NESTED_BLOCK_1_vars); ?>";
		$this->assertContains( $expected, $data );
		$this->assertContains( 'endforeach; // END NESTED_BLOCK_1', $data );
	}

	/**
	 * @covers ::setIncludes
	 * @covers ::replaceInclude
	 * @covers ::process
	 * @covers ::compile
	 * @uses \Kshabazz\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\SigmaRemix\Parser::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Parser::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Parser::setBlocks
	 * @uses \Kshabazz\SigmaRemix\Parser::replacePlaceholder
	 * @uses \Kshabazz\SigmaRemix\Parser::setReplaceBlocks
	 */
	public function test_should_parse_an_include_tag()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'include-placeholders-1.html'
		);

		$parser = new Parser( $template, $this->templateDir );

		$data = $parser->process();

		$this->assertContains( 'Including placeholders-1.html was', $data );
		$this->assertContains( '$TEST_1', $data );
	}

	/**
	 * @covers ::setBlockReplacements
	 * @covers ::setBlocks
	 * @covers ::replaceBlock
	 * @uses \Kshabazz\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\SigmaRemix\Parser::process
	 * @uses \Kshabazz\SigmaRemix\Parser::compile
	 * @uses \Kshabazz\SigmaRemix\Parser::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Parser::replaceBlock
	 * @uses \Kshabazz\SigmaRemix\Parser::setIncludes
	 * @uses \Kshabazz\SigmaRemix\Parser::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Parser::setReplaceBlocks
	 */
	public function test_should_replace_a_block()
	{
		$template = \file_get_contents(
			$this->templateDir . \DIRECTORY_SEPARATOR . 'block-1.html'
		);

		$parser = new Parser( $template );

		$parser->setBlockReplacements([ 'BLOCK_1' => 'Replacement content' ]);

		$actual = $parser->process();

		$this->assertContains( 'Replacement content', $actual );
	}

	/**
	 * @covers ::removeBlocks
	 * @covers ::setBlocks
	 * @covers ::replaceBlock
	 * @uses \Kshabazz\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\SigmaRemix\Parser::process
	 * @uses \Kshabazz\SigmaRemix\Parser::compile
	 * @uses \Kshabazz\SigmaRemix\Parser::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Parser::setIncludes
	 * @uses \Kshabazz\SigmaRemix\Parser::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Parser::setReplaceBlocks
	 */
	public function test_should_remove_a_block()
	{
		$template = \file_get_contents(
			$this->templateDir . \DIRECTORY_SEPARATOR . 'blocks-2.html'
		);

		$parser = new Parser( $template );

		$parser->removeBlocks([ 'BLOCK_1' ]);

		$actual = $parser->process();

		$this->assertNotContains( 'Block 1 content.', $actual );
	}

	/**
	 * @covers ::getBlocks
	 * @uses \Kshabazz\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\SigmaRemix\Parser::process
	 * @uses \Kshabazz\SigmaRemix\Parser::compile
	 * @uses \Kshabazz\SigmaRemix\Parser::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Parser::setIncludes
	 * @uses \Kshabazz\SigmaRemix\Parser::replaceBlock
	 * @uses \Kshabazz\SigmaRemix\Parser::setBlocks
	 * @uses \Kshabazz\SigmaRemix\Parser::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Parser::setReplaceBlocks
	 */
	public function test_should_get_a_list_of_blocks_in_a_template()
	{
		$template = \file_get_contents(
			$this->templateDir . \DIRECTORY_SEPARATOR . 'blocks-2.html'
		);

		$parser = new Parser( $template );

		$parser->process();

		$actual = $parser->getBlocks();

		$this->assertContains( 'BLOCK_1', $actual );
	}

	/**
	 * @expectedException \Kshabazz\SigmaRemix\ParserException
	 * @expectedExceptionMessage
	 * @expectedExceptionCode 1
	 * @covers ::setStrict
	 * @covers ::isStrict
	 * @uses \Kshabazz\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\SigmaRemix\Parser::process
	 * @uses \Kshabazz\SigmaRemix\Parser::compile
	 * @uses \Kshabazz\SigmaRemix\Parser::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Parser::replaceInclude
	 * @uses \Kshabazz\SigmaRemix\Parser::setIncludes
	 * @uses \Kshabazz\SigmaRemix\SigmaRemixException
	 * @uses \Kshabazz\SigmaRemix\ParserException
	 */
	public function test_should_error_when_cannot_include_file_from_include_tag()
	{
		$template = \file_get_contents(
			$this->templateDir . \DIRECTORY_SEPARATOR . 'replace-block-1.html'
		);

		// Turn on parser strict mode.
		Parser::setStrict( TRUE );

		$parser = new Parser( $template );

		$parser->process();

		// Turn off parser strict mode.
		Parser::setStrict( FALSE );
	}

	/**
	 * @covers ::setReplaceBlocks
	 * @covers ::replaceReplaceTag
	 * @uses \Kshabazz\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\SigmaRemix\Parser::process
	 * @uses \Kshabazz\SigmaRemix\Parser::compile
	 * @uses \Kshabazz\SigmaRemix\Parser::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Parser::replaceInclude
	 * @uses \Kshabazz\SigmaRemix\Parser::setIncludes
	 * @uses \Kshabazz\SigmaRemix\Parser::replaceBlock
	 * @uses \Kshabazz\SigmaRemix\Parser::setBlocks
	 * @uses \Kshabazz\SigmaRemix\Parser::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Parser::setStrict
	 */
	public function test_should_replace_one_block_with_another()
	{
		$template = \file_get_contents(
			$this->templateDir . \DIRECTORY_SEPARATOR . 'replace-block-1.html'
		);

		// Turn on Parser strict mode.
		Parser::setStrict( TRUE );

		$parser = new Parser( $template, $this->templateDir . \DIRECTORY_SEPARATOR );

		$actual = $parser->process();

		$this->assertContains( 'Content was replaced.', $actual );
		$this->assertNotContains( 'Block 1 content.', $actual );

		// Turn off Parser strict mode.
		Parser::setStrict( FALSE );
	}

	/**
	 * @covers ::setIncludes
	 * @covers ::replaceInclude
	 * @covers ::process
	 * @covers ::compile
	 * @uses \Kshabazz\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\SigmaRemix\Parser::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Parser::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Parser::setBlocks
	 * @uses \Kshabazz\SigmaRemix\Parser::replacePlaceholder
	 * @uses \Kshabazz\SigmaRemix\Parser::setReplaceBlocks
	 */
	public function test_should_parse_an_include_tags_recursively()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'include-recursively-once.html'
		);

		$parser = new Parser( $template, $this->templateDir );

		$data = $parser->process();

		$this->assertContains( 'Including placeholders-1.html was', $data );
		$this->assertContains( '$TEST_1', $data );
	}

	/**
	 * @expectedException \Kshabazz\SigmaRemix\ParserException
	 * @expectedExceptionMessage Maximum number of recursive/nested INCLUDE tags has been reached (function
	 * replaceInclude)
	 * @expectedExceptionCode 2
	 * @covers ::setIncludes
	 * @covers ::replaceInclude
	 * @covers ::process
	 * @covers ::compile
	 * @uses \Kshabazz\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\SigmaRemix\Parser::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Parser::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Parser::setBlocks
	 * @uses \Kshabazz\SigmaRemix\Parser::replacePlaceholder
	 * @uses \Kshabazz\SigmaRemix\Parser::setReplaceBlocks
	 * @uses \Kshabazz\SigmaRemix\Parser::setStrict
	 * @uses \Kshabazz\SigmaRemix\Parser::isStrict
	 * @uses \Kshabazz\SigmaRemix\SigmaRemixException
	 */
	public function test_should_parse_an_include_tags_infinite_loop()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'include-recursively-infinite-loop.html'
		);

		Parser::setStrict( TRUE );

		$parser = new Parser( $template, $this->templateDir );

		$parser->process();

		Parser::setStrict( FALSE );
	}

	/**
	 * @covers ::setIncludes
	 * @covers ::replaceInclude
	 * @covers ::process
	 * @covers ::compile
	 * @uses \Kshabazz\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\SigmaRemix\Parser::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Parser::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Parser::setBlocks
	 * @uses \Kshabazz\SigmaRemix\Parser::replacePlaceholder
	 * @uses \Kshabazz\SigmaRemix\Parser::setReplaceBlocks
	 * @uses \Kshabazz\SigmaRemix\Parser::setStrict
	 * @uses \Kshabazz\SigmaRemix\Parser::isStrict
	 * @uses \Kshabazz\SigmaRemix\SigmaRemixException
	 */
	public function testCompileAnIncludeTagThatLoopsLoopsInfinitelyButStopsAtTheSetRecursionLimit()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'include-recursively-infinite-loop-2.html'
		);

		Parser::setStrict( FALSE );

		$parser = new Parser( $template, $this->templateDir );

		$actual = $parser->process();

		$this->assertEquals( 10, \substr_count($actual, 'INCLUDE TEST') );
	}

	/**
	 * @covers ::setIncludes
	 * @covers ::replaceInclude
	 * @covers ::process
	 * @covers ::compile
	 * @uses \Kshabazz\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\SigmaRemix\Parser::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Parser::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Parser::setBlocks
	 * @uses \Kshabazz\SigmaRemix\Parser::replacePlaceholder
	 * @uses \Kshabazz\SigmaRemix\Parser::setReplaceBlocks
	 * @uses \Kshabazz\SigmaRemix\Parser::setStrict
	 * @uses \Kshabazz\SigmaRemix\Parser::isStrict
	 * @uses \Kshabazz\SigmaRemix\SigmaRemixException
	 */
	public function test_calling_replace_on_two_blocks_in_one_template()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'layout-1-user.html'
		);

		Parser::setStrict( TRUE );

		$parser = new Parser( $template, $this->templateDir );

		$actual = $parser->process();

		$this->assertContains( 'Replace block 1', $actual );
		$this->assertContains( 'Replace block 2', $actual );
		$this->assertNotContains( 'INCLUDE', $actual );
		$this->assertNotContains( 'REPLACE', $actual );

		Parser::setStrict( FALSE );
	}
}
?>