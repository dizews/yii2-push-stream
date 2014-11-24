<?php

namespace dizews\pushStream;

use yii\web\AssetBundle;

class PushStreamAsset extends AssetBundle
{
    public $sourcePath = '@bower/pushstream';

    public $js = [
        'pushstream.js'
    ];
}