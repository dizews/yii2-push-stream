<?php

namespace dizews\pushStream\behavior;

use common\components\job\CashierJob;
use common\helpers\ArrayHelper;
use common\models\Payment;
use common\models\PaymentOperation;
use common\models\PaymentStatus;
use dizews\pushStream\Pusher;
use Yii;
use yii\base\Behavior;
use yii\base\Event;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;
use common\models\PaymentAccountType;


class PusherBehavior extends Behavior
{
    public $pusherComponent = 'pusher';

    public $channelAttribute = 'channel';


    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'process',
            ActiveRecord::EVENT_AFTER_UPDATE => 'process',
            ActiveRecord::EVENT_BEFORE_DELETE => 'delete',
        ];
    }

    /**
     * @param AfterSaveEvent $event
     */
    public function process(AfterSaveEvent $event)
    {
        /** @var Payment $sender */
        $sender = $event->sender;
        $channel = $sender->getAttribute($this->channelAttribute);
        if ($this->isAttributeChanged($event, $this->channelAttribute)) {
            if ($event->name == ActiveRecord::EVENT_AFTER_UPDATE) {
                $this->getClient()->delete($event->changedAttributes[$this->channelAttribute]);
            }
            $this->getClient()->create($channel);
        }
    }

    public function delete(Event $event)
    {
        $channel = $event->sender->getAttribute($this->channelAttribute);
        $this->getClient()->delete($channel);
    }

    /**
     * @return Pusher|object
     */
    protected function getClient()
    {
        return Yii::$app->get($this->pusherComponent);
    }

    private function isAttributeChanged($event, $attribute)
    {
        return key_exists($attribute, $event->changedAttributes)
            && $event->sender->getAttribute($attribute) != $event->changedAttributes[$attribute]
        ;
    }
}