<?php namespace Kshabazz\SigmaRemix\Tests;

use Kshabazz\SigmaRemix\Compiler;
use Kshabazz\SigmaRemix\Parser;

/**
 * Class ParserTest
 *
 * @package \Kshabazz\SigmaRemix\Tests
 * @coversDefaultClass \Kshabazz\SigmaRemix\Compiler
 * @uses \Kshabazz\SigmaRemix\Parser
 */
class CompilerTest extends \PHPUnit_Framework_TestCase
{
	private $templateDir;

	public function setUp()
	{
		$this->templateDir = FIXTURES_DIR;
	}

	/**
	 * @covers ::__construct
	 * @uses \Kshabazz\SigmaRemix\Compiler
	 * @uses \Kshabazz\SigmaRemix\Parser
	 */
	public function testInitializingAParserObject()
	{
		$parser = new Compiler( '{TEST_1}', NULL );

		$this->assertInstanceOf( '\\Kshabazz\\SigmaRemix\\Compiler' , $parser );
	}

	/**
	 * @covers ::__construct
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage fake-dir is not a valid directory.
	 */
	public function testShouldThrowAnExceptionWhenTheSecondParameterIsAnInvalidDirectory()
	{
		( new Compiler('{TEST_1}', 'fake-dir') );
	}

	/**
	 * @covers ::setPlaceholders
	 * @covers ::replacePlaceholder
	 * @uses \Kshabazz\SigmaRemix\Compiler::__construct
	 * @uses \Kshabazz\SigmaRemix\Compiler::process
	 * @uses \Kshabazz\SigmaRemix\Compiler::compile
	 * @uses \Kshabazz\SigmaRemix\Compiler::setIncludes
	 * @uses \Kshabazz\SigmaRemix\Compiler::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Compiler::setBlocks
	 * @uses \Kshabazz\SigmaRemix\Compiler::setReplaceBlocks
	 */
	public function testCompileASinglePlaceholderToAPhpEchoStatement()
	{
		$parser = new Compiler('{TEST_1}', NULL);
		$data = $parser->process();

		$this->assertEquals('<?= $TEST_1; ?>', $data );
		$this->assertNotContains('{TEST_1}', $data );
	}

