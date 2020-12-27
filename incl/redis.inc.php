<?php
/**
 * Barcode Buddy for Grocy
 *
 * PHP version 7
 *
 * LICENSE: This source file is subject to version 3.0 of the GNU General
 * Public License v3.0 that is attached to this project.
 *
 *
 * Redis cache connection
 *
 * @author     Marc Ole Bulling
 * @copyright  2020 Marc Ole Bulling
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html  GNU GPL v3.0
 * @since      File available since Release 1.6
 */


/**
 * Creates a cache connection and offers cache functions
 */
class RedisConnection {
    const KEY_CACHE_AVAILABLE        = "bbuddy_isavail";
    const KEY_CACHE_ALL_PRODUCT_INFO = "bbuddy_apo";
    const KEY_CACHE_ALL_BARCODES     = "bbuddy_abc";
    const TIMEOUT_REDIS_CACHE_S      = 5 * 60;


    /**
     * Connects to Redis server
     * @return Redis|null
     * @throws RedisException Exception when unable to connect, for some reason not documented
     */
    private static function establishConnection(): ?Redis {
        $redis       = new Redis();
        $isConnected = $redis->connect('127.0.0.1', 6379, 0.2);
        if (!$isConnected)
            return null;
        return $redis;

    }

    private static function connectToRedis(): ?Redis {
        try {
            $redis = self::establishConnection();
        } catch (RedisException $e) {
            return null;
        }
        return $redis;
    }

    /**
     * Checks if Grocy data is cached
     * @return bool
     */
    public static function isCacheAvailable(): bool {
        return self::getData(self::KEY_CACHE_AVAILABLE) !== false;
    }

    /**
     * Gets a cached version of API::getAllProductsInfo()
     * @return array|null
     */
    public static function getAllProductsInfo(): ?array {
        $data = self::getData(self::KEY_CACHE_ALL_PRODUCT_INFO);
        if ($data === false)
            return null;
        $result = unserialize($data);
        if ($result === false)
            return null;
        return $result;
    }

    /**
     * Saves the result of API::getAllProductsInfo() to cache
     * @param $input
     */
    public static function cacheAllProductsInfo($input) {
        self::setData(self::KEY_CACHE_AVAILABLE, true);
        self::setData(self::KEY_CACHE_ALL_PRODUCT_INFO, serialize($input));
    }


    /**
     * Gets a cached version of API::getAllBarcodes()
     * @return array|null
     */
    public static function getAllBarcodes(): ?array {
        $data = self::getData(self::KEY_CACHE_ALL_BARCODES);
        if ($data === false)
            return null;
        $result = unserialize($data);
        if ($result === false)
            return null;
        return $result;
    }


    /**
     * Saves the result of API::getAllBarcodes() to cache
     * @param $input
     */
    public static function cacheAllBarcodes($input) {
        self::setData(self::KEY_CACHE_AVAILABLE, true);
        self::setData(self::KEY_CACHE_ALL_BARCODES, serialize($input));
    }


    public static function expireAllBarcodes() {
        self::expires(self::KEY_CACHE_ALL_BARCODES);
    }

    public static function expireAllProductInfo() {
        self::expires(self::KEY_CACHE_ALL_PRODUCT_INFO);
    }

    public static function invalidateCache() {
        self::expires(self::KEY_CACHE_AVAILABLE);
        self::expires(self::KEY_CACHE_ALL_BARCODES);
        self::expires(self::KEY_CACHE_ALL_PRODUCT_INFO);
    }

    private static function expires($key) {
        $redis = self::connectToRedis();
        if ($redis != null) {
            $redis->expire($key, 0);
        }
    }

    private static function setData($key, $data) {
        $redis = self::connectToRedis();
        if ($redis != null) {
            $redis->set($key, $data, self::TIMEOUT_REDIS_CACHE_S);
        }
    }

    private static function getData($key) {
        $redis = self::connectToRedis();
        if ($redis != null) {
            return $redis->get($key);
        }
        return false;
    }

    public static function isRedisAvailable(): bool {
        return self::connectToRedis() != null;
    }

    public static function getErrorMessage(): ?string {
        try {
            self::establishConnection();
        } catch (RedisException $e) {
            return $e->getMessage();
        }
        return null;
    }

}