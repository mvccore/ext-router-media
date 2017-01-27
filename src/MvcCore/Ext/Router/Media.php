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

namespace MvcCore\Ext\Router;

class MediaSiteKey {
	const FULL = 'full';
	const TABLET = 'tablet';
	const MOBILE = 'mobile';
}

class Media extends \MvcCore\Router {

	/**
	 * MvcCore Extension - Router Media - version:
	 * Comparation by PHP function version_compare();
	 * @see http://php.net/manual/en/function.version-compare.php
	 */
	const VERSION = '4.0.0';

	/**
	 * Key name for media version in second argument $params in $router->Url();  method,
	 * to tell $router->Url() method to generate different media version url.
	 */
	const MEDIA_SITE_KEY_URL_PARAM = 'mediaSiteKey';

	/**
	 * Special $_GET param name for session strict mode, how to change site media version.
	 */
	const MEDIA_SITE_KEY_SWITCH_URL_PARAM = 'media_site_key';

	/**
	 * Session expiration seconds for remembering detected media site version by user agent.
	 * Session record is always used to compare if user is requesting different media 
	 * site version then he has in session - if there is difference - user is redirected 
	 * to session media site version and this seconds is time to remember that sessio record 
	 * for described redirection.
	 * @var int
	 */
	public $SessionExpirationSeconds = 3600; // hour

	/**
	 * Url prefixes prepended before url paths to describe media site version in url.
	 * Key is media site version value and value in array is url prefix how 
	 * to describe media site version in url.
	 * @var array
	 */
	public $AllowedSiteKeysAndUrlPrefixes = array(
		MediaSiteKey::MOBILE	=> '/m',
		MediaSiteKey::TABLET	=> '/t',
		MediaSiteKey::FULL		=> '',
	);

	/**
	 * Session record is always used to compare if user is requesting different media
	 * site version then he has in session - if there is difference - user is redirected
	 * to session media site version
	 * @var \MvcCore\Session|\stdClass
	 */
	protected $session = NULL;

	/**
	 * Media site version founded in session.
	 * @var string
	 */
	protected $sessionMediaSiteKey = '';

	/**
	 * Final media site key used in \MvcCore\Request object
	 * @var string
	 */
	protected $mediaSiteKey = '';

	/**
	 * Media site version for switching, always initialized by special switching $_GET param.
	 * @var string|bool
	 */
	protected $mediaSiteKeySwitchUriParam = FALSE;

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
	 * Static initialization - called when class is included by autoloader
	 * @return void
	 */
	public static function StaticInit () {
		\MvcCore::AddPreRouteHandler(function (\MvcCore\Request & $request, \MvcCore\Response & $response) {
			\MvcCore::SessionStart();
			static::GetInstance()->ProcessMediaSiteVersion($request);
		});
	}

	/**
	 * Set session expiration seconds for remembering detected media site version by user agent.
	 * Session record is always used to compare if user is requesting different media
	 * site version then he has in session - if there is difference - user is redirected
	 * to session media site version and this seconds is time to remember that sessio record
	 * for described redirection.
	 * @var int
	 */
	public function SetSessionExpirationSeconds ($sessionExpirationSeconds = 3600) {
		$this->SessionExpirationSeconds = $sessionExpirationSeconds;
		return $this;
	}

