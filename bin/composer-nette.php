<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

function out($c, $msg = NULL)
{
	echo $msg, PHP_EOL;
	exit($c);
}

/**
 * Thanks to @Vrtak-CZ (Patrik Votoček) for the idea
 */
call_user_func(function () {
	$projectRoot = getcwd();

	$version = getenv('NETTE');
	if (!$version) {
		out(0, "Version constant NETTE is not defined.");
	}

	echo "Nette version " . $version . PHP_EOL;

	if (!file_exists($composerJsonFile = $projectRoot . '/composer.json')) {
		out(2, "Cannot locate the composer.json");
	}

	$content = file_get_contents($composerJsonFile);
	$composer = json_decode($content, TRUE);

	if (!array_key_exists('require', $composer)) {
		out(3, "The composer.json has no require section");
	}

	$modifyRequirement = function ($callback) use ($composer) {
		foreach (array('require', 'require-dev') as $req) {
			if (!isset($composer[$req])) {
				continue;
			}

			foreach ($composer[$req] as $dep => $version) {
				if (stripos($dep, 'nette/') !== 0 && stripos($dep, 'tracy/') !== 0 && stripos($dep, 'latte/') !== 0) {
					continue;
				}

				if (strtolower($dep) === 'nette/tester') {
					continue;
				}

				$composer[$req][$dep] = $callback($dep, $version);
			}
		}

		return $composer;
	};

	switch ($version) {
		case 'nette-2.2':
			$composer = $modifyRequirement(function ($dep, $version) {
				return '~2.2,>=2.2.0';
			});
			break;

		case 'nette-2.2-dev':
			$composer['require'] = array(
					"nette/application" => "~2.2@dev",
					"nette/bootstrap" => "~2.2@dev",
					"nette/caching" => "~2.2@dev",
					"nette/component-model" => "~2.2@dev",
					"nette/database" => "~2.2@dev",
					"nette/deprecated" => "~2.2@dev",
					"nette/di" => "~2.2@dev",
					"nette/finder" => "~2.2@dev",
					"nette/forms" => "~2.2@dev",
					"nette/http" => "~2.2@dev",
					"nette/mail" => "~2.2@dev",
					"nette/neon" => "~2.2@dev",
					"nette/php-generator" => "~2.2@dev",
					"nette/reflection" => "~2.2@dev",
					"nette/robot-loader" => "~2.2@dev",
					"nette/safe-stream" => "~2.2@dev",
					"nette/security" => "~2.2@dev",
					"nette/tokenizer" => "~2.2@dev",
					"nette/utils" => "~2.2@dev",
					"latte/latte" => "~2.2@dev",
					"tracy/tracy" => "~2.2@dev",
				) + $composer['require'];

			break;

		case 'nette-2.1':
			$composer = $modifyRequirement(function ($dep, $version) {
				return '2.1.*';
			});
			break;

		case 'nette-2.0':
			$composer = $modifyRequirement(function ($dep, $version) {
				return '2.0.*';
			});
			break;

		case 'default':
			out(0, "Nothing to change");
			break;

		default:
			out(4, "Unsupported requirement: " . $version);
	}

	$content = defined('JSON_PRETTY_PRINT') ? json_encode($composer, JSON_PRETTY_PRINT) : json_encode($composer);
	file_put_contents($composerJsonFile, $content);

	out(0);
});
