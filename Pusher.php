<?php

namespace dizews\pushStream;


use GuzzleHttp\Client;
use GuzzleHttp\Stream\Utils;
use yii\base\Component;
use yii\helpers\ArrayHelper;


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

    public $autoFlush = true;

    protected $channels = [];

    public function init()
    {
        $this->listenServerOptions = ArrayHelper::merge($this->serverOptions, $this->listenServerOptions);
        $this->client = new Client();
    }


    /**
     * @param string $channel channel name
     * @param string $event event name
     * @param mixed $data body of event
     * @param bool $debug debug mode
     * @return mixed
     */
    public function publish($channel, $event, $data, $debug = false)
    {
        $this->channels[$channel][] = [
            'name' => $event,
            'body' => $data
        ];

        if ($this->autoFlush) {
            return $this->flush($debug);
        }

        return true;
    }

    /**
     * @param bool $debug
     * @return mixed
     */
    public function flush($debug = false)
    {
        $endpoint = $this->makeEndpoint($this->serverOptions);

        foreach ($this->channels as $channel => $events) {
            //send $payload into $endpoint
            $response = $this->client->post($endpoint, [
                'debug' => $debug,
                'query' => ['id' => $channel],
                $this->format => [
                    'events' => $events,
                ]
            ]);
        }

        return $response->getBody()->getContents();
    }

    /**
     * @param $channels list of channels
     * @param null $callback
     * @param bool $debug
     */
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

    /**
     *
     * @param $serverOptions array of server options
     * @return string
     */
    private function makeEndpoint($serverOptions)
    {
        $protocol = $serverOptions['useSsl'] ? 'https' : 'http';
        return $protocol .'://'. $serverOptions['host'].':'.$serverOptions['port'].$serverOptions['path'];
    }
}
