<?php


namespace inkime\elasticsearch\tests;


use \PHPUnit\Framework\TestCase;
use inkime\elasticsearch\data\EsModel;

class SearchTest extends TestCase
{
    public function testOne()
    {
        $result = EsModel::find()->index('wx')->one();
        $this->assertNotEmpty($result);
    }

    public function testAll()
    {
        $size = 10;
        $result = EsModel::find()->index('wx')->offset(0)->limit($size)->all();
        $this->assertCount($size, $result);
    }

    public function testCount()
    {
        $result = EsModel::find()->index('weibo')->offset(0)->limit(5)->count();
        $this->assertNotEmpty($result);
    }

    public function testExists()
    {
        $result = EsModel::find()->index('wx')->exists();
        $this->assertNotEmpty($result);
    }

    public function testWhere()
    {
        $field = 'news_uuid';
        $value = 'b15e02a0bddacc0ee61d51d36d0022eb';
        $value_1 = 'e698094102c12deb978e35617e72b633';
        $valueArr = [$value, $value_1];
        $result = EsModel::find()->index('wx')->select($field)->where([$field => $value])->one();
        $result_1 = EsModel::find()->index('wx')->select($field)->where([$field => $valueArr])->indexBy($field)->all();
        $this->assertEquals($value, $result[$field]);
        $this->assertArrayHasKey($value, $result_1);
        $this->assertArrayHasKey($value_1, $result_1);
    }

    public function testWhereNot()
    {
        $field = 'news_uuid';
        $value = 'b15e02a0bddacc0ee61d51d36d0022eb';
        $value_1 = 'e698094102c12deb978e35617e72b633';
        // 单值
        $condition = [$field => $value];
        // 多值
        $condition_1 = [$field => [$value, $value_1]];
        // {"_source":["news_uuid"],"size":1,"query":{"bool":{"must_not":{"bool":{"must":[{"term":{"news_uuid":"b15e02a0bddacc0ee61d51d36d0022eb"}}]}}}}}
        $result = EsModel::find()->index('wx')->select($field)->where(['not', $condition])->one();
        // {"_source":["news_uuid"],"size":1,"query":{"bool":{"must_not":{"bool":{"must":[{"terms":{"news_uuid":["b15e02a0bddacc0ee61d51d36d0022eb","e698094102c12deb978e35617e72b633"]}}]}}}}}
        $result_1 = EsModel::find()->index('wx')->select($field)->where(['not', $condition_1])->one();
        $this->assertNotEquals($value, $result[$field]);
        $this->assertNotEquals($value, $result_1[$field]);
        $this->assertNotEquals($value_1, $result_1[$field]);
    }

    public function testWhereAnd()
    {
        $field = 'news_uuid';
        $field_1 = 'media_name';
        $value = 'b15e02a0bddacc0ee61d51d36d0022eb';
        $value_1 = '城镇城镇交费';
        $condition = [$field => $value];
        $condition_1 = array_merge($condition, [$field_1 => $value_1]);
        // {"_source":["news_uuid"],"size":1,"query":{"bool":{"must":[{"bool":{"must":[{"term":{"news_uuid":"b15e02a0bddacc0ee61d51d36d0022eb"}}]}}]}}}
        $result = EsModel::find()->index('wx')->select($field)->where(['and', $condition])->one();
        // {"_source":["news_uuid","media_name"],"size":1,"query":{"bool":{"must":[{"bool":{"must":[{"term":{"news_uuid":"b15e02a0bddacc0ee61d51d36d0022eb"}},{"term":{"media_name":"城镇城镇交费"}}]}}]}}}
        $result_1 = EsModel::find()->index('wx')->select($field . ',' . $field_1)->where(['and', $condition_1])->one();
        // 与上面的查询结果相同
        $result_2 = EsModel::find()->index('wx')
            ->select($field)
            ->addSelect($field_1)
            ->where($condition)
            ->andWhere([$field_1 => $value_1])
            ->one();
        $this->assertEquals($value, $result[$field]);
        $this->assertEquals($value, $result_1[$field]);
        $this->assertEquals($value_1, $result_1[$field_1]);
        $this->assertEquals($value, $result_2[$field]);
        $this->assertEquals($value_1, $result_2[$field_1]);
    }

