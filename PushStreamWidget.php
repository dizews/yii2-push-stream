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
            $channels .= "pushstream.addChannel('{$channel}');";
        }
        $js = <<<JS
            var pushstream = new PushStream($options);
            {$channels}
            pushstream.onmessage = function (text, id, channel) {
                $.each(text.events, function (index, event) {
                    $('#{$this->containerId}').trigger({
                        channel: channel,
                        type: event.name,
                        body: event.body
                    });
                });
            };
JS;
        if ($this->connect) {
            $js .= 'pushstream.connect();';
        }
        $view->registerJs($js);
    }
}