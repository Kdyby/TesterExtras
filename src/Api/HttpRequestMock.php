<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\TesterExtras\Api;

use Kdyby;
use Nette;
use Nette\Http\UrlScript;



/**
 * @author Filip Procházka <filip@prochazka.su>
 * @author Matěj Koubík <matej@koubik.name>
 *
 * @property array $headers
 * @property-read array $headers
 */
class HttpRequestMock extends Nette\Http\Request
{

	/**
	 * @var string
	 */
	private $rawBody = NULL;

	/**
	 * @var array
	 */
	private $headers;



	public function __construct(
		UrlScript $url, $query = NULL, $post = NULL, $files = NULL, $cookies = NULL,
		$headers = NULL, $method = NULL, $remoteAddress = NULL, $remoteHost = NULL, $rawBodyCallback = NULL
	) {
		parent::__construct($url, $query, $post, $files, $cookies, $headers, $method, $remoteAddress, $remoteHost, $rawBodyCallback);
		$this->headers = (array) $headers;
	}



	/**
	 * @return array
	 */
	public function getOriginalHeaders()
	{
		return $this->headers;
	}



	public function setRawBody($body)
	{
		$this->rawBody = $body;
		return $this;
	}



	public function getRawBody()
	{
		return $this->rawBody;
	}
}
