<?php
/**
 * @author Kiko Seijo
 * */

namespace Ksoft\Bybit;

use Ksoft\Bybit\Api\Inverse\Privates;
use Ksoft\Bybit\Api\Inverse\Publics;

class BybitInverse
{
    protected $key;
    protected $secret;
    protected $host;

    protected $options=[];

    public function __construct(string $key='', string $secret='', string $host='https://api.bybit.com')
    {
        $this->key=$key;
        $this->secret=$secret;
        $this->host=$host;
    }

    /**
     *
     * */
    private function init()
    {
        return [
            'key'=>$this->key,
            'secret'=>$this->secret,
            'host'=>$this->host,
            'options'=>$this->options,

            'platform'=>'inverse',
            'version'=>'',
        ];
    }

    /**
     *
     * */
    public function setOptions(array $options=[])
    {
        $this->options=$options;
    }

    /**
     *
     * */
    public function privates()
    {
        return  new Privates($this->init());
    }

    /**
     *
     * */
    public function publics()
    {
        return  new Publics($this->init());
    }
}
