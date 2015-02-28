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
use Nette\Http\Request as HttpRequest;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
trait PresenterRunner
{

	/**
	 * @var Nette\Application\UI\Presenter
	 */
	protected $presenter;

	/**
	 * @var  Nette\Http\UrlScript
	 */
	private $fakeUrl;



	protected function openPresenter($fqa)
	{
		/** @var IntegrationTestCase|PresenterRunner $this */

		$sl = $this->getContainer();

		//insert fake HTTP Request for Presenter - for presenter->link() etc.
		$params = $sl->getParameters();
		$this->fakeUrl = new Nette\Http\UrlScript(isset($params['console']['url']) ? $params['console']['url'] : 'localhost');

		$sl->removeService('httpRequest');
		$sl->addService('httpRequest', new HttpRequest($this->fakeUrl, NULL, [], [], [], [], PHP_SAPI, '127.0.0.1', '127.0.0.1'));

		/** @var Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = $sl->getByType('Nette\Application\IPresenterFactory');

		$name = substr($fqa, 0, $namePos = strrpos($fqa, ':'));
		$class = $presenterFactory->getPresenterClass($name);

		if (!class_exists($overriddenPresenter = 'DamejidloTests\\' . $class)) {
			$classPos = strrpos($class, '\\');
			eval('namespace DamejidloTests\\' . substr($class, 0, $classPos) . '; class ' . substr($class, $classPos + 1) . ' extends \\' . $class . ' { '
				. 'protected function startup() { if ($this->getParameter("__terminate") == TRUE) { $this->terminate(); } parent::startup(); } '
				. 'public static function getReflection() { return parent::getReflection()->getParentClass(); } '
				. '}');
		}

		$this->presenter = $sl->createInstance($overriddenPresenter);
		$sl->callInjects($this->presenter);

		$app = $this->getService('Nette\Application\Application');
		$appRefl = new \ReflectionProperty($app, 'presenter');
		$appRefl->setAccessible(TRUE);
		$appRefl->setValue($app, $this->presenter);

		$this->presenter->autoCanonicalize = FALSE;
		$this->presenter->run(new Nette\Application\Request($name, 'GET', ['action' => substr($fqa, $namePos + 1) ?: 'default', '__terminate' => TRUE]));
	}



	/**
	 * @param $action
	 * @param string $method
	 * @param array $params
	 * @param array $post
	 * @return Nette\Application\IResponse
	 */
	protected function runPresenterAction($action, $method = 'GET', $params = [], $post = [])
	{
		/** @var IntegrationTestCase|PresenterRunner $this */

		if (!$this->presenter) {
			throw new \LogicException("You have to open the presenter using \$this->openPresenter(\$name); before calling actions");
		}

		$request = new Nette\Application\Request($this->presenter->getName(), $method, ['action' => $action] + $params, $post);

		return $this->presenter->run($request);
	}



	/**
	 * @return Nette\Application\IResponse
	 */
	protected function runPresenterSignal($action, $signal, $params = [], $post = [])
	{
		/** @var IntegrationTestCase|PresenterRunner $this */

		return $this->runPresenterAction($action, $post ? 'POST' : 'GET', ['do' => $signal] + $params, $post);
	}

}
