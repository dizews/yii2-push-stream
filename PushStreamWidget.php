<?php

namespace dizews\pushStream;

use yii\base\Widget;
use yii\helpers\Json;

class PushStreamWidget extends Widget
{
    public $pluginOptions;

    public $channels;

    public $pusher = 'pusher';

    public $connect = true;


    public function init()
    {
        $config = \Yii::$app->get($this->pusher)->listenServerOptions;
        $this->pluginOptions = [
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
                //var json = $.parseJSON(text);
                var json = text;
                $(pushstream).trigger({
                    channel: channel,
                    type: json.type,
                    body: json.data
                });
            };
JS;
        if ($this->connect) {
            $js .= 'pushstream.connect();';
        }
        $view->registerJs($js);
    }
}