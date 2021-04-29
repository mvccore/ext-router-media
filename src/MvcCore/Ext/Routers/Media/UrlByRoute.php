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

/**
 * @mixin \MvcCore\Ext\Routers\Media
 */
trait UrlByRoute {

	/**
	 * Complete non-absolute, url by route instance reverse info with special 
	 * media type prefix or without the prefix. If there is key `media_version` 
	 * in `$params`, unset this param before route URL completing and choose by 
	 * this param url prefix to prepend completed URL string.
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
	 * @param \MvcCore\Route &$route
	 * @param array $params
	 * @param string $urlParamRouteName
	 * @return string
	 */
	public function UrlByRoute (\MvcCore\IRoute $route, array & $params = [], $urlParamRouteName = NULL) {
		// get domain with base path url section, 
		// path with query string url section 
		// and system params for url prefixes
		list($urlBaseSection, $urlPathWithQuerySection, $systemParams) = $this->urlByRouteSections(
			$route, $params, $urlParamRouteName
		);

		// create prefixed url
		return $this->urlByRoutePrefixSystemParams(
			$urlBaseSection, $urlPathWithQuerySection, $systemParams
		);
	}
}
