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
- [where](#where查询)
    - [whereNot](#whereNot)
    - [whereAnd](#whereAnd)
    - [whereOr](#whereOr)
    - [whereBetween](#whereBetween)
    - [whereNotBetween](#whereNotBetween)
    - [whereIn](#whereIn)
    - [whereNotIn](#whereNotIn)
    - [whereRange](#whereRange)

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
