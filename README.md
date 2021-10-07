# ES网关查询生成器

提供了更贴近Yii2模型操作的API来查询ES网关数据

[![Latest Stable Version](https://poser.pugx.org/inkime/elasticsearch-query-builder/v/stable.png)](https://packagist.org/packages/inkime/elasticsearch-query-builder)
[![Total Downloads](https://poser.pugx.org/inkime/elasticsearch-query-builder/downloads.png)](https://packagist.org/packages/inkime/elasticsearch-query-builder)

Composer安装：
>composer require inkime/elasticsearch-query-builder

支持API如下：
- [one](#单条记录)
- [all](#多条记录)
- [count](#获取总数)
- [exists](#exists)
- [index](#index)
- [select / addSelect](#select-/-addSelect)
- [aggs / addAggs](#aggs-/-addAggs)
- [aggregations](#aggregations)
- [indexBy](#indexBy)
- [dsl](#dsl)
- [map](#map)
- [highlight](#highlight)
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
->addAggs('dateDayCount', 'date_histogram', ['field' => 'news_posttime', 'interval' => 'day', 'format' => 'yyyy-MM-dd', 'min_doc_count' => 0])
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
自定义DSL语句
EsModel::find()->index('wx')->select('news_is_origin')->addSelect('news_uuid')
->where(['news_uuid' => 'b15e02a0bddacc0ee61d51d36d0022eb'])
->map([
    'bool' => [
        'filter' => [
            'bool' => ['must_not' => [['term' => ['news_is_origin' => '']]]],
            'exists' => ['field' => 'news_is_origin']
        ]
    ]
]) // 自定义DSL
->one();
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
->map(['match' => ['news_title' => '补贴']])
->highlight(EsModel::highLight([$field])) // 高亮配置
->query(); // 仅支持query查询
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