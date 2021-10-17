<?php

namespace inkime\elasticsearch\data;

use inkime\elasticsearch\ActiveRecord;
use inkime\elasticsearch\Query;

class EsModel extends ActiveRecord
{
    public static $gateway = 'http://10.94.183.131:8081/mg/search/es';

    public static $authorization = 'Bearer eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiLoiIbmg4U1LjAiLCJpc3MiOiJlY2hpc2FuIiwiZXhwIjo0NzQ0NjYxOTgxLCJpYXQiOjE1OTEwNjE5ODEsInJvbCI6IlJPTEVfVVNFUiJ9.AOdhiZ36ItW6mMO9CnGLaPVi7HBF5c-J6-hbaH4s96diMyGG3QQJfDSLu1QeX5u5_5p11c3GBmXzMdHcEDP1tg';

    public static $originIndex = 'web,aq,weibo,wx,app,bbs,journal,media_ifeng,media_sohu,media_wangyi,media_eastday,media_btime,media_toutiao,media_yidianzixun,media_chejia,media_yiche,media_qq,media_people,video,comment';

    public function logRecord($request, $response)
    {
        // xxx
    }

    /**
     * 文章发布时间筛选，开始时间和结束时间不需要同时存在
     * @param string $field
     * @param string|int $begin_time
     * @param string|int $end_time
     * @return array
     */
    public static function range($field, $begin_time = '', $end_time = '')
    {
        $obj = new Query();
        if ($begin_time) {
            $begin_time = $begin_time == date('Y-m-d', strtotime($begin_time)) ? "$begin_time 00:00:00" : date('Y-m-d 00:00:00', $begin_time);
            $obj->andWhere(['gte', $field, $begin_time]);
        }
        if ($end_time) {
            $end_time = $end_time == date('Y-m-d', strtotime($end_time)) ? "$end_time 23:59:59" : date('Y-m-d 23:59:59', $end_time);
            $obj->andWhere(['lte', $field, $end_time]);
        }
        return $obj->where;
    }

    /**
     * 文章情感属性：正面，中性，负面，敏感
     * 支持传入字符串，数组
     * @param array|string $emotions
     * @return array
     */
    public static function emotion($emotions = [])
    {
        $obj = new Query();
        if (!is_array($emotions)) {
            $emotions = preg_split('/\s*,\s*/', trim($emotions), -1, PREG_SPLIT_NO_EMPTY);
        }
        $obj->where(['in', 'news_emotion', $emotions]);
        return $obj->where;
    }

    /**
     * 分组多字段求和
     * @param $group_field
     * @param $sum_fields
     * @return array
     */
    public static function groupMultiSum($group_field = '', $sum_fields = [])
    {
        if (empty($group_field) || empty($sum_fields)) return [];

        $tmp = [
            'group' => [
                "terms" => [
                    "field" => $group_field,
                ],
            ]
        ];

        $aggs = [];
        foreach ($sum_fields as $field) {
            $aggs[$field] = [
                "sum" => [
                    "field" => $field
                ]
            ];
        }

        $tmp['group']['aggs'] = $aggs;
        return $tmp;
    }

    /**
     * 过滤空值
     * @param $field
     * @return array
     */
    public static function exists($field)
    {
        $condition['bool']['filter'][] = [
            "bool" => [
                "must_not" => [
                    [
                        "term" => [
                            $field => ""
                        ]
                    ]
                ]
            ]
        ];
        $condition['bool']['filter'][] = [
            "exists" => [
                "field" => $field
            ]
        ];
        return $condition;
    }

    /**
     * 高亮显示
     * @param array $fields
     * @param string $pre_tags
     * @param string $post_tags
     * @return array
     */
    public static function highLight($fields = [], $pre_tags = '<em>', $post_tags = '</em>')
    {
        $newFieldsArr = [];
        foreach ($fields as $field) {
            $newFieldsArr[$field] = ['number_of_fragments' => 0];
        }
        return [
            "require_field_match" => false,
            "pre_tags" => [
                $pre_tags
            ],
            "post_tags" => [
                $post_tags
            ],
            "fields" => $newFieldsArr
        ];
    }
}