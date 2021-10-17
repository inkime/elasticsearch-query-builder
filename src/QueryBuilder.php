<?php


namespace inkime\elasticsearch;


class QueryBuilder
{
    public $db;

    public function __construct($connection, $config = [])
    {
        $this->db = $connection;
    }

    /**
     * 根据查询条件解析DSL语句
     * @param $query
     */
    public function build($query)
    {
        $parts = [];

        if ($query->storedFields !== null) {
            $parts['stored_fields'] = $query->storedFields;
        }
        if ($query->scriptFields !== null) {
            $parts['script_fields'] = $query->scriptFields;
        }

        if ($query->select !== null) {
            $parts['_source'] = is_array($query->select) ? array_values($query->select) : $query->select;
        }
        if ($query->limit !== null && $query->limit >= 0) {
            $parts['size'] = $query->limit;
        }
        if ($query->offset > 0) {
            $parts['from'] = (int)$query->offset;
        }
        if (isset($query->minScore)) {
            $parts['min_score'] = (float)$query->minScore;
        }
        if (isset($query->explain)) {
            $parts['explain'] = $query->explain;
        }

        // combine query with where
        $parts['query'] = [];
        $whereQuery = $this->buildQueryFromWhere($query->where);
        if ($whereQuery) {
            $parts['query'] = ['bool' => $whereQuery];
        }
        if ($query->map) {
            $parts['query'] = array_merge_recursive($parts['query'], $query->map);
        }

        if (!empty($query->highlight)) {
            $parts['highlight'] = $query->highlight;
        }
        if (!empty($query->aggregations)) {
            $parts['aggregations'] = $query->aggregations;
        }
        if (!empty($query->stats)) {
            $parts['stats'] = $query->stats;
        }
        if (!empty($query->suggest)) {
            $parts['suggest'] = $query->suggest;
        }
        if (!empty($query->postFilter)) {
            $parts['post_filter'] = $query->postFilter;
        }
        if (!empty($query->collapse)) {
            $parts['collapse'] = $query->collapse;
        }

        $sort = $this->buildOrderBy($query->orderBy);
        if (!empty($sort)) {
            $parts['sort'] = $sort;
        }

        return [
            'statement' => json_encode($parts, JSON_UNESCAPED_UNICODE),
            'index' => $query->index,
            'startStamp' => strtotime(date('Y-m-d H:i:s', strtotime('-3 month'))) * 1000,
            'endStamp' => time() * 1000,
        ];
    }

    public function buildOrderBy($columns)
    {
        $sorts = [];
        if (!$columns) {
            return $sorts;
        }
        foreach ($columns as $column => $direction) {
            $sorts[$column] = ['order' => $direction];
        }
        return $sorts;
    }

    public function buildQueryFromWhere($condition)
    {
        $condition = $this->buildCondition($condition);
        foreach ($condition as $key => $item) {
            if ($key == 'range') {
                $condition['must'] = [$key => $item];
                unset($condition[$key]);
            }
        }
        return $condition;
    }

    /**
     * 解析 where 语句
     *
     * @param array $condition the condition specification. Please refer to [[Query::where()]] on how to specify a condition.
     * @return array the generated SQL expression
     * @throws Exception
     */
    public function buildCondition($condition)
    {
        static $builders = [
            'not' => 'buildNotCondition',
            'and' => 'buildBoolCondition',
            'or' => 'buildBoolCondition',
            'between' => 'buildBetweenCondition',
            'not between' => 'buildBetweenCondition',
            'in' => 'buildInCondition',
            'not in' => 'buildInCondition',
            'lt' => 'buildHalfBoundedRangeCondition',
            '<' => 'buildHalfBoundedRangeCondition',
            'lte' => 'buildHalfBoundedRangeCondition',
            '<=' => 'buildHalfBoundedRangeCondition',
            'gt' => 'buildHalfBoundedRangeCondition',
            '>' => 'buildHalfBoundedRangeCondition',
            'gte' => 'buildHalfBoundedRangeCondition',
            '>=' => 'buildHalfBoundedRangeCondition',
        ];

        if (!$condition) {
            return [];
        }
        if (is_string($condition)) {
            throw new Exception('不支持 String 类型');
        }
        if (isset($condition[0])) {
            $operator = strtolower($condition[0]);
            if (isset($builders[$operator])) {
                $method = $builders[$operator];
                array_shift($condition);

                return $this->$method($operator, $condition);
            } else {
                throw new Exception('不支持查询操作方法: ' . $operator);
            }
        } else {
            return $this->buildHashCondition($condition);
        }
    }

    private function completeQuery($condition)
    {
        $key = array_keys($condition)[0];
        if (in_array($key, ['must', 'must_not', 'should', 'filter', 'minimum_should_match'])) {
            return ['bool' => $condition];
        }
        return $condition;
    }

