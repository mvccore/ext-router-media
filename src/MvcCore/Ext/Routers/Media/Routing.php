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

trait Routing
{
	/**
	 * Route current application request by configured routes list or by query string data.
	 * - Set up before routing media site version from previous request (from session)
	 *   or try to recognize media site version by `User-Agent` http header. If requested
	 *   version is different than recognized version (or also for more conditions by 
	 *   configuration), redirect user to proper media website version and route there again.
	 * - If there is strictly defined `controller` and `action` value in query string,
	 *   route request by given values, add new route and complete new empty
	 *   `\MvcCore\Router::$currentRoute` route with `controller` and `action` values from query string.
	 * - If there is no strictly defined `controller` and `action` value in query string,
	 *   go throught all configured routes and try to find matching route:
	 *   - If there is catched any matching route:
	 *	 - Set up `\MvcCore\Router::$currentRoute`.
	 *	 - Reset `\MvcCore\Request::$params` again with with default route params,
	 *	   with request params itself and with params parsed from matching process.
	 * - If there is no route matching the request and also if the request is targeting homepage
	 *   or there is no route matching the request and also if the request is targeting something
	 *   else and also router is configured to route to default controller and action if no route
	 *   founded, complete `\MvcCore\Router::$currentRoute` with new empty automaticly created route
	 *   targeting default controller and action by configuration in application instance (`Index:Index`)
	 *   and route type create by configured `\MvcCore\Application::$routeClass` class name.
	 * - Return `TRUE` if routing has no redirection or `FALSE` for redirection.
	 *
	 * This method is always called from core routing by:
	 * - `\MvcCore\Application::Run();` => `\MvcCore\Application::routeRequest();`.
	 * @return bool
	 */
	public function Route () {
		$this->internalRequest = $this->request->IsInternalRequest();
		if (!$this->internalRequest && !$this->routeByQueryString)
			if (!$this->redirectToProperTrailingSlashIfNecessary()) return FALSE;
		list($requestCtrlName, $requestActionName) = $this->routeDetectStrategy();
		if (!$this->internalRequest) {
			$this->prepare();
			$this->prepareMedia();
			if (!$this->preRouteMedia()) return FALSE;
		}
		if ($this->routeByQueryString) {
			$this->queryStringRouting($requestCtrlName, $requestActionName);
		} else {
			$this->rewriteRouting($requestCtrlName, $requestActionName);
		}
		if (!$this->routeProcessRouteRedirectionIfAny()) return FALSE;
		return $this->routeSetUpDefaultForHomeIfNoMatch()
					->routeSetUpSelfRouteNameIfAny()
					->canonicalRedirectIfAny();
					
	}
}
