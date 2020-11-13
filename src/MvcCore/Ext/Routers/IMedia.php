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
 * Responsibility - recognize media site version from URL or user agent or 
 *					session and set up request object, complete automatically 
 *					rewritten URL with remembered media site version. Redirect 
 *					to proper media site version by configuration.Than route 
 *					request like parent class does. Generate URL addresses with 
 *					prefixed media site version for recognized special devices 
 *					or add only media site version into query string if necessary.
 */
interface IMedia
{
	/**
	 * Predefined media site version value for full version.
	 * @var string
	 */
	const MEDIA_VERSION_FULL = 'full';

	/**
	 * Predefined media site version value for tablet version.
	 * @var string
	 */
	const MEDIA_VERSION_TABLET = 'tablet';

	/**
	 * Predefined media site version value for mobile version.
	 * @var string
	 */
	const MEDIA_VERSION_MOBILE = 'mobile';

	/**
	 * Key name for URL `$params` when building URL in controller or template by:
	 * `$this->Url('route_name', $params);`. If you need to create URL into different
	 * media site version, you need to add into `$params` array key with this value,
	 * then your URL will be created into different media site version.
	 * Example:
	 * `$this->Url('route_name', ['media_version' => 'mobile']);`
	 * `$this->Url('route_name', [\MvcCore\Ext\Routes\IMedia::URL_PARAM_MEDIA_VERSION => 'mobile']);`.
	 * @var string
	 */
	const URL_PARAM_MEDIA_VERSION = 'media_version';

	/**
	 * Special `$_GET` param name for session strict mode.
	 * To change to different media website version in session 
	 * strict mode, you need to add after URL something like this:
	 * `/any/path?any=params&media_version=mobile`.
	 * but if you are creating URL into different site version in
	 * controller or template by `$this->Url()` with key `media_version`
	 * in second `$params` array argument, this switching param for strict 
	 * mode is added automatically.
	 * @var string
	 */
	const URL_PARAM_SWITCH_MEDIA_VERSION = 'switch_media_version';
	

	/**
	 * Get resolved media site version, used in `\MvcCore\Request` 
	 * object and possible to use in controller or view.
	 * Possible values are always: `"full" | "tablet" | "mobile" | NULL`.
	 * @return string|NULL
	 */
	public function GetMediaSiteVersion ();

	/**
	 * Set media site version, used in `\MvcCore\Request` 
	 * object and possible to use in controller or view.
	 * Possible values are always: `"full" | "tablet" | "mobile" | NULL`.
	 * @param string|NULL $mediaSiteVersion
	 * @return \MvcCore\Ext\Routers\IMedia
	 */
	public function SetMediaSiteVersion ($mediaSiteVersion);

	/**
	 * Get URL prefixes prepended before request URL path to describe media site 
	 * version in url. Keys are media site version values and values in array are 
	 * URL prefixes, how to describe media site version in URL. Full version with 
	 * possible empty string prefix is necessary to have as last item. If you do 
	 * not want to use rewrite routes, just have under your allowed keys any values.
	 * Example: 
	 * ```
	 * [
	 *		'mobile'	=> 'm', // to have `/m` substring in every mobile URL begin.
	 *		'full'		=> '',	// to have nothing extra in URL for full site version.
	 * ];
	 * ```
	 * @return array
	 */
	public function & GetAllowedMediaVersionsAndUrlValues ();

	/**
	 * Set URL prefixes prepended before request URL path to describe media site 
	 * version in URL. Keys are media site version values and values in array are 
	 * URL prefixes, how to describe media site version in URL. Full version with 
	 * possible empty string prefix is necessary to put as last item. If you do 
	 * not want to use rewrite routes, just put under your allowed keys any values.
	 * Example: 
	 * ```
	 * \MvcCore\Ext\Routers\Media::GetInstance()->SetAllowedMediaVersionsAndUrlValues([
	 *		'mobile'	=> 'm', // to have `/m` substring in every mobile URL begin.
	 *		'full'		=> '',	// to have nothing extra in URL for full site version.
	 * ]);
	 * ```
	 * @param array $allowedMediaVersionsAndUrlValues
	 * @return \MvcCore\Ext\Routers\IMedia
	 */
	public function SetAllowedMediaVersionsAndUrlValues ($allowedMediaVersionsAndUrlValues = []);

	/**
	 * Route current app request by configured routes lists or by query string.
	 * 1. Check if request is targeting any internal action in internal ctrl.
	 * 2. Choose route strategy by request path and existing query string 
	 *    controller and/or action values - strategy by query string or by 
	 *    rewrite routes.
	 * 3. If request is not internal, redirect to possible better URL form by
	 *    configured trailing slash strategy and return `FALSE` for redirection.
	 * 4. Prepare media site version properties and redirect if necessary.
	 * 5. Try to complete current route object by chosen strategy.
	 * 6. If any current route found and if route contains redirection, do it.
	 * 7. If there is no current route and request is targeting homepage, create
	 *    new empty route by default values if ctrl configuration allows it.
	 * 8. If there is any current route completed, complete self route name by 
	 *    it to generate `self` routes and canonical URL later.
	 * 9. If there is necessary, try to complete canonical URL and if canonical 
	 *    URL is shorter than requested URL, redirect user to shorter version.
	 * If there was necessary to redirect user in routing process, return 
	 * immediately `FALSE` and return from this method. Else continue to next 
	 * step and return `TRUE`. This method is always called from core routing by:
	 * `\MvcCore\Application::Run();` => `\MvcCore\Application::routeRequest();`.
	 * @throws \LogicException Route configuration property is missing.
	 * @throws \InvalidArgumentException Wrong route pattern format.
	 * @return bool
	 */
	public function Route ();

	/**
	 * Complete non-absolute, non-localized url by route instance reverse info.
	 * If there is key `media_version` in `$params`, unset this param before
	 * route url completing and choose by this param url prefix to prepend 
	 * completed url string.
	 * If there is key `localization` in `$params`, unset this param before
	 * route url completing and place this param as url prefix to prepend 
	 * completed url string and to prepend media site version prefix.
	 * Example:
	 *	Input (`\MvcCore\Route::$reverse`):
	 *		`"/products-list/<name>/<color>"`
	 *	Input ($params):
	 *		`array(
	 *			"name"			=> "cool-product-name",
	 *			"color"			=> "red",
	 *			"variant"		=> ["L", "XL"],
	 *			"media_version"	=> "mobile",
	 *		);`
	 *	Output:
	 *		`/application/base-bath/m/products-list/cool-product-name/blue?variant[]=L&amp;variant[]=XL"`
	 * @param \MvcCore\Route|\MvcCore\IRoute &$route
	 * @param array $params
	 * @param string $urlParamRouteName
	 * @return string
	 */
	public function UrlByRoute (\MvcCore\IRoute $route, array & $params = [], $urlParamRouteName = NULL);
}