    private function buildHashCondition($condition)
    {
        $parts = $emptyFields = [];
        foreach ($condition as $attribute => $value) {
            if (is_array($value)) { // IN condition
                $parts[] = ['terms' => [$attribute => $value]];
            } else {
                if ($value === null) {
                    $emptyFields[] = ['exists' => ['field' => $attribute]];
                } else {
                    $parts[] = ['term' => [$attribute => $value]];
                }
            }
        }

        $query = ['must' => $parts];
        if ($emptyFields) {
            $query['must_not'] = $emptyFields;
        }
        return $query;
    }

    private function buildNotCondition($operator, $operands)
    {
        if (count($operands) != 1) {
            throw new Exception("Operator '$operator' requires exactly one operand.");
        }

        $operand = reset($operands);
        if (is_array($operand)) {
            $operand = $this->completeQuery($this->buildCondition($operand));
        }

        return [
            'must_not' => $operand
        ];
    }

    private function buildBoolCondition($operator, $operands)
    {
        $parts = [];
        if ($operator === 'and') {
            $clause = 'must';
        } else if ($operator === 'or') {
            $clause = 'should';
        } else {
            throw new Exception("Operator should be 'or' or 'and'");
        }

        foreach ($operands as $operand) {
            if (is_array($operand)) {
                $operand = $this->completeQuery($this->buildCondition($operand));
            }
            if (!empty($operand)) {
                $parts[] = $operand;
            }
        }
        return [$clause => $parts];
    }

    private function buildBetweenCondition($operator, $operands)
    {
        if (!isset($operands[0], $operands[1], $operands[2])) {
            throw new Exception("Operator '$operator' requires three operands.");
        }

        list($column, $value1, $value2) = $operands;
        if ($column === '_id') {
            throw new Exception('Between condition is not supported for the _id field.');
        }
        $filter = ['range' => [$column => ['gte' => $value1, 'lte' => $value2]]];
        if ($operator === 'not between') {
            $filter = ['must_not' => $filter];
        } else {
            $filter = ['must' => $filter];
        }

        return $filter;
    }

    private function buildInCondition($operator, $operands)
    {
        if (!isset($operands[0], $operands[1]) || !is_array($operands)) {
            throw new Exception("Operator '$operator' requires array of two operands: column and values");
        }

        list($column, $values) = $operands;

        $values = (array)$values;

        if (empty($values) || $column === []) {
            return $operator === 'in' ? ['must_not' => [['match_all' => new \stdClass()]]] : []; // this condition is equal to WHERE false
        }

        if (is_array($column)) {
            if (count($column) > 1) {
                throw new Exception('Just support an array of single value');
            }
            $column = reset($column);
        }
        $canBeNull = false;
        foreach ($values as $i => $value) {
            if (is_array($value)) {
                $values[$i] = $value = isset($value[$column]) ? $value[$column] : null;
            }
            if ($value === null) {
                $canBeNull = true;
                unset($values[$i]);
            }
        }
        if (empty($values) && $canBeNull) {
            $filter = [
                'must_not' => [
                    'exists' => ['field' => $column],
                ]
            ];
        } else {
            $filter = ['terms' => [$column => array_values($values)]];
            if ($canBeNull) {
                $filter = [
                    'should' => [
                        ['bool' => ['must' => $filter]],
                        ['bool' => ['must_not' => ['exists' => ['field' => $column]]]],
                    ]
                ];
            }
        }

        if ($operator === 'not in') {
            $filter = [
                'must_not' => ['bool' => $filter]
            ];
        }

        return $filter;
    }

    /**
     * 区间比较
     * 支持操作数："gt", ">", "gte", ">=", "lt", "<", "lte", "<="
     * @param string $operator
     * @param array $operands
     * @return array
     */
    private function buildHalfBoundedRangeCondition($operator, $operands)
    {
        if (!isset($operands[0], $operands[1])) {
            throw new Exception("Operator '$operator' requires two operands.");
        }

        list($column, $value) = $operands;

        $range_operator = null;

        if (in_array($operator, ['gte', '>='])) {
            $range_operator = 'gte';
        } elseif (in_array($operator, ['lte', '<='])) {
            $range_operator = 'lte';
        } elseif (in_array($operator, ['gt', '>'])) {
            $range_operator = 'gt';
        } elseif (in_array($operator, ['lt', '<'])) {
            $range_operator = 'lt';
        }

        if ($range_operator === null) {
            throw new Exception("Operator '$operator' is not implemented.");
        }

        return [
            'range' => [
                $column => [
                    $range_operator => $value
                ]
            ]
        ];
    }
}