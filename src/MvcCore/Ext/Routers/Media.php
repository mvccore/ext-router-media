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
	use \MvcCore\Ext\Routers\Media\UrlCompletion;
	
	/**
	 * Get singleton instance of `\MvcCore\Ext\Routers\Media` stored always 
	 * in parent class `MvcCore\Router::$instance` static property.
	 * Optionaly set routes as first argument.
	 * This method automaticly patch `\MvcCore\Application` with it's class name for router:
	 * `\MvcCore\Application::GetInstance()->SetRouterClass(get_called_class());`.
	 * @param \MvcCore\Route[]|\MvcCore\Interfaces\IRoute[]|array $routes Keyed array with routes,
	 *																	  keys are route names or route
	 *																	  `Controller::Action` definitions.
	 * @return \MvcCore\Ext\Routers\Media|\MvcCore\Ext\Routers\IMedia
	 */
	public static function & GetInstance (array $routes = []) {
		static::$application = \MvcCore\Application::GetInstance();
		static::$application->SetRouterClass(get_called_class()); // patch current router class in core
		$router = parent::GetInstance($routes);
		static::$application
			->AddPreRouteHandler(function (\MvcCore\Interfaces\IRequest & $request) use (& $router) {
				return $router->preRouteHandlerMedia($request);
			});
		return $router;
	}
}
