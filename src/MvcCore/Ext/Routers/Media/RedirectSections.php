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

trait RedirectSections {

	/**
	 * Redirect to target media site version with path and by global `$_GET` 
	 * collection (cloned from request object). Return always `FALSE`.
	 * @param array $systemParams 
	 * @return array `[string $urlBaseSection, string $urlPathWithQuerySection, array $systemParams, bool|NULL $urlPathWithQueryIsHome]`
	 */
	protected function redirectToVersionSections ($systemParams) {
		/** @var $this \MvcCore\Ext\Routers\Media */
		$request = $this->request;
		$urlBaseSection = $request->GetBaseUrl();
		$urlPathWithQuerySection = $request->GetPath(TRUE);


		// unset site key switch param and redirect to no switch param uri version
		$mediaVersionParamName = static::URL_PARAM_MEDIA_VERSION;
		$targetMediaUrlValue = $this->redirectMediaGetUrlValueAndUnsetGet(
			$systemParams[$mediaVersionParamName]
		);
		

		$urlPathWithQueryIsHome = NULL;
		if ($this->anyRoutesConfigured) {


			if ($targetMediaUrlValue === NULL) {
				unset($systemParams[$mediaVersionParamName]);
			} else {
				$systemParams[$mediaVersionParamName] = $targetMediaUrlValue;
			}


			$this->redirectAddAllRemainingInGlobalGet($urlPathWithQuerySection);
		} else {
			$this->removeDefaultCtrlActionFromGlobalGet();
			if ($this->requestGlobalGet)
				$urlPathWithQuerySection .= $request->GetScriptName();
			$this->redirectAddAllRemainingInGlobalGet($urlPathWithQuerySection);
		}

		$this->redirectStatusCode = \MvcCore\IResponse::SEE_OTHER;

		return [
			$urlBaseSection,
			$urlPathWithQuerySection, 
			$systemParams,
			$urlPathWithQueryIsHome
		];
	}
}
