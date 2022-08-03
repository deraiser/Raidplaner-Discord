<?php

namespace rp\system\event\listener;

use rp\data\event\discord\EventDiscord;
use rp\data\event\discord\EventDiscordAction;
use rp\data\event\discord\EventDiscordEditor;
use rp\data\event\Event;
use rp\data\event\EventAction;
use rp\data\event\EventEditor;
use rp\system\cache\builder\EventDiscordCacheBuilder;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\event\listener\IParameterizedEventListener;

/**
 *  Project:    Raidplaner: Discord
 *  Package:    info.daries.rp.discord
 *  Link:       http://daries.info
 *
 *  Copyright (C) 2018-2022 Daries.info Developer Team
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published
 *  by the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @author      Marco Daries
 * @package     Daries\RP\System\Event\Listener
 */
class EventActionDiscordListener implements IParameterizedEventListener
{

    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters): void
    {
        /** @var EventAction $eventObj */
        $actionName = $eventObj->getActionName();
        switch ($actionName) {
            case 'create':
                /** @var Event $event */
                $event = $eventObj->getReturnValues()['returnValues'];
                if ($event->objectTypeID !== ObjectTypeCache::getInstance()->getObjectTypeIDByName('info.daries.rp.eventController', 'info.daries.rp.event.raid')) return;

                $this->create($event, $event->startTime);
                break;

            case 'update':
                $eventParameters = $eventObj->getParameters()['data'];

                if (isset($eventParameters['startTime'])) {
                    foreach ($eventObj->getObjects() as $eventEditor) {
                        if ($eventEditor->startTime != $eventParameters['startTime']) {
                            if ($eventEditor->eventDiscordID === null) {
                                $this->create($eventEditor->getDecoratedObject(), $eventParameters['startTime']);
                            } else {
                                $editor = new EventDiscordEditor(new EventDiscord($eventEditor->eventDiscordID));
                                $editor->update([
                                    'endTime' => $eventParameters['startTime'],
                                    'nextTime' => $eventParameters['startTime'] - (RP_EVENT_RAID_DISCORD_START * 60 * 60)
                                ]);
                            }

                            EventDiscordCacheBuilder::getInstance()->reset();
                        }
                    }
                }
                break;
        }
    }

    protected function create(Event $event, int $startTime): void
    {
        if (TIME_NOW < ($startTime - (RP_EVENT_RAID_DISCORD_INTERVAL * 60))) {
            $discordAction = new EventDiscordAction([], 'create', ['data' => [
                    'endTime' => $startTime,
                    'eventID' => $event->eventID,
                    'nextTime' => $startTime - (RP_EVENT_RAID_DISCORD_START * 60 * 60)
            ]]);
            $eventDiscord = $discordAction->executeAction()['returnValues'];

            $editor = new EventEditor($event);
            $editor->update([
                'eventDiscordID' => $eventDiscord->eventDiscordID,
            ]);

            EventDiscordCacheBuilder::getInstance()->reset();
        }
    }
}
