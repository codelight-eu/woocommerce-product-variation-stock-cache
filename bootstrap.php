<?php

add_action('after_setup_theme', function() {

    /**
     * To skip automatically instantiating and running the package, pass false in this filter
     */
    if (!apply_filters('codelight/variation_stock/autoload', true)) {
        return;
    }

    $cache = \Codelight\VariationStockCache\Cache::getInstance();
    $stock = new \Codelight\VariationStockCache\VariationStockManager($cache);
    $stock->setup();
});
