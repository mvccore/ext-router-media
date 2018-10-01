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

trait UrlCompletion
{
	/**
	 * Complete non-absolute, non-localized url by route instance reverse info.
	 * If there is key `media_version` in `$params`, unset this param before
	 * route url completing and choose by this param url prefix to prepend 
	 * completed url string.
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
	 *		`/application/base-path/m/products-list/cool-product-name/blue?variant[]=L&amp;variant[]=XL"`
	 * @param \MvcCore\Route|\MvcCore\IRoute &$route
	 * @param array $params
	 * @return string
	 */
	public function UrlByRoute (\MvcCore\IRoute & $route, & $params = []) {
		/** @var $route \MvcCore\Route */
		$requestedUrlParams = $this->GetRequestedUrlParams();
		
		$mediaVersionUrlParam = static::MEDIA_VERSION_URL_PARAM;
		
		if (isset($params[$mediaVersionUrlParam])) {
			$mediaSiteVersion = $params[$mediaVersionUrlParam];
			unset($params[$mediaVersionUrlParam]);
		} else if (isset($requestedUrlParams[$mediaVersionUrlParam])) {
			$mediaSiteVersion = $requestedUrlParams[$mediaVersionUrlParam];
			unset($requestedUrlParams[$mediaVersionUrlParam]);
		} else {
			$mediaSiteVersion = $this->mediaSiteVersion;
		}

		if ($this->stricModeBySession && $mediaSiteVersion !== $this->mediaSiteVersion) 
			$params[static::SWITCH_MEDIA_VERSION_URL_PARAM] = $mediaSiteVersion;

		if (isset($this->allowedSiteKeysAndUrlPrefixes[$mediaSiteVersion])) {
			$mediaSiteUrlPrefix = $this->allowedSiteKeysAndUrlPrefixes[$mediaSiteVersion];
		} else {
			$mediaSiteUrlPrefix = '';
			trigger_error(
				'['.__CLASS__.'] Not allowed media site version used to generate url: `'
				.$mediaSiteVersion.'`. Allowed values: `'
				.implode('`, `', array_keys($this->allowedSiteKeysAndUrlPrefixes)) . '`.',
				E_USER_ERROR
			);
		
		$result = $route->Url(
			$params, $requestedUrlParams, $this->getQueryStringParamsSepatator()
		);


		return $this->request->GetBasePath() 
			. $mediaSiteUrlPrefix 
			. $result;
	}
}
