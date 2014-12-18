<?php

namespace dizews\pushStream;


use GuzzleHttp\Client;
use GuzzleHttp\Stream\Utils;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;


class Pusher extends Component
{
    public $format = 'json';

    /* @var Client */
    public $client;

    public $serverOptions = [
        'useSsl' => false,
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
        $this->client = new Client();
    }


    /**
     * @param string $channel channel name
     * @param array $events array of events with data ['event-name' => ["message1"]]
     * @param null $socketId
     * @param bool $debug debug mode
     * @return mixed
     */
    public function publish($channel, $events, $socketId = null, $debug = false)
    {
        $endpoint = $this->makeEndpoint($this->serverOptions);

        //send $payload into $endpoint
        $response = $this->client->post($endpoint, [
            'debug' => $debug,
            'query' => ['id' => $channel],
            $this->format => [
                'events' => $events,
                'socketId' => $socketId,
            ]
        ]);

        return $response->getBody()->getContents();
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
        $protocol = $serverOptions['useSsl'] ? 'https' : 'http';
        return $protocol .'://'. $serverOptions['host'].':'.$serverOptions['port'].$serverOptions['path'];
    }
}
