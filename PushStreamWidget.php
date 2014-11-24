<?php

namespace dizews\pushStream;

use yii\base\Widget;
use yii\helpers\Json;

class PushStreamWidget extends Widget
{
    public $pluginOptions;

    public $channels;


    public function init()
    {
        $this->pluginOptions = [
            'port'=> 8080,
            'modes' => 'stream'
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
                console.log(text + id + channel);
            }
            //pushstream.connect();
JS;
        $view->registerJs($js);
    }
}