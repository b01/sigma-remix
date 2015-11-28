<?php namespace Kshabazz\Web\SigmaRemix\Tests;

require_once(__DIR__
	. DIRECTORY_SEPARATOR . '..'
	. DIRECTORY_SEPARATOR . 'vendor'
	. DIRECTORY_SEPARATOR . 'autoload.php'
);

const FIXTURES_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures';

const CACHE_DIR = FIXTURES_DIR . DIRECTORY_SEPARATOR . 'cache';
if ( !\is_dir(CACHE_DIR) )
{
	mkdir(CACHE_DIR);
}
?>