    public function testWhereOr()
    {
        $field = 'news_uuid';
        $value = 'b15e02a0bddacc0ee61d51d36d0022eb';
        $value_1 = 'e698094102c12deb978e35617e72b633';
        $condition = [$field => $value];
        // 如果Key相同，Value可以写成数组
        $condition_1 = [$field => [$value, $value_1]];
        // Key不同，Value中需要分别指定Key，以下是演示
        $condition_2 = ['or', [$field => $value], [$field => $value_1]];
        // {"_source":["news_uuid"],"size":1,"query":{"bool":{"should":[{"bool":{"must":[{"term":{"news_uuid":"b15e02a0bddacc0ee61d51d36d0022eb"}}]}}]}}}
        $result = EsModel::find()->index('wx')->select($field)->where(['or', $condition])->one();
        // {"_source":["news_uuid"],"query":{"bool":{"should":[{"bool":{"must":[{"terms":{"news_uuid":["b15e02a0bddacc0ee61d51d36d0022eb","e698094102c12deb978e35617e72b633"]}}]}}]}}}
        $result_1 = EsModel::find()->index('wx')->select($field)->where(['or', $condition_1])->indexBy($field)->all();
        // {"_source":["news_uuid"],"query":{"bool":{"should":[{"bool":{"must":[{"term":{"news_uuid":"b15e02a0bddacc0ee61d51d36d0022eb"}}]}},{"bool":{"must":[{"term":{"news_uuid":"e698094102c12deb978e35617e72b633"}}]}}]}}}
        $result_2 = EsModel::find()->index('wx')->select($field)->where($condition_2)->indexBy($field)->all();
        // 与上面的查询结果相同
        $result_3 = EsModel::find()->index('wx')
            ->select($field)
            ->where($condition)
            ->orWhere([$field => $value_1])
            ->indexBy($field)
            ->all();
        $this->assertEquals($value, $result[$field]);
        $this->assertArrayHasKey($value, $result_1);
        $this->assertArrayHasKey($value_1, $result_1);
        $this->assertArrayHasKey($value, $result_2);
        $this->assertArrayHasKey($value_1, $result_2);
        $this->assertArrayHasKey($value, $result_3);
        $this->assertArrayHasKey($value_1, $result_3);
    }

    public function testWhereBetween()
    {
        $field = 'news_is_origin';
        $value = '0';
        $value_1 = '1';
        // {"_source":["news_is_origin"],"size":1,"query":{"range":{"news_is_origin":{"gte":"0","lte":"1"}}}}
        $result = EsModel::find()->index('wx')->select($field)->where(['between', $field, $value, $value_1])->one();
        $this->assertGreaterThanOrEqual($value, $result[$field]);
        $this->assertLessThanOrEqual($value_1, $result[$field]);
    }

    public function testWhereNotBetween()
    {
        $field = 'news_comment_count';
        $value = '10';
        $value_1 = '100';
        // {"_source":["news_comment_count"],"size":1,"query":{"bool":{"must_not":{"range":{"news_comment_count":{"gte":"10","lte":"100"}}}}}}
        $result = EsModel::find()->index('wx')->select($field)->where(['not between', $field, $value, $value_1])->one();
        $this->assertEquals(0, $result[$field] <= $value_1 && $result[$field] >= $value);
    }

    public function testWhereIn()
    {
        $field = 'media_name';
        // 该字段不存在或者为NULL
        $value = [null];
        $value_1 = ['爆笑短片', '智慧人生', '', null];
        // {"_source":["media_name"],"size":1,"query":{"bool":{"must_not":{"exists":{"field":"media_name"}}}}}
        $result = EsModel::find()->index('wx')->select($field)->where(['in', $field, $value])->one();
        // {"_source":["media_name"],"size":1,"query":{"bool":{"should":[{"bool":{"must":{"terms":{"media_name":["爆笑短片","智慧人生",""]}}}},{"bool":{"must_not":{"exists":{"field":"media_name"}}}}]}}}
        $result_1 = EsModel::find()->index('wx')->select($field)->where(['in', $field, $value_1])->one();
        $this->assertEquals(false, $result);
        $this->assertEquals(true, in_array($result_1[$field], $value_1));
    }

    public function testWhereNotIn()
    {
        $field = 'media_name';
        $value = ['爆笑短片', '智慧人生', '', null];
        // {"_source":["media_name"],"size":1,"query":{"bool":{"must_not":{"bool":{"should":[{"bool":{"must":{"terms":{"media_name":["爆笑短片","智慧人生",""]}}}},{"bool":{"must_not":{"exists":{"field":"media_name"}}}}]}}}}}
        $result = EsModel::find()->index('wx')->select($field)->where(['not in', $field, $value])->one();
        $this->assertEquals(true, !in_array($result[$field], $value));
    }

