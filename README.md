# ES网关查询生成器

Composer安装：
>composer require inkime/elasticsearch-query-builder

使用示例：
~~~
// 单条记录
EsModel::find()->index('wx')->one();

// 多条记录
EsModel::find()->index('wx')->offset(0)->limit(10)->all();

// 获取总数
EsModel::find()->index('weibo')->offset(0)->limit(5)->count();

// where查询
EsModel::find()->index('wx')->select('news_uuid')
->where(['news_uuid' => 'b15e02a0bddacc0ee61d51d36d0022eb'])->one();

// whereNot
EsModel::find()->index('wx')->select('news_uuid')
->where(['not', ['news_uuid' => 
    [
        'b15e02a0bddacc0ee61d51d36d0022eb', 
        'e698094102c12deb978e35617e72b633'
    ]
]])
->one();

// whereAnd
EsModel::find()->index('wx')->select('news_uuid,media_name')
->where(['and', [
    'news_uuid' => 'b15e02a0bddacc0ee61d51d36d0022eb',
    'media_name' => '城镇城镇交费'
]])->one();

// whereOr
EsModel::find()->index('wx')->select('news_uuid')
->where(['or', ['news_uuid' => 'b15e02a0bddacc0ee61d51d36d0022eb'], ['media_name' => '城镇城镇交费']])
->one();

// whereBetween
EsModel::find()->index('wx')->select('news_is_origin')->where(['between', 'news_is_origin', 0, 1])->one();
~~~