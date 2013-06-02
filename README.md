# woocommerce-json-api


A simple, Abstract JSON API for Wordpress' Awesome Plugin: WooCommerce

## How to install

`cd` into your wp-content/plugins directory and clone the repo

`git clone git://github.com/jasonknight/woocommerce-json-api.git`

## How to update

`cd` into your wp-content/plugins/woocommerce-json-api directory and run:

`git pull`

## How it works

This is a generalized JSON api to connect to woocommerce for thirdparty non PHP apps,
that may or may not be running on the same server. It was built to integrate Salor Retail
and Salor Hospitality into a Wordpress/WooCommerce webstore.

### POSTing Data to WooCommerce via the API

Discover your ajax url, which should be http://yoursite.com/wp-admin/admin-ajax.php

    {
      action: "woocommerce_json_api",
      proc:   "request_authentication"
      arguments: {
        user_token: 'xyz",
      },
    }

You will receive:

    {
      action: "woocommerce_json_api",
      proc:   "request_authentication",
      status: true|false,
      errors: [],
      payload: [
        { secret_key: "0ABCDEF456" }, // this will end up being the wpnonce
      ]
    }

All requests and responses rigidly follow this format.

Sending a request is always in this format:

    {
      action: "woocommerce_json_api",
      proc:   "api_method_here"
      arguments: {
        secret_key: "What request_authentication replied with",
        arg1: "xxx",
        arg2: true,
        argn: ...,
      },
    }

You will always receive a JSON object in this format:

    {
      action: "woocommerce_json_api",
      proc:   "api_method_here",
      status: true|false,
      errors: [], // always an empty collection, 
                  // when errors are present, they are represented as 
                  // {text: 'text', code: 12344, retry: true|false ... }
      payload: [
        { secret_key: "0ABCDEF456" }, // may be a collection of objects, 
                                      // arrays, strings, or JS values, or 
                                      // could be empty, even on success
      ], // Always a collection, even if empty
    }

### Caveat

The JSON API is very general, and works from it's own idiom about products because it was created to work
with different types of software that may work and think about sales differently, the JSON communication
medium is an intermediate representation of Products, Categories and so on. This is to facilitate a
simple communication between 3rd party software that is not part of the plugin ecosystem of woocommerce.

### Coding Standards

Wordpress and WooCommerce have their own ideas about coding standards. While these are fine, they are generally
designed for plugin developers and theme tweakers to follow to smooth their participation within the plugin
ecosystem of a website. This plugin is about Enterprise Integration, a field of programming that has
it's own standards of operation and rigeur. All attempts are made to obey WordPress coding standards except
where they might conflict with established methods of Enterprise Integration and Object Oriented programming.

The goal of this plugin is to be comprehensive and understandable by diverse programmers, from other OOP languages,
so it will strictly adhere to well established OOP patterns and paradigms, or in so much as this is possible.

When designing an API it's not possible to please everyone, so this plugin attempts to *displease everyone
as equally as possible*. This is a roundabout way of saying: If you don't like it, **don't use it**, if you need it,
**learn it**, and if you have an opinion, please `cat` it to `/dev/null` :)
