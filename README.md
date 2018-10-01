# MvcCore Extension - Router - Media

[![Latest Stable Version](https://img.shields.io/badge/Stable-v4.3.1-brightgreen.svg?style=plastic)](https://github.com/mvccore/ext-router-media/releases)
[![License](https://img.shields.io/badge/Licence-BSD-brightgreen.svg?style=plastic)](https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md)
![PHP Version](https://img.shields.io/badge/PHP->=5.3-brightgreen.svg?style=plastic)

MvcCore Router extension to manage your website media version in url
to have media flag in request, controller and view to render different
templates, css and js files for mobiles, tables or desktops.

## Installation
```shell
composer require mvccore/ext-router-media
```

## Features
- Recognizes user device by http header `User-Agent` with [`\Mobile_Detect`](https://github.com/serbanghita/Mobile-Detect) library into versions `full`, `tablet` or `mobile`.
- Stores recognized device version in it's own session namespace with configurable expiration to not process `\Mobile_Detect` recognition in every request.
- Completes `$request->GetMediaSiteVersion()` value to use it enywhere in your app.
- Removes possibly founded media prefix substring from `$request->GetPath()` to 
  process routing as usual (without any special url variants with or without media prefixes 
  to annoying you in your projects and routing).
- Completes every application url (or every get url by configuration) with media prefix substring (also possible to configure the url usbstring).
- Strict mode media site version configuration option to drive application media version strictly by session value.

## Usage
Add this to `/App/Bootstrap.php` or to **very application beginning**, 
before application routing or any other extension configuration
using router for any purposes:
```php
$app = & \MvcCore\Application::GetInstance();
$app->SetRouterClass('\MvcCore\Ext\Routers\Media');
...
/** @var $router \MvcCore\Ext\Routers\Media */
$router = & \MvcCore\Router::GetInstance();
```

## Configuration

### Session expiration
There is possible to change session expiration about detected media
site version value to not recognize media site version every request
where is no prefix in url, because to process all regular expressions 
in `\Mobile_Detect` library could take some time. By **default** there is **1 hour**. 
You can change it by:
```php
$router->SetSessionExpirationSeconds(
	\MvcCore\Session::EXPIRATION_SECONDS_DAY
);
```

### Media url prefixes and allowed media versions
To allow only selected media site versions and to configure url prefixes, you can use:
```php
// to allow only mobile version (with url prefix '/mobile') 
// and full version (with no url prefix):
use \MvcCore\Ext\Routers;
...
// now, tablet version is not allowed:
$router->SetAllowedSiteKeysAndUrlPrefixes([
	Routers\Media::MEDIA_VERSION_MOBILE	=> '/mobile',
	// if you are using an empty string url prefix for full version, 
	// you need to define it as the last item!
	Routers\Media::MEDIA_VERSION_FULL	=> '',
]);
```

### Strict session mode
Stric session mode is router mode, when media site version is managed
by session value from the first request recognition. 

Normally, there is possible to get different media site version only by 
requesting different media site version url prefix. For example - to get 
different version from `full` version, for example to get `mobile` version, 
it's only necessary to request application with configured `mobile` prefix 
in url like this: `/mobile/any/application/request/path`.

**But in session strict mode, there is not possible to change media site 
version only by requesting different media site version prefix in url.**
All requests to different media site version then version in session are 
automaticly redirected to media site version stored in session.

In session strict mode, there is possible to change media site version only 
by special `$_GET` parametter in your media version navigation. For example - 
to get different version from `full` version, for eample `mobile` version, 
you need to add into query string parametter like this:
`/any/application/request/path?switch_media_version=mobile`
Then, there is changed media site version stored in session and user is 
redirected to mobile application version with mobile url prefixes everywhere.

To have this session strict mode, you only need to configure router by:
```php
$router->SetStricModeBySession(TRUE);
```

### Routing `GET` requests only
Router manages media site version only for `GET` requests. It means
redirections to proper version in session stric mode or to redirect
in first request to recognized media site version. `POST` requests
and other request methods to manage for media site version doesn't 
make sence. For those requests, you have still media site version 
record in session and you can use it any time. But to process all
request methods, you can configure router to do so like this:
```php
$router->SetRouteGetRequestsOnly(FALSE);
```
