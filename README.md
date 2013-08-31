# WooCommerce JSON API

** You cannot test this code while logged into Wordpress.** You will need to
log out of wordpress, or open a new incognito window to test the code.

A simple, Abstract JSON API for Wordpress' Awesome Plugin: WooCommerce

Here is an example, using jQuery

```javascript
    var url = 'http://woo.localhost/c6db13944977ac5f7a8305bbfb06fd6a/?callback=?';
    params = { action: 'woocommerce_json_api', proc:"get_products"};
    params.arguments = {token: 1234, per_page: 10, page: 1}
    jQuery.getJSON(url,params).done(function (data) { console.log(data);});
```

And here would be the response, trimmed down a bit:

```javascript
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
            ...
            {
                "id": "1279",
                "name": "Bodum Brazil Coffee Press 8 Cup",
                "slug": "bodum-brazil-coffee-press-8-cup",
                "type": "product",
                "description": "<p>Pellentesque habitant morbi ...<\/p>",
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
```

### How to install

`cd` into your wp-content/plugins directory and clone the repo

`git clone git://github.com/jasonknight/woocommerce-json-api.git`

### How to update

`cd` into your wp-content/plugins/woocommerce-json-api directory and run:

`git pull`

### Visit the Wiki for more Documentation

### TODO