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
 * Responsibility - recognize media site version from URL or user agent or 
 *					session and set up request object, complete automatically 
 *					rewritten URL with remembered media site version. Redirect 
 *					to proper media site version by configuration.Than route 
 *					request like parent class does. Generate URL addresses with 
 *					prefixed media site version for recognized special devices 
 *					or add only media site version into query string if necessary.
 */
class		Media 
extends		\MvcCore\Router
implements	\MvcCore\Ext\Routers\IMedia,
			\MvcCore\Ext\Routers\IExtended
{
	use \MvcCore\Ext\Routers\Extended;

	use \MvcCore\Ext\Routers\Media\Preparing;
	use \MvcCore\Ext\Routers\Media\PreRouting;
	use \MvcCore\Ext\Routers\Media\PropsGettersSetters;
	use \MvcCore\Ext\Routers\Media\RedirectSections;
	use \MvcCore\Ext\Routers\Media\Routing;
	use \MvcCore\Ext\Routers\Media\UrlByRoute;
	use \MvcCore\Ext\Routers\Media\UrlByRouteSections;
	use \MvcCore\Ext\Routers\Media\UrlByRouteSectionsMedia;
	
	/**
	 * MvcCore Extension - Router Media - version:
	 * Comparison by PHP function version_compare();
	 * @see http://php.net/manual/en/function.version-compare.php
	 */
	const VERSION = '5.0.0-alpha';
}
