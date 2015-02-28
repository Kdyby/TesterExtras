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
class HttpLogger extends Nette\Object
{

	/**
	 * @var callable|NULL
	 */
	public $outputFilter;

	/**
	 * @var string
	 */
	public $baseUri;

	/**
	 * @var string
	 */
	protected $buffer = '';



	public function log(HttpRequestMock $request, HttpResponseMock $response)
	{
		$buffer = $this->formatRequest($request);
		$buffer .= $this->formatResponse($response);

		$this->buffer .= $buffer . PHP_EOL . PHP_EOL;
	}



	public function write($file)
	{
		@file_put_contents($file, $this->buffer, FILE_APPEND | LOCK_EX);
		$this->buffer = '';
	}



	public function output()
	{
		echo $this->buffer;
		$this->buffer = '';
	}



	public function formatRequest(HttpRequestMock $request)
	{
		$path = str_replace($this->baseUri, '', $request->getUrl()->getHostUrl() . $request->getUrl()->getPath());

		$return = $this->formatRequestHead($request, $path);
		$return .= $this->formatHeaders($request->getOriginalHeaders());
		$return .= $this->formatBody($request->getRawBody(), TRUE);

		return $return . PHP_EOL;
	}



	protected function formatRequestHead(HttpRequestMock $request, $path)
	{
		return (new \DateTime())->format('[Y-m-d H:i:s]') . PHP_EOL
			. sprintf('> %s %s', $request->getMethod(), $path) . PHP_EOL;
	}



	public function formatResponse(HttpResponseMock $response)
	{
		$return = $this->formatResponseHead($response);
		$return .= $this->formatHeaders($response->getHeaders());
		$return .= $this->formatBody($response->getContent(), FALSE);

		return $return . PHP_EOL;
	}



	protected function formatResponseHead(HttpResponseMock $response)
	{
		return sprintf('< %s', $response->getCode()) . PHP_EOL;
	}



	public function formatHeaders(array $headers)
	{
		if (empty($headers)) {
			return '';
		}

		$result = '';
		foreach ($headers as $name => $value) {
			$result .= "$name: $value" . PHP_EOL;
		}

		$result = Strings::indent($result, 8, ' ');
		$result = Strings::indent("+ Headers" . PHP_EOL . PHP_EOL . $result, 4, ' ');

		return PHP_EOL . $result;
	}



	public function formatBody($body, $request = TRUE)
	{
		if (empty($body)) {
			return '';
		}

		$body = $this->tryFormatJson($body, $request);

		$result = Strings::indent($body, 8, ' ');
		$result = Strings::indent("+ Body" . PHP_EOL . PHP_EOL . $result, 4, ' ');

		return PHP_EOL . $result;
	}



	protected function tryFormatJson($string, $request)
	{
		try {
			$data = Json::decode($string);
			if ($data === NULL) {
				return '';
			}

			if ($this->outputFilter) {
				$data = call_user_func($this->outputFilter, $data, $request);
			}

			return Json::encode($data, Json::PRETTY);

		} catch (Nette\Utils\JsonException $e) {
			return $string;
		}
	}

}