    public function testWhereRange()
    {
        $field = 'news_postweek_day';
        $value = 3;
        $value_1 = 5;
        // {"_source":["news_postweek_day"],"size":1,"query":{"range":{"news_postweek_day":{"lt":3}}},"sort":{"news_postweek_day":{"order":"desc"}}}
        $result = EsModel::find()->index('wx')->select($field)->where(['<', $field, $value])->orderBy($field . ' desc')->one();
        $result_1 = EsModel::find()->index('wx')->select($field)->where(['lt', $field, $value])->orderBy($field . ' desc')->one();
        // {"_source":["news_postweek_day"],"size":1,"query":{"range":{"news_postweek_day":{"lte":3}}},"sort":{"news_postweek_day":{"order":"desc"}}}
        $result_2 = EsModel::find()->index('wx')->select($field)->where(['<=', $field, $value])->orderBy($field . ' desc')->one();
        $result_3 = EsModel::find()->index('wx')->select($field)->where(['lte', $field, $value])->orderBy($field . ' desc')->one();
        // {"_source":["news_postweek_day"],"size":1,"query":{"range":{"news_postweek_day":{"gt":5}}},"sort":{"news_postweek_day":{"order":"asc"}}}
        $result_4 = EsModel::find()->index('wx')->select($field)->where(['>', $field, $value_1])->orderBy($field . ' asc')->one();
        $result_5 = EsModel::find()->index('wx')->select($field)->where(['gt', $field, $value_1])->orderBy($field . ' asc')->one();
        // {"_source":["news_postweek_day"],"size":1,"query":{"range":{"news_postweek_day":{"gte":5}}},"sort":{"news_postweek_day":{"order":"asc"}}}
        $result_6 = EsModel::find()->index('wx')->select($field)->where(['>=', $field, $value_1])->orderBy($field . ' asc')->one();
        $result_7 = EsModel::find()->index('wx')->select($field)->where(['gte', $field, $value_1])->orderBy($field . ' asc')->one();
        $this->assertLessThan($value, $result[$field]);
        $this->assertLessThan($value, $result_1[$field]);
        $this->assertLessThanOrEqual($value, $result_2[$field]);
        $this->assertLessThanOrEqual($value, $result_3[$field]);
        $this->assertGreaterThan($value_1, $result_4[$field]);
        $this->assertGreaterThan($value_1, $result_5[$field]);
        $this->assertGreaterThanOrEqual($value_1, $result_6[$field]);
        $this->assertGreaterThanOrEqual($value_1, $result_7[$field]);
    }

    public function testFilterWhere()
    {
        $field = 'news_title';
        $obj = EsModel::find()->index('wx')->select($field)->filterWhere([]);
        $obj_1 = EsModel::find()->index('wx')->select($field)->filterWhere(['news_uuid' => '', 'news_title' => null]);
        $obj_2 = EsModel::find()->index('wx')->select($field)
            ->filterWhere(['news_uuid' => 'b15e02a0bddacc0ee61d51d36d0022eb', 'news_title' => '']);
        $result = $obj_2->addSelect('news_uuid')->one();
        $this->assertEmpty($obj->where);
        $this->assertEmpty($obj_1->where);
        $this->assertCount(1, $obj_2->where);
        $this->assertArrayHasKey('news_uuid', $obj_2->where);
        $this->assertEquals('b15e02a0bddacc0ee61d51d36d0022eb', $result['news_uuid']);
    }

    public function testAndFilterWhere()
    {
        $field = 'news_title';
        $map = ['news_uuid' => 'b15e02a0bddacc0ee61d51d36d0022eb'];
        $obj = EsModel::find()->index('wx')->select($field)->where($map)->andFilterWhere([]);
        // {"_source":["news_title","news_uuid","media_name"],"size":1,"query":{"bool":{"must":[{"bool":{"must":[{"term":{"news_uuid":"b15e02a0bddacc0ee61d51d36d0022eb"}}]}},{"bool":{"must":[{"bool":{"must":[{"term":{"media_name":"城镇城镇交费"}}]}}]}}]}}}
        $result = EsModel::find()->index('wx')->select($field)
            ->addSelect('news_uuid,media_name')
            ->where($map)
            ->andFilterWhere(['and', ['media_name' => '城镇城镇交费', 'news_title' => '']])
            ->one();
        $this->assertEquals($map, $obj->where);
        $this->assertEquals('b15e02a0bddacc0ee61d51d36d0022eb', $result['news_uuid']);
        $this->assertEquals('城镇城镇交费', $result['media_name']);
    }

    public function testOrFilterWhere()
    {
        $field = 'news_title';
        $map = ['news_uuid' => 'b15e02a0bddacc0ee61d51d36d0022eb'];
        $obj = EsModel::find()->index('wx')->select($field)->where($map)->orFilterWhere([]);
        // {"_source":["news_title","news_uuid","media_name","news_posttime"],"size":1,"query":{"bool":{"should":[{"bool":{"must":[{"term":{"news_uuid":"b15e02a0bddacc0ee61d51d36d0022eb"}}]}},{"bool":{"must":[{"bool":{"must":[{"term":{"media_name":"城镇城镇交费"}},{"term":{"news_posttime":"2021-09-01 19:00:00"}}]}}]}}]}}}
        $result = EsModel::find()->index('wx')->select($field)
            ->addSelect('news_uuid,media_name,news_posttime')
            ->where($map)
            ->orFilterWhere(['and', ['media_name' => '城镇城镇交费', 'news_posttime' => '2021-09-01 19:00:00']]) // 条件2：各筛选项 AND 关系
            ->one();
        $this->assertEquals($map, $obj->where);
        $this->assertEquals('b15e02a0bddacc0ee61d51d36d0022eb', $result['news_uuid']);
        $this->assertEquals('城镇城镇交费', $result['media_name']);
    }

