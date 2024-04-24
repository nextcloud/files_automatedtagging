<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @license GNU AGPL version 3 or any later version
 *
 * SPDX-FileCopyrightText: 2016 Joas Schilling <coding@schilljs.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

if (!defined('PHPUNIT_RUN')) {
	define('PHPUNIT_RUN', 1);
}

require_once __DIR__.'/../../../lib/base.php';

if (!class_exists('PHPUnit\Framework\TestCase')) {
	require_once('PHPUnit/Autoload.php');
}

// Fix for "Autoload path not allowed: .../files_automatedtagging/tests/..."
\OC_App::loadApp('files_external');
\OC_App::loadApp('files_automatedtagging');

OC_Hook::clear();
