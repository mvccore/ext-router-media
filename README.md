# MvcCore Extension - Media Router

[![Latest Stable Version](https://img.shields.io/badge/Stable-v3.2.0-brightgreen.svg?style=plastic)](https://github.com/mvccore/ext-media-router/releases)
[![License](https://img.shields.io/badge/Licence-BSD-brightgreen.svg?style=plastic)](https://mvccore.github.io/docs/mvccore/3.0.0/LICENCE.md)
![PHP Version](https://img.shields.io/badge/PHP->=5.3-brightgreen.svg?style=plastic)

MvcCore Router extension to manage your website media version for different 
templates/css/js files rendering.

## Features
- recognizes user device by useragent with Mobile_Detect library into full/tablet/mobile
- stores recognized device version into session namespace for hout by default, possible to change
- completes public property MediaSiteVersion in MvcCore_Request to use it in your app
- removes possibly founded media prefix flag from MvcCore_Request Path property to 
  process routing as usual
- completes every get application url with media prefix flag, possible to change
- strict mode media site version configuration by:


## Installation
```shell
composer require mvccore/ext-media-router
```

## Usage
Add this to **Bootstrap.php** or to **very application beginning**, 
before application routing or any other extension configuration
using router for any purposes:
```php
MvcCore::GetInstance()->SetRouterClass(MvcCoreExt_MediaRouter::class);
```

## Configuration

### Session expiration
There is possible to change session expiration about detected media
site version value to not recognize media site version every request
where is no prefix in url, because all regular expressions in Mobile_Detect
library could takes some time. By **default** there is **1 hour**. 
You can change it by:
```php
MvcCoreExt_MediaRouter::GetInstance()->SetSessionExpirationSeconds(86400); // day
```
But it's not practicly necessary, because if there is necessary to detect
user device again, it's not so often when the detection process is only 
once per hour - it costs realy nothing per hour. And only a few users stay
on your site more than one hour.

### Media url prefixes and allowed media versions
To allow only some media site versions and configure url prefixes, you can use:
```php
// to allow only mobile version (with url prefix '/mobile') 
// and full version (with no url prefix):
MvcCoreExt_MediaRouter::GetInstance()->SetAllowedSiteKeysAndUrlPrefixes(array(
	MediaSiteKey::MOBILE	=> '/mobile',
	MediaSiteKey::FULL		=> '',
));
```

### Strict session mode
To change managing user media version into more strict mode,
where is not possible to change media version only by request 
application with different media prefix in path like:
```
/mobile/any/application/request/path
```
but ony where is possible to change media site version by 
special $_GET param "media_site_key" like:
```
/mobile/any/application/request/path?media_site_key=mobile
```
you need to configure router into strict session mode by:
```php
MvcCoreExt_MediaRouter::GetInstance()->SetStricModeBySession();
```
