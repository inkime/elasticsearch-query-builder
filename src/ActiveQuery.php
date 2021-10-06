<?php


namespace inkime\elasticsearch;


class ActiveQuery extends Query
{
    /**
     * @var string the name of the ActiveRecord class.
     */
    public $modelClass;

    public function __construct($modelClass)
    {
        $this->modelClass = $modelClass;
    }

    public function createCommand()
    {
        /* @var $modelClass ActiveRecord */
        $modelClass = $this->modelClass;
        $initConfig = array_merge($modelClass::$initConfig, [
            'url' => $modelClass::$gateway,
            'authorization' => $modelClass::$authorization
        ]);
        $db = $modelClass::getDb($initConfig);

        $commandConfig['queryParts'] = $db->getQueryBuilder()->build($this);
        $commandConfig['layout'] = $this->layout;
        return $db->createCommand($commandConfig);
    }
}