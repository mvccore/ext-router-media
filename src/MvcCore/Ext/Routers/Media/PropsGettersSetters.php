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

namespace MvcCore\Ext\Routers\Media;

use MvcCore\Ext\Routers;

trait PropsGettersSetters
{
	/*************************************************************************************
	 *                              Configurable Properties                              *
	 ************************************************************************************/

	/**
	 * Url prefixes prepended before request url path to describe media site version in url.
	 * Keys are media site version values and values in array are url prefixes, how
	 * to describe media site version in url.
	 * Full version with possible empty string prefix is necessary to put as last item.
	 * If you do not want to use rewrite routes, just put under your alowed keys any values.
	 * @var array
	 */
	protected $allowedSiteKeysAndUrlValues = [
		Routers\IMedia::MEDIA_VERSION_MOBILE	=> 'm',
		Routers\IMedia::MEDIA_VERSION_TABLET	=> 't',
		Routers\IMedia::MEDIA_VERSION_FULL		=> '',
	];


	/*************************************************************************************
	 *                                Internal Properties                                *
	 ************************************************************************************/
	
	/**
	 * Media site version to switch user into. 
	 * Default value is `NULL`. If there is any media site version 
	 * under key `media_version` in `$_GET` array, this properthy  
	 * has string with this value.
	 * @var string|NULL
	 */
	protected $switchUriParamMediaSiteVersion = NULL;

	/**
	 * Finally resolved media site version, used in `\MvcCore\Request` 
	 * object and possible to use in controller or view.
	 * Possible values are always: `"full" | "tablet" | "mobile"`.
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
	
	
	/*************************************************************************************
	 *                                  Public Methods                                   *
	 ************************************************************************************/

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
	public function & GetAllowedSiteKeysAndUrlValues () {
		return $this->allowedSiteKeysAndUrlValues;
	}

	/**
	 * Set url prefixes prepended before request url path to describe media site version in url.
	 * Keys are media site version values and values in array are url prefixes, how
	 * to describe media site version in url.
	 * Full version with possible empty string prefix is necessary to put as last item.
	 * If you do not want to use rewrite routes, just put under your alowed keys any values.
	 * Example: 
	 * ```
	 * \MvcCore\Ext\Routers\Media::GetInstance()->SetAllowedSiteKeysAndUrlValues([
	 *		'mobile'	=> 'm', // to have `/m` substring in every mobile url begin.
	 *		'full'		=> '',	// to have nothing extra in url for full site version.
	 * ]);
	 * ```
	 * @param array $allowedSiteKeysAndUrlValues
	 * @return \MvcCore\Ext\Routers\Media|\MvcCore\Ext\Routers\IMedia
	 */
	public function & SetAllowedSiteKeysAndUrlValues ($allowedSiteKeysAndUrlValues = []) {
		$this->allowedSiteKeysAndUrlValues = $allowedSiteKeysAndUrlValues;
		return $this;
	}
	

	/*************************************************************************************
	 *                                  Protected Methods                                *
	 ************************************************************************************/

	// TODO: provizorní
	protected function redirectMediaGetPrefixAndUnsetGet ($targetMediaSiteVersion) {
		$mediaVersionUrlParam = static::URL_PARAM_MEDIA_VERSION;
		if (isset($this->requestGlobalGet[$mediaVersionUrlParam])) {
			if ($targetMediaSiteVersion === static::MEDIA_VERSION_FULL) {
				unset($this->requestGlobalGet[$mediaVersionUrlParam]);
			} else {
				$this->requestGlobalGet[$mediaVersionUrlParam] = $targetMediaSiteVersion;
			}
			$targetMediaUrlValue = NULL;
		} else {
			$targetMediaUrlValue = $this->allowedSiteKeysAndUrlValues[$targetMediaSiteVersion];
		}
		return $targetMediaUrlValue;
	}
}
