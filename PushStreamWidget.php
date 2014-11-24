<?php

namespace dizews\pushStream;

use yii\base\Widget;

class PushStreamWidget extends Widget
{

    public $pluginOptions = [
        'timeout' => 20000,
        //'modes' => 'eventsource|stream'
    ];

    /**
     * @inheritdoc
     */
    public function run()
    {
        $view = $this->getView();
        $view->registerJsFile('@bower/pushstream/pushstream.js');
        $js = "var pushStream = new PushStream({{$this->pluginOptions}});";
        $view->registerJs($js);
    }
}