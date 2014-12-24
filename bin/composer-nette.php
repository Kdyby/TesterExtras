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

	static $nettePackages = array(
		"nette/application",
		"nette/bootstrap",
		"nette/caching",
		"nette/component-model",
		"nette/database",
		"nette/deprecated",
		"nette/di",
		"nette/finder",
		"nette/forms",
		"nette/http",
		"nette/mail",
		"nette/neon",
		"nette/php-generator",
		"nette/reflection",
		"nette/robot-loader",
		"nette/safe-stream",
		"nette/security",
		"nette/tokenizer",
		"nette/utils",
		"latte/latte",
		"tracy/tracy",
	);

	$modifyRequirement = function ($callback) use ($composer, $nettePackages) {
		foreach (array('require', 'require-dev') as $req) {
			if (!isset($composer[$req])) {
				continue;
			}

			foreach ($composer[$req] as $dep => $version) {
				if (!in_array(strtolower($dep), $nettePackages, TRUE)) {
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
			$composer['require'] = array_fill_keys($nettePackages, "~2.2@dev") + $composer['require'];

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
