# oEmbed Provider #
**Contributors:** pfefferle, candrews  
**Tags:** oembed, links  
**Requires at least:** 3.0  
**Tested up to:** 4.0.1  
**Stable tag:** 2.1.0  

An oEmbed provider for Wordpress.

## Description ##

The oEmbed provider plugin makes Wordpress an oEmbed provider, compliant with the XML and JSON specification at http://www.oembed.com.

oEmbed is a powerful protocol that allows sites to automatically embed content from 3rd parties directly into their site in whatever way they choose. For example, if a user on http://identi.ca links to a Wordpress blog with this plugin enabled, the link, when clicked, will show an excerpt from the blog post, the authors name, and various links... automatically.

## Installation ##

1. Upload the plugin to `oembed-provider.php` in the `/wp-content/plugins/oembed-provider/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Step 3? There is no step 3!

## Frequently Asked Questions ##

### Is it really that easy to install this plugin? ###

Yes!

## Changelog ##

### 2.1.0 ###
* added `oembed_url` filter: https://github.com/pfefferle/wordpress-oembed-provider/pull/2
* added `custom_post_type` support

### 2.0.1 ###
* type 'rich' now returns the full html
* take use of more WordPress functions

### 2.0.0 ###
* complete refactoring

### 1.0 ###
* Initial release
