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
