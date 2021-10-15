<?php

/**
 *  +----------------------------------------------------------------
 *  连接 ElasticSearch 网关
 *  +----------------------------------------------------------------
 * @author heykaka1020@163.com
 *  +----------------------------------------------------------------
 *  功能：支持切换不同 HTTP 请求
 *  +----------------------------------------------------------------
 */

namespace inkime\elasticsearch;


class Connection
{
    /**
     * @var float timeout to use for connecting to an Elasticsearch node.
     * This value will be used to configure the curl `CURLOPT_CONNECTTIMEOUT` option.
     * If not set, no explicit timeout will be set for curl.
     */
    public $connectionTimeout = null;

    /**
     * @var float timeout to use when reading the response from an Elasticsearch node.
     * This value will be used to configure the curl `CURLOPT_TIMEOUT` option.
     * If not set, no explicit timeout will be set for curl.
     */
    public $dataTimeout = null;

    public function __construct($initConfig = [])
    {
        Command::configure($this, $initConfig);
    }

    /**
     * 根据连接信息创建查询构造器
     */
    public function getQueryBuilder()
    {
        return new QueryBuilder($this);
    }

    /**
     * 创建执行命令的实例
     * @param array $config 配置信息
     * @return Command
     */
    public function createCommand($config = [])
    {
        $config['db'] = $this;
        $command = new Command($config);

        return $command;
    }

    public function get($requestBody = [])
    {
        return $this->httpRequest('GET', $requestBody);
    }

    public function post($requestBody = [])
    {
        return $this->httpRequest('POST', $requestBody);
    }

    /**
     * Performs HTTP request
     *
     * @param string $method method name
     * @param string $requestBody request body
     * @return mixed if request failed
     * @throws Exception if request failed
     * @throws InvalidConfigException
     */
    protected function httpRequest($method, $requestBody = null)
    {
        $options = [
            CURLOPT_URL => $this->url,
            CURLOPT_HTTPHEADER => [
                'Expect:',
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: ' . $this->authorization
            ],
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_RETURNTRANSFER => 1,
        ];
        if ($this->connectionTimeout !== null) {
            $options[CURLOPT_CONNECTTIMEOUT] = $this->connectionTimeout;
        }
        if ($this->dataTimeout !== null) {
            $options[CURLOPT_TIMEOUT] = $this->dataTimeout;
        }
        switch ($method) {
            case "GET" :
                $options[CURLOPT_HTTPGET] = 1;
                break;
            case "POST":
                $options[CURLOPT_POST] = 1;
                $options[CURLOPT_POSTFIELDS] = $requestBody;
                break;
        }
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $this->logRecord->invoke(new $this->logRecord->class());
        return json_decode($response, true);
    }
}