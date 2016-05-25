# TL Normalizer

Normalizes content, excerpt, title and comment content to Normalization Form C.

## Why?

For everyone getting this warning from W3C validator: "Text run is not in Unicode Normalization Form C."

See: http://www.w3.org/International/docs/charmod-norm/#choice-of-normalization-form

## Requirements

For best results have PHP 5.3+ and the PHP Normalizer extension (intl and icu) installed.

However the claim is that this version should work without the PHP Normalizer extension being installed, or if your installation
is without UTF-8 for PCRE, or if you're running PHP 5.2.4...

See: http://php.net/manual/en/normalizer.normalize.php
See also: https://core.trac.wordpress.org/ticket/30130

## Installation

If you don’t know how to install a plugin for WordPress, [here’s how](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

## Changelog

### 2.0.5
* A few more filters (attachment upload, date/time preview).
* Optimize Normalizer for "if ( ! isNoramlized() ) normalize()" pattern, using direct TLN_REGEX_XXX regular expression defines and preg_replace_callback().
* More PHP unit tests, stub qunit test.

### 2.0.4
* Fix some bugs in Symfony Normalizer and upgrade to UCD 8.0.0 conformance (replaces supplied "combiningClass.php" with generated one).
* Just use single-byte PCRE regexs.
* Make isNormalized() do full check by falling back to doing full normalize(), caching result.
* Improve NFC subset regex check, using generated alternatives.
* Cater for mbstring overload.
* Add (a lot) more PHP unit tests.

### 2.0.3
* Add (a lot) more filters.
* Update to latest version of Symfony Normalizer (uses ".php" files instead of ".ser" files for data for optimization reasons).
* Use single-byte regex instead of PHP for-loop to deal with PCRE with no UTF-8.
* Add customizer paste.
* Fix caret position on paste (uses rangyinputs jquery plugin).
* Facilitate testing/debugging.
* Port Symfony unit tests.
* Add some limited unit tests for posts, comments, users, options.

### 2.0.2
* Fix Normalizer dependency on PCRE with UTF-8.
* Fix adding filters too late. Use 'init' not 'admin_init' action.
* Add paste normalization to front-end text inputs/textareas.

### 2.0.1
* Add paste normalization to admin text inputs/textareas and some media stuff.

### 2.0.0
* Support PHP without the Normalizer extension by using a polyfill.
* Support normalizing text pasted into tinymce, using a polyfill for browsers without String.prototype.normalize.

### 1.0

* Initial release.
