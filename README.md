# WooCommerce JSON API


A simple, Abstract JSON API for Wordpress' Awesome Plugin: WooCommerce

Here is an example, using jQuery

    var url = 'http://woo.localhost/c6db13944977ac5f7a8305bbfb06fd6a/?callback=?';
    params = { action: 'woocommerce_json_api', proc:"get_products"};
    params.arguments = {token: 1234, per_page: 10, page: 1}
    jQuery.getJSON(url,params).done(function (data) { console.log(data);});

And here would be the response, trimmed down a bit:

    jQuery171020958687388338149_1373358917457({
        "callback": "jQuery171020958687388338149_1373358917457",
        "action": "woocommerce_json_api",
        "proc": "get_products",
        "arguments": {
            "token": "1234",
            "per_page": "10",
            "page": "1"
        },
        "_": "1373358924670",
        "status": true,
        "errors": [

        ],
        "warnings": [

        ],
        "notifications": [

        ],
        "payload": [
            {
                "id": "1461",
                "name": "Api created product 14",
                "slug": "",
                "type": "product",
                "description": "",
                "status": "",
                "sku": "A349",
                "downloadable": "",
                "virtual": "",
                "manage_stock": "",
                "sold_individually": "",
                "featured": "",
                "allow_backorders": "",
                "quantity": 0,
                "height": "",
                "weight": "",
                "length": "",
                "price": "15.95",
                "regular_price": "15.95",
                "sale_price": "",
                "sale_from": "",
                "sale_to": "",
                "attributes": "",
                "tax_class": "",
                "tax_status": "",
                "categories": [

                ],
                "tags": [

                ],
                "featured_image": false
            },
            {
                "id": "1462",
                "name": "Api created product 26",
                "slug": "",
                "type": "product",
                "description": "<b>Hello World!<\/b>",
                "status": "",
                "sku": "A241",
                "downloadable": "",
                "virtual": "",
                "manage_stock": "",
                "sold_individually": "",
                "featured": "",
                "allow_backorders": "",
                "quantity": 0,
                "height": "",
                "weight": "",
                "length": "",
                "price": "15.95",
                "regular_price": "15.95",
                "sale_price": "",
                "sale_from": "",
                "sale_to": "",
                "attributes": "",
                "tax_class": "",
                "tax_status": "none",
                "categories": [

                ],
                "tags": [

                ],
                "featured_image": false
            },
            ...
            {
                "id": "1279",
                "name": "Bodum Brazil Coffee Press 8 Cup",
                "slug": "bodum-brazil-coffee-press-8-cup",
                "type": "product",
                "description": "<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, commodo vitae, ornare sit amet, wisi. Aenean fermentum, elit eget tincidunt condimentum, eros ipsum rutrum orci, sagittis tempus lacus enim ac dui. Donec non enim in turpis pulvinar facilisis. Ut felis. Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan porttitor, facilisis luctus, metus<\/p>",
                "status": "instock",
                "sku": "W027",
                "downloadable": "no",
                "virtual": "no",
                "manage_stock": "no",
                "sold_individually": "",
                "featured": "no",
                "allow_backorders": "no",
                "quantity": 0,
                "height": "",
                "weight": "",
                "length": "",
                "price": "43",
                "regular_price": "43",
                "sale_price": "",
                "sale_from": "",
                "sale_to": "",
                "attributes": "",
                "tax_class": "",
                "tax_status": "taxable",
                "categories": [
                    {
                        "id": "26",
                        "name": "Coffee &amp; Tea",
                        "slug": "coffee-tea",
                        "description": "",
                        "parent_id": "0",
                        "count": "4",
                        "group_id": "0",
                        "taxonomy_id": "28"
                    },
                    {
                        "id": "41",
                        "name": "Coffee Plungers",
                        "slug": "coffee-plungers",
                        "description": "",
                        "parent_id": "26",
                        "count": "1",
                        "group_id": "0",
                        "taxonomy_id": "43"
                    }
                ],
                "tags": [

                ],
                "featured_image": "http:\/\/woo.localhost\/wp-content\/uploads\/2012\/12\/Bodum-Coffee-Press-1.jpg"
            },
            ...
        ],
        "payload_length": 10
    });

### How to install

`cd` into your wp-content/plugins directory and clone the repo

`git clone git://github.com/jasonknight/woocommerce-json-api.git`

### How to update

`cd` into your wp-content/plugins/woocommerce-json-api directory and run:

`git pull`

### How it works

This is a generalized JSON api to connect to woocommerce for thirdparty non PHP apps,
that may or may not be running on the same server. It was built to integrate Salor Retail
and Salor Hospitality into a Wordpress/WooCommerce webstore.

#### POSTing Data to WooCommerce via the API

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

#### Caveat

The JSON API is very general, and works from it's own idiom about products because it was created to work
with different types of software that may work and think about sales differently, the JSON communication
medium is an intermediate representation of Products, Categories and so on. This is to facilitate a
simple communication between 3rd party software that is not part of the plugin ecosystem of woocommerce.

## JSON API Calls

Here is a list of all API Calls currently supported. All **What you receive** JSON is what is actually
output from a request. It should not be necessary for you to even look at the PHP code.

### Checking that the API is up, Getting the System's Time and Date

#### get_system_time

This call is essentially a test to see if the JSON API is up and running.

#### What you send

    {
      action: "woocommerce_json_api",
      proc:   "get_system_time"
      arguments: {
        token: "Your User Token",
      },
    }

#### What you receive

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

### Getting products

#### get_products

If you want so simply iterate over all products (bad idea unless you know that you really need this), use this call.

#### What you send

    {
        "action": "woocommerce_json_api",
        "proc": "get_products",
        "arguments": {
            "token": "1234",
            "per_page": 2,
            "page": 1
        }
    }
    
#### What you receive

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
              "id": "1461",
              "name": "Api created product 14",
              "slug": "",
              "type": "product",
              "description": "",
              "status": "",
              "sku": "A349",
              "downloadable": "",
              "virtual": "",
              "manage_stock": "",
              "sold_individually": "",
              "featured": "",
              "allow_backorders": "",
              "quantity": 0,
              "height": "",
              "weight": "",
              "length": "",
              "price": "15.95",
              "regular_price": "15.95",
              "sale_price": "",
              "sale_from": "",
              "sale_to": "",
              "attributes": "",
              "tax_class": "",
              "tax_status": "",
              "categories": [

              ],
              "tags": [

              ],
              "featured_image": false
          },
          {
              "id": "1462",
              "name": "Api created product 26",
              "slug": "",
              "type": "product",
              "description": "<b>Hello World!<\/b>",
              "status": "",
              "sku": "A241",
              "downloadable": "",
              "virtual": "",
              "manage_stock": "",
              "sold_individually": "",
              "featured": "",
              "allow_backorders": "",
              "quantity": 0,
              "height": "",
              "weight": "",
              "length": "",
              "price": "15.95",
              "regular_price": "15.95",
              "sale_price": "",
              "sale_from": "",
              "sale_to": "",
              "attributes": "",
              "tax_class": "",
              "tax_status": "none",
              "categories": [

              ],
              "tags": [

              ],
              "featured_image": false
          }
      ],
    }

### Getting product(s) by id

You can keep and store the product id, and then use it in later calls. The JSON API **always** returns a collection **even** if all you wanted
was a single item.

#### What you send

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
 


## Getting product(s) by SKU

#### What you send

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
    

## Getting product(s) by tags

#### What you send

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
### Getting Tags

#### What you send

    {
        "action": "woocommerce_json_api",
        "proc": "get_tags",
        "arguments": {
            "token": "1234",
        }
    }
