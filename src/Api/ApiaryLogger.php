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
use Nette\Utils\Json;
use Nette\Utils\Strings;



/**
 * @author Filip Procházka <filip@prochazka.su>
 * @author Matěj Koubík <matej@koubik.name>
 */
class ApiaryLogger extends HttpLogger
{

	/**
	 * @var string
	 */
	public $baseUri;



	protected function formatRequestHead(HttpRequestMock $request, $path)
	{
		$contentType = $request->headers['content-type'];
		return '+ Request' . ($contentType ? " ($contentType)" : '') . PHP_EOL;
	}



	protected function formatResponseHead(HttpResponseMock $response)
	{
		$contentType = $response->headers['Content-Type'];
		return sprintf('+ Response %s', $response->getCode()) . ($contentType ? " ($contentType)" : '') . PHP_EOL;
	}



	public function formatHeaders(array $headers)
	{
		unset($headers['Content-Type'], $headers['Vary'], $headers['Cache-Control'], $headers['Expires']);
		return parent::formatHeaders($headers);
	}

}
