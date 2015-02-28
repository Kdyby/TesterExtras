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

	use CompiledContainer;



	protected function tearDown()
	{
		$this->tearDownContainer();
	}

}
