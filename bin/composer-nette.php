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

	$parseVersion = function ($version) {
		preg_match('~^(?P<modifier>\\~|\\^|(\\>|\\<)?=)?(?P<number>[^\\@]+)?(?:\\@(?P<stability>[^\\|]+))?(?P<other>.*)\\z~', $version, $v);
		return ((array) $v) + array('modifier' => '', 'number' => '0', 'stability' => 'stable');
	};

	$isNewerVersion = function ($what, $against) use ($parseVersion) {
		$w = $parseVersion($what);
		$a = $parseVersion($against);
		return version_compare($w['number'], $a['number'], '>');
	};

	$asStable = function ($version) use ($parseVersion) {
		$v = $parseVersion($version);
		return $v['modifier'] . $v['number']; // remove stability modifier
	};

	switch ($version) {
		case 'nette-2.3':
			$composer = $modifyRequirement(function ($dep, $version) use ($isNewerVersion, $asStable) {
				if (in_array($dep, array('nette/component-model', 'nette/tokenizer'), TRUE)) {
					return '~2.2@dev';
				}

				return $isNewerVersion($version, '~2.3') ? $asStable($version) : '~2.3';
			});

			$composer['require-dev'] = array('nette/nette' => '~2.3') + $composer['require-dev'];

			break;

		case 'nette-2.3-dev':
			$allPackages = $composer['require'] + $composer['require-dev'];
			if ($diff = array_diff_key(array_fill_keys($nettePackages, "~2.3@dev"), $allPackages)) {

				$formatted = '';
				foreach ($diff as $dep => $version) {
					$formatted .= sprintf("\t\"%s\": \"%s\",\n", $dep, $version);
				}

				out(5, "There are missing packages in the require-dev section of composer.json:\n\n" . $formatted);
			}

			out(0, "Nothing to change");
			break;

		case 'nette-2.2':
			$composer = $modifyRequirement(function ($dep, $version) {
				return '2.2.*';
			});

			$composer['require-dev'] = array('nette/nette' => '2.2.*') + $composer['require-dev'];

			break;

		case 'nette-2.2-dev':
			out(0, "Nothing to change");
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

	if (!empty($composer['require']['nette/deprecated'])) {
		$composer['require']['nette/deprecated'] = '@dev';
	}

	if (!empty($composer['require-dev']['nette/deprecated'])) {
		$composer['require-dev']['nette/deprecated'] = '@dev';
	}

	$content = defined('JSON_PRETTY_PRINT') ? json_encode($composer, JSON_PRETTY_PRINT) : json_encode($composer);
	$content = preg_replace_callback('~^(    )+~m', function (array $m) {
		return str_replace('    ', "\t", $m[0]);
	}, $content);
	file_put_contents($composerJsonFile, $content . "\n");

	echo "\n", print_r(array(
		'require' => $composer['require'],
		'require-dev' => !empty($composer['require-dev']) ? $composer['require-dev'] : array(),
	), TRUE);

	out(0);
});
