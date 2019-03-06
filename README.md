# WooCommerce Product Variation Stock Cache
This library adds a way to filter variable products by attributes *and* stock status.

WooCommerce currently doesn't have a way of querying products by attribute and displaying it only if a variation with the given attribute is in stock. For details, [see the github issue](https://github.com/woocommerce/woocommerce/issues/20689).
This functionality is scheduled to be released in v4.0, which is scheduled to be released somewhere in 2019, but no certain information is available yet.

Until then, you can use this library as a relatively simple workaround that won't affect performance much.

## How it works
The library keeps track of each variation's stock status in the parent product's post meta.  

Let's say you have an attribute "Size" with the following values: "S", "M", "L". The library stores the stock statuses in the parent post meta like this:
```
'_codelight_stock_pa_size:s' => 'instock'
'_codelight_stock_pa_size:m' => 'instock'
'_codelight_stock_pa_size:l' => 'instock'
```

This allows you to use simple meta queries to fetch only those variable parent products that have a variation with a specific attribute in stock.

## Installation
`composer require codelight/woocommerce-product-variation-stock-cache`

The library is initialized automatically and will start keeping track of stock status changes. To generate cache for existing products, see below.

## Usage
In your WP_Query for retrieving the products, use the following meta query:
```php
<?php

$args['meta_query'][] = [
    'key'   => Cache::getInstance()->getCacheKey(['attribute_name' => 'attribute_value']),
    'value' => 'instock',
];

```

For example:

```php
<?php

$args['meta_query'][] = [
    'key'   => Cache::getInstance()->getCacheKey(['pa_size' => 'm']),
    'value' => 'instock',
];

```

Keep in mind that the attribute_value is the *slug* of the attribute, not the human-readable value.

## Multi-dimensional variation attributes
The library also works with multi-dimensional variation attributes. Let's say that in addition to the "Size" attribute, you also have "Color": "red" and "blue".
In this case, a power set with the following cache keys will be created:
```
'_codelight_stock_pa_size:s' => 'instock'
'_codelight_stock_pa_size:m' => 'instock'
'_codelight_stock_pa_size:l' => 'instock'
'_codelight_stock_pa_color:red' => 'instock'
'_codelight_stock_pa_color:blue' => 'instock'
'_codelight_stock_pa_color:red_pa_size:s' => 'instock'
'_codelight_stock_pa_color:red_pa_size:m' => 'instock'
'_codelight_stock_pa_color:red_pa_size:l' => 'instock'
'_codelight_stock_pa_color:blue_pa_size:s' => 'instock'
'_codelight_stock_pa_color:blue_pa_size:m' => 'instock'
'_codelight_stock_pa_color:blue_pa_size:l' => 'instock'
```

Having a power set allows you to filter by any single attribute or combination of attributes, in this case:
* only size, 
* only color,
* both size and color.

The following example will filter by both size and color:

```php
<?php

$args['meta_query'][] = [
    'key'   => Cache::getInstance()->getCacheKey(['pa_size' => 'm', 'pa_color' => 'red']),
    'value' => 'instock',
];

```

**Note that this library is not a good solution if you have a lot of attributes.** The number of postmeta fields increases exponentially with the attributes and that's going to hurt your performance.
However, it should still work properly, even with very long cache keys - if a cache key exceeds the allowed 255 chars, an md5 hash will be used instead.

## Generating the initial cache
If you need to prime your cache for existing products:
1) Log in as (super) admin
2) Append the following query string to any URL: codelight_prime_cache=1
3) You won't see anything happening, but the cache will be primed.

Note that this will attempt to take care of **all** products in one request, so if you have a lot of products, this might timeout and crash.  
Batch priming and a proper CLI command are in the todo list, help is appreciated.

## Customization

### Ignore an attribute
By default, all product attributes will be used for generating the cache. If you wish to use only specific attributes:

```php
<?php
add_filter('codelight/variation_stock/tracked_attributes', function(array $attributes) {
  // modify your attributes here
  return $attributes;
});
```

### Change the postmeta key prefix
The default prefix is `_codelight_stock`. To change that:

```php
<?php
add_filter('codelight/variation_stock/key_prefix', function() {
  return '_custom_prefix';
});
```

### Disable automatically initializing the library
The library runs automatically and hooks are automatically set up on 'after_setup_theme'. If you wish to change that:

```php
<?php
add_filter('codelight/variation_stock/autoload', function() {
  return false;
});
```

## Contributing
Any help is appreciated!

## About
This library is proudly brought to you by [Codelight](https://codelight.eu), a web development agency based in Estonia, EU.

