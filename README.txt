=== Typecase Web Fonts ===

Contributors: chriswallace,garand,upthemes,andrew.taylor
Donate link: http://upthemes.com/
Tags: web fonts, typography, font manager, type, Google Web Fonts
Requires at least: 3.1
Tested up to: 4.1
Stable tag: 1.1.1
License: GPLv2

Typecase is a web font management plugin that allows you to browse, search, and embed over 650 fonts from Google Web Fonts.

== Description ==

Typecase makes working with web fonts in WordPress a glorious experience. With over 650 fonts from Google Web Fonts, Typecase is a unique and easy-to-use typography plugin that allows you to quickly browse, find, and select fonts to apply to your website.

Developed by [UpThemes](http://upthemes.com).

Typecase works out-of-the-box with all the default themes, including the brand new Twenty Fifteen. We've incorporated new controls for font selection with the Customizer, making it easy to select fonts that fit your style.

== Advanced Usage ==

Typecase offers the ability to apply fonts to any theme through the use of CSS. If you don't know how to find CSS selectors to target in your theme, make sure to ask the original theme author to implement theme support for Typecase. Once the theme declares support for Typecase, you will easily be able to customize the fonts in your theme without the need for using CSS selectors.

== Installation ==

1. [Download Typecase](http://downloads.wordpress.org/plugin/typecase.zip)
2. Install the Typecase plugin by going to Plugins > Add New > Upload and select the typecase.zip file and click the "upload" button.
3. Activate the plugin.
4. Locate the Typecase menu item on the left side menu.
5. Follow the on-screen instructions to begin using Typecase.

== Frequently Asked Questions ==

= Which themes are currently supported for use with the Customizer? =

* Twenty Ten
* Twenty Eleven
* Twenty Twelve
* Twenty Thirteen
* Twenty Fourteen
* Twenty Fifteen

Soon, all [UpThemes WordPress themes](https://upthemes.com/themes/) will support Typecase as well. We will continue to update this section as more theme authors add support for their themes.

= What If I Don't Understand CSS selectors? =

I would encourage you to learn some CSS. If you don't want to learn CSS, I would encourage you to install [Firebug](http://getfirebug.com/) (for Firefox) or enable the webkit developer tools for [Safari](http://inspectelement.com/didyouknow/enable-safaris-awesome-built-in-development-tools/) or Chrome.

Once you install one of these plugins, use the inspector tool to be able to hover over and click on different page elements like h1 (a top-level heading element) or p (a paragraph element).

Once you find the element you want to target, you will add a new "selector" for the font you've added to "Your Collection." In the "Selectors" sidebar, simple enter the element like so: "h1" or "body" or "article" (this is a new HTML5 element).

To target certain parts of the page that may use an ID or class name, you would enter "#id" or ".classname" to target those sections of the page.

= What If I'm Targeting Something That is Already Being Targeted? =

Well, this is a problem. Your theme is probably applying the "font-family" CSS property in a non-graceful way. My recommendation is to "cascade" your styles by using extremely specific selectors so that you override your theme's naughty CSS by selecting "html body #id" or something to that effect.

= Are all fonts available from Google Web Fonts? =

The plugin fetches the latest list of fonts from Google every time you edit your fonts, making it easy to grab the latest and greatest web fonts Google has to offer.

== Screenshots ==

1. Edit your website's web fonts easily with Typecase.
2. Instantly search Google Web Fonts' selection of over 500 web fonts, all optimized for the web.
3. Take a peek at your beautiful fonts on the front-end of your website. Ahhh, so pretty.

== Changelog ==

= 1.1.1 =
* Fixed Customizer compatibility with older versions, it now works in WordPress 4.0 and 4.1

= 1.1 =
* Added ability for themes to declare theme support for Typecase for usage in custom themes in Customizer
* Added support for quick font selection using the Customizer all WordPress default themes
* Removed branding for Typecase Pro

= 1.0 =
* Fixed some compatibility bugs
* Merged Typecase Pro features into free version

= 0.3.4 =
* Fixed bug where fonts would eventually be hidden in Firefox on Windows.
* Fixed issue where some fonts display "Undefined" for character sets.
* Fixed deleting fonts does not re-enable the add button in the library.
* Fixed deleting a selector triggers confirm dialog.

= 0.3.1 =
* Initial release.
