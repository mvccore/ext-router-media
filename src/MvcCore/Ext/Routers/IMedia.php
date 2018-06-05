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
	 * Key name for url `$params` when building url in controller or template by:
	 * `$this->Url('route_name', $params);`. If you need to create url into different
	 * media website version, you need to add into `$params` array key with this value,
	 * then your url will be created into different media website version.
	 * Example:
	 * `$this->Url('route_name', ['media_version' => 'mobile']);`
	 * `$this->Url('route_name', [\MvcCore\Ext\Routes\IMedia::MEDIA_VERSION_URL_PARAM => 'mobile']);`.
	 * @var string
	 */
	const MEDIA_VERSION_URL_PARAM = 'media_version';

	/**
	 * Media site key controller property value for full site version.
	 * @var string
	 */
	const MEDIA_VERSION_FULL = 'full';

	/**
	 * Media site key controller property value for tablet site version.
	 * @var string
	 */
	const MEDIA_VERSION_TABLET = 'tablet';

	/**
	 * Media site key controller property value for mobile site version.
	 * @var string
	 */
	const MEDIA_VERSION_MOBILE = 'mobile';

	/**
	 * Special `$_GET` param name for session strict mode.
	 * To change to different media website version in session 
	 * strict mode, you need to add after url something like this:
	 * `/any/path?any=params&media_version=mobile`.
	 * but if you are creating url into different site version in
	 * controller or template by `$this->Url()` with key `mediaSiteKey`
	 * in second `$params` array argument, this switching param for strict 
	 * mode is added automaticly.
	 * @var string
	 */
	const SWITCH_MEDIA_VERSION_URL_PARAM = 'switch_media_version';
}
