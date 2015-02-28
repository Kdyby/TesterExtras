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
use Nette\DI\Container;
use Nette\Http\Session;
use Tester\TestCase;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
abstract class IntegrationTestCase extends TestCase
{

	/**
	 * @var Container|\SystemContainer
	 */
	private $container;



	/**
	 * @return Container
	 */
	protected function getContainer()
	{
		if ($this->container === NULL) {
			$this->container = $this->createContainer();
		}

		return $this->container;
	}



	protected function refreshContainer()
	{
		$container = $this->getContainer();

		/** @var Session $session */
		if (($session = $container->getByType('Nette\Http\Session')) && $session->isStarted()) {
			$session->close();
		}

		$this->container = new $container();
		$this->container->initialize();
	}



	protected function doCreateConfiguration()
	{
		$config = new Nette\Configurator();
		$config->addParameters([
			// vendor/kdyby/tester-extras/src
			'rootDir' => $rootDir = dirname(dirname(dirname(dirname(__DIR__)))),
			'appDir' => $rootDir . '/app',
			'wwwDir' => $rootDir . '/www',
		]);

		// shared compiled container for faster tests
		$config->setTempDirectory(dirname(TEMP_DIR));

		return $config;
	}



	/**
	 * @param array $configs
	 * @return Container
	 */
	protected function createContainer(array $configs = [])
	{
		$config = $this->doCreateConfiguration();

		foreach ($configs as $file) {
			$config->addConfig($file);
		}

		/** @var Container $container */
		$container = $config->createContainer();

		return $container;
	}



	/**
	 * @param string $type
	 * @return object
	 */
	public function getService($type)
	{
		$container = $this->getContainer();
		if ($object = $container->getByType($type, FALSE)) {
			return $object;
		}

		return $container->createInstance($type);
	}



	protected function tearDown()
	{
		if ($this->container) {
			/** @var Session $session */
			$session = $this->container->getByType('Nette\Http\Session');
			if ($session->isStarted()) {
				$session->destroy();
			}

			$this->container = NULL;
		}
	}

}
