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

Upon installing the plugin, a page will be created for you called WooCommerce JSON API. You will
need to provide the permalink to users of the API.

In some instances, certain themes may not be using wp_list_pages, and so we cannot easily prevent the
page from showing up in the menu. In that case, delete the page, and then any page on the site
can be used as an entry to the JSON API. This method is less secure, but it will work until you
get your theme developer to use the standard wp_list_pages so we can filter it out easily, or
you complain loud enough to Wordpress for them to add hidden pages that don't require a password
to access. 

    {
      action: "woocommerce_json_api",
      proc:   "get_system_time"
      arguments: {
        token: 'xyz",
      },
    }

You will receive:

    {
      "action":"woocommerce_json_api",
      "proc":"get_system_time",
      "arguments":{
        "token":"1234"
      },
      "status":true,
      "errors":[],
      "payload":[
        {
          "timezone":"UTC",
          "date":"2013-06-04",
          "time":"04:46:29"
        }
      ]
    }


All requests and responses rigidly follow this format.

Sending a request is always in this format:

    {
      action: "woocommerce_json_api",
      proc:   "api_method_here"
      arguments: {
        token: "YourUserToken",
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
      arguments: {
         token: "YourUserToken" 
      }
      payload: [
                                      // may be a collection of objects, 
                                      // arrays, strings, or JS values, or 
                                      // could be empty, even on success
      ], // Always a collection, even if empty
    }

### Caveat

The JSON API is very general, and works from it's own idiom about products because it was created to work
with different types of software that may work and think about sales differently, the JSON communication
medium is an intermediate representation of Products, Categories and so on. This is to facilitate a
simple communication between 3rd party software that is not part of the plugin ecosystem of woocommerce.

# JSON API Calls

Here is a list of all API Calls currently supported. All **What you receive** JSON is what is actually
output from a request. It should not be necessary for you to even look at the PHP code.

## get_system_time

This call is essentially a test to see if the JSON API is up and running.

### What you send

    {
      action: "woocommerce_json_api",
      proc:   "get_system_time"
      arguments: {
        token: "Your User Token",
      },
    }

### What you receive

    {
      "action":"woocommerce_json_api",
      "proc":"get_system_time",
      "arguments":{
        "token":"1234"
      },
      "status":true,
      "errors":[],
      "payload":[
        {
          "timezone":"UTC",
          "date":"2013-06-04",
          "time":"04:46:29"
        }
      ]
    }

## get_products

### What you send

    {
        "action": "woocommerce_json_api",
        "proc": "get_products",
        "arguments": {
            "token": "1234",
            "per_page": 2
        }
    }
    
### What you receive

    {
        "action": "woocommerce_json_api",
        "proc": "get_products",
        "arguments": {
            "token": "1234",
            "per_page": "2"
        },
        "status": true,
        "errors": [

        ],
        "payload": [
            [
                {
                    "name": "Eva Solo My Teapot",
                    "description": " ... Stuff Here ...",
                    "price": {
                        "amount": "75",
                        "currency": "GBP",
                        "symbol": "&pound;",
                        "taxable": true
                    },
                    "sku": "W039",
                    "stock": {
                        "managed": false,
                        "for_sale": "",
                        "in_stock": "",
                        "downloadable": false,
                        "virtual": false,
                        "sold_individually": false,
                        "download_paths": [

                        ]
                    }
                },
                {
                    "name": "Eva Solo My Teapot",
                    "description": " ... stuff here ...",
                    "price": {
                        "amount": "75",
                        "currency": "GBP",
                        "symbol": "&pound;",
                        "taxable": true
                    },
                    "sku": "W038",
                    "stock": {
                        "managed": false,
                        "for_sale": "",
                        "in_stock": "",
                        "downloadable": false,
                        "virtual": false,
                        "sold_individually": false,
                        "download_paths": [

                        ]
                    }
                }
            ]
        ]
    }
