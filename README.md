# Normalizer #
**Contributors:** zodiac1978  
**Donate link:** https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=LCH9UVV7RKDFY  
**Tags:** Unicode, Normalization, Form C, Unicode Normalization Form C, Normalize, Normalizer  
**Requires at least:** 1.5.2  
**Tested up to:** 4.5  
**Stable tag:** 2.0.6  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Normalizes UTF-8 input to Normalization Form C.

[![Build Status](https://travis-ci.org/gitlost/tl-normalizer.png?branch=master)](https://travis-ci.org/gitlost/tl-normalizer)

## Description ##

**For everyone getting this warning from W3C validator:** "Text run is not in Unicode Normalization Form C."  

**See:** http://www.w3.org/International/docs/charmod-norm/#choice-of-normalization-form  

For best results have the PHP Normalizer extension "intl" installed.

However the claim is that this version should work without the PHP Normalizer extension being installed, or if your installation
is without UTF-8 for PCRE, or if you're running PHP 5.2.4...

**See:** http://php.net/manual/en/normalizer.normalize.php  
**See also:** https://core.trac.wordpress.org/ticket/30130  

## Installation ##

1. Upload the zip file from this plugin on your plugins page or search for `Normalizer` and install it directly from the repository
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Done!

## Frequently Asked Questions ##

### I don't see any changes. ###

The plugin just adds the normalization if there are problematic characters. Furthermore it does do the normalization before saving, so you don't see anything. It just works if it is needed.

### Will this slow down my site? ###

Sorry, but I don't have a clue. Maybe just a little bit. 

## Screenshots ##

### 1. Correct transliteration if you enter the word directly ###
![Correct transliteration if you enter the word directly](https://ps.w.org/normalizer/assets/screenshot-1.png)

### 2. Missing transliteration for copy/pasted word from PDF ###
![Missing transliteration for copy/pasted word from PDF](https://ps.w.org/normalizer/assets/screenshot-2.png)

### 3. Error message from W3C ###
![Error message from W3C](https://ps.w.org/normalizer/assets/screenshot-3.png)


## Changelog ##

### 2.0.6 ###
* Move most of javascript into "js/tl-normalize.js".
* Add Gruntfile.js, generating minifieds.
* Move tests/test-xxx.php to tests/xxxTest.php for grunt-phpunit compatibility.
* A few Normalizer optimizations.
* Try to get travis working.

### 2.0.5 ###
* A few more filters (attachment upload, date/time preview).
* Optimize Normalizer for "if ( ! isNormalized() ) normalize()" pattern, using direct TLN_REGEX_XXX regular expression defines and preg_replace_callback().
* More PHP unit tests, stub qunit test.

### 2.0.4 ###
* Fix some bugs in Symfony Normalizer and upgrade to UCD 8.0.0 conformance (replaces supplied "combiningClass.php" with generated one).
* Just use single-byte PCRE regexs.
* Make isNormalized() do full check by falling back to doing full normalize(), caching result.
* Improve NFC subset regex check, using generated alternatives.
* Cater for mbstring overload.
* Add (a lot) more PHP unit tests.

### 2.0.3 ###
* Add (a lot) more filters.
* Update to latest version of Symfony Normalizer (uses ".php" files instead of ".ser" files for data for optimization reasons).
* Use single-byte regex instead of PHP for-loop to deal with PCRE with no UTF-8.
* Add customizer paste.
* Fix caret position on paste (uses rangyinputs jquery plugin).
* Facilitate testing/debugging.
* Port Symfony unit tests.
* Add some limited unit tests for posts, comments, users, options.

### 2.0.2 ###
* Fix Normalizer dependency on PCRE with UTF-8.
* Fix adding filters too late. Use 'init' not 'admin_init' action.
* Add paste normalization to front-end text inputs/textareas.

### 2.0.1 ###
* Add paste normalization to admin text inputs/textareas and some media stuff.

### 2.0.0 ###
* Support PHP without the Normalizer extension by using a polyfill.
* Support normalizing text pasted into tinymce, using a polyfill for browsers without String.prototype.normalize.

### 1.0.0 ###
* Initial release
