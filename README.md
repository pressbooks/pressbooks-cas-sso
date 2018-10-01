# Pressbooks CAS Single Sign-On 
**Contributors:** conner_bw, greatislander  
**Donate link:** https://opencollective.com/pressbooks/  
**Tags:** pressbooks, sso, cas  
**Requires at least:** 4.9.8  
**Tested up to:** 4.9.8  
**Stable tag:** 1.1.1  
**License:** GPLv3 or later  
**License URI:** https://www.gnu.org/licenses/gpl-3.0.html  

CAS Single Sign-On integration for Pressbooks.


## Description 

[![Packagist](https://img.shields.io/packagist/v/pressbooks/pressbooks-cas-sso.svg?style=flat-square)](https://packagist.org/packages/pressbooks/pressbooks-cas-sso) [![GitHub release](https://img.shields.io/github/release/pressbooks/pressbooks-cas-sso.svg?style=flat-square)](https://github.com/pressbooks/pressbooks-cas-sso/releases) [![Travis](https://img.shields.io/travis/pressbooks/pressbooks-cas-sso.svg?style=flat-square)](https://travis-ci.org/pressbooks/pressbooks-cas-sso/) [![Codecov](https://img.shields.io/codecov/c/github/pressbooks/pressbooks-cas-sso.svg?style=flat-square)](https://codecov.io/gh/pressbooks/pressbooks-cas-sso)

Plugin to integrate Pressbooks with [Central AuthenticationService (CAS)](http://en.wikipedia.org/wiki/Central_Authentication_Service) single sign-on architectures.

Users who attempt to login to Pressbooks are redirected to the central CAS sign-on screen. After the user’s credentials are verified, they are redirected back to the Pressbooks
network. If the CAS username matches the Pressbooks username, the user is recognized as valid and allowed access. If the CAS user does not have an account in Pressbooks, a new
user can be created, or access can be refused, depending on the configuration.


## Installation 

```
composer require pressbooks/pressbooks-cas-sso
```

Or, download the latest version from the releases page and unzip it into your WordPress plugin directory: https://github.com/pressbooks/pressbooks-cas-sso/releases

Then, activate and configure the plugin at the Network level.

Read the developer documentation for more info: https://docs.pressbooks.org/integrations/pressbooks-cas-sso



### Optional Config 

    putenv( 'PB_CAS_CERT_PATH=/path/to/cachain.pem' ); // Path to the CA chain that issued the CAS server certificate

Because this plugin uses the fabulous [apereo/phpCAS](https://github.com/apereo/phpCAS) library, [many other configuration variables can be tweaked](https://github.com/apereo/phpCAS/tree/master/docs/examples).


## Screenshots 

![Pressbooks CAS Administration.](screenshot-1.png)


## Changelog 

### 1.1.1 
 * New `pb_integrations_multidomain_email` filter
 * Update apereo/phpCAS to 5ad9c1e


### 1.1.0 
* Bump minimum PHP to 7.1 ([#17](https://github.com/pressbooks/pressbooks-cas-sso/issues/17)): [#18](https://github.com/pressbooks/pressbooks-cas-sso/pull/18)
* Switch to Pressbooks Coding Standards ([#16](https://github.com/pressbooks/pressbooks-cas-sso/issues/16)): [#18](https://github.com/pressbooks/pressbooks-cas-sso/pull/18)
* Switch to wp dist-archive for release build ([#14](https://github.com/pressbooks/pressbooks-cas-sso/issues/14)): [#18](https://github.com/pressbooks/pressbooks-cas-sso/pull/18)


### 1.0.0 
* Semantic versioning starts here.
* Improve code to get contact email.


### 0.4.1 
* Fix build.


### 0.4.0 
* Redirect to the page the user signed in from (network homepage or book homepage).
* Clarify error message when CAS is set to "Refuse Access".
* Fix unwanted slashes when button text has apostrophe.
* Fix Travis CI build script.


### 0.3.0 
* Demo for Open source Slack channel.


### 0.2.0 
* Initial release.


## Upgrade Notice 


### 1.1.1 
* Pressbooks CAS Single Sign-On requires Pressbooks >= 5.5.2 and WordPress >= 4.9.8.
