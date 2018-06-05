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

class Media extends \MvcCore\Router implements \MvcCore\Ext\Routers\IMedia
{
	/**
	 * Session expiration seconds for remembering detected media site version by user agent.
	 * Session record is always used to compare if user is requesting different media
	 * site version then he has in session - if there is difference - user is redirected
	 * to session media site version and this seconds is time to remember that sessio record
	 * for described redirection.
	 * @var int
	 */
	protected $sessionExpirationSeconds = 3600; // hour

	/**
	 * Url prefixes prepended before url paths to describe media site version in url.
	 * Key is media site version value and value in array is url prefix how
	 * to describe media site version in url.
	 * @var array
	 */
	protected $allowedSiteKeysAndUrlPrefixes = [
		IMedia::MEDIA_SITE_KEY_MOBILE	=> '/m',
		IMedia::MEDIA_SITE_KEY_TABLET	=> '/t',
		IMedia::MEDIA_SITE_KEY_FULL		=> '',
	];

	/**
	 * If true, process media site version strictly by session stored version,
	 * so if request contains some version and in session is different, redirect
	 * user to session version value adress, only when media site switching param
	 * is contained in $_GET, switch the version in session.
	 * If false, process media site version more benevolently, so if request
	 * contains some version and in session is different, store in session media
	 * site version from request and do not redirect user.
	 * @var bool
	 */
	protected $stricModeBySession = FALSE;

	/**
	 * Reference to global `$_GET` array in request object.
	 * @var array
	 */
	protected $requestGlobalGet = [];
	
	/**
	 * Media site version for switching, always initialized by special switching $_GET param.
	 * @var string|NULL
	 */
	protected $mediaSiteKeySwitchUriParam = NULL;

	/**
	 * Final media site key used in \MvcCore\Request object
	 * @var string
	 */
	protected $mediaSiteKey = NULL;

	/**
	 * Media site version founded in session.
	 * @var string
	 */
	protected $sessionMediaSiteKey = NULL;

	/**
	 * Session record is always used to compare if user is requesting different media
	 * site version then he has in session - if there is difference - user is redirected
	 * to session media site version
	 * @var \MvcCore\Session|\stdClass
	 */
	protected $session = NULL;

	/**
	 * Reference to `\MvcCore\Application::GetInstance();`.
	 * @var \MvcCore\Interfaces\IApplication
	 */
	protected static $application = NULL;

	/**
	 * Static initialization - called when class is included by autoloader
	 * @return void
	 */
	public static function & GetInstance ($routes = []) {
		$router = parent::GetInstance($routes);
		static::$application = \MvcCore\Application::GetInstance();
		static::$application
			->AddPreRouteHandler($router->preRouteHandler)
			->AddPreDispatchHandler($router->preDispatchHandler);
		return $router;
	}

	/**
	 * Set session expiration seconds for remembering detected media site version by user agent.
	 * Session record is always used to compare if user is requesting different media
	 * site version then he has in session - if there is difference - user is redirected
	 * to session media site version and this seconds is time to remember that sessio record
	 * for described redirection.
	 * @param int $sessionExpirationSeconds
	 * @return \MvcCore\Ext\Routers\Media
	 */
	public function & SetSessionExpirationSeconds ($sessionExpirationSeconds = 3600) {
		$this->sessionExpirationSeconds = $sessionExpirationSeconds;
		return $this;
	}

	/**
	 * Set url prefixes prepended before url paths to describe media site version in url
	 * and by defined keys in given array - set only allowed media versions to work with.
	 * Key is media site version value and value is url prefix how to describe
	 * media site version in url.
	 * @param array $allowedSiteKeysAndUrlPrefixes
	 * @return \MvcCore\Ext\Routers\Media
	 */
	public function & SetAllowedSiteKeysAndUrlPrefixes ($allowedSiteKeysAndUrlPrefixes = []) {
		$this->allowedSiteKeysAndUrlPrefixes = $allowedSiteKeysAndUrlPrefixes;
		return $this;
	}

	/**
	 * Set session strict mode.
	 * If true, process media site version strictly by session stored version,
	 * so if request contains some version and in session is different, redirect
	 * user to session version value adress, only when media site switching param
	 * is contained in $_GET, switch the version in session.
	 * If false, process media site version more benevolently, so if request
	 * contains some version and in session is different, store in session media
	 * site version from request and do not redirect user.
	 * @param bool $stricModeBySession
	 * @return \MvcCore\Ext\Routers\Media
	 */
	public function SetStricModeBySession ($stricModeBySession = TRUE) {
		$this->stricModeBySession = $stricModeBySession;
		return $this;
	}

