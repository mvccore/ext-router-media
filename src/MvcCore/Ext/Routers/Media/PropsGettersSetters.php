<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md
 */

namespace MvcCore\Ext\Routers\Media;

use MvcCore\Ext\Routers;

/**
 * @mixin \MvcCore\Ext\Routers\Media
 */
trait PropsGettersSetters {

	/*************************************************************************************
	 *                              Configurable Properties                              *
	 ************************************************************************************/

	/**
	 * Url prefixes prepended before request URL path to describe media site version in url.
	 * Keys are media site version values and values in array are URL prefixes, how
	 * to describe media site version in url.
	 * Full version with possible empty string prefix is necessary to put as last item.
	 * If you do not want to use rewrite routes, just put under your allowed keys any values.
	 * @var array
	 */
	protected $allowedMediaVersionsAndUrlValues = [
		Routers\IMedia::MEDIA_VERSION_MOBILE	=> 'm',
		Routers\IMedia::MEDIA_VERSION_TABLET	=> 't',
		Routers\IMedia::MEDIA_VERSION_FULL		=> '',
	];


	/***************************************************************************
	 *                           Internal Properties                           *
	 **************************************************************************/
	
	/**
	 * Media site version to switch user into. 
	 * Default value is `NULL`. If there is any media site version 
	 * under key `media_version` in `$_GET` array, this property  
	 * has string with this value.
	 * @var string|NULL
	 */
	protected $switchUriParamMediaSiteVersion = NULL;

	/**
	 * Resolved media site version, used in `\MvcCore\Request` 
	 * object and possible to use in controller or view.
	 * Possible values are always: `"full" | "tablet" | "mobile" | NULL`.
	 * @var string|NULL
	 */
	protected $mediaSiteVersion = NULL;

	/**
	 * Media site version founded in session.
	 * @var string|NULL
	 */
	protected $sessionMediaSiteVersion = NULL;

	/**
	 * Requested media site version.
	 * @var string|NULL
	 */
	protected $requestMediaSiteVersion = NULL;

	/**
	 * If `NULL`, request was not first, there was something in session stored by 
	 * previous requests. 
	 * If `TRUE`, request was first, nothing was in session from previous requests 
	 * and detected version is the same as requested media site version. 
	 * 
	 * If `FALSE`, request was first, nothing was in session from previous requests 
	 * and detected version is different from requested media site version. 
	 * There is necessary to redirect user to detected version from first request.
	 * @var bool|NULL
	 */
	protected $firstRequestMediaDetection = NULL;
	
	
	/***************************************************************************
	 *                             Public Methods                              *
	 **************************************************************************/

	/**
	 * Get resolved media site version, used in `\MvcCore\Request` 
	 * object and possible to use in controller or view.
	 * Possible values are always: `"full" | "tablet" | "mobile" | NULL`.
	 * @return string|NULL
	 */
	public function GetMediaSiteVersion () {
		return $this->mediaSiteVersion;
	}
	
	/**
	 * Set media site version, used in `\MvcCore\Request` 
	 * object and possible to use in controller or view.
	 * Possible values are always: `"full" | "tablet" | "mobile" | NULL`.
	 * @param string|NULL $mediaSiteVersion
	 * @return \MvcCore\Ext\Routers\Media
	 */
	public function SetMediaSiteVersion ($mediaSiteVersion) {
		$this->mediaSiteVersion = $mediaSiteVersion;
		return $this;
	}

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
	public function GetAllowedMediaVersionsAndUrlValues () {
		return $this->allowedMediaVersionsAndUrlValues;
	}

	/**
	 * Set URL prefixes prepended before request URL path to describe media site 
	 * version in URL. Keys are media site version values and values in array are 
	 * URL prefixes, how to describe media site version in URL. Full version with 
	 * possible empty string prefix is necessary to put as last item. If you do 
	 * not want to use rewrite routes, just put under your allowed keys any values.
	 * Example: 
	 * ```
	 * \MvcCore\Ext\Routers\Media::GetInstance()->SetAllowedMediaVersionsAndUrlValues([
	 *     'mobile' => 'm', // to have `/m` substring in every mobile URL begin.
	 *     'full'   => '',  // to have nothing extra in URL for full site version.
	 * ]);
	 * ```
	 * @param array $allowedMediaVersionsAndUrlValues
	 * @return \MvcCore\Ext\Routers\Media
	 */
	public function SetAllowedMediaVersionsAndUrlValues ($allowedMediaVersionsAndUrlValues = []) {
		$this->allowedMediaVersionsAndUrlValues = $allowedMediaVersionsAndUrlValues;
		return $this;
	}
	

	/***************************************************************************
	 *                             Protected Methods                           *
	 **************************************************************************/

	/**
	 * Return media site version string value for redirection URL but if media 
	 * site version is defined by `GET` query string param, return `NULL` and set 
	 * target media site version string into `GET` params to complete query 
	 * string params into redirect URL later. But if the target media site version 
	 * string is the same as full media site version (default value), unset this
	 * param from `GET` params array and return `NULL` in query string media 
	 * site version definition case.
	 * @param string $targetMediaSiteVersion Media site version string.
	 * @return string|NULL
	 */
	protected function redirectMediaGetUrlValueAndUnsetGet ($targetMediaSiteVersion) {
		$mediaVersionUrlParam = static::URL_PARAM_MEDIA_VERSION;
		if (isset($this->requestGlobalGet[$mediaVersionUrlParam])) {
			if ($targetMediaSiteVersion === static::MEDIA_VERSION_FULL) {
				unset($this->requestGlobalGet[$mediaVersionUrlParam]);
			} else {
				$this->requestGlobalGet[$mediaVersionUrlParam] = $targetMediaSiteVersion;
			}
			$targetMediaUrlValue = NULL;
		} else {
			$targetMediaUrlValue = $this->allowedMediaVersionsAndUrlValues[$targetMediaSiteVersion];
		}
		return $targetMediaUrlValue;
	}
}
