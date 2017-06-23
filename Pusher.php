<?php

namespace dizews\pushStream;


use Yii;
use GuzzleHttp\Client;
use GuzzleHttp\Stream\Utils;
use yii\base\Application;
use yii\base\Component;
use yii\helpers\ArrayHelper;


class Pusher extends Component
{
    public $format = 'json';

    public $debug = false;

    public $eventIdheader = 'Event-Id';

    /* @var Client */
    private $client;


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

    public $flushAfterRequest = true;

    protected $channels = [];


    public function init()
    {
        $this->listenServerOptions = ArrayHelper::merge($this->serverOptions, $this->listenServerOptions);
        $this->client = new Client();

        if ($this->flushAfterRequest) {
            Yii::$app->on(Application::EVENT_AFTER_REQUEST, function () {
                $this->flush();
            });
        }
    }


    /**
     * publish event
     *
     * @param string $channel channel name
     * @param string $event event name
     * @param mixed $data body of event
     * @return mixed
     */
    public function publish($channel, $event, $data)
    {
        $this->channels[$channel][] = [
            'name' => $event,
            'body' => $data
        ];

        if (!$this->flushAfterRequest) {
            return $this->flush();
        }

        return true;
    }

    /**
     * flush all events onto endpoint
     * @return mixed
     */
    public function flush()
    {
        $endpoint = $this->makeEndpoint($this->serverOptions);
        Yii::trace('flush events of pusher');

        if ($this->channels) {
            foreach ($this->channels as $channel => $events) {
                foreach ($events as $event) {
                    //send $payload into $endpoint
                    $response = $this->client->post($endpoint, [
                        'headers' => [
                            'Content-Type' => $this->format == 'json' ? 'application/json' : null,
                            $this->eventIdheader => $event['name'],
                        ],
                        'debug' => $this->debug,
                        'query' => ['id' => $channel],
                        $this->format => $event['body']
                    ]);
                }
            }

            $this->channels = [];
            return $response->getBody()->getContents();
        }
    }

    /**
     * listen endpoint
     *
     * @param $channels list of channels
     * @param null $callback
     */
    public function listen($channels, $callback = null)
    {
        $endpoint = $this->makeEndpoint($this->listenServerOptions);
        if (substr($this->listenServerOptions['path'], -1) != '/') {
            $endpoint .= '/';
        }
        $endpoint .= implode(',', (array)$channels);

        $response = $this->client->get($endpoint, [
            'debug' => $this->debug,
            'stream' => true
        ]);
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
