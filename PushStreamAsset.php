<?php

namespace dizews\pushStream;

use yii\web\AssetBundle;

class PushStreamAsset extends AssetBundle
{
    public $sourcePath = '@vendor/dizews/yii2-push-stream/assets';

    public $js = [
        'NchanSubscriber.js'
    ];
}