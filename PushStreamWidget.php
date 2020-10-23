<?php

namespace dizews\pushStream;

use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;

class PushStreamWidget extends Widget
{
    public $pluginOptions = [];

    public $channels;

    public $pusher = 'pusher';

    public $connect = true;

    public $containerId = 'push-stream-events';

    private $url;


    public function init()
    {
        $config = array_merge($this->getPusher()->serverOptions, $this->getPusher()->listenServerOptions);

        $this->url = "{$config['host']}{$config['path']}";
        $this->pluginOptions = ArrayHelper::merge([
            'host' => $config['host'],
            'port' => $config['port'],
            'modes' => $config['modes'],
        ], $this->pluginOptions);
    }
    /**
     * @inheritdoc
     */
    public function run()
    {
        echo Html::tag('div', null, ['id' => $this->containerId, 'style'=> 'display:none']);
        $view = $this->getView();
        PushStreamAsset::register($view);

        $options = Json::encode($this->pluginOptions);

        $url = $this->url.'/'.implode('/', $this->channels);

        $js= <<<JS
            var {$this->pusher} = new NchanSubscriber('{$url}', {$options});
            {$this->pusher}.on("message", function(event, message_metadata) {
                var msg = jQuery.parseJSON(event);
                $('#{$this->containerId}').trigger({
                    channel: msg.channel,
                    type: msg.eventId,
                    body: msg.payload,
                    time: msg.time
                });
            });
JS;
        if ($this->connect) {
            $js .= $this->pusher .'.start();';
        }
        $view->registerJs($js);
    }

    /**
     * @return Pusher
     */
    private function getPusher()
    {
        return Yii::$app->get($this->pusher);
    }
}