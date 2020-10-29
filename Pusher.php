<?php

namespace dizews\pushStream;


use Yii;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use yii\base\Application;
use yii\base\Component;
use yii\helpers\ArrayHelper;


class Pusher extends Component
{
    public $format = 'json';

    public $debug = false;

    public $eventIdheader = 'X-EventSource-Event';

    public $channelSplitter = '/';

    /* @var Client */
    private $client;


    public $serverOptions = [
        'host' => 'http://127.0.0.1',
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
    public function publish(string $channel, string $event, $payload = [])
    {
        $this->channels[$channel][] = [
            'time' => date(\DateTime::RFC7231),
            'event' => $event,
            'payload' => $payload,
        ];

        return $this;
    }

    /**
     * @param string $channel
     */
    public function create(string $channel)
    {
        $endpoint = $this->makeEndpoint($this->serverOptions);
        $this->getClient()->put($endpoint, ['query' => ['id' => $channel]]);
    }

    /**
     * @param string $channel
     */
    public function delete(string $channel)
    {
        $endpoint = $this->makeEndpoint($this->serverOptions);
        $this->getClient()->delete($endpoint, ['query' => ['id' => $channel]]);
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
                    $event['channel'] = (string)$channel;
                    $response = $this->getClient()->post($endpoint, [
                        'query' => ['id' => $channel],
                        'headers' => array_filter([
                            'Content-Type' => $this->format == 'json' ? 'application/json' : null,
                            $this->eventIdheader => $event['eventId'],
                        ]),
                        $this->format => $event
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
        $endpoint = $this->getListenerHost($channels);

        $response = $this->getClient()->get($endpoint, [
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

    public function getListenerHost($channels)
    {
        $endpoint = $this->makeEndpoint(array_merge($this->serverOptions, $this->listenServerOptions));
        if (substr($endpoint, -1) != '/') {
            $endpoint .= '/';
        }
        $endpoint .= implode($this->channelSplitter, (array)$channels);

        return $endpoint;
    }

    /**
     * @return Client
     */
    protected function getClient()
    {
        if (!$this->client) {
            $this->client = new Client();
        }

        return $this->client;
    }

    /**
     *
     * @param $serverOptions array of server options
     * @return string
     */
    private function makeEndpoint($serverOptions)
    {
        return $serverOptions['host'].$serverOptions['path'];
    }
}
