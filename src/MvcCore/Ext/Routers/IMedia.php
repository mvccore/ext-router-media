<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md
 */

namespace MvcCore\Ext\Routers;

interface IMedia
{
	/**
	 * MvcCore Extension - Router Media - version:
	 * Comparation by PHP function version_compare();
	 * @see http://php.net/manual/en/function.version-compare.php
	 */
	const VERSION = '5.0.0-alpha';

	/**
	 * Key name for media version in second argument $params in $router->Url();  method,
	 * to tell $router->Url() method to generate different media version url.
	 */
	const MEDIA_SITE_KEY_URL_PARAM = 'mediaSiteKey';

	/**
	 * Special $_GET param name for session strict mode, how to change site media version.
	 */
	const MEDIA_SITE_KEY_SWITCH_URL_PARAM = 'media_site_key';

	const MEDIA_SITE_KEY_FULL = 'full';
	const MEDIA_SITE_KEY_TABLET = 'tablet';
	const MEDIA_SITE_KEY_MOBILE = 'mobile';
}
