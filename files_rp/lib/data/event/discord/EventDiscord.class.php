<?php

namespace rp\data\event\discord;

use rp\data\event\Event;
use rp\system\event\EventDiscordScheduler;
use wcf\data\DatabaseObject;
use wcf\data\user\User;
use wcf\system\WCF;

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
 * Represents a event discord.
 * 
 * @author      Marco Daries
 * @package     Daries\RP\Data\Event\Discord
 * 
 * @property-read   int     $eventDiscordID         unique id of the event discord
 * @property-read   int     $eventID                id of the event
 * @property-read   int     $nextTime               time when next execution is
 * @property-read   int     $endTime                time of the end of the execution
 */
class EventDiscord extends DatabaseObject
{
    /**
     * @inheritDoc
     */
    protected static $databaseTableIndexName = 'eventDiscordID';

    /**
     * event object
     */
    public ?Event $event = null;

    /**
     * Executes open event discords.
     */
    public function executeEventDiscords(): void
    {
        // switch session owner to 'system' during execution of event discords
        WCF::getSession()->changeUser(new User(null, ['userID' => 0, 'username' => 'System']), true);
        WCF::getSession()->disableUpdate();

        EventDiscordScheduler::getInstance()->executeEventDiscords();
    }

    /**
     * Returns the event of event discord.
     */
    public function getEvent(): Event
    {
        if ($this->event === null) {
            $this->event = new Event($this->eventID);
        }

        return $this->event;
    }

    public static function getEventDiscordByEventID(int $eventID): ?EventDiscord
    {
        $list = new EventDiscordList();
        $list->getConditionBuilder()->add('eventID = ?', [$eventID]);

        if (!$list->countObjects()) return null;

        $list->readObjects();
        return $list->getSingleObject();
    }

    /**
     * Validates the 'executeEventDiscords' action.
     */
    public function validateExecuteEventDiscords(): void
    {
        // does nothing
    }
}
