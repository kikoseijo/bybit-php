<?php
/**
 * @author Kiko Seijo
 * */

namespace Ksoft\Bybit\Api\WebSocket;

trait SocketFunction
{
    public static $USER_DELIMITER = '===';

    /**
     * @param $global
     * @param $tag
     * @param $data
     * @param string $keysecret
     */
    protected function errorMessage($global, $tag, $data, $keysecret = '')
    {
        $all_sub = $global->get('all_sub');
        if (empty($all_sub)) {
            return;
        }

        if ($tag == 'public') {
            foreach ($all_sub as $k => $v) {
                if (is_array($v)) {
                    continue;
                }
                $sub = strtolower($v);
                if (stristr(strtolower($data['message']), $v) !== false) {
                    $global->save($sub, $data);
                }
            }
        } else {
            /*foreach ($all_sub as $k=>$v){
                if(!is_array($v)) continue;
                $sub=strtolower($v[0]);
                $global->add($keysecret['key'].$sub,$data);
            }*/
        }
    }

    protected function log($message)
    {
        if (!is_string($message)) {
            $message=json_encode($message);
        }

        $time = time();
        $tiemdate = date('d.m.Y H:i:s', $time);

        $message = $tiemdate . ' ' . $message . PHP_EOL;

        if (isset($this->config['log'])) {
            if (is_array($this->config['log']) && isset($this->config['log']['filename'])) {
                $filename = $this->config['log']['filename'] . '--' . date('d.m.Y', $time) . '.log';
            } else {
                $filename = date('d.m.Y', $time) . '.log';
            }

            file_put_contents($filename, $message, FILE_APPEND);
        }

        echo $message;
    }


    protected function userKey(array $keysecret, string $sub)
    {
        return $keysecret['key'] . self::$USER_DELIMITER . $sub;
    }


    private function reconnection($global, $type = 'public', array $keysecret = [])
    {
        $all_sub = $global->get('all_sub');
        if (empty($all_sub)) {
            return;
        }

        $temp = [];
        if ($type == 'public') {
            foreach ($all_sub as $v) {
                if (!is_array($v)) {
                    $temp[] = $v;
                }
            }
        //$global->save('add_sub',$temp);
        } else {
        }

        $add_sub = $global->get('add_sub');
        if (empty($add_sub)) {
            $global->save('add_sub', $temp);
        } else {
            $global->save('add_sub', array_merge($temp, $add_sub));
        }
    }
}
