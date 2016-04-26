=== Normalizer ===
Contributors: zodiac1978
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=LCH9UVV7RKDFY
Tags: Unicode, Normalization, Form C, Unicode Normalization Form C, Normalize, Normalizer
Requires at least: 1.5.2
Tested up to: 4.5
Stable tag: 2.0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Normalizes content, excerpt, title and comment content to Normalization Form C.

== Description ==

For everyone getting this warning from W3C validator: "Text run is not in Unicode Normalization Form C."

See: http://www.w3.org/International/docs/charmod-norm/#choice-of-normalization-form

For best results have PHP 5.3+ and the PHP Normalizer extension (intl and icu) installed.

However the claim is that this version should work without the PHP Normalizer extension being installed, or if your installation
is without UTF-8 for PCRE, or if you're running PHP 5.2.4...

See: http://php.net/manual/en/normalizer.normalize.php
See also: https://core.trac.wordpress.org/ticket/30130

== Installation ==

1. Upload the zip file from this plugin on your plugins page or search for `Normalizer` and install it directly from the repository
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Done!

== Frequently Asked Questions ==

= I don't see any changes. =

The plugin just adds the normalization if there are problematic characters. Furthermore it does do the normalization before saving, so you don't see anything. It just works if it is needed.

= Will this slow down my site? =

Sorry, but I don't have a clue. Maybe just a little bit. 

== Screenshots ==

1. Correct transliteration if you enter the word directly
2. Missing transliteration for copy/pasted word from PDF
3. Error message from W3C

== Changelog ==

= 2.0.3 =
* Add (a lot) more filters.
* Update to latest version of Symfony Normalizer (uses ".php" files instead of ".ser" files for data for optimization reasons).
* Use single-byte regex instead of PHP for-loop to deal with PCRE with no UTF-8.
* Add customizer paste.
* Fix caret position on paste (uses rangyinputs jquery plugin).
* Facilitate testing/debugging.
* Port Symfony unit tests.
* Add some limited unit tests for posts, comments, users, options.

= 2.0.2 =
* Fix Normalizer dependency on PCRE with UTF-8.
* Fix adding filters too late. Use 'init' not 'admin_init' action.
* Add paste normalization to front-end text inputs/textareas.

= 2.0.1 =
* Add paste normalization to admin text inputs/textareas and some media stuff.

= 2.0.0 =
* Support PHP without the Normalizer extension by using a polyfill.
* Support normalizing text pasted into tinymce, using a polyfill for browsers without String.prototype.normalize.

= 1.0.0 =
* Initial release
