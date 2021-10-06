<?php


namespace inkime\elasticsearch;

use inkime\elasticsearch\traits\QueryTrait;


class Query
{
    use QueryTrait;

    /* @var string */
    public $collapse;

    /* @var string */
    public $layout;

    /* @var array */
    public $aggregations;

    /* @var array */
    public $map;

    /* @var array */
    public $highlight;

    public function all()
    {
        $result = $this->createCommand()->search();
        if ($result === false) {
            throw new Exception('Elasticsearch search query failed.');
        }
        if (isset($result['output'])) {
            return $result['output'];
        }
        if (empty($result['hits']['hits'])) {
            return [];
        }
        $rows = $result['hits']['hits'];
        return $this->populate($rows);
    }

    public function one()
    {
        $this->limit = 1;
        $result = $this->createCommand()->search();
        if ($result === false) {
            throw new Exception('Elasticsearch search query failed.');
        }
        if (isset($result['output'])) {
            return $result['output'];
        }
        if (empty($result['hits']['hits'])) {
            return false;
        }
        $record = reset($result['hits']['hits']);

        return $record['_source'];
    }

    public function query()
    {
        $result = $this->createCommand()->search();
        if ($result === false) {
            throw new Exception('Elasticsearch search query failed.');
        }
        if (isset($result['output'])) {
            return $result['output'];
        }
        return $result;
    }

    public function count()
    {
        $result = $this->createCommand()->search();

        if (isset($result['hits']['total'])) {
            return is_array($result['hits']['total']) ? (int)$result['hits']['total']['value'] : (int)$result['hits']['total'];
        }
        return 0;
    }

    public function exists()
    {
        return self::one() !== false;
    }

    /**
     * @param array $condition 自定义组装查询条件
     * @return $this
     */
    public function map($condition = [])
    {
        if ($condition) {
            $this->map = $condition;
        }
        return $this;
    }

    public function collapse($column)
    {
        $this->collapse = $column;
        return $this;
    }

    /**
     * 设置查询字段
     * @param array | string $column 字段名，支持数组 OR 逗号分割字符串
     * @return $this
     */
    public function select($column)
    {
        $this->select = $this->normalizeSelect($column);
        return $this;
    }

    /**
     * 设置返回 DSL 类型
     * @param string $layout json|array
     * @return $this
     */
    public function dsl($layout = 'json')
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * 追加查询字段
     * @param array | string $columns
     * @return $this
     */
    public function addSelect($columns)
    {
        if ($columns === null) {
            return $this->select($columns);
        }
        if (!is_array($this->select)) {
            $this->select = $this->normalizeSelect($this->select);
        }
        $this->select = array_merge($this->select, $this->normalizeSelect($columns));
        return $this;
    }

    protected function normalizeSelect($columns)
    {
        if (!is_array($columns)) {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }
        $select = [];
        foreach ($columns as $columnAlias => $columnDefinition) {
            if (is_string($columnAlias)) {
                $select[$columnAlias] = $columnDefinition;
                continue;
            }
            if (is_string($columnDefinition)) {
                // 支持alias
                if (
                    preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_\.]+)$/', $columnDefinition, $matches) &&
                    !preg_match('/^\d+$/', $matches[2]) &&
                    strpos($matches[2], '.') === false
                ) {
                    $select[$matches[2]] = $matches[1];
                    continue;
                }
                if (strpos($columnDefinition, '(') === false) {
                    $select[$columnDefinition] = $columnDefinition;
                    continue;
                }
            }
            $select[] = $columnDefinition;
        }
        return $select;
    }

    public function createCommand()
    {
        $connect = new Connection();
        $commandConfig = $connect->getQueryBuilder()->build($this);
        return $connect->createCommand($commandConfig);
    }

    public function populate($rows)
    {
        array_walk($rows, function (&$v) {
            $v = $v['_source'];
        });
        if ($this->indexBy === null) {
            return $rows;
        }
        $models = [];
        foreach ($rows as $key => $row) {
            if ($this->indexBy !== null) {
                if (is_string($this->indexBy)) {
                    $key = $row[$this->indexBy];
                } else {
                    $key = call_user_func($this->indexBy, $row);
                }
            }
            $models[$key] = $row;
        }
        return $models;
    }

    /**
     * @param $alias string 别名
     * @param $operate string 操作
     * @param $options array 配置项
     * @return $this
     */
    public function aggs($alias, $operate, $options)
    {
        $this->aggregations = [$alias => [$operate => $options]];
        return $this;
    }

    /**
     * @param $alias string 别名
     * @param $operate string 操作
     * @param $options array 配置项
     * @return $this
     */
    public function addAggs($alias, $operate, $options)
    {
        $this->aggregations = array_merge($this->aggregations, [$alias => [$operate => $options]]);
        return $this;
    }

    /**
     * @param $condition array 自定义聚合条件
     * @return $this
     */
    public function aggregations($condition = [])
    {
        if ($condition) {
            $this->aggregations = $condition;
        }
        return $this;
    }

    /**
     * @param $config array 高亮配置
     * @return $this
     */
    public function highlight($config = [])
    {
        if ($config) {
            $this->highlight = $config;
        }
        return $this;
    }
}