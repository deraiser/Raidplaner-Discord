<?php

namespace rp\data\event\discord;

use rp\data\event\Event;
use rp\data\event\raid\attendee\EventRaidAttendee;
use rp\system\event\EventDiscordScheduler;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\user\User;
use wcf\system\discord\DiscordApi;
use wcf\system\exception\SystemException;
use wcf\system\exception\UserInputException;
use wcf\system\session\SessionHandler;
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
 * Executes event-discord-related actions.
 * 
 * @author      Marco Daries
 * @package     Daries\RP\Data\Event\Discord
 * 
 * @method      EventDiscordEditor[]    getObjects()
 * @method      EventDiscordEditor      getSingleObject()
 */
class EventDiscordAction extends AbstractDatabaseObjectAction
{
    /**
     * @inheritDoc
     */
    protected $allowGuestAccess = ['executeEventDiscords'];

    /**
     * @inheritDoc
     */
    protected static $baseClass = EventDiscord::class;

    /**
     * Executes open event discords.
     */
    public function executeEventDiscords()
    {
        // switch session owner to 'system' during execution of event discords
        WCF::getSession()->changeUser(new User(null, ['userID' => 0, 'username' => 'System']), true);
        WCF::getSession()->disableUpdate();

        EventDiscordScheduler::getInstance()->executeEventDiscords();
    }

    /**
     * Post event in Discord
     */
    public function postDiscord(): void
    {
        // read objects
        if (empty($this->objects)) {
            $this->readObjects();

            if (empty($this->objects)) {
                throw new UserInputException('objectIDs');
            }
        }

        $postFull = $this->parameters['postFull'] ?? false;

        $user = WCF::getUser();

        $bots = @\unserialize(\RP_EVENT_RAID_DISCORD_CHANNEL);
        if (\is_array($bots)) {
            foreach ($bots as $botID => $channelID) {
                if (empty($channelID)) continue;

                try {
                    $bot = DiscordApi::getApiByID($botID);
                    if ($bot === null) {
                        throw new SystemException("Discord Bot '{$botID}' not exist.");
                    }

                    $channel = $bot->getChannel($channelID);
                    if (!empty($channel['error'])) {
                        throw new SystemException("Discord Bot '{$botID}' channel error: " . $channel['error']['message']);
                    }

                    SessionHandler::getInstance()->changeUser(new User(null), true);
                    if (!WCF::debugModeIsEnabled()) {
                        \ob_start();
                    }

                    /** @var Event $event */
                    foreach ($this->getObjects() as $discordEvent) {
                        $event = $discordEvent->getEvent();
                        if (!$event->eventID) continue;

                        $fields = [];
                        $allCount = $allMaxCount = 0;
                        if ($event->getController()->getObjectTypeName() === 'info.daries.rp.event.raid') {
                            $availableDistributions = $event->getController()->getContentData('availableDistributions');
                            $raidData = $event->getController()->getContentData('attendees');

                            $countAttendees = [];
                            foreach ($raidData as $status => $objects) {
                                if ($status !== EventRaidAttendee::STATUS_CONFIRMED) continue;

                                foreach ($objects as $distributionID => $attendees) {
                                    $countAttendees[$distributionID] = \count($attendees);
                                }
                            }

                            foreach ($availableDistributions as $distributionID => $distribution) {
                                if ($event->distributionMode === 'none') {
                                    $maxCount = $event->participants;
                                } else {
                                    $maxCount = $event->{$distribution->identifier};
                                }

                                $count = $countAttendees[$distributionID] ?? 0;

                                $fields[] = [
                                    'name' => \is_object($distribution) ? $distribution->getTitle() : $distribution,
                                    'value' => WCF::getLanguage()->getDynamicVariable('rp.event.discord.field.value', [
                                        'count' => $count,
                                        'maxCount' => $maxCount,
                                    ]),
                                    'inline' => true
                                ];

                                $allCount += $count;
                                $allMaxCount += $maxCount;
                            }
                        }

                        if ($allCount == $allMaxCount && !$postFull) continue;

                        if (!empty($fields)) {
                            $bot->createMessage($channelID, [
                                'embed' => [
                                    'color' => \hexdec('800000'),
                                    'description' => $event->getFormattedPlainNotes(),
                                    'fields' => $fields,
                                    'title' => $event->getTitle() . ' (' . $event->getFormattedStartTime() . ')',
                                    'thumbnail' => [
                                        'url' => $event->getIconPath()
                                    ],
                                    'url' => $event->getLink()
                                ]
                            ]);
                        }
                    }
                } catch (\Exception $ex) {
                    
                } finally {
                    if (!WCF::debugModeIsEnabled()) {
                        \ob_end_clean();
                    }
                    SessionHandler::getInstance()->changeUser($user, true);
                }
            }
        }
    }

    /**
     * Validates the 'executeEventDiscords' action.
     */
    public function validateExecuteEventDiscords(): void
    {
        // does nothing
    }

    /**
     * Validates the 'postDiscord' action.
     */
    public function validatePostDiscord(): void
    {
        // does nothing
    }
}
