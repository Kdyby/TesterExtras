<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\TesterExtras\Api;

use Damejidlo\InvalidStateException;
use Kdyby;
use Nette;
use Nette\Application\Responses\JsonResponse;
use Nette\Http;



/**
 * @author Filip Procházka <filip@prochazka.su>
 * @author Matěj Koubík <matej@koubik.name>
 *
 * @property array $headers
 */
class HttpResponseMock extends Http\Response implements Http\IResponse
{

	/**
	 * @var int
	 */
	private $code = self::S200_OK;

	/**
	 * @var array
	 */
	private $headers = [];

	/**
	 * @var string
	 */
	private $content;

	/**
	 * @var Nette\Application\IResponse|JsonResponse
	 */
	private $appResponse;



	public function setCode($code)
	{
		$this->code = $code;
	}



	public function getCode()
	{
		return $this->code;
	}



	/**
	 * @return Nette\Application\IResponse|JsonResponse
	 */
	public function getAppResponse()
	{
		return $this->appResponse;
	}



	/**
	 * @param Nette\Application\IResponse|JsonResponse $response
	 */
	public function setAppResponse(Nette\Application\IResponse $response)
	{
		$this->appResponse = $response;
	}



	public function setHeader($name, $value)
	{
		$this->headers[$name] = $value;
	}



	public function addHeader($name, $value)
	{
		$this->headers[$name] = $value;
	}



	public function getHeader($header, $default = NULL)
	{
		return isset($this->headers[$header]) ? $this->headers[$header] : $default;
	}



	public function getHeaders()
	{
		return $this->headers;
	}



	public function getContent()
	{
		return $this->content;
	}



	public function setContent($content)
	{
		$this->content = $content;
	}



	/**
	 * @return array|\stdClass
	 */
	public function getPayload()
	{
		if (!$this->appResponse instanceof JsonResponse) {
			throw new \RuntimeException("Unexpected response");
		}

		return $this->appResponse->getPayload();
	}

}
