#!/usr/bin/python

import json
import pycurl
import requests
import os.path


token = '1234' # Change token to that set up in your store 

new_product_data = {
  'name': "An API Created Product",
  'price': 5,
  'sku': "9999",
  'visibility': 'visible',
  'product_type': 'simple',
  'type': 'product',
  'status': 'instock',
  'description': 'API created product description',
  'quantity': 5,
  'manage_stock': 'yes',
  'featured_image': [{
    'name': 'fractal.png'
  }],
  'weight': '0.1',
  'height': '1',
  'width': '2',
  'length': '3'
}

request = {
  'action': 'woocommerce_json_api',
  'proc': 'set_products',
  'arguments': json.dumps({
    'token': token
    
  }),
  'payload': json.dumps([new_product_data,])
  
}

print "request="+str(request)

files={'images[0]': (os.path.abspath('fractal.png'), open('fractal.png', 'rb'), 'image/png')}

r=requests.post('http://localhost/', data=request, files=files) # Change to the URL of your store

print r.status_code
print r.content
print r.json()