	/**
	 * Complete url by route instance reverse info
	 * @param \MvcCore\Route|\MvcCore\Interfaces\IRoute &$route
	 * @param array $params
	 * @return string
	 */
	public function UrlByRoute (\MvcCore\Interfaces\IRoute & $route, & $params = []) {
		/** @var $route \MvcCore\Route */
		$cleanedRequestParams = $this->GetCleanedRequestParams();
		$mediaSiteKey = '';
		if (isset($params[static::MEDIA_SITE_KEY_URL_PARAM])) {
			$mediaSiteKey = $params[static::MEDIA_SITE_KEY_URL_PARAM];
			unset($params[static::MEDIA_SITE_KEY_URL_PARAM]);
		} else if (isset($cleanedRequestParams[static::MEDIA_SITE_KEY_URL_PARAM])) {
			$mediaSiteKey = $cleanedRequestParams[static::MEDIA_SITE_KEY_URL_PARAM];
			unset($cleanedRequestParams[static::MEDIA_SITE_KEY_URL_PARAM]);
		} else {
			$mediaSiteKey = $this->mediaSiteKey;
		}
		$routeUrl = $route->Url(
			$params, $cleanedRequestParams, $this->getQueryStringParamsSepatator()
		);
		$mediaSiteUrlPrefix  = $mediaSiteKey && isset($this->allowedSiteKeysAndUrlPrefixes[$mediaSiteKey])
			? $this->allowedSiteKeysAndUrlPrefixes[$mediaSiteKey]
			: '';
		return $this->request->GetBasePath() 
			. $mediaSiteUrlPrefix 
			. $routeUrl;
	}

	/**
	 * Process media site version before request is routed by \MvcCore\Router
	 * Prepare:
	 * - media version from request
	 * - media version from session (setted by any previous request)
	 * - detect if there is any special media site switching parameter in $_GET (static::MEDIA_SITE_KEY_SWITCH_URL_PARAM)
	 * For each GET request - do:
	 * - if there is special media site switching param in request $_GET and it's allowed
	 *	 - switch media site version in session by it and redirect to the same url with new media site version substring in url
	 * - if there is no media site version in session or if media site version from sesson is not allowed
	 *   - recognize media site version by Mobile_Detect third party library and store recognized version in this context and in session
	 * - else set up media site version from session
	 * - later if detected media site version is not the same as requested media site version - redirect to detected version
	 * @param \MvcCore\Request|\MvcCore\Interfaces\IRequest &$request
	 * @param \MvcCore\Response|\MvcCore\Interfaces\IResponse & $response
	 * @return void
	 */
	protected function preRouteHandler (
		\MvcCore\Interfaces\IRequest & $request, 
		\MvcCore\Interfaces\IResponse & $response
	) {
		/** @var $request \MvcCore\Request */
		$this->request = & $request;
		$request->SetOriginalPath($request->GetPath());
		// switching media site version will be only by get:
		$this->isGet = $request->GetMethod() == \MvcCore\Interfaces\IRequest::METHOD_GET;
		// look into request params if are we just switching any new site media version
		$this->requestGlobalGet = & $request->GetGlobalCollection('get');
		if (isset($this->requestGlobalGet[static::MEDIA_SITE_KEY_SWITCH_URL_PARAM])) {
			$this->mediaSiteKeySwitchUriParam = strtolower($this->requestGlobalGet[static::MEDIA_SITE_KEY_SWITCH_URL_PARAM]);
		}
		// set up current media site version from url string
		$this->setUpRequestMediaSiteKeyFromUrl();
		// Set up session object to look inside for something from previous requests. 
		// This command starts the session if not started yet.
		static::setUpSession();
		// look into session object if there are or not any record about recognized device from previous request
		if (isset($this->session->{static::MEDIA_SITE_KEY_URL_PARAM})) {
			$this->sessionMediaSiteKey = $this->session->{static::MEDIA_SITE_KEY_URL_PARAM};
		}

		if (
			$this->isGet &&
			$this->mediaSiteKeySwitchUriParam !== NULL &&
			isset($this->allowedSiteKeysAndUrlPrefixes[$this->mediaSiteKeySwitchUriParam])
		) {
			$this->manageSwitchingAndRedirect();

		} else if (
			$this->isGet &&
			(
				$this->sessionMediaSiteKey === NULL ||
				!isset($this->allowedSiteKeysAndUrlPrefixes[$this->sessionMediaSiteKey])
			)
		) {
			$this->manageDetectionAndStoreInSession();
			$this->checkMediaSiteVersionWithRequestVersionAndRedirectIfDifferent();

		} else {
			$this->mediaSiteKey = $this->sessionMediaSiteKey;
			$this->checkMediaSiteVersionWithRequestVersionAndRedirectIfDifferent();
		}

		$request->SetMediaSiteKey($this->mediaSiteKey);
	}

