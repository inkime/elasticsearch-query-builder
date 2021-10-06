<?php


namespace inkime\elasticsearch;


class Exception extends \Exception
{
    public function getName()
    {
        return 'Elasticsearch Query Exception';
    }
}