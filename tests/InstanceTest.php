<?php


namespace inkime\elasticsearch\tests;

use \PHPUnit\Framework\TestCase;
use inkime\elasticsearch\data\EsModel;

class InstanceTest extends TestCase
{
    public function testFunction()
    {
        $field = 'news_posttime';
        $begin_time = '2021-09-01';
        $end_time = '2021-09-01';
        // {"_source":["news_posttime"],"size":1,"query":{"bool":{"must":[{"range":{"news_posttime":{"gte":"2021-09-01 00:00:00"}}},{"range":{"news_posttime":{"lte":"2021-09-01 23:59:59"}}}]}}}
        $result = EsModel::find()->index('wx')->select($field)
            ->where(EsModel::range($field, $begin_time, $end_time))
            ->one();
        $news_posttime = strtotime($result[$field]);
        $this->assertLessThanOrEqual(strtotime($end_time . ' 23:59:59'), $news_posttime);
        $this->assertGreaterThanOrEqual(strtotime($begin_time), $news_posttime);
    }

    public function testEmotion()
    {
        $field = 'news_emotion';
        $emotions = ['正面'];
        $emotions_1 = '反面,中性';
        // {"_source":["news_emotion"],"size":1,"query":{"bool":{"should":[{"terms":{"news_emotion":["正面"]}},{"terms":{"news_emotion":["反面","中性"]}}]}}}
        $result = EsModel::find()->index('wx')->select($field)
            ->where(EsModel::emotion($emotions))
            ->orWhere(EsModel::emotion($emotions_1))
            ->one();
        $emotionsFinal = array_merge($emotions, explode(',', $emotions_1));
        $this->assertEquals(true, in_array($result[$field], $emotionsFinal));
    }

    public function testGroupMultiSum()
    {
        $field = 'news_is_origin';
        $field_1 = ['news_reposts_count', 'news_comment_count', 'news_like_count'];
        // {"_source":["news_is_origin","news_reposts_count","news_comment_count","news_like_count"],"size":2,"aggregations":{"group":{"terms":{"field":"news_is_origin"},"aggs":{"news_reposts_count":{"sum":{"field":"news_reposts_count"}},"news_comment_count":{"sum":{"field":"news_comment_count"}},"news_like_count":{"sum":{"field":"news_like_count"}}}}}}
        $result = EsModel::find()->index('wx')->select($field)
            ->addSelect($field_1)
            ->aggregations(EsModel::groupMultiSum($field, $field_1))
            ->limit(2)
            ->query();

        $publish_news_num = !empty($result['hits']) ? $result['hits']['total'] : 0;
        $aggs = !empty($result['aggregations']) ? $result['aggregations']['group']['buckets'] : [];

        $repost_count = $comment_count = $like_count = $origin_repost_count = $origin_comment_count = $origin_news_num = $origin_like_count = 0;

        // 统计：转发数、评论数、点赞数，原创文章数、原创转发数、原创评论数、原创点赞数
        if (!empty($aggs)) {
            foreach ($aggs as $row) {
                $repost_count += $row['news_reposts_count']['value'];
                $comment_count += $row['news_comment_count']['value'];
                $like_count += $row['news_like_count']['value'];

                if ($row['key'] == 1) {
                    $origin_news_num = $row['doc_count'];
                    $origin_repost_count = $row['news_reposts_count']['value'];
                    $origin_comment_count = $row['news_comment_count']['value'];
                    $origin_like_count = $row['news_like_count']['value'];
                }
            }
        }
        $this->assertNotEmpty($result['aggregations']['group']);
    }

    public function testFieldExists()
    {
        $field = 'news_is_origin';
        $field_1 = 'news_uuid';
        $value = 'b15e02a0bddacc0ee61d51d36d0022eb';
        // {"_source":["news_is_origin","new_uuid"],"size":1,"query":{"bool":{"must":[{"bool":{"must":[{"term":{"new_uuid":"b15e02a0bddacc0ee61d51d36d0022eb"}}]}},{"bool":{"filter":[{"bool":{"must_not":[{"term":{"news_is_origin":""}}]}},{"exists":{"field":"news_is_origin"}}]}}]}}}
        $result = EsModel::find()->index('wx')->select($field)->addSelect($field_1)
            ->where([$field_1 => $value])
            ->map(EsModel::exists($field)) // 自定义DSL
            ->one();
        $this->assertEquals($value, $result[$field_1]);
    }

    public function testHighlight()
    {
        $field = 'news_title';
        $field_1 = 'news_uuid';
        $value = 'b15e02a0bddacc0ee61d51d36d0022eb';
        // {"_source":["news_title","news_uuid"],"size":1,"query":{"bool":{"must":[{"bool":{"must":[{"term":{"news_uuid":"b15e02a0bddacc0ee61d51d36d0022eb"}}]}},{"match":{"news_title":"补贴"}}]}},"highlight":{"require_field_match":false,"pre_tags":["<em>"],"post_tags":["<\/em>"],"fields":{"news_title":{"number_of_fragments":0}}}}
        $result = EsModel::find()->index('wx')->select($field)->addSelect($field_1)
            ->where([$field_1 => $value])
            ->map(['match' => [$field => '补贴']])
            ->highlight(EsModel::highLight([$field])) // 高亮配置
            ->query();
        $highlight = $result['hits']['hits'][0]['highlight'];
        $this->assertNotEmpty($highlight[$field][0]);
        $this->assertEquals(true, strpos($highlight[$field][0], 'em') !== false);
    }
}