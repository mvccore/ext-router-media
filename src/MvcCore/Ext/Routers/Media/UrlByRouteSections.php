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

trait UrlByRouteSections
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
	 * @param string $urlParamRouteName
	 * @return string
	 */
	protected function urlByRouteSections (\MvcCore\IRoute & $route, array & $params = [], $urlParamRouteName = NULL) {
		/** @var $route \MvcCore\Route */
		$defaultParams = array_merge([], $this->defaultParams());
		if ($urlParamRouteName == 'self') 
			$params = array_merge($this->requestedParams, $params);

		// separate `$mediaSiteVersion` from `$params` to work with the version more specificly
		$mediaVersionUrlParam = static::URL_PARAM_MEDIA_VERSION;
		if (isset($params[$mediaVersionUrlParam])) {
			$mediaSiteVersion = $params[$mediaVersionUrlParam];
			unset($params[$mediaVersionUrlParam]);
		} else if (isset($defaultParams[$mediaVersionUrlParam])) {
			$mediaSiteVersion = $defaultParams[$mediaVersionUrlParam];
			unset($defaultParams[$mediaVersionUrlParam]);
		} else {
			$mediaSiteVersion = $this->mediaSiteVersion;
		}
		// add special switching param to global get, if strict session mode and target version is different
		if ($this->stricModeBySession && $mediaSiteVersion !== $this->mediaSiteVersion) 
			$params[static::URL_PARAM_SWITCH_MEDIA_VERSION] = $mediaSiteVersion;

		// get url version value from application value (only for allowed request types)
		if ($this->routeGetRequestsOnly && $route->GetMethod() !== \MvcCore\IRequest::METHOD_GET) {
			$mediaSiteUrlValue = '';
		} else if (isset($this->allowedSiteKeysAndUrlValues[$mediaSiteVersion])) {
			$mediaSiteUrlValue = $this->allowedSiteKeysAndUrlValues[$mediaSiteVersion];
		} else {
			$mediaSiteUrlValue = '';
			trigger_error(
				'['.__CLASS__.'] Not allowed media site version used to generate url: `'
				.$mediaSiteVersion.'`. Allowed values: `'
				.implode('`, `', array_keys($this->allowedSiteKeysAndUrlValues)) . '`.',
				E_USER_ERROR
			);
		}
		
		// complete by given route base url address part and part with path and query string
		list($resultBase, $resultPathWithQuery) = $route->Url(
			$this->request, $params, $this->GetDefaultParams(), $this->getQueryStringParamsSepatator()
		);
		
		// finalizing possible trailing slash after prefix
		if (
			// if trailing slash is not allowed
			$this->trailingSlashBehaviour === \MvcCore\IRouter::TRAILING_SLASH_REMOVE &&
			// if there is not full (empty) media prefix:
			$mediaSiteUrlValue !== ''
		) {
			$questionMarkPos = mb_strpos($resultPathWithQuery, '?');
			$resultPath = $questionMarkPos !== FALSE 
				? mb_substr($resultPathWithQuery, 0, $questionMarkPos)
				: $resultPathWithQuery;
			// if url targets homepage:
			if (trim($resultPath, '/') === '') 
				// trim result path and query url part from left like this:
				// `/?any=query&string=content`	=> `?any=query&string=content`
				// to complete address with prefix for mobiles without trailing slash like this:
				// `/m/?any=query&string=content`	=> `/m?any=query&string=content`
				$resultPathWithQuery = ltrim($resultPathWithQuery, '/');
		}
		
		$mediaSiteUrlPrefix = $mediaSiteUrlValue === '' ? '' : '/' . $mediaSiteUrlValue ;

		return [$resultBase, $mediaSiteUrlPrefix, $resultPathWithQuery];
	}
}
