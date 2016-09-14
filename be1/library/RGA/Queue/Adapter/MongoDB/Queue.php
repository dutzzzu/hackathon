<?php
class RGA_Queue_Adapter_MongoDB_Queue extends Shanty_Mongo_Document {
    protected static $_collection = 'queue';

    public static function one(array $query = array(), array $fields = array()) {
        $options = Zend_Registry::get('config');
        $cache =  new RGA_Cache_Proxy(
            $options['cache']['frontend']['class'],
            $options['cache']['backend']['class'],
            $options['cache']['frontend']['options'],
            $options['cache']['backend']['options']
        );
        $cacheKey = md5(serialize(func_get_args()));
        if (($body = $cache->load($cacheKey)) === false) {
            $body = parent::one($query,$fields);
            $cache->save($body, $cacheKey, array('queue'));
        }

        return $body;
    }

}