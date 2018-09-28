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
 */
class		Media 
extends		\MvcCore\Router
implements	\MvcCore\Ext\Routers\IMedia,
			\MvcCore\Ext\Routers\IExtended
{
	use \MvcCore\Ext\Routers\Extended;
	use \MvcCore\Ext\Routers\Media\PropsGettersSetters;
	use \MvcCore\Ext\Routers\Media\Routing;
	use \MvcCore\Ext\Routers\Media\Redirecting;
	use \MvcCore\Ext\Routers\Media\UrlCompletion;
	
	/**
	 * MvcCore Extension - Router Media - version:
	 * Comparation by PHP function version_compare();
	 * @see http://php.net/manual/en/function.version-compare.php
	 */
	const VERSION = '5.0.0-alpha';

	/**
	 * @return bool
	 */
	public function Route () {
		if (!$this->redirectToProperTrailingSlashIfNecessary()) return FALSE;
		$request = & $this->request;
		$requestCtrlName = $request->GetControllerName();
		$requestActionName = $request->GetActionName();
		$this->anyRoutesConfigured = count($this->routes) > 0;
		$this->preRoutePrepare();
		if (!$this->preRoutePrepareMedia()) return FALSE;
		if (!$this->preRouteMedia()) return FALSE;
		if ($requestCtrlName && $requestActionName) {
			$this->routeByControllerAndActionQueryString($requestCtrlName, $requestActionName);
		} else {
			$this->routeByRewriteRoutes($requestCtrlName, $requestActionName);
		}
		if ($this->currentRoute === NULL && (
			($request->GetPath() == '/' || $request->GetPath() == $request->GetScriptName()) ||
			$this->routeToDefaultIfNotMatch
		)) {
			list($dfltCtrl, $dftlAction) = $this->application->GetDefaultControllerAndActionNames();
			$this->SetOrCreateDefaultRouteAsCurrent(
				\MvcCore\IRouter::DEFAULT_ROUTE_NAME, $dfltCtrl, $dftlAction
			);
		}
		return $this->currentRoute instanceof \MvcCore\IRoute;
	}
}
