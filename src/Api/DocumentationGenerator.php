<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\TesterExtras\Api;

use Damejidlo;
use Kdyby;
use Kdyby\Doctrine\Connection;
use Nette;
use Nette\DI\Container;



/**
 * @author Filip Procházka <filip@prochazka.su>
 * @author Matěj Koubík <matej@koubik.name>
 */
class DocumentationGenerator extends Nette\Object
{

	use Kdyby\TesterExtras\DoctrineDbSetup;

	/**
	 * @var array
	 */
	public $defaultHeaders = [];

	/**
	 * @var Container
	 */
	protected $sl;

	/**
	 * @var ApiaryLogger
	 */
	protected $logger;

	/**
	 * @var array
	 */
	protected $configs;

	/**
	 * @var string
	 */
	protected $basePath;



	public function __construct($basePath = 'https://www.kdyby.org/api/v1', array $configs = [])
	{
		$this->logger = new ApiaryLogger();
		$this->configs = $configs;
		$this->basePath = $basePath;
	}



	public function execute(\Closure $callback)
	{
		$this->sl = $this->createContainer($this->configs);

		try {
			call_user_func($callback, $this->sl, $this->logger);

		} finally {
			/** @var Nette\Http\Session $session */
			$session = $this->sl->getByType('Nette\Http\Session');
			if ($session->isStarted()) {
				$session->destroy();
			}

			$this->logger->outputFilter = NULL;
		}
	}



	/**
	 * @param string $method
	 * @param string $path
	 * @param array $headers
	 * @param string|null $body
	 * @return RequestProcessor
	 */
	public function request($method, $path, array $headers = [], $body = NULL)
	{
		$processor = new RequestProcessor($this->sl, $this->logger, $this->basePath);

		try {
			$processor->request($method, $path, $headers + $this->defaultHeaders, $body);

		} finally {
			$this->logger->output();
		}

		return $processor;
	}

}
