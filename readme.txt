=== Caching Compatible Cookie Opt-In and JavaScript ===
Contributors:  matthias.wagner
Donate link: https://www.matthias-wagner.at
Tags: cookie, optin, caching, cache, fastest cache, total cache, autoptimize, asynchronous, javascript, speed, performance, opt-in, dsgvo, gdpr
Requires at least: 5.0
Requires PHP: 7.0
Tested up to: 6.6
Stable tag: 0.0.10
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Shows an opt-in banner and loads scripts either always or only after opt-in. Provides full compatibility with caching plugins since scripts are loaded asynchronous with jQuery.

== Description ==

This lightweight plugin closes the gap between the annoying need for a cookie-banner to opt-in to some scripts (third party tracking, ...) and super fast websites enabled by server-side caching plugins like WP Fastest Cache, W3 Total Cache and so on.

It is very simple:
* A visitor makes his selection by clicking the buttons in the cookie banner (use all cookies or use only necessary cookies)
* The selection is stored in a cookie
* Now after every page load our JS checks the cookie and asynchronously fetches the allowed scripts depending on the users selection
* You can also place shortcodes with inline scripts and html in your content with the same functionality and inline-warnings if no optin was made
* You can also place a shortcode to show the visitor his decision and allow him to revoke that decision, which seems necessary if you want to be gdpr compliant with your cookie solution

[Our documentation including information on action and filter hooks can be found here](https://www.notion.so/falkemedia/Caching-Compatible-Cookie-Opt-In-and-JavaScript-4375c9b364b04b51bbd396e1514f105b)

== Installation ==

= 1. Plugin Installation =
1. Upload plugin-folder to your "/wp-content/plugins/" directory.
2. Activate the plugin through the Plugins-menu in WordPress.

= 2. Plugin Setup =
Scripts-Tab: Fill in the scripts you would like to execute
Settings-Tab: Choose your custom wording and styling

== Frequently Asked Questions ==

= Why another cookie notice plugin? =
Many popular Cookie-Notice-Plugins will fail if you try to set them up together with server-side caching plugins. That is because they check the opt-in cookie in PHP, which will be cached. This plugin works differently, since the whole logic happens in JavaScript. So everything can be cached server-side without any troubles for the opt-in process.

= Where are the settings? =
Look for "Cookie-Optin & JS" in your Settings-Tab in the WordPress dashboard.

= Why so many input fields? =
This plugin will put your JavaScript code into the <head> or <body> section of your website. For each section you can decide if you want to have code there all the time or only after opt-in. So this can be used as an all-in-one solution for placing website-wide JavaScript used for tracking and so on in your website. Since this is done after page load it will also have a good impact on website speed.

= Is it possible to place inline JavaScript or HTML =
Yes, we provide a shortcode for that which enables the same functionality as for the head or body scripts.

= Is it possible to revoke the decision from the cookie banner? =
Yes, we provide a shortcode which will display a button to revoke. Additionally, you can define the name of cookies which you want to unset when a user clicks this revoke button.

= What about multilingual sites? =
So far we only support polylang. If you use polylang, you can translate the strings which will be visible in the banner in the "Strings translations" settings from polylang. If you use any other plugin or want to change texts and settings programmatically, you can use actions and filters like described in our [documentation](https://www.notion.so/falkemedia/Caching-Compatible-Cookie-Opt-In-and-JavaScript-4375c9b364b04b51bbd396e1514f105b)

= Do you have further questions? =
We will do our best to answer them in the support threads.

== Screenshots ==

1. Various input fields for your scripts
2. Use your individual texts and colors
3. Banner style "Information"
4. Banner style "Blocking"


== Changelog ==

= 0.0.10 =
* Fixed missing background for JavaScript-hint

= 0.0.9 =
* Added documentation, action and filter hooks

= 0.0.8 =
* Added the ccco-inline shortcode to place scripts and html everywhere in content

= 0.0.7 =
* Added functionality to unset cookies on revoke also from top-level-domain (like google analytics sets them)

= 0.0.6 =
* Added possibility to unset cookies on revoke

= 0.0.5 =
* Added shortcode to provide a revoke functionality

= 0.0.4 =
* Bugfix button style

= 0.0.3 =
* Initial public release
