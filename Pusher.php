<?php

namespace dizews\pushStream;


use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;


class Pusher extends Component
{
    public $contentType = 'application/json';

    public $serverOptions = [
        'host' => '127.0.0.1',
        'port' => 80,
        'path' => '/pub'
    ];

    public $listenServerOptions = [
        'path' => '/sub',
        'modes' => 'stream'
    ];

    public function init()
    {
        $this->listenServerOptions = ArrayHelper::merge($this->serverOptions, $this->listenServerOptions);
    }


    public function publish($channels, $event, $data, $socketId = null, $debug = false, $encoded = false)
    {
        $channels = (array)$channels;
        $endpoint = $this->makeEndpoint($this->serverOptions);

        //we need to add limit of channels
        foreach ($channels as $channel) {
            $endpoint .= http_build_query(['id' => $channel]);

            $payload = [
                'name' => $event,
                'data' => $encoded ? $data : Json::encode($data),
                'socketId' => $socketId
            ];
            //send $payload into $endpoint
        }
    }

    public function listen($channels, $callback = null, $infinityLoop = false)
    {
        $endpoint = $this->makeEndpoint($this->listenServerOptions);
        if (substr($this->listenServerOptions['path'], -1) != '/') {
            $endpoint .= '/';
        }
        $endpoint .= implode(',', (array)$channels);

        do {
            //listen server
            if ($callback instanceof \Closure) {
                $result = call_user_func($callback);
            } else {
                echo 'done!';
            }
        } while (!$infinityLoop);

    }

    private function makeEndpoint($serverOptions)
    {
        return $serverOptions['host'].$serverOptions['port'].$serverOptions['path'];
    }
}