	/**
	 * @param \MvcCore\Request|\MvcCore\Interfaces\IRequest &$request
	 * @param \MvcCore\Response|\MvcCore\Interfaces\IResponse & $response
	 * @return void
	 */
	protected function preDispatchHandler (
		\MvcCore\Interfaces\IRequest & $request, 
		\MvcCore\Interfaces\IResponse & $response
	) {
		static::$application->GetController()->mediaSiteKey = $this->mediaSiteKey;
	}

	/**
	 * Store new media site key from url into session,
	 * remove it from $_GET, redirect to the same url as reqest without
	 * switch media site version param and exit inside redirect function
	 * @return void
	 */
	protected function manageSwitchingAndRedirect () {
		$mediaSiteKey = $this->mediaSiteKeySwitchUriParam;
		// store switched site key into session
		$this->session->{static::MEDIA_SITE_KEY_URL_PARAM} = $mediaSiteKey;
		unset($this->requestGlobalGet[static::MEDIA_SITE_KEY_SWITCH_URL_PARAM]);
		// unset site key switch param and redirect to no switch param uri version
		$request = & $this->request;
		$targetUrl = $request->GetDomainUrl()
			. $request->GetBasePath()
			. $this->allowedSiteKeysAndUrlPrefixes[$mediaSiteKey] 
			. $request->GetPath() 
			. $request->GetQuery(TRUE);
		$this->redirect($targetUrl, \MvcCore\Interfaces\IResponse::SEE_OTHER);
	}

	/**
	 * Detect media site version by sended user agent string (Mobile_Detect library)
	 * and store detected result in session for next requests
	 * @return void
	 */
	protected function manageDetectionAndStoreInSession() {
		$detect = new \Mobile_Detect();
		$mediaSiteKeys = array_keys($this->allowedSiteKeysAndUrlPrefixes);
		if ($detect->isMobile()) {
			$this->mediaSiteKey = $mediaSiteKeys[0];
		} else if ($detect->isTablet()) {
			$this->mediaSiteKey = $mediaSiteKeys[1];
		} else {
			$this->mediaSiteKey = $mediaSiteKeys[2];
		}
		$this->session->{static::MEDIA_SITE_KEY_URL_PARAM} = $this->mediaSiteKey;
	}

	/**
	 * If local media site version (completed previously from session or Mobile_Detect library)
	 * is different then media site version from request - redirect to url with media site version
	 * from local $this context and exit (inside redirect function)
	 * @return void
	 */
	protected function checkMediaSiteVersionWithRequestVersionAndRedirectIfDifferent() {
		$request = & $this->request;
		$requestMediaSiteKey = $request->GetMediaSiteKey();
		$sessionOrDetectionSameWithRequest = $this->mediaSiteKey === $requestMediaSiteKey;
		if (!$sessionOrDetectionSameWithRequest) {
			if ($this->stricModeBySession) {
				if ($this->isGet) 
					$this->redirect(
						$request->GetBaseUrl()
						. $this->allowedSiteKeysAndUrlPrefixes[$this->mediaSiteKey] 
						. $request->GetPath()
						. $request->GetQuery(TRUE), 
						\MvcCore\Interfaces\IResponse::SEE_OTHER
					);
			} else {
				$this->session->{static::MEDIA_SITE_KEY_URL_PARAM} = $requestMediaSiteKey;
				$this->mediaSiteKey = $requestMediaSiteKey;
			}
		}
	}

	/**
	 * If session namespace by this class is not initialized,
	 * init namespace and move expiration to next hour
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
	 * Try to set up into \MvcCore\Request object a MediaSiteKey property by requested url
	 * by defined url prefixes founded in requested url.
	 * @return void
	 */
	protected function setUpRequestMediaSiteKeyFromUrl () {
		$requestPath = $this->request->GetPath();
		foreach ($this->allowedSiteKeysAndUrlPrefixes as $mediaSiteKey => $requestPathPrefix) {
			if (mb_strpos($requestPath, $requestPathPrefix . '/') === 0) {
				$this->request
					->SetMediaSiteKey($mediaSiteKey)
					->SetPath(mb_substr($requestPath, strlen($requestPathPrefix)));
				break;
			}
		}
	}
}
