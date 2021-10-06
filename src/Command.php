<?php


namespace inkime\elasticsearch;

/**
 * Class Command 执行 Elasticsearch REST API.
 * @package inkime\elasticsearch
 */
class Command
{
    /**
     * @var Connection
     */
    public $db;

    /**
     * @var array 查询体，数组或者JSON字符串
     */
    public $queryParts;

    public function __construct($config = [])
    {
        self::configure($this, $config);
    }

    public function search()
    {
        $query = $this->queryParts;
        if (isset($this->layout)) {
            $result['output'] = $this->layout == 'json' ? $query['statement'] : json_decode($query['statement'], true);
            return $result;
        }
        if (empty($query)) {
            $query = '{}';
        }
        if (is_array($query)) {
            $query = json_encode($query, JSON_UNESCAPED_UNICODE);
        }

        return $this->db->post($query);
    }

    public static function configure($object, $properties)
    {
        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }

        return $object;
    }
}