	/**
	 * Set url prefixes prepended before url paths to describe media site version in url
	 * and by defined keys in given array - set only allowed media versions to work with.
	 * Key is media site version value and value is url prefix how to describe 
	 * media site version in url.
	 * @param array $allowedSiteKeysAndUrlPrefixes 
	 * @return \MvcCore\Ext\Router\Media
	 */
	public function SetAllowedSiteKeysAndUrlPrefixes ($allowedSiteKeysAndUrlPrefixes = array()) {
		$this->AllowedSiteKeysAndUrlPrefixes = $allowedSiteKeysAndUrlPrefixes;
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
	 * @return \MvcCore\Ext\Router\Media
	 */
	public function SetStricModeBySession ($stricModeBySession = TRUE) {
		$this->stricModeBySession = $stricModeBySession;
		return $this;
	}

	/**
	 * Generates url by:
	 * - 'Controller:Action' name and params array
	 *   (for routes configuration when routes array has keys with 'Controller:Action' strings
	 *   and routes has not controller name and action name defined inside)
	 * - route name and params array
	 *	 (route name is key in routes configuration array, should be any string
	 *	 but routes must have information about controller name and action name inside)
	 * Result address should have two forms:
	 * - nice rewrited url by routes configuration
	 *   (for apps with .htaccess supporting url_rewrite and when first param is key in routes configuration array)
	 * - for all other cases is url form: index.php?controller=ctrlName&action=actionName
	 *	 (when first param is not founded in routes configuration array)
	 * @param string $controllerActionOrRouteName	Should be 'Controller:Action' combination or just any route name as custom specific string
	 * @param array  $params						optional
	 * @return string
	 */
	public function Url ($name = '', $params = array()) {
		if (isset($params[static::MEDIA_SITE_KEY_URL_PARAM])) {
			$mediaSiteKey = $params[static::MEDIA_SITE_KEY_URL_PARAM];
			unset($params[static::MEDIA_SITE_KEY_URL_PARAM]);
		} else {
			$mediaSiteKey = $this->mediaSiteKey;
		}
		$url = parent::Url($name, $params);
		if (!isset($this->routes[$name]) && $name != 'self') return $url;
		if (strpos($name, 'Controller:Asset') === 0) return $url;
		return $this->AllowedSiteKeysAndUrlPrefixes[$mediaSiteKey] . $url;
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
	 * @param \MvcCore\Request $request
	 * @return void
	 */
	public function ProcessMediaSiteVersion (\MvcCore\Request & $request)
	{
		$this->request = & $request;
		$this->request->OriginalPath = $this->request->Path;
		// switching media site version will be only by get:
		$this->isGet = $request->Method == \MvcCore\Request::METHOD_GET;
		// look into request params if are we just switching any new site media version
		if (isset($_GET[static::MEDIA_SITE_KEY_SWITCH_URL_PARAM])) {
			$this->mediaSiteKeySwitchUriParam = strtolower($_GET[static::MEDIA_SITE_KEY_SWITCH_URL_PARAM]);
		}
		// set up current media site version from url string
		$this->setUpRequestMediaSiteKeyFromUrl();
		// set up session object to look inside for something from previous requests
		static::setUpSession();
		// look into session object if there are or not any record about recognized device from previous request
		if (isset($this->session->{static::MEDIA_SITE_KEY_URL_PARAM})) {
			$this->sessionMediaSiteKey = $this->session->{static::MEDIA_SITE_KEY_URL_PARAM};
		}

		if (
			$this->isGet && 
			$this->mediaSiteKeySwitchUriParam !== FALSE && 
			isset($this->AllowedSiteKeysAndUrlPrefixes[$this->mediaSiteKeySwitchUriParam])
		) {
			$this->manageSwitchingAndRedirect();

		} else if (
			$this->isGet && 
			(
				$this->sessionMediaSiteKey === '' || 
				!isset($this->AllowedSiteKeysAndUrlPrefixes[$this->sessionMediaSiteKey])
			)
		) {
			$this->manageDetectionAndStoreInSession();
			$this->checkMediaSiteVersionWithRequestVersionAndRedirectIfDifferent();
			
		} else {
			$this->mediaSiteKey = $this->sessionMediaSiteKey;
			$this->checkMediaSiteVersionWithRequestVersionAndRedirectIfDifferent();
		}
		
		$request->MediaSiteKey = $this->mediaSiteKey;
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
		unset($_GET[static::MEDIA_SITE_KEY_SWITCH_URL_PARAM]);
		// unset site key switch param and redirect to no switch param uri version
		$query = count($_GET) > 0 ? '?' . http_build_query($_GET) : '';
		$targetUrl = $this->request->DomainUrl . $this->request->BasePath
			. $this->AllowedSiteKeysAndUrlPrefixes[$mediaSiteKey] . $this->request->Path . $query;
		\MvcCore\Controller::Redirect($targetUrl);
	}

	/**
	 * Detect media site version by sended user agent string (Mobile_Detect library)
	 * and store detected result in session for next requests
	 * @return void
	 */
	protected function manageDetectionAndStoreInSession() {
		$detect = new \Mobile_Detect();
		$mediaSiteKeys = array_keys($this->AllowedSiteKeysAndUrlPrefixes);
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
		$sessionOrDetectionSameWithRequest = $this->mediaSiteKey === $this->request->MediaSiteKey;
		if (!$sessionOrDetectionSameWithRequest) {
			if ($this->stricModeBySession) {
				$targetUrl = $this->request->Protocol . '//' . $this->request->Host . $this->request->BasePath
					. $this->AllowedSiteKeysAndUrlPrefixes[$this->mediaSiteKey] . $this->request->Path;
				$targetUrl .= $this->request->Query ? '?' . $this->request->Query : '';
				if ($this->isGet) \MvcCore\Controller::Redirect($targetUrl);
			} else {
				$this->session->{static::MEDIA_SITE_KEY_URL_PARAM} = $this->request->MediaSiteKey;
				$this->mediaSiteKey = $this->request->MediaSiteKey;
			}
		}
	}

	/**
	 * If session namespace by this class is not initialized,
	 * init namespace and move expiration to next hour
	 * @return void
	 */
	protected function setUpSession () {
		if (is_null($this->session)) {
			$this->session = \MvcCore\Session::GetNamespace(__CLASS__);
			$this->session->SetExpirationSeconds($this->SessionExpirationSeconds);
		}
	}

	/**
	 * Try to set up into \MvcCore\Request object a MediaSiteKey property by requested url
	 * by defined url prefixes founded in requested url.
	 * @return void
	 */
	protected function setUpRequestMediaSiteKeyFromUrl () {
		foreach ($this->AllowedSiteKeysAndUrlPrefixes as $mediaSiteKey => $requestPathPrefix) {
			if (mb_strpos($this->request->Path, $requestPathPrefix . '/') === 0) {
				$this->request->MediaSiteKey = $mediaSiteKey;
				$this->request->Path = mb_substr($this->request->Path, strlen($requestPathPrefix));
				break;
			}
		}
	}
}
Media::StaticInit();