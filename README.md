# ES网关查询生成器

提供了更贴近Yii2模型操作的API来查询ES网关数据

[![Latest Stable Version](https://poser.pugx.org/inkime/elasticsearch-query-builder/v/stable.png)](https://packagist.org/packages/inkime/elasticsearch-query-builder)
[![Total Downloads](https://poser.pugx.org/inkime/elasticsearch-query-builder/downloads.png)](https://packagist.org/packages/inkime/elasticsearch-query-builder)
[![License](http://poser.pugx.org/inkime/elasticsearch-query-builder/license)](https://packagist.org/packages/inkime/elasticsearch-query-builder)
[![PHP Version Require](http://poser.pugx.org/inkime/elasticsearch-query-builder/require/php)](https://packagist.org/packages/inkime/elasticsearch-query-builder)

Composer安装：
>composer require inkime/elasticsearch-query-builder

支持API如下：
- [query](#常规查询)
- [one](#单条记录)
- [all](#多条记录)
- [count](#获取总数)
- [exists](#exists)
- [index](#index)
- [select / addSelect](#select--addSelect)
- [aggs / addAggs](#aggs--addAggs)
- [aggregations](#aggregations)
- [indexBy](#indexBy)
- [dsl](#dsl)
- [map](#map)
- [addMap](#addMap)
- [highlight](#highlight)
- [collapse](#collapse)
- [where](#where查询)
    - [whereNot](#whereNot)
    - [whereAnd](#whereAnd)
    - [whereOr](#whereOr)
    - [whereBetween](#whereBetween)
    - [whereNotBetween](#whereNotBetween)
    - [whereIn](#whereIn)
    - [whereNotIn](#whereNotIn)
    - [whereRange](#whereRange)
- [andWhere](#andWhere)
- [orWhere](#orWhere)
- [filterWhere](#filterWhere)
- [andFilterWhere](#andFilterWhere)
- [orFilterWhere](#orFilterWhere)
- orderBy / addOrderBy
- offset
- limit

自定义日志操作：
~~~
<?php
namespace webapi\es;
use inkime\elasticsearch\ActiveRecord;
use inkime\elasticsearch\Query;
use webapi\services\LogService;

class SdModel extends ActiveRecord
{
    public static $gateway = 'xxx';

    public static $authorization = 'xxx';

    public function logRecord($request, $response)
    {
        // 使用1，记录Log日志
        $logFile = \Yii::getAlias('@runtime/logs/es.log');
        file_put_contents($logFile, var_export($request, true) . PHP_EOL, FILE_APPEND);
        file_put_contents($logFile, var_export($response, true) . PHP_EOL, FILE_APPEND);
        // 使用2，持久化存DB
        (new LogService())->saveSysLog(var_export($request, true));
    }
}
~~~

#### 常规查询
~~~
$result = EsModel::find()->index('wx')->query();
$count = $result['count'];
$list = $result['list'];
~~~
#### 单条记录
~~~
EsModel::find()->index('wx')->one();
~~~
#### 多条记录
~~~
EsModel::find()->index('wx')->offset(0)->limit(10)->all();
~~~
#### 获取总数
~~~
EsModel::find()->index('weibo')->offset(0)->limit(5)->count();
~~~
#### exists
~~~
EsModel::find()->index('wx')->where(['news_uuid' => 'b15e02a0bddacc0ee61d51d36d0022eb'])->exists();
~~~
#### index
~~~
可任意切换索引库，如wx/weibo等
EsModel::find()->index('wx')->one();
~~~
#### select / addSelect
~~~
指定查询字段，支持追加操作，参数支持数组或字符串 #逗号分割
EsModel::find()->index('wx')->select('news_uuid')->addSelect(['news_title', 'news_sim_hash'])->one();
~~~
#### aggs / addAggs
~~~
注意：只能用query查询
EsModel::find()->index('wx')
->select('news_uuid,news_title,news_posttime,platform')
->aggs('platformCount', 'terms', ['field' => 'platform', 'size' => 3, 'order' => ['_count' => 'asc']])
->addAggs('dateDayCount', 'date_histogram', 
      ['field' => 'news_posttime', 'interval' => 'day', 'format' => 'yyyy-MM-dd', 'min_doc_count' => 0])
->limit(5)
->query();
~~~
#### aggregations
~~~
自定义聚合查询
EsModel::find()->index('wx')->select('news_is_origin')
->addSelect(['news_reposts_count', 'news_comment_count', 'news_like_count'])
->aggregations([
    'group' => [
        "terms" => ["field" => 'news_is_origin']
    ],
    'aggs' => [
        'news_reposts_count' => ['sum' => ['field' => 'news_reposts_count']],
        'news_comment_count' => ['sum' => ['field' => 'news_comment_count']],
        'news_like_count' => ['sum' => ['field' => 'news_like_count']],
    ]
])
->limit(2)
->query();
~~~
#### indexBy
~~~
EsModel::find()->index('wx')->select('news_uuid')->indexBy('news_uuid')->limit(5)->all();
支持回调函数
$field = 'news_uuid';
$secretStr = 'qingbo#$%t2000';
EsModel::find()->index('wx')->select($field)->indexBy(function($v) use ($field, $secretStr) { 
    // $v 表示每条记录
    return $v[$field] . $secretStr;
})->limit(5)->all();
~~~
#### dsl
~~~
输出DSL语句，支持json/array两种格式，默认json
EsModel::find()->index('wx')->select('news_uuid')
->where(['news_uuid' => 'b15e02a0bddacc0ee61d51d36d0022eb'])->dsl()
->one();
~~~
#### map
~~~
自定义DSL语句，map或者addMap操作仅仅支持bool查询，bool键名支持省略
例如：['must' => [['match' => ['news_title' => '补贴']]]]
系统会补全：['bool' => ['must' => [['match' => ['news_title' => '补贴']]]]]
EsModel::find()->index('wx')->select('news_is_origin')->addSelect('news_uuid')
->where(['news_uuid' => 'b15e02a0bddacc0ee61d51d36d0022eb'])
->map([
    'bool' => [
        'filter' => [
            'exists' => ['field' => 'news_is_origin']
        ]
    ]
]) // 自定义DSL
->one();
~~~
#### addMap
~~~
自定义DSL语句
EsModel::find()->index('wx')->select('news_title,news_uuid')
->where(['news_uuid' => 'b15e02a0bddacc0ee61d51d36d0022eb'])
->addMap(['must' => [['match' => ['news_title' => '补贴']]]])
->query();
~~~
#### highlight
~~~
自定义高亮配置
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
EsModel::find()->index('wx')->select('news_title')->addSelect('news_uuid')
->where(['news_uuid' => 'b15e02a0bddacc0ee61d51d36d0022eb'])
->highlight(EsModel::highLight([$field])) // 高亮配置
->query(); // 仅支持query查询
~~~
#### collapse
~~~
EsModel::find()->index('wx')->select('news_uuid')->collapse('news_title')
->where(['media_name' => '城镇城镇交费'])->all();
~~~
#### where查询
~~~
EsModel::find()->index('wx')->select('news_uuid')
->where(['news_uuid' => 'b15e02a0bddacc0ee61d51d36d0022eb'])->one();
~~~
#### whereNot
~~~
EsModel::find()->index('wx')->select('news_uuid')
->where([
    'not', 
    ['news_uuid' => ['b15e02a0bddacc0ee61d51d36d0022eb', 'e698094102c12deb978e35617e72b633']]
])->one();
~~~
#### whereAnd
~~~
EsModel::find()->index('wx')->select('news_uuid,media_name')
->where(['and', ['news_uuid' => 'b15e02a0bddacc0ee61d51d36d0022eb','media_name' => '城镇城镇交费']])
->one();
~~~
#### whereOr
~~~
EsModel::find()->index('wx')->select('news_uuid')
->where(['or', ['news_uuid' => 'b15e02a0bddacc0ee61d51d36d0022eb'], ['media_name' => '城镇城镇交费']])
->one();
~~~
#### whereBetween
~~~
EsModel::find()->index('wx')->select('news_is_origin')->where(['between', 'news_is_origin', 0, 1])
->one();
~~~
#### whereNotBetween
~~~
EsModel::find()->index('wx')->select('news_is_origin')
->where(['not between', 'news_comment_count', 10, 100])->one();
~~~
#### whereIn
~~~
EsModel::find()->index('wx')->select('media_name')
->where(['in', 'media_name', ['爆笑短片', '智慧人生', '', null]])->one();
~~~
#### whereNotIn
~~~
EsModel::find()->index('wx')->select('media_name')
->where(['not in', 'media_name', ['爆笑短片', '智慧人生', '', null]])->one();
~~~
#### whereRange
~~~
EsModel::find()->index('wx')->select('news_postweek_day')->where(['<', 'news_postweek_day', 3])
->orderBy('news_postweek_day desc')->one();
这里操作符支持：<、lt、<=、lte、>、gt、>=、gte
~~~
#### andWhere
~~~
EsModel::find()->index('wx')->select('news_uuid,media_name')
->where(['news_uuid' => 'b15e02a0bddacc0ee61d51d36d0022eb'])
->andWhere(['media_name' => '城镇城镇交费'])->one();
~~~
#### orWhere
~~~
EsModel::find()->index('wx')->select('news_uuid,media_name')
->where(['news_uuid' => 'b15e02a0bddacc0ee61d51d36d0022eb'])
->orWhere(['media_name' => '城镇城镇交费'])->one();
~~~
#### filterWhere
~~~
EsModel::find()->index('wx')->select('news_title')
->filterWhere(['news_uuid' => 'b15e02a0bddacc0ee61d51d36d0022eb', 'news_title' => ''])
->one();
~~~
#### andFilterWhere
~~~
EsModel::find()->index('wx')->select('news_title')
->where(['news_uuid' => 'b15e02a0bddacc0ee61d51d36d0022eb'])
->andFilterWhere(['and', ['media_name' => '城镇城镇交费', 'news_title' => '']])
->one();
~~~
#### orFilterWhere
~~~
EsModel::find()->index('wx')->select('news_title')
->where(['news_uuid' => 'b15e02a0bddacc0ee61d51d36d0022eb'])
->orFilterWhere(['and', ['media_name' => '城镇城镇交费', 'news_posttime' => '2021-09-01 19:00:00']])
->one();
~~~