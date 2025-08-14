<?php
namespace App\Services\Cache;

abstract class BaseCache {

    protected $redis;

    public function get($key) {
        return $this->redis->get($key);
    }

    public function set ($key, $value) {
        if(is_array($value)) {
            $value = json_encode($value);
        }
        return $this->redis->set($key, $value);
    }

    public function remove($key) {
        return $this->redis->del($key);
    }

    public function addItemToList ($key, $value) {
        if(is_array($value)) {
            $value = json_encode($value);
        }
        return $this->redis->lpush($key, $value);
    }



    public function getItemFromList($key, $number, $order = false) {
        if ($order) {
            return $this->redis->lrange($key, -$number, -1);
        } else {
            return $this->redis->lrange($key, 0, $number);
        }

    }


    public function countList($key) {
        $length =   $this->redis->llen($key);
        return $length;
    }


    public function addItemSet($key, $values) {

        return $this->redis->sadd($key, $values);
    }

    public function chekExistItemSet($key, $item){
        $check = $this->redis->sismember($key, $item);
        return boolval($check);

    }
    public function countSet($key) {
        $length = $this->redis->scard($key);
        return $length;
    }

    public function getAllItemSet($key) {
        $data = $this->redis->smembers($key);
        return $data;
    }


    public function removeItemFromSet($key, $item) {
        try {
            return $this->redis->srem($key, $item);
        } catch (\Exception $ex) {
        }
    }

    // public function addItemToHash($key,$field, $data, $override = true) {
    //     if(is_array($data)) {
    //         $data = json_encode($data);
    //     }
    //     if($override == true) {
    //         return $this->redis->hset($key, $field, $data);
    //     } else {
    //         return $this->redis->hsetnx($key, $field, $data);
    //     }
    // }

    public function addItemToHash($key, $field, $data, $override = true, $expire = null) {
        if(is_array($data)) {
            $data = json_encode($data);
        }
        if($override == true) {
            $this->redis->hset($key, $field, $data);
        } else {
            $this->redis->hsetnx($key, $field, $data);
        }

        if ($expire) {
            $this->redis->expire($key, $expire);
        }
    }

    public function addItemsToHash($key, $list = [],$override = false) {

        foreach ($list as $field => $value) {
            $this->addItemToHash($key,$field,$value, $override);
        }
    }

    public function countHash($key) {
        $total = $this->redis->hlen($key);
        return $total;
    }

    public function getItemsFromHash($key, $items) {
        return $this->redis->hmget($key, $items);
    }

    public function getItemFromHash($key, $field) {
        return $this->redis->hget($key, $field);
    }

    public function removeItemsFromHash($key, $field) {

        return $this->redis->hdel($key, $field);
    }

    public function addItemSortedSet($key, $value){
        $result =  $this->redis->zadd($key,"CH",$value);
        return $result;
    }

    public function chekExistItemSortedSet($key, $item){
        $check = $this->redis->zscore($key, $item);
        return boolval($check);

    }

    public function getItemFromSortedSet($key, $start , $end ) {
        return $this->redis->zrange($key, $start, $end);
    }

    public function getItemFromSortedSetByScore($key, $min = 0, $max = 300, $includeScore = false) {
        return $this->redis->zrangebyscore($key, $min, $max, ['withscores' => $includeScore]);
    }

    public function getItemScoreFromSortedSet($key, $item) {
        return $this->redis->zscore($key, $item);
    }

    public function removeItemFromSortedSetByScore($key, $min = 0, $max = 300) {
        return $this->redis->zremrangebyscore($key, $min, $max);
    }

    public function removeItemFromSortedSet($key, $item) {
        return $this->redis->zrem($key, $item);
    }
    

    public function countSortSet($key) {
        $total = $this->redis->zcard($key);
        return $total;
    }

    public function popItemFromList($key) {
        $total = $this->redis->lpop($key);
        return $total;
    }

    public function popItemFromSet($key) {
        $total = $this->redis->spop($key);
        return $total;
    }

}
