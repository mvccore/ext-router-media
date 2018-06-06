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
class Media extends \MvcCore\Router implements \MvcCore\Ext\Routers\IMedia
{
	/*************************************************************************************
	 *                              Configurable Properties                              *
	 ************************************************************************************/

	/**
	 * Session expiration in seconds to remember previously detected media site version by 
	 * user agent from previous requests. To not recognize media site version by user agent 
	 * everytime, because it's time consuming. Default value is `0` - "until the browser is 
	 * closed". Session record is always used to compare, if user is requesting the same or 
	 * different media site version. If request by url is into the same media site version, 
	 * session record expiration is enlarged by this value. If request by url is into 
	 * current session place, session different media site version, then new different 
	 * media site version is stored in expiration is enlarged by this value and user is 
	 * redirected to different place. But if router is configured into session strict mode, 
	 * than to redirect user into new media site version, there is necesary to add special 
	 * url switch param: `&media_site_version=mobile` (always automaticly added by `Url()` 
	 * method). Because without it, user is redirected strictly back into the same media 
	 * version.
	 * @var int
	 */
	protected $sessionExpirationSeconds = 0;

	/**
	 * Url prefixes prepended before request url path to describe media site version in url.
	 * Keys are media site version values and values in array are url prefixes, how
	 * to describe media site version in url.
	 * Full version with possible empty string prefix is necessary to put as last item.
	 * If you do not want to use rewrite routes, just put under your alowed keys any values.
	 * @var array
	 */
	protected $allowedSiteKeysAndUrlPrefixes = [
		IMedia::MEDIA_VERSION_MOBILE	=> '/m',
		IMedia::MEDIA_VERSION_TABLET	=> '/t',
		IMedia::MEDIA_VERSION_FULL		=> '',
	];

	/**
	 * `TRUE` (default is `FALSE`) to prevent user to be able to switch media site version 
	 * only by requesting different url with different media site version prefix. If he does it
	 * and this configuration is `TRUE`, he is redirected back to his remembered media site 
	 * version by session. 
	 * but if you realy want to switch media site version for your users, you need to add into 
	 * url special param to switch the version: `&media_version=mobile`. But if you are 
	 * creating url in controller or in template, it's added automaticly, when you put into 
	 * second argument `$params` key with different media site version:
	 * `$this->Url('self', ['media_version' => 'mobile']);`.
	 * @var bool
	 */
	protected $stricModeBySession = FALSE;


	/*************************************************************************************
	 *                                Internal Properties                                *
	 ************************************************************************************/

	/**
	 * Reference to global `$_GET` array from request object.
	 * @var array
	 */
	protected $requestGlobalGet = [];
	
	/**
	 * Media site version to switch user into. 
	 * Default value is `NULL`. If there is any media site version 
	 * under key `media_version` in `$_GET` array, this properthy  
	 * has string with this value.
	 * @var string|NULL
	 */
	protected $mediaSiteVersionSwitchUriParam = NULL;

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
	 * Session namespace to store previously recognized media site version by user agent
	 * to not do this every time, because it's time consuming.
	 * @var \MvcCore\Session|\MvcCore\Interfaces\ISession|NULL
	 */
	protected $session = NULL;

	/**
	 * Reference to `\MvcCore\Application::GetInstance();` 
	 * to not call this very time we need app instance.
	 * @var \MvcCore\Application|\MvcCore\Interfaces\IApplication|NULL
	 */
	protected static $application = NULL;
	

	/*************************************************************************************
	 *                                  Public Methods                                   *
	 ************************************************************************************/

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
				return $router->preRouteHandler($request);
			});
		return $router;
	}

	/**
	 * Set session expiration in seconds to remember previously detected media site version by 
	 * user agent from previous requests. To not recognize media site version by user agent 
	 * everytime, because it's time consuming. Default value is `0` - "until the browser is 
	 * closed". Session record is always used to compare, if user is requesting the same or 
	 * different media site version. If request by url is into the same media site version, 
	 * session record expiration is enlarged by this value. If request by url is into 
	 * current session place, session different media site version, then new different 
	 * media site version is stored in expiration is enlarged by this value and user is 
	 * redirected to different place. But if router is configured into session strict mode, 
	 * than to redirect user into new media site version, there is necesary to add special 
	 * url switch param: `&media_version=mobile` (always automaticly added by `Url()` 
	 * method). Because without it, user is redirected strictly back into the same media 
	 * version.
	 * @param int $sessionExpirationSeconds
	 * @return \MvcCore\Ext\Routers\Media|\MvcCore\Ext\Routers\IMedia
	 */
	public function & SetSessionExpirationSeconds ($sessionExpirationSeconds = 0) {
		$this->sessionExpirationSeconds = $sessionExpirationSeconds;
		return $this;
	}

	/**
	 * Set url prefixes prepended before request url path to describe media site version in url.
	 * Keys are media site version values and values in array are url prefixes, how
	 * to describe media site version in url.
	 * Full version with possible empty string prefix is necessary to put as last item.
	 * If you do not want to use rewrite routes, just put under your alowed keys any values.
	 * Example: 
	 * ```
	 * \MvcCore\Ext\Routers\Media::GetInstance()->SetAllowedSiteKeysAndUrlPrefixes([
	 *		'mobile'	=> '/m',// to have `/m` substring in every mobile url begin.
	 *		'full'		=> '',	// to have nothing extra in url for full site version.
	 * ]);
	 * ```
	 * @param array $allowedSiteKeysAndUrlPrefixes
	 * @return \MvcCore\Ext\Routers\Media|\MvcCore\Ext\Routers\IMedia
	 */
	public function & SetAllowedSiteKeysAndUrlPrefixes ($allowedSiteKeysAndUrlPrefixes = []) {
		$this->allowedSiteKeysAndUrlPrefixes = $allowedSiteKeysAndUrlPrefixes;
		return $this;
	}

	/**
	 * Set `TRUE` (default is `FALSE`) to prevent user to be able to switch media site version 
	 * only by requesting different url with different media site version prefix. If he does it
	 * and this configuration is `TRUE`, he is redirected back to his remembered media site 
	 * version by session. 
	 * but if you realy want to switch media site version for your users, you need to add into 
	 * url special param to switch the version: `&media_version=mobile`. But if you are 
	 * creating url in controller or in template, it's added automaticly, when you put into 
	 * second argument `$params` key with different media site version:
	 * `$this->Url('self', ['media_version' => 'mobile']);`.
	 * @param bool $stricModeBySession
	 * @return \MvcCore\Ext\Routers\Media|\MvcCore\Ext\Routers\IMedia
	 */
	public function SetStricModeBySession ($stricModeBySession = TRUE) {
		$this->stricModeBySession = $stricModeBySession;
		return $this;
	}
	
	/**
	 * Complete non-absolute, non-localized url by route instance reverse info.
	 * If there is key `mediaSiteVersion` in `$params`, unset this param before
	 * route url completing and choose by this param url prefix to prepend 
	 * into completed url string.
	 * Example:
	 *	Input (`\MvcCore\Route::$reverse`):
	 *		`"/products-list/<name>/<color>"`
	 *	Input ($params):
	 *		`array(
	 *			"name"			=> "cool-product-name",
	 *			"color"			=> "red",
	 *			"variant"		=> array("L", "XL"),
	 *			"mediaSiteVersion"	=> "mobile",
	 *		);`
	 *	Output:
	 *		`/application/base-bath/m/products-list/cool-product-name/blue?variant[]=L&amp;variant[]=XL"`
	 * @param \MvcCore\Route|\MvcCore\Interfaces\IRoute &$route
	 * @param array $params
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function UrlByRoute (\MvcCore\Interfaces\IRoute & $route, & $params = []) {
		/** @var $route \MvcCore\Route */
		$requestedUrlParams = $this->GetRequestedUrlParams();
		$mediaSiteVersion = NULL;
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
		if ($this->stricModeBySession && $mediaSiteVersion !== $this->mediaSiteVersion) {
			$sessStrictModeSwitchUrlParam = static::SWITCH_MEDIA_VERSION_URL_PARAM;
			$params[$sessStrictModeSwitchUrlParam] = $mediaSiteVersion;
		}
		$routeUrl = $route->Url(
			$params, $requestedUrlParams, $this->getQueryStringParamsSepatator()
		);
		$mediaSiteUrlPrefix = '';
		if ($mediaSiteVersion) {
			if (isset($this->allowedSiteKeysAndUrlPrefixes[$mediaSiteVersion])) {
				$mediaSiteUrlPrefix = $this->allowedSiteKeysAndUrlPrefixes[$mediaSiteVersion];
			} else {
				throw new \InvalidArgumentException(
					'['.__CLASS__.'] Not allowed media site version used to generate url: `'
					.$mediaSiteVersion.'`. Allowed values: `'
					.implode('`, `', array_keys($this->allowedSiteKeysAndUrlPrefixes)) . '`.'
				);
			}
		}
		return $this->request->GetBasePath() 
			. $mediaSiteUrlPrefix 
			. $routeUrl;
	}
	

	/*************************************************************************************
	 *                                 Protected Methods                                 *
	 ************************************************************************************/

	/**
	 * Process media site version before request is routed by `\MvcCore\Router`.
	 * Prepare:
	 * - Media site version from requested url.
	 * - Media site version from session initialized by any previous request.
	 * - Detect if there is any special media site switching parameter in 
	 *   request object global array `$_GET` with name by 
	 *   `static::SWITCH_MEDIA_VERSION_URL_PARAM`.
	 * Than process for every request::
	 * - If there is special media site switching param in request object global
	 *   array `$_GET` and it's value is allowed as media site version:
	 *	 - Switch media site version in session by given switching param value 
	 *     and redirect to the same page with new media site version substring 
	 *     in url or with new media site version query string param.
	 * - If there is no media site version in session or if media site version 
	 *   from sesson is not allowed:
	 *   - Recognize media site version by `\Mobile_Detect` third party library 
	 *     and store recognized version in this context and in session namespace.
	 * - Else set up media site version from session into this context
	 * - Later - if detected media site version is not the same as requested 
	 *   media site version - redirect to detected version in this context.
	 * @param \MvcCore\Request|\MvcCore\Interfaces\IRequest &$request
	 * @return bool
	 */
	protected function preRouteHandler (\MvcCore\Interfaces\IRequest & $request) {
		$result = TRUE;
		/** @var $request \MvcCore\Request */
		$this->request = & $request;
		$request->SetOriginalPath($request->GetPath());
		
		// switching media site version will be only by get:
		$this->isGet = $request->GetMethod() == \MvcCore\Interfaces\IRequest::METHOD_GET;
		
		// look into request params if are we just switching any new site media version
		$this->requestGlobalGet = array_merge([], $request->GetGlobalCollection('get')); // clone `$_GET`
		
		//if ($this->stricModeBySession) { // check it with any strict session configuration to have more flexible navigations
			$sessStrictModeSwitchUrlParam = static::SWITCH_MEDIA_VERSION_URL_PARAM;
			if (isset($this->requestGlobalGet[$sessStrictModeSwitchUrlParam])) {
				$this->mediaSiteVersionSwitchUriParam = strtolower($this->requestGlobalGet[$sessStrictModeSwitchUrlParam]);
			}
		//}
		
		// set up current media site version from url string
		$this->setUpRequestMediaSiteVersionFromUrl();
		
		// Set up session object to look inside for something from previous requests. 
		// This command starts the session if not started yet.
		$this->setUpSession();
		
		// look into session object if there are or not any record about recognized device from previous request:
		$mediaVersionUrlParam = static::MEDIA_VERSION_URL_PARAM;
		if (isset($this->session->{$mediaVersionUrlParam})) {
			$this->sessionMediaSiteVersion = $this->session->{$mediaVersionUrlParam};
		}
		
		if (
			$this->isGet &&
			$this->mediaSiteVersionSwitchUriParam !== NULL &&
			isset($this->allowedSiteKeysAndUrlPrefixes[$this->mediaSiteVersionSwitchUriParam])
		) {
			// if there is detected in requested url media site version switching param,
			// store switching param value in session, remove param from `$_GET` 
			// and redirect to the same page with new media site version:
			$result = $this->manageSwitchingAndRedirect();

		} else if (
			$this->isGet && (
				$this->sessionMediaSiteVersion === NULL ||
				!isset($this->allowedSiteKeysAndUrlPrefixes[$this->sessionMediaSiteVersion])
			)
		) {
			// if there is no session record about media site version:
			$this->manageDetectionAndStoreInSession();
			// check if media site version is the same as local media site version:
			$result = $this->checkMediaSiteVersionWithRequestVersionAndRedirectIfDifferent();

		} else {
			// if there is media site version in session already:
			$this->mediaSiteVersion = $this->sessionMediaSiteVersion;
			// check if media site version is the same as local media site version:
			$result = $this->checkMediaSiteVersionWithRequestVersionAndRedirectIfDifferent();
		}

		// set up stored/detected media site version into request:
		$request->SetMediaSiteVersion($this->mediaSiteVersion);
		
		// return `TRUE` or `FALSE` to break or not preroute handlers queue dispatching:
		return $result;
	}

	/**
	 * Store new media site version from url in session namespace, remove 
	 * media site version switching param from request object global collection 
	 * `$_GET` and redirect to the same page without switching param.
	 * @return bool
	 */
	protected function manageSwitchingAndRedirect () {
		$mediaSiteVersion = $this->mediaSiteVersionSwitchUriParam;
		// store switched site key into session
		$mediaVersionUrlParam = static::MEDIA_VERSION_URL_PARAM;
		$this->session->{$mediaVersionUrlParam} = $mediaSiteVersion;
		$sessStrictModeSwitchUrlParam = static::SWITCH_MEDIA_VERSION_URL_PARAM;
		unset($this->requestGlobalGet[$sessStrictModeSwitchUrlParam]);
		// unset site key switch param and redirect to no switch param uri version
		$request = & $this->request;
		if ($this->anyRoutesConfigured) {
			$targetUrl = $request->GetBaseUrl()
				. $this->allowedSiteKeysAndUrlPrefixes[$mediaSiteVersion] 
				. $request->GetPath();
		} else {
			$targetUrl = $request->GetBaseUrl();
			if ($mediaSiteVersion === static::MEDIA_VERSION_FULL) {
				if (isset($this->requestGlobalGet[$mediaVersionUrlParam]))
					unset($this->requestGlobalGet[$mediaVersionUrlParam]);
			} else {
				$this->requestGlobalGet[$mediaVersionUrlParam] = $mediaSiteVersion;
			}
			$this->removeDefaultControllerAndActionFromGlobalGet();
			if ($this->requestGlobalGet)
				$targetUrl .= $request->GetScriptName();
		}
		if ($this->requestGlobalGet) {
			$amp = $this->getQueryStringParamsSepatator();
			$targetUrl .= '?' . http_build_query($this->requestGlobalGet, '', $amp);
		}
		$this->redirect($targetUrl, \MvcCore\Interfaces\IResponse::SEE_OTHER);
		return FALSE;
	}

	/**
	 * Detect media site version by sended user agent string by 
	 * third party `\Mobile_Detect` library and store detected result 
	 * in session namespace for next requests.
	 * @return void
	 */
	protected function manageDetectionAndStoreInSession() {
		$detect = new \Mobile_Detect();
		$mediaSiteVersions = array_keys($this->allowedSiteKeysAndUrlPrefixes);
		if ($detect->isMobile()) {
			$this->mediaSiteVersion = $mediaSiteVersions[0];
		} else if ($detect->isTablet()) {
			$this->mediaSiteVersion = $mediaSiteVersions[1];
		} else {
			$this->mediaSiteVersion = $mediaSiteVersions[2];
		}
		$mediaVersionUrlParam = static::MEDIA_VERSION_URL_PARAM;
		$this->session->{$mediaVersionUrlParam} = $this->mediaSiteVersion;
	}

	/**
	 * If local media site version is the same as requested - do not process 
	 * any redirections. If local media site version is different version than 
	 * version in requested url (local media site version is completed previously 
	 * from session or by `\Mobile_Detect` library), then  - if strict mode by 
	 * session is configured as `TRUE` - redirect to local media site version. 
	 * If it's configured as `FALSE`, redirect to requested media site version.
	 * @return bool
	 */
	protected function checkMediaSiteVersionWithRequestVersionAndRedirectIfDifferent() {
		$request = & $this->request;
		$requestMediaSiteVersion = $request->GetMediaSiteVersion();
		$sessionOrDetectionSameWithRequest = $this->mediaSiteVersion === $requestMediaSiteVersion;
		if (!$sessionOrDetectionSameWithRequest) {
			// if requested media site version is not the same as version in session 
			// fix it by `$this->stricModeBySession` configuration:
			$mediaVersionUrlParam = static::MEDIA_VERSION_URL_PARAM;
			if ($this->stricModeBySession && $this->isGet) {
				// redirect back to `$this->mediaSiteVersion` by session
				$targetMediaSiteVersion = $this->mediaSiteVersion;
			} else {
				// redirect to requested version by `$requestMediaSiteVersion`:
				$targetMediaSiteVersion = $requestMediaSiteVersion;
			}
			// store the right media site version in session
			$this->session->{$mediaVersionUrlParam} = $targetMediaSiteVersion;
			$this->mediaSiteVersion = $targetMediaSiteVersion;
			// complete new url to redirect into
			if ($this->anyRoutesConfigured) {
				$targetUrl = $request->GetBaseUrl()
					. $this->allowedSiteKeysAndUrlPrefixes[$targetMediaSiteVersion] 
					. $request->GetPath()
					. $request->GetQuery(TRUE);
			} else {
				$targetUrl = $request->GetBaseUrl();
				if ($targetMediaSiteVersion === static::MEDIA_VERSION_FULL) {
					if (isset($this->requestGlobalGet[$mediaVersionUrlParam]))
						unset($this->requestGlobalGet[$mediaVersionUrlParam]);
				} else {
					$this->requestGlobalGet[$mediaVersionUrlParam] = $targetMediaSiteVersion;
				}
				$this->removeDefaultControllerAndActionFromGlobalGet();
				if ($this->requestGlobalGet) {
					$targetUrl .= $request->GetScriptName();
					$amp = $this->getQueryStringParamsSepatator();
					$targetUrl .= '?' . http_build_query($this->requestGlobalGet, '', $amp);
				}
			}
			// redirect
			$this->redirect(
				$targetUrl, 
				\MvcCore\Interfaces\IResponse::SEE_OTHER
			);
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * If local request object global collection `$_GET` contains any items
	 * and if controller and action in collection have the same values as 
	 * default controller and action values, unset them from request global 
	 * `$_GET` collection.
	 * @return void
	 */
	protected function removeDefaultControllerAndActionFromGlobalGet () {
		if ($this->requestGlobalGet) {
			$app = \MvcCore\Application::GetInstance();
			$toolClass = $app->GetToolClass();
			list($dfltCtrlPc, $dftlActionPc) = $app->GetDefaultControllerAndActionNames();
			$dfltCtrlDc = $toolClass::GetDashedFromPascalCase($dfltCtrlPc);
			$dftlActionDc = $toolClass::GetDashedFromPascalCase($dftlActionPc);
			if (isset($this->requestGlobalGet['controller']) && isset($this->requestGlobalGet['action']))
				if ($this->requestGlobalGet['controller'] == $dfltCtrlDc && $this->requestGlobalGet['action'] == $dftlActionDc)
					unset($this->requestGlobalGet['controller'], $this->requestGlobalGet['action']);
		}
	}

	/**
	 * If session namespace by this class is not initialized,
	 * initialize session namespace under this class name and 
	 * move expiration to configured value.
	 * @return void
	 */
	protected function setUpSession () {
		if ($this->session === NULL) {
			$sessionClass = static::$application->GetSessionClass();
			$this->session = $sessionClass::GetNamespace(__CLASS__);
			$this->session->SetExpirationSeconds($this->sessionExpirationSeconds);
		}
	}

	/**
	 * Try to set up into `\MvcCore\Request` object new 
	 * media site version detected from requested url by url path prefix
	 * or by url query string param. If there is catched any valid media site 
	 * version - set this value into request object. If there is nothing catched,
	 * set into request object full media site version anyway.
	 * @return void
	 */
	protected function setUpRequestMediaSiteVersionFromUrl () {
		if ($this->routes) {
			$requestPath = $this->request->GetPath(TRUE);
			$mediaSiteVersionCatchedInPath = FALSE;
			foreach ($this->allowedSiteKeysAndUrlPrefixes as $mediaSiteVersion => $requestPathPrefix) {
				if (mb_strpos($requestPath, $requestPathPrefix . '/') === 0) {
					$mediaSiteVersionCatchedInPath = TRUE;
					$this->request
						->SetMediaSiteVersion($mediaSiteVersion)
						->SetPath(mb_substr($requestPath, strlen($requestPathPrefix)));
					break;
				}
			}
			if (!$mediaSiteVersionCatchedInPath) 
				$this->request->SetMediaSiteVersion(static::MEDIA_VERSION_FULL);
		} else {
			$requestMediaVersion = $this->request->GetParam(self::MEDIA_VERSION_URL_PARAM, 'a-zA-Z');
			if ($requestMediaVersion) $requestMediaVersion = strtolower($requestMediaVersion);
			if (isset($this->allowedSiteKeysAndUrlPrefixes[$requestMediaVersion])) {
				$mediaSiteVersion = $requestMediaVersion;
			} else {
				$mediaSiteVersion = static::MEDIA_VERSION_FULL;
			}
			$this->request->SetMediaSiteVersion($mediaSiteVersion);
		}
	}
}
