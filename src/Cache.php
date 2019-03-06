<?php

namespace Codelight\VariationStockCache;

/**
 * Handles creating cache keys from product attribute and value combinations.
 *
 * Class Cache
 *
 * @package Codelight\VariationStockCache
 */
class Cache
{
    /**
     * Default cache key prefix for the post meta
     *
     * @var string
     */
    protected $keyPrefix = '_codelight_stock';

    /**
     * Singleton instance
     *
     * @var Cache
     */
    private static $instance;

    /**
     * When created, allow filtering the cache key prefix
     */
    private function __construct()
    {
        $this->keyPrefix = apply_filters('codelight/variation_stock/key_prefix', $this->keyPrefix);
    }

    /**
     * Generate an array of all applicable cache keys based on a power set of all attributes
     *
     * @param array $attributes
     * @return array
     */
    public function getCacheKeys(array $attributes)
    {
        // Remove attributes with empty value, as they're equivalent to "Any [attribute]"
        $attributes = array_filter($attributes);

        // Allow removing some of the attributes from being tracked
        $attributes = apply_filters('codelight/variation_stock/tracked_attributes', $attributes);

        if (!is_array($attributes) or !count($attributes)) {
            return [];
        }

        /**
         * Generate all possible combinations of sets containing the given attributes.
         * This means that for example, given a T-shirt with attributes "Red" and "M", we will get the following results:
         * ["pa_size" => "m"],
         * ["pa_color" => "red"],
         * ["pa_size" => "m", "pa_color" => "red"]
         *
         * This allows us to filter the products by a single attribute as well as multiple at the same time.
         */
        $powerSet = $this->generateArrayPowerSet($attributes);

        if (!count($powerSet)) {
            return [];
        }

        $cacheKeys = [];

        foreach ($powerSet as $item) {
            $cacheKeys[] = $this->getCacheKey($item);
        }

        return $cacheKeys;
    }

    /**
     * Given the variation attributes of a product, get the corresponding cache key
     *
     * @param array $attributes
     * @return string
     */
    public function getCacheKey(array $attributes)
    {
        // Since the order of the attributes is editable via admin,
        // sort alphabetically to ensure they are always in the same order.
        ksort($attributes);

        // Start assembling the cache key
        $key = $this->keyPrefix;

        // Loop over each attribute and add the key and value
        foreach ($attributes as $name => $value) {

            // Remove the 'attibute_' prefix from the attribute name
            $name = str_replace('attribute_', '', $name);

            // Ensure the attribute value is lowercase
            $value = strtolower($value);

            // Add an underscore suffix to the key act as separator from previous values - if required
            if (substr($key, -1) !== "_") {
                $key .= '_';
            }

            // Use colon character to separate attribute name and value for better readability/debugging via the database
            $key .= "{$name}:{$value}";
        }

        // The postmeta key field has a maximum length of 255 chars. If we go over it, just md5 the result.
        if (strlen($key) > 255) {
            $key = $this->keyPrefix . '_' . md5($key);
        }

        return $key;
    }

    /**
     * @param array $array
     * @return array
     */
    protected function generateArrayPowerSet(array $array)
    {
        $results = [[]];

        foreach ($array as $key => $value) {
            foreach ($results as $combination) {
                array_push($results, array_merge([$key => $value], $combination));
            }
        }

        // Remove the initially added empty set, as we have no use for it
        array_shift($results);

        return $results;
    }

    /**
     * To ensure it's possible to access this class comfortably from anywhere
     * for using its getCacheKey() function, it's a singleton.
     *
     * @return Cache
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }
}
