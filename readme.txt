=== Preload Featured Images ===
Contributors: WPZOOM
Donate link: http://paypal.me/wpzm/10usd
Tags: pagespeed, preload, lcp, featured images, performance
Requires at least: 6.3
Requires PHP: 7.4
Tested up to: 7.0
Stable tag: 1.1.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.txt

Preload Featured Images automatically in posts to increase the PageSpeed Score.

== Description ==

Preload Featured Images automatically in posts to increase the PageSpeed Score.

This plugin is a "must-have" for websites using themes that display the Featured Image automatically at the top in single post pages.

== Why was this plugin created? ==

Suppose your theme displays the Featured Image at the top in posts automatically. In that case, the chances that the image is the **LCP (Largest Contentful Paint)** are very high, so the PageSpeed tool will highly recommend you to preload it.

Are you getting the following recommendation when testing the PageSpeed score of a single post: **"Preload Largest Contentful Paint image"**? Then this plugin will help you!


== How it works? ==

Go to the **Settings > Preload Featured Images** page and choose the image size used by your theme to make sure the right image size is preloaded.

== Compatible Themes ==

The plugin supports all themes, but it's very important to choose the right Image Size on the settings page.

If you are not sure which is the image size used by your theme, simply get in touch with your theme's developer and they will be able to help you with that.

If you're using one of the following popular themes, then the plugin will automatically pick the right Image Size, so you don't have to configure anything:

* Foodica
* Foodica PRO
* Gourmand
* Cookely
* Astra
* Neve
* OceanWP
* GeneratePress
* BlossomRecipe
* Divi
* Ashe


== Installation ==

1. Install Preload Featured Images either via the Plugins > Add New page in your WordPress Dashboard, or by downloading the plugin and uploading the file in Plugins > Add New.

2. After activating Preload Featured Images, a new settings page will appear under the "Settings" section in the admin menu.


== Frequently Asked Questions ==

= What does "Preload Largest Contentful Paint image" and how I solve it? =

If you have tested your website on PageSpeed Insights or GTmetrix and you got the message "Preload Largest Contentful Paint image", it means that the image in question is being lazy loaded.
You can fix this problem by installing this plugin.

= Is this plugin compatible with Jetpack's Image Accelerator (Photon) =

Yes, this plugin supports Image CDNs like Jetpack and other providers, so you can be sure that the right images will be preloaded.

= Can I preload a different image size on mobile and desktop? =

Yes. On the settings page you can choose separate image sizes for desktop and mobile. The plugin outputs responsive preload tags using `media` queries (instead of detecting the device on the server), so it works correctly even when your site is behind a page cache.


== Screenshots ==

1. Settings Page

== Changelog ==

= 1.1.0 =
* Fixed: the preload link now includes `fetchpriority="high"`. Without it the LCP image was preloaded at Low priority and the in-flight preload "won" the request, so the image downloaded at Low priority despite the rendered <img> having fetchpriority=high — failing PageSpeed's "LCP request discovery" audit.
* Fixed: separate mobile/desktop image sizes now use cache-safe responsive `media` preloads instead of `wp_is_mobile()`, which was unreliable behind any page cache.
* Fixed: mobile visitors no longer miss the preload when only a desktop image size is configured.
* Fixed: front-end preloading no longer depends on the admin having initialized the theme defaults.
* Improved: settings input is now validated against your site's registered image sizes.
* Improved: cleaner, escaped preload markup output.
* Tested up to WordPress 7.0.

= 1.0 =
* Initial release