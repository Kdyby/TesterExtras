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
use Nette\Http;
use Tester\TestCase;
use Tracy;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
abstract class ApiTestCase extends TestCase
{

	use DoctrineDbSetup;

	/**
	 * @var Nette\Application\IRouter
	 */
	protected $router;

	/**
	 * @var string
	 */
	protected $baseUrl = 'Https://www.kdyby.org/api/v1';



	protected function setUp()
	{
		parent::setUp();

		$sl = $this->getContainer();
		$services = $sl->findByType('Nette\Application\IRouter');
		$this->router = $sl->createService($services[0]);
	}



	/**
	 * @param string $method
	 * @param string $path
	 * @param array $headers
	 * @param string|null $body
	 * @return Http\Response
	 */
	protected function getHttpResponse($method, $path, array $headers = [], $body = NULL)
	{
		$sl = $this->getContainer();

		$processor = new Api\RequestProcessor($sl, $logger = new Api\HttpLogger(), $this->baseUrl);
		$processor->setRouter($this->router);

		try {
			$processor->request($method, $path, $headers, $body);

		} finally {
			$this->appResponse = $processor->getAppResponse();

			$classRefl = new \ReflectionClass($this);
			$logger->write(dirname($classRefl->getFileName()) . '/requests.log');
		}

		return $processor->getHttpResponse();
	}

}
