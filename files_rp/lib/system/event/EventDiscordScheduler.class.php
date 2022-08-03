<?php

namespace rp\system\event;

use rp\data\event\discord\EventDiscordAction;
use rp\data\event\discord\EventDiscordEditor;
use rp\data\event\discord\EventDiscordList;
use rp\data\event\Event;
use rp\system\cache\builder\EventDiscordCacheBuilder;
use wcf\system\SingletonFactory;

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
 * Provides functions to execute event discords.
 *
 * @author      Marco Daries
 * @package     Daries\RP\System\Event
 */
class EventDiscordScheduler extends SingletonFactory
{
    /**
     * cached times of the next event discord execution
     * @var int
     */
    protected $nextTime = 0;

    /**
     * Clears the event discord data cache.
     */
    public static function clearCache(): void
    {
        EventDiscordCacheBuilder::getInstance()->reset();
    }

    /**
     * Executes outstanding execute event discords.
     */
    public function executeEventDiscords(): void
    {
        // break if there are no outstanding event discords
        if ($this->nextTime > TIME_NOW) {
            return;
        }

        // get outstanding cronjobs
        $eventDiscords = $this->loadEventDiscords();

        $posts = [];
        foreach ($eventDiscords as $eventDiscord) {
            /** @var Event $event */
            $event = $eventDiscord->getEvent();

            $nextTime = TIME_NOW + (RP_EVENT_RAID_DISCORD_INTERVAL * 60);
            $eventDiscordEditor = new EventDiscordEditor($eventDiscord);
            if ($eventDiscordEditor->endTime <= $nextTime) {
                $eventDiscordEditor->delete();
            } else {
                $eventDiscordEditor->update(['nextTime' => $nextTime]);
            }

            if ($event->isClosed || $event->isDisabled || $event->isDeleted || $event->raidID) continue;
            
            $posts[] = $eventDiscord;
        }
        
        if (!empty($posts)) {
            $action = new EventDiscordAction($posts, 'postDiscord');
            $action->executeAction();
        }
    }

    /**
     * @inheritDoc
     */
    protected function init(): void
    {
        $this->loadCache();
    }

    /**
     * Loads the cached data for event discord execution.
     */
    protected function loadCache(): void
    {
        $this->nextTime = EventDiscordCacheBuilder::getInstance()->getData([], 'nextTime');
    }

    /**
     * Loads outstanding event discords.
     */
    protected function loadEventDiscords(): array
    {
        $list = new EventDiscordList();
        $list->getConditionBuilder()->add('nextTime < ?', [TIME_NOW]);
        $list->readObjects();
        return $list->getObjects();
    }
}