    public function testSelect()
    {
        $field = 'news_title';
        $result = EsModel::find()->index('wx')->select($field)->one();
        $this->assertCount(1, $result);
        $this->assertNotEmpty($result[$field]);
    }

    public function testAddSelect()
    {
        $field = 'news_title';
        $appendField = 'news_uuid';
        $fieldArr = ['platform'];
        $appendfieldArr = ['media_name'];
        $result = EsModel::find()->index('wx')->select($field)->addSelect($appendField)->one();
        $result_1 = EsModel::find()->index('wx')->select($fieldArr)->addSelect($appendfieldArr)->one();
        $this->assertCount(2, $result);
        $this->assertCount(2, $result_1);
        $this->assertNotEmpty($result[$field]);
        $this->assertNotEmpty($result_1[$fieldArr[0]]);
        $this->assertNotEmpty($result[$appendField]);
        $this->assertNotEmpty($result_1[$appendfieldArr[0]]);
    }

    public function testIndex()
    {
        $index = 'weibo';
        $field = 'platform';
        $result = EsModel::find()->index($index)->select('platform')->one();
        $this->assertEquals($index, $result[$field]);
    }

    public function testOrderBy()
    {
        $index = 'wx';
        $field = 'news_comment_count';
        $field_1 = 'news_is_origin';
        $result = EsModel::find()->index($index)
            ->select($field)
            ->orderBy($field . ' desc')
            ->offset(0)
            ->limit(2)
            ->all();
        $result_1 = EsModel::find()->index($index)
            ->select($field)
            ->orderBy($field . ' asc')
            ->offset(0)
            ->limit(2)
            ->all();
        $result_2 = EsModel::find()->index($index)
            ->select([$field_1, $field])
            ->orderBy($field_1 . ' desc,' . $field . ' desc')
            ->offset(0)
            ->limit(2)
            ->all();
        $this->assertGreaterThanOrEqual($result[1][$field], $result[0][$field]);
        $this->assertLessThanOrEqual($result_1[1][$field], $result_1[0][$field]);
        $this->assertGreaterThanOrEqual($result_2[1][$field], $result_2[0][$field]);
    }

    public function testIndexBy()
    {
        $index = 'wx';
        $select = 'news_uuid,news_title';
        // 验证字段
        $field = 'news_uuid';
        $result = EsModel::find()->index($index)
            ->select($select)
            ->indexBy($field)
            ->offset(0)
            ->limit(1)
            ->all();
        // 验证匿名函数
        $secretStr = 'qingbo';
        $result_1 = EsModel::find()->index($index)
            ->select($select)
            ->indexBy(function ($v) use ($field, $secretStr) { // $v 表示每条记录
                return $v[$field] . $secretStr;
            })
            ->offset(0)
            ->limit(1)
            ->all();
        $key = array_column($result, $field)[0];
        $key_1 = array_column($result_1, $field)[0];
        $this->assertArrayHasKey($key, $result);
        $this->assertNotEmpty($result[$key]);
        $this->assertArrayHasKey($key_1 . $secretStr, $result_1);
        $this->assertNotEmpty($result_1[$key_1 . $secretStr]);
    }

    public function testDsl()
    {
        $index = 'wx';
        $select = 'news_uuid,news_title';
        $result = EsModel::find()->index($index)->select($select)->dsl()->one();
        $result_1 = EsModel::find()->index($index)->select($select)->dsl('array')->one();
        $this->assertNotEmpty($result);
        $this->assertNotEmpty($result_1);
        $this->assertInternalType('string', $result);
        $this->assertInternalType('array', $result_1);
    }

    public function testAggs()
    {
        $result = EsModel::find()->index('wx')
            ->select('news_uuid,news_title,news_posttime,platform')
            ->aggs('platformCount', 'terms', ['field' => 'platform', 'size' => 3, 'order' => ['_count' => 'asc']])
            ->addAggs('dateDayCount', 'date_histogram', ['field' => 'news_posttime', 'interval' => 'day', 'format' => 'yyyy-MM-dd', 'min_doc_count' => 0])
            ->limit(5)
            ->query();
        $this->assertArrayHasKey('platformCount', $result['aggregations']);
        $this->assertArrayHasKey('dateDayCount', $result['aggregations']);
    }
}
