<?php

namespace Codelight\VariationStockCache;

/**
 * Handles saving stock cache on product stock status changes.
 *
 * Class VariationStockManager
 *
 * @package Codelight\VariationStockCache
 */
class VariationStockManager
{
    /* @var Cache */
    protected $cache;

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Whenever a product's stock is changed, modify the parent product's variation stock data
     */
    public function setup()
    {
        add_action('woocommerce_product_set_stock_status', [$this, 'cacheVariableStockData'], 10, 3);
        add_action('woocommerce_variation_set_stock_status', [$this, 'cacheVariationStockData'], 10, 3);
    }

    /**
     * When a parent product's stock status is changed, check if it has children/variations.
     * If yes, update the cached stock data for each variation.
     *
     * @param             $id
     * @param             $stockStatus
     * @param \WC_Product $product
     */
    public function cacheVariableStockData($id, $stockStatus, \WC_Product $product)
    {
        $variations = $product->get_children();

        if (!count($variations)) {
            return;
        }

        foreach ($variations as $variation) {
            /* @var $variationProduct \WC_Product_Variation */
            $variationProduct = wc_get_product($variation);
            if ($variationProduct) {
                $this->cacheStockData($variationProduct, $variationProduct->get_stock_status());
            }
        }
    }

    /**
     * When a variation's stock is updated, update the stock status cache.
     *
     * @param                       $id
     * @param                       $stockStatus
     * @param \WC_Product_Variation $product
     */
    public function cacheVariationStockData($id, $stockStatus, \WC_Product_Variation $product)
    {
        $this->cacheStockData($product, $stockStatus);
    }

    /**
     * Actually store stock cache in parent's postmeta
     *
     * @param \WC_Product_Variation $product
     * @param                       $stockStatus
     */
    public function cacheStockData(\WC_Product_Variation $product, $stockStatus)
    {
        if (!$product->get_parent_id()) {
            return;
        }

        $attributes = $product->get_variation_attributes();

        $cacheKeys = $this->cache->getCacheKeys($attributes);

        foreach ($cacheKeys as $key) {
            update_post_meta($product->get_parent_id(), $key, $stockStatus);
        }
    }
}
