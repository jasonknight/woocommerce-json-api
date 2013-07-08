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
      warnings: [], // warnings about what you did that didn't succeed, such as not finding one of many products by sku or some such
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

## Checking that the API is up, Getting the System's Time and Date

### get_system_time

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
      "warnings":[],
      "payload":[
        {
          "timezone":"UTC",
          "date":"2013-06-04",
          "time":"04:46:29"
        }
      ]
    }

## Getting products

### get_products

If you want so simply iterate over all products (bad idea unless you know that you really need this), use this call.

### What you send

    {
        "action": "woocommerce_json_api",
        "proc": "get_products",
        "arguments": {
            "token": "1234",
            "per_page": 2,
            "page": 1
        }
    }
    
### What you receive

    {
        "action": "woocommerce_json_api",
        "proc": "get_products",
        "arguments": {
            "token": "1234",
            "per_page": "2",
            "page": "1"
        },
        "status": true,
        "errors": [

        ],
        "warnings": [

        ],
        "payload": [
        {
            "id": "1304",
            "name": "Rainbow Salt",
            "slug": "rainbow-salt",
            "type": "product",
            "description": "",
            "status": "instock",
            "sku": "W003",
            "downloadable": "no",
            "virtual": "no",
            "manage_stock": "yes",
            "sold_individually": "",
            "featured": "no",
            "allow_backorders": "no",
            "quantity": 3,
            "height": "",
            "weight": "",
            "length": "",
            "price": "3",
            "regular_price": "3",
            "sale_price": "",
            "sale_from": "",
            "sale_to": "",
            "attributes": "",
            "tax_class": "",
            "tax_status": "taxable",
            "categories": [

            ],
            "tags": [
                {
                    "term_id": "56",
                    "name": "tag 2",
                    "slug": "tag-2",
                    "term_group": "0",
                    "term_taxonomy_id": "58",
                    "taxonomy": "product_tag",
                    "description": "",
                    "parent": "0",
                    "count": "1"
                },
                {
                    "term_id": "55",
                    "name": "Tes tag",
                    "slug": "tes-tag",
                    "term_group": "0",
                    "term_taxonomy_id": "57",
                    "taxonomy": "product_tag",
                    "description": "",
                    "parent": "0",
                    "count": "1"
                }
            ],
            "featured_image": "http:\/\/woo.localhost\/wp-content\/uploads\/2012\/12\/Zoku-Quick-Pop-Maker-3.jpg"
        }
    ],

    }

## Getting product(s) by id

You can keep and store the product id, and then use it in later calls. The JSON API **always** returns a collection **even** if all you wanted
was a single item.

### What you send

    {
        "action": "woocommerce_json_api",
        "proc": "get_products",
        "arguments": {
            "token": "1234",
            "ids": [
                1288
            ]
        }
    }
 
### What you receive

    {
        "action": "woocommerce_json_api",
        "proc": "get_products",
        "arguments": {
            "token": "1234",
            "ids": [
                "1288"
            ]
        },
        "status": true,
        "errors": [

        ],
        "warnings": [

        ],
        "payload": [
          {
              "id": "1304",
              "name": "Rainbow Salt",
              "slug": "rainbow-salt",
              "type": "product",
              "description": "",
              "status": "instock",
              "sku": "W003",
              "downloadable": "no",
              "virtual": "no",
              "manage_stock": "yes",
              "sold_individually": "",
              "featured": "no",
              "allow_backorders": "no",
              "quantity": 3,
              "height": "",
              "weight": "",
              "length": "",
              "price": "3",
              "regular_price": "3",
              "sale_price": "",
              "sale_from": "",
              "sale_to": "",
              "attributes": "",
              "tax_class": "",
              "tax_status": "taxable",
              "categories": [

              ],
              "tags": [
                  {
                      "term_id": "56",
                      "name": "tag 2",
                      "slug": "tag-2",
                      "term_group": "0",
                      "term_taxonomy_id": "58",
                      "taxonomy": "product_tag",
                      "description": "",
                      "parent": "0",
                      "count": "1"
                  },
                  {
                      "term_id": "55",
                      "name": "Tes tag",
                      "slug": "tes-tag",
                      "term_group": "0",
                      "term_taxonomy_id": "57",
                      "taxonomy": "product_tag",
                      "description": "",
                      "parent": "0",
                      "count": "1"
                  }
              ],
              "featured_image": "http:\/\/woo.localhost\/wp-content\/uploads\/2012\/12\/Zoku-Quick-Pop-Maker-3.jpg"
          }
      ],

    }

## Getting product(s) by SKU

### What you send

    {
        "action": "woocommerce_json_api",
        "proc": "get_products",
        "arguments": {
            "token": "1234",
            "skus": [
                "W021",
                "DOESNTEXIST"
            ]
        }
    }
    
### What you receive

    {
        "action": "woocommerce_json_api",
        "proc": "get_products",
        "arguments": {
            "token": "1234",
            "skus": [
                "W021",
                "DOESNTEXIST"
            ]
        },
        "status": true,
        "errors": [

        ],
        "warnings": [
            {
                "text": "DOESNTEXIST: Product does not exist",
                "code": 1,
                "sku": "DOESNTEXIST"
            }
        ],
        "payload": [
          {
              "id": "1304",
              "name": "Rainbow Salt",
              "slug": "rainbow-salt",
              "type": "product",
              "description": "",
              "status": "instock",
              "sku": "W003",
              "downloadable": "no",
              "virtual": "no",
              "manage_stock": "yes",
              "sold_individually": "",
              "featured": "no",
              "allow_backorders": "no",
              "quantity": 3,
              "height": "",
              "weight": "",
              "length": "",
              "price": "3",
              "regular_price": "3",
              "sale_price": "",
              "sale_from": "",
              "sale_to": "",
              "attributes": "",
              "tax_class": "",
              "tax_status": "taxable",
              "categories": [

              ],
              "tags": [
                  {
                      "term_id": "56",
                      "name": "tag 2",
                      "slug": "tag-2",
                      "term_group": "0",
                      "term_taxonomy_id": "58",
                      "taxonomy": "product_tag",
                      "description": "",
                      "parent": "0",
                      "count": "1"
                  },
                  {
                      "term_id": "55",
                      "name": "Tes tag",
                      "slug": "tes-tag",
                      "term_group": "0",
                      "term_taxonomy_id": "57",
                      "taxonomy": "product_tag",
                      "description": "",
                      "parent": "0",
                      "count": "1"
                  }
              ],
              "featured_image": "http:\/\/woo.localhost\/wp-content\/uploads\/2012\/12\/Zoku-Quick-Pop-Maker-3.jpg"
          }
      ],

        "payload_length": 1
    }

## Getting product(s) by tags

### What you send

    {
        "action": "woocommerce_json_api",
        "proc": "get_products_by_tags",
        "arguments": {
            "token": "1234",
            "tags": [
                'slug-1',
                'another-slug'
            ]
        }
    }
## Getting Tags

### What you send

    {
        "action": "woocommerce_json_api",
        "proc": "get_tags",
        "arguments": {
            "token": "1234",
        }
    }
