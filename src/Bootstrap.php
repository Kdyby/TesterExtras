<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\TesterExtras;

use Kdyby;
use Nette;
use Tester;
use Tracy;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Bootstrap
{

	public static function setup($rootDir)
	{
		// configure environment
		umask(0);
		Tester\Environment::setup();
		class_alias('Tester\Assert', 'Assert');
		date_default_timezone_set('Europe/Prague');

		// create temporary directory
		define('TEMP_DIR', $rootDir . '/tmp/' . (isset($_SERVER['argv']) ? md5(serialize($_SERVER['argv'])) : getmypid()));
		Tester\Helpers::purge(TEMP_DIR);
		@chmod(TEMP_DIR, 0777);
		Tracy\Debugger::$logDirectory = TEMP_DIR;

//		$_SERVER = array_intersect_key($_SERVER, array_flip(array(
//			'PHP_SELF', 'SCRIPT_NAME', 'SERVER_ADDR', 'SERVER_SOFTWARE', 'HTTP_HOST', 'DOCUMENT_ROOT', 'OS', 'argc', 'argv'
//		)));
		$_SERVER['REQUEST_TIME'] = 1234567890;
		$_ENV = $_GET = $_POST = $_FILES = array();
	}



	/**
	 * @param array $dirs
	 * @return Nette\Loaders\RobotLoader
	 */
	public static function createRobotLoader($dirs = array())
	{
		if (!is_array($dirs)) {
			$dirs = func_get_args();
		}

		$loader = new Nette\Loaders\RobotLoader;
		$loader->setCacheStorage(new Nette\Caching\Storages\FileStorage(TEMP_DIR));
		$loader->autoRebuild = TRUE;

		foreach ($dirs as $dir) {
			$loader->addDirectory($dir);
		}

		return $loader->register();
	}



	public static function setupDoctrineDatabase(Nette\DI\Container $sl, $sqls = array(), $prefix = 'kdyby', &$id = NULL)
	{
		$db = $sl->getByType('Kdyby\Doctrine\Connection'); // default connection
		/** @var \Kdyby\Doctrine\Connection $db */

		$testDbName = $prefix . '_test_' . ($id = ($id ?: getmypid()));
		$db->exec("DROP DATABASE IF EXISTS `$testDbName`");
		$db->exec("CREATE DATABASE `$testDbName`");
		$db->exec("USE `$testDbName`");

		foreach ($sqls as $file) {
			Kdyby\Doctrine\Helpers::loadFromFile($db, $file);
		}

		// drop on shutdown
		register_shutdown_function(function () use ($db, $testDbName) {
			$db->exec("DROP DATABASE IF EXISTS `$testDbName`");
		});

		return $testDbName;
	}

}
