<?php

use wcf\system\database\table\column\IntDatabaseTableColumn;
use wcf\system\database\table\column\NotNullInt10DatabaseTableColumn;
use wcf\system\database\table\column\ObjectIdDatabaseTableColumn;
use wcf\system\database\table\DatabaseTable;
use wcf\system\database\table\index\DatabaseTableForeignKey;
use wcf\system\database\table\PartialDatabaseTable;

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
 * @package     Daries\RP
 */
return [
        DatabaseTable::create('rp1_event_discord')
        ->columns([
            ObjectIdDatabaseTableColumn::create('eventDiscordID'),
            IntDatabaseTableColumn::create('eventID')
            ->length(10),
            NotNullInt10DatabaseTableColumn::create('nextTime')
            ->defaultValue(0),
            NotNullInt10DatabaseTableColumn::create('endTime')
            ->defaultValue(0)
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
            ->columns(['eventID'])
            ->referencedTable('rp1_event')
            ->referencedColumns(['eventID'])
            ->onDelete('CASCADE')
        ]),
        PartialDatabaseTable::create('rp1_event')
        ->columns([
            IntDatabaseTableColumn::create('eventDiscordID')
            ->length(10)
            ->notNull(false),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
            ->columns(['eventDiscordID'])
            ->referencedTable('rp1_event_discord')
            ->referencedColumns(['eventDiscordID'])
            ->onDelete('SET NULL')
        ]),
];