	/**
	 * @covers ::process
	 * @covers ::compile
	 * @covers ::setBlocks
	 * @uses \Kshabazz\SigmaRemix\Compiler::__construct
	 * @uses \Kshabazz\SigmaRemix\Compiler::setIncludes
	 * @uses \Kshabazz\SigmaRemix\Compiler::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Compiler::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Compiler::setReplaceBlocks
	 */
	public function testCompileASingleBlockHavingOnlyTextToForeachStatement()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'block-1.html'
		);

		$parser = new Compiler( $template, NULL );

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
	 * @uses \Kshabazz\SigmaRemix\Compiler::__construct
	 * @uses \Kshabazz\SigmaRemix\Compiler::setIncludes
	 * @uses \Kshabazz\SigmaRemix\Compiler::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Compiler::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Compiler::setReplaceBlocks
	 */
	public function testCompile2ConsecutiveBlocksToForeachStatements()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'blocks-2.html'
		);

		$parser = new Compiler( $template, NULL );

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
	 * @uses \Kshabazz\SigmaRemix\Compiler::__construct
	 * @uses \Kshabazz\SigmaRemix\Compiler::setIncludes
	 * @uses \Kshabazz\SigmaRemix\Compiler::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Compiler::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Compiler::setReplaceBlocks
	 */
	public function testCompileNestedBlocksToNestedForeachStatements()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'nested-blocks.html'
		);

		$parser = new Compiler( $template, NULL );

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
	 * @uses \Kshabazz\SigmaRemix\Compiler::__construct
	 * @uses \Kshabazz\SigmaRemix\Compiler::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Compiler::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Compiler::setBlocks
	 * @uses \Kshabazz\SigmaRemix\Compiler::replacePlaceholder
	 * @uses \Kshabazz\SigmaRemix\Compiler::setReplaceBlocks
	 */
	public function test_should_parse_an_include_tag()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'include-placeholders-1.html'
		);

		$parser = new Compiler( $template, $this->templateDir );

		$data = $parser->process();

		$this->assertContains( 'Including placeholders-1.html was', $data );
		$this->assertContains( '$TEST_1', $data );
	}

	/**
	 * @covers ::setBlockReplacements
	 * @covers ::setBlocks
	 * @uses \Kshabazz\SigmaRemix\Compiler::__construct
	 * @uses \Kshabazz\SigmaRemix\Compiler::process
	 * @uses \Kshabazz\SigmaRemix\Compiler::compile
	 * @uses \Kshabazz\SigmaRemix\Compiler::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Compiler::setIncludes
	 * @uses \Kshabazz\SigmaRemix\Compiler::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Compiler::setReplaceBlocks
	 */
	public function test_should_replace_a_block()
	{
		$template = \file_get_contents(
			$this->templateDir . \DIRECTORY_SEPARATOR . 'block-1.html'
		);

		$parser = new Compiler( $template );

		$parser->setBlockReplacements([ 'BLOCK_1' => 'Replacement content' ]);

		$actual = $parser->process();

		$this->assertContains( 'Replacement content', $actual );
	}

	/**
	 * @covers ::setRemoveBlocks
	 * @covers ::setBlocks
	 * @uses \Kshabazz\SigmaRemix\Compiler::__construct
	 * @uses \Kshabazz\SigmaRemix\Compiler::process
	 * @uses \Kshabazz\SigmaRemix\Compiler::compile
	 * @uses \Kshabazz\SigmaRemix\Compiler::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Compiler::setIncludes
	 * @uses \Kshabazz\SigmaRemix\Compiler::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Compiler::setReplaceBlocks
	 */
	public function test_should_remove_a_block()
	{
		$template = \file_get_contents(
			$this->templateDir . \DIRECTORY_SEPARATOR . 'blocks-2.html'
		);

		$parser = new Compiler( $template );

		$parser->setRemoveBlocks([ 'BLOCK_1' ]);

		$actual = $parser->process();

		$this->assertNotContains( 'Block 1 content.', $actual );
	}

	/**
	 * @covers ::getBlocks
	 * @uses \Kshabazz\SigmaRemix\Compiler::__construct
	 * @uses \Kshabazz\SigmaRemix\Compiler::process
	 * @uses \Kshabazz\SigmaRemix\Compiler::compile
	 * @uses \Kshabazz\SigmaRemix\Compiler::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Compiler::setIncludes
	 * @uses \Kshabazz\SigmaRemix\Compiler::setBlocks
	 * @uses \Kshabazz\SigmaRemix\Compiler::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Compiler::setReplaceBlocks
	 */
	public function test_should_get_a_list_of_blocks_in_a_template()
	{
		$template = \file_get_contents(
			$this->templateDir . \DIRECTORY_SEPARATOR . 'blocks-2.html'
		);

		$parser = new Compiler( $template );

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
	 * @uses \Kshabazz\SigmaRemix\Compiler::__construct
	 * @uses \Kshabazz\SigmaRemix\Compiler::process
	 * @uses \Kshabazz\SigmaRemix\Compiler::compile
	 * @uses \Kshabazz\SigmaRemix\Compiler::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Compiler::replaceInclude
	 * @uses \Kshabazz\SigmaRemix\Compiler::setIncludes
	 * @uses \Kshabazz\SigmaRemix\SigmaRemixException
	 * @uses \Kshabazz\SigmaRemix\ParserException
	 */
	public function test_should_error_when_cannot_include_file_from_include_tag()
	{
		$template = \file_get_contents(
			$this->templateDir . \DIRECTORY_SEPARATOR . 'replace-block-1.html'
		);

		// Turn on parser strict mode.
		Compiler::setStrict( TRUE );

		$parser = new Compiler( $template );

		$parser->process();

		// Turn off parser strict mode.
		Compiler::setStrict( FALSE );
	}

	/**
	 * @covers ::setReplaceBlocks
	 * @uses \Kshabazz\SigmaRemix\Compiler::__construct
	 * @uses \Kshabazz\SigmaRemix\Compiler::process
	 * @uses \Kshabazz\SigmaRemix\Compiler::compile
	 * @uses \Kshabazz\SigmaRemix\Compiler::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Compiler::replaceInclude
	 * @uses \Kshabazz\SigmaRemix\Compiler::setIncludes
	 * @uses \Kshabazz\SigmaRemix\Compiler::setBlocks
	 * @uses \Kshabazz\SigmaRemix\Compiler::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Compiler::setStrict
	 */
	public function test_should_replace_one_block_with_another()
	{
		$template = \file_get_contents(
			$this->templateDir . \DIRECTORY_SEPARATOR . 'replace-block-1.html'
		);

		// Turn on Compiler strict mode.
		Compiler::setStrict( TRUE );

		$parser = new Compiler( $template, $this->templateDir . \DIRECTORY_SEPARATOR );

		$actual = $parser->process();

		$this->assertContains( 'Content was replaced.', $actual );
		$this->assertNotContains( 'Block 1 content.', $actual );

		// Turn off Compiler strict mode.
		Compiler::setStrict( FALSE );
	}

	/**
	 * @covers ::setIncludes
	 * @covers ::replaceInclude
	 * @covers ::process
	 * @covers ::compile
	 * @uses \Kshabazz\SigmaRemix\Compiler::__construct
	 * @uses \Kshabazz\SigmaRemix\Compiler::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Compiler::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Compiler::setBlocks
	 * @uses \Kshabazz\SigmaRemix\Compiler::replacePlaceholder
	 * @uses \Kshabazz\SigmaRemix\Compiler::setReplaceBlocks
	 */
	public function test_should_parse_an_include_tags_recursively()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'include-recursively-once.html'
		);

		$parser = new Compiler( $template, $this->templateDir );

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
	 * @uses \Kshabazz\SigmaRemix\Compiler::__construct
	 * @uses \Kshabazz\SigmaRemix\Compiler::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Compiler::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Compiler::setBlocks
	 * @uses \Kshabazz\SigmaRemix\Compiler::replacePlaceholder
	 * @uses \Kshabazz\SigmaRemix\Compiler::setReplaceBlocks
	 * @uses \Kshabazz\SigmaRemix\Compiler::setStrict
	 * @uses \Kshabazz\SigmaRemix\Compiler::isStrict
	 * @uses \Kshabazz\SigmaRemix\SigmaRemixException
	 */
	public function testThrowAnExceptionWhenIncludeTagsLoopInfinitely()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'include-recursively-infinite-loop.html'
		);

		Compiler::setStrict( TRUE );

		$parser = new Compiler( $template, $this->templateDir );

		$parser->process();

		Compiler::setStrict( FALSE );
	}

	/**
	 * @covers ::setIncludes
	 * @covers ::replaceInclude
	 * @covers ::process
	 * @covers ::compile
	 * @uses \Kshabazz\SigmaRemix\Compiler::__construct
	 * @uses \Kshabazz\SigmaRemix\Compiler::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Compiler::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Compiler::setBlocks
	 * @uses \Kshabazz\SigmaRemix\Compiler::replacePlaceholder
	 * @uses \Kshabazz\SigmaRemix\Compiler::setReplaceBlocks
	 * @uses \Kshabazz\SigmaRemix\Compiler::setStrict
	 * @uses \Kshabazz\SigmaRemix\Compiler::isStrict
	 * @uses \Kshabazz\SigmaRemix\SigmaRemixException
	 */
	public function testCompileAnIncludeTagThatLoopsInfinitelyButStopsAtTheSetRecursionLimit()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'include-recursively-infinite-loop-2.html'
		);

		Compiler::setStrict( FALSE );

		$parser = new Compiler( $template, $this->templateDir );

		$actual = $parser->process();

		$this->assertEquals( 10, \substr_count($actual, 'INCLUDE TEST') );
	}

	/**
	 * @covers ::setIncludes
	 * @covers ::replaceInclude
	 * @covers ::process
	 * @covers ::compile
	 * @uses \Kshabazz\SigmaRemix\Compiler::__construct
	 * @uses \Kshabazz\SigmaRemix\Compiler::setPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Compiler::setFunctions
	 * @uses \Kshabazz\SigmaRemix\Compiler::setBlocks
	 * @uses \Kshabazz\SigmaRemix\Compiler::replacePlaceholder
	 * @uses \Kshabazz\SigmaRemix\Compiler::setReplaceBlocks
	 * @uses \Kshabazz\SigmaRemix\Compiler::setStrict
	 * @uses \Kshabazz\SigmaRemix\Compiler::isStrict
	 * @uses \Kshabazz\SigmaRemix\SigmaRemixException
	 */
	public function testCallingReplaceOn2BlocksInOneTemplate()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'layout-1-user.html'
		);

		Compiler::setStrict( TRUE );

		$parser = new Compiler( $template, $this->templateDir );

		$actual = $parser->process();

		$this->assertContains( 'Replace block 1', $actual );
		$this->assertContains( 'Replace block 2', $actual );
		$this->assertNotContains( 'INCLUDE', $actual );
		$this->assertNotContains( 'REPLACE', $actual );

		Compiler::setStrict( FALSE );
	}
}
?>