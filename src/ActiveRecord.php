<?php


namespace inkime\elasticsearch;


class ActiveRecord
{
    /**
     * 网关地址
     * @return string
     */
    public static $gateway = 'http://www.gsdata.cn/';

    /**
     * Authorization
     * @return string
     */
    public static $authorization = 'qingbo';

    /**
     * CURL基础配置
     * @return string
     */
    public static $initConfig = [
        'connectionTimeout' => 10,
        'dataTimeout' => 30,
    ];

    /**
     * 自定义日志操作
     * @param array $request 请求值
     * @param array $response 返回值
     */
    public function logRecord($request, $response)
    {
        // xxx
    }

    /**
     * @return ActiveQuery 创建 [[ActiveQuery]] 实例
     */
    public static function find()
    {
        return new ActiveQuery(get_called_class());
    }

    public static function getDb($initConfig = [])
    {
        return new Connection($initConfig);
    }
}