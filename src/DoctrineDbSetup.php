<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\TesterExtras;

use Doctrine\DBAL\Connection;
use Kdyby;
use Nette;
use Tracy;
use Tester\Assert;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
trait DoctrineDbSetup
{

	use CompiledContainer {
		CompiledContainer::createContainer as parentCreateContainer;
		CompiledContainer::refreshContainer as parentRefreshContainer;
	}


	/**
	 * @var string|NULL
	 */
	protected $databaseName;



	protected function refreshContainer()
	{
		/** @var Connection $oldDb */
		$oldDb = $this->getContainer()->getByType('Doctrine\DBAL\Connection');

		$this->parentRefreshContainer();

		/** @var Connection $newDb */
		$newDb = $this->getContainer()->getByType('Doctrine\DBAL\Connection');
		$newDb->__construct(
			['pdo' => $oldDb->getWrappedConnection()] + $oldDb->getParams(),
			$newDb->getDriver(),
			$newDb->getConfiguration(),
			$newDb->getEventManager()
		);
	}



	/**
	 * @param array $configs
	 * @return Nette\DI\Container
	 */
	protected function createContainer(array $configs = [])
	{
		$sl = $this->parentCreateContainer($configs);

		/** @var DbConnectionMock $db */
		$db = $sl->getByType('Doctrine\DBAL\Connection');
		if (!$db instanceof DbConnectionMock) {
			$serviceNames = $sl->findByType('Doctrine\DBAL\Connection');
			throw new \LogicException(sprintf(
				'The service %s should be instance of Kdyby\TesterExtras\DbConnectionMock, to allow lazy schema initialization',
				reset($serviceNames)
			));
		}

		$db->onConnect[] = function (Connection $db) use ($sl) {
			if ($this->databaseName !== NULL) {
				return;
			}

			try {
				if (!method_exists($this, 'doSetupDatabase')) {
					throw new \LogicException(sprintf("Method %s:%s is not implemented", get_class($this), __FUNCTION__));
				}

				$this->doSetupDatabase($db);

			} catch (\Exception $e) {
				Tracy\Debugger::log($e, Tracy\Debugger::ERROR);
				Assert::fail($e->getMessage());
			}
		};

		return $sl;
	}



	/**
	 * THIS IS AN EXAMPLE IMPLEMENTATION FOR YOUR "BaseTestCase"
	 *
	protected function doSetupDatabase(Connection $db)
	{
		$this->databaseName = 'kdyby_tests_' . getmypid();

		$db->exec("DROP DATABASE IF EXISTS `{$this->databaseName}`");
		$db->exec("CREATE DATABASE `{$this->databaseName}` COLLATE 'utf8_general_ci'");

		$sqls = array(
			__DIR__ . '/../../app/sql/schema.sql',
			__DIR__ . '/../sql/fixtures.sql',
		);

		$db->exec("USE `{$this->databaseName}`");
		$db->exec("SET GLOBAL time_zone = '+01:00'");
		$db->transactional(function (Connection $db) use ($sqls) {
			$db->exec("SET foreign_key_checks = 0;");
			$db->exec("SET @disable_triggers = 1;");

			foreach ($sqls as $file) {
				Kdyby\Doctrine\Helpers::loadFromFile($db, $file);
			}

			// move autoincrement
			$db->insert('orders', array(
				'id' => (string) (1200000000 + (int) ((time() - 1381357699) . substr(getmypid(), -1))),
				'id_restaurant' => 1
			));
		});

		$db->exec("SET foreign_key_checks = 1;");
		$db->exec("SET @disable_triggers = NULL;");

		$databaseName = $this->databaseName;
		register_shutdown_function(function () use ($db, $databaseName) {
			$db->exec("DROP DATABASE IF EXISTS `{$databaseName}`");
		});

	}

	 */

}
