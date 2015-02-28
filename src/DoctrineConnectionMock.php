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



/**
 * @method onConnect(DbConnectionMock $self)
 */
class DbConnectionMock extends Kdyby\Doctrine\Connection
{

	public $onConnect = array();



	public function connect()
	{
		if (parent::connect()) {
			$this->onConnect($this);
		}
	}

}
