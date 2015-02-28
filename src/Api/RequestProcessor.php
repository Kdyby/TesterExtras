<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\TesterExtras\Api;

use DamejidloTests\Server;
use Kdyby;
use Nette;
use Nette\Application\Application;
use Nette\Application\PresenterFactory;
use Nette\Http;



/**
 * @author Filip Procházka <filip@prochazka.su>
 * @author Matěj Koubík <matej@koubik.name>
 *
 * @method onBeforeRequest(Nette\Application\Request $appRequest, Nette\DI\Container $sl)
 */
class RequestProcessor extends Nette\Object
{

	/**
	 * @var array
	 */
	public $onBeforeRequest = [];

	/**
	 * @var Nette\DI\Container
	 */
	private $sl;

	/**
	 * @var Nette\Application\IRouter
	 */
	private $router;

	/**
	 * @var Nette\Application\IResponse
	 */
	private $appResponse;

	/**
	 * @var HttpRequestMock
	 */
	private $httpRequest;

	/**
	 * @var HttpResponseMock
	 */
	private $httpResponse;

	/**
	 * @var string
	 */
	private $baseUri;

	/**
	 * @var HttpLogger
	 */
	private $logger;



	public function __construct(Nette\DI\Container $container, HttpLogger $logger, $baseUri)
	{
		$this->sl = $container;
		$this->baseUri = $baseUri;

		$this->logger = $logger;
		$this->logger->baseUri = $baseUri;
	}



	public function setRouter(Nette\Application\IRouter $router)
	{
		$this->router = $router;
	}



	protected function getRouter()
	{
		if ($this->router === NULL) {
			$this->router = $this->sl->getByType(Nette\Application\IRouter::class);
		}

		return $this->router;
	}



	/**
	 * @param string $method
	 * @param string $path
	 * @param array $headers
	 * @param string|null $body
	 * @return Http\Response
	 */
	public function request($method, $path, array $headers = [], $body = NULL)
	{
		$this->appResponse = NULL;

		$url = new Http\UrlScript($this->baseUri . $path);
		$this->httpRequest = (new HttpRequestMock($url, NULL, [], [], [], $headers, $method, '127.0.0.1', '127.0.0.1'))->setRawBody($body);
		$this->httpResponse = new HttpResponseMock();

		// mock request & response
		$this->sl->removeService('httpRequest');
		$this->sl->addService('httpRequest', $this->httpRequest);

		$this->sl->removeService('httpResponse');
		$this->sl->addService('httpResponse', $this->httpResponse);

		/** @var Kdyby\FakeSession\Session $session */
		$session = $this->sl->getService('session');
		$session->__construct(new Http\Session($this->httpRequest, $this->httpResponse));

		/** @var Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = $this->sl->getByType('Nette\Application\IPresenterFactory');

		/** @var Application $application */
		$application = $this->sl->getByType('Nette\Application\Application');
		$application->__construct($presenterFactory, $this->getRouter(), $this->httpRequest, $this->httpResponse);

		$application->onResponse[] = function (Application $application, Nette\Application\IResponse $response) {
			$this->appResponse = $response;
			$this->httpResponse->setAppResponse($response);
		};

		$appRequest = $this->getRouter()->match($this->httpRequest);

		$this->onBeforeRequest($appRequest, $this->sl);

		try {
			ob_start();

			try {
				$this->appResponse = NULL;
				$application->processRequest($appRequest);

			} catch (\Exception $e) {
				$application->processException($e);
			}

			$this->httpResponse->setContent(ob_get_clean());

		} finally {
			$this->logger->log($this->httpRequest, $this->httpResponse);
		}

		return $this->httpResponse;
	}



	/**
	 * @return Nette\Application\IResponse
	 */
	public function getAppResponse()
	{
		return $this->appResponse;
	}



	/**
	 * @return HttpResponseMock
	 */
	public function getHttpResponse()
	{
		return $this->httpResponse;
	}



	/**
	 * @return HttpRequestMock
	 */
	public function getHttpRequest()
	{
		return $this->httpRequest;
	}

}
