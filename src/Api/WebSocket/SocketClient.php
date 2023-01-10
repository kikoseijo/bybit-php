<?php
/**
 * @author Kiko Seijo
 * */

namespace Ksoft\Bybit\Api\WebSocket;

use Exception;
use Ksoft\Bybit\Api\WebSocket\SocketGlobal;
use Ksoft\Bybit\Api\WebSocket\SocketFunction;

use Workerman\Lib\Timer;
use Workerman\Worker;

class SocketClient
{
  use SocketGlobal;
  use SocketFunction;

  private $config=[];
  private $keysecret=[];

  private $worker = null;

  function __construct(array $config=[]) {
    $this->config = $config;

    $this->client();

    $this->init();

  }

  protected function init() {
    $this->add('global_key',[]);

    $this->add('all_sub',[]);

    $this->add('add_sub',[]);

    $this->add('del_sub',[]);

    $this->add('keysecret',[]);

    $this->add('global_local',[]);

    $this->add('debug',[]);

  }

  function keysecret(array $keysecret=[]) {
    $this->keysecret = $keysecret;
    return $this;
  }

  /**
   * @param array $sub
   */
  public function subscribe(array $sub=[]) {
    if (!empty($this->keysecret)) {
      $keysecret = $this->get('keysecret');

      if (!isset($keysecret[$this->keysecret['key']]['connection'])) {
        $this->keysecretInit($this->keysecret, [
          'connection' => 0,
        ]);
      }
    }

    //$this->save('add_sub',$sub);
    $add_sub = $this->get('add_sub');
    if (!empty($sub) && !empty($add_sub)) {
      $this->save('add_sub',array_merge($sub, $add_sub));
    } elseif (!empty($add_sub)) {
      $this->save('add_sub', $add_sub);
    } elseif (!empty($sub)) {
      $this->save('add_sub', $sub);
    }
  }

  /**
   * @param array $sub
   */
  public function unsubscribe(array $sub=[]){
    if (!empty($this->keysecret)) {
      if (!isset($keysecret[$this->keysecret['key']]['connection']))
      $this->keysecretInit($this->keysecret, [
        'connection_close' => 1,
      ]);
    }

    if (!empty($sub)) $this->save('del_sub',$sub);
  }

  /**
   * @param array $sub
   * @param null $callback
   * @param bool $daemon
   * @return mixed
   */
  public function getSubscribe(array $sub, $callback = null, $daemon = false){
    if ($daemon) $this->daemon($callback,$sub);

    return $this->getData($this, $callback, $sub, $this->user_id);
  }

  /**
   * @param null $callback
   * @param bool $daemon
   * @return array
   */
  public function getSubscribes($callback = null, $daemon = false){
    if($daemon) $this->daemon($callback);

    return $this->getData($this, $callback, [], $this->user_id);
  }

  protected function daemon($callback = null, $sub = []){
    $this->worker = new Worker();
    $this->worker->name = 'Keys scanning, slot' . (isset($this->config['crypto_slot']) ? 'No: ' . $this->config['crypto_slot'] : 'unknown');

    $this->worker->onWorkerStart = function() use($callback, $sub) {
      $global = $this->client();

      $time = isset($this->config['data_time']) ? $this->config['data_time'] : 0.1 ;

      Timer::add($time, function() use ($global, $callback, $sub) {
        $this->getData($global, $callback, $sub);
      });
    };
    Worker::runAll();
  }

  /**
   * @param $global
   * @param null $callback
   * @param array $sub
   * @return array
   */
  protected function getData($global, $callback = null, $sub=[])
  {
    $all_sub = $global->get('all_sub');
    if (empty($all_sub)) return [];

    $global_local = $global->get('global_local');
    $temp = [];

    if (empty($sub)) {
      foreach ($all_sub as $k => $v){
        if (is_array($v)) {
          if (empty($this->keysecret) || $this->keysecret['key'] != $k) continue;

          foreach ($v as $vv) {
            $data = $global->getQueue($vv);
            $temp[$vv] = $data;
          }
        } else {
          //$data = $global->get($v);
          if (isset($global_local['public'][$v])) $temp[$v] = $global_local['public'][$v];
        }
      }
    } else {
      if (!empty($this->keysecret)) {
        if (isset($all_sub[$this->keysecret['key']])) $sub = array_merge($sub, $all_sub[$this->keysecret['key']]);

      }
      //print_r($sub); die;
      foreach ($sub as $k => $v){
        $temp_v = explode(self::$USER_DELIMITER, $v);
        if (count($temp_v) > 1){
          //private
          $data = $global->getQueue($v);
        } else {
          //public
          //$data = $global->get($v);
          if (isset($global_local['public'][$v])) $data = $global_local['public'][$v];
        }

        if (empty($data)) continue;

        $temp[$v] = $data;
      }
    }

    if ($callback !== null){
      call_user_func_array($callback, array($temp));
    }

    return $temp;
  }

  /*
    *
    * */
  function reconPrivate(string $key){
    $debug = $this->client->debug;
    if (empty($debug)){
      $this->client->debug = [
        'private' => [$key => $key],
      ];
    } else {
      $this->client->debug = ['private' => array_merge($this->client->debug['private'], [$key => $key])];
    }
  }

  function reconPublic(){
    $this->client->debug = [
      'public' => ['market' => 'close', 'kline' => 'close'],
    ];
  }

  function test(){
    print_r($this->client->all_sub);
    print_r($this->client->add_sub);
    print_r($this->client->del_sub);
    print_r($this->client->keysecret);
    print_r($this->client->global_key);
  }

  function test2(){
    $global_key=$this->client->global_key;
    foreach ($global_key as $k=>$v){
      echo count($this->client->$v).'----'.$k.PHP_EOL;
      echo json_encode($this->client->$v).PHP_EOL;
    }
  }

  function test_reconnection(){
    $this->reconPublic();
  }

  function test_reconnection2(){
    $this->client->debug2=1;
  }

  function test_reconnection3($key){
    $this->reconPrivate($key);
  }
}
