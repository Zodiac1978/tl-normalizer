# TL Normalizer

Normalizes content, excerpt, title and comment content to Normalization Form C.

## Why?

For everyone getting this warning from W3C validator: "Text run is not in Unicode Normalization Form C."
http://www.w3.org/International/docs/charmod-norm/#choice-of-normalization-form

## Requirements

For best results have PHP 5.3+ and the PHP-Normalizer-extension (intl and icu) installed.
See: http://php.net/manual/en/normalizer.normalize.php

## Installation

If you don’t know how to install a plugin for WordPress, [here’s how](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

## Changelog

### 2.0.0
* Support PHP without the Normalizer extension by using a polyfill.
* Support normalizing text pasted into tinymce, using a polyfill for browsers without String.prototype.normalize.

### 1.0

* Initial release.
