<?php

namespace dizews\pushStream;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;

class PushStreamWidget extends Widget
{
    public $pluginOptions;

    public $channels;

    public $pusher = 'pusher';

    public $connect = true;

    public $containerId = 'push-stream-events';


    public function init()
    {
        $config = \Yii::$app->get($this->pusher)->listenServerOptions;
        $this->pluginOptions = [
            'useSsl' => $config['useSsl'],
            'host' => $config['host'],
            'port' => $config['port'],
            'modes' => $config['modes']
        ];
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
        $channels = '';
        foreach ((array)$this->channels as $channel) {
            $channels .= $this->pusher .".addChannel('{$channel}');";
        }
        $js = <<<JS
            var {$this->pusher} = new PushStream($options);
            {$channels}
            {$this->pusher}.onmessage = function (text, id, channel, eventId, time) {
                $('#{$this->containerId}').trigger({
                    channel: channel,
                    type: eventId,
                    body: text,
                    time: time,
                });
            };
JS;
        if ($this->connect) {
            $js .= $this->pusher .'.connect();';
        }
        $view->registerJs($js);
    }
}