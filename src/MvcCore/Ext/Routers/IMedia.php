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

/**
 * Responsibility - recognize media site version from url or user agent or session and set 
 *					up request object, complete automaticly rewrited url with remembered 
 *					media site version. Redirect to proper media site version by configuration.
 *					Than route request like parent class does.
 */
interface IMedia
{
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
	 * Key name for url `$params` when building url in controller or template by:
	 * `$this->Url('route_name', $params);`. If you need to create url into different
	 * media website version, you need to add into `$params` array key with this value,
	 * then your url will be created into different media website version.
	 * Example:
	 * `$this->Url('route_name', ['media_version' => 'mobile']);`
	 * `$this->Url('route_name', [\MvcCore\Ext\Routes\IMedia::URL_PARAM_MEDIA_VERSION => 'mobile']);`.
	 * @var string
	 */
	const URL_PARAM_MEDIA_VERSION = 'media_version';

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
	const URL_PARAM_SWITCH_MEDIA_VERSION = 'switch_media_version';
	

	/**
	 * Get url prefixes prepended before request url path to describe media site version in url.
	 * Keys are media site version values and values in array are url prefixes, how
	 * to describe media site version in url.
	 * Full version with possible empty string prefix is necessary to have as last item.
	 * If you do not want to use rewrite routes, just have under your alowed keys any values.
	 * Example: 
	 * ```
	 * [
	 *		'mobile'	=> 'm', // to have `/m` substring in every mobile url begin.
	 *		'full'		=> '',	// to have nothing extra in url for full site version.
	 * ];
	 * ```
	 * @return array
	 */
	public function & GetAllowedMediaVersionsAndUrlValues ();

	/**
	 * Set url prefixes prepended before request url path to describe media site version in url.
	 * Keys are media site version values and values in array are url prefixes, how
	 * to describe media site version in url.
	 * Full version with possible empty string prefix is necessary to put as last item.
	 * If you do not want to use rewrite routes, just put under your alowed keys any values.
	 * Example: 
	 * ```
	 * \MvcCore\Ext\Routers\Media::GetInstance()->SetAllowedMediaVersionsAndUrlValues([
	 *		'mobile'	=> 'm', // to have `/m` substring in every mobile url begin.
	 *		'full'		=> '',	// to have nothing extra in url for full site version.
	 * ]);
	 * ```
	 * @param array $allowedMediaVersionsAndUrlValues
	 * @return \MvcCore\Ext\Routers\IMedia
	 */
	public function & SetAllowedMediaVersionsAndUrlValues ($allowedMediaVersionsAndUrlValues = []);
}
