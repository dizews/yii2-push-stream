<?php

namespace dizews\pushStream;


use GuzzleHttp\Client;
use GuzzleHttp\Stream\Utils;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;


class Pusher extends Component
{
    public $contentType = 'application/json';

    /* @var Client */
    public $client;

    public $serverOptions = [
        'host' => 'http://127.0.0.1',
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
        $this->client = new Client();
    }


    public function publish($channels, $event, $data, $socketId = null, $debug = false, $encoded = false)
    {
        $channels = (array)$channels;
        $endpoint = $this->makeEndpoint($this->serverOptions);

        //we need to add limit of channels
        foreach ($channels as $channel) {
            //send $payload into $endpoint
            $response = $this->client->post($endpoint, [
                'debug' => $debug,
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'query' => ['id' => $channel],
                'body' => Json::encode([
                    'name' => $event,
                    'data' => $encoded ? $data : Json::encode($data),
                    'socketId' => $socketId,
                ])."\n"
            ]);

            return $response->getBody()->getContents();
        }
    }

    public function listen($channels, $callback = null, $debug = false)
    {
        $endpoint = $this->makeEndpoint($this->listenServerOptions);
        if (substr($this->listenServerOptions['path'], -1) != '/') {
            $endpoint .= '/';
        }
        $endpoint .= implode(',', (array)$channels);

        $response = $this->client->get($endpoint, ['debug' => $debug, 'stream' => true]);
        $body = $response->getBody();

        while (!$body->eof()) {
            if (is_callable($callback)) {
                call_user_func($callback, Utils::readline($body));
            } else {
                echo Utils::readline($body);
            }
        }
    }

    private function makeEndpoint($serverOptions)
    {
        return $serverOptions['host'].':'.$serverOptions['port'].$serverOptions['path'];
    }
}
