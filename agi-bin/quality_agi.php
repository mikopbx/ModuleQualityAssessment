#!/usr/bin/php
<?php
/*
 * MikoPBX - free phone system for small business
 * Copyright © 2017-2023 Alexey Portnov and Nikolay Beketov
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <https://www.gnu.org/licenses/>.
 */

use MikoPBX\Core\Asterisk\AGI;
use MikoPBX\Core\System\Util;
require_once 'Globals.php';

$agi 		= new AGI();
$linkedId   = $agi->get_variable('CDR(linkedid)', true);
$arr = [
    'quality'   => $agi->request['agi_extension'],
    'f_num'     => $agi->get_variable('f_num', true),
    'filename'  => $agi->get_variable('filename', true),
    'linkedid'  => $linkedId,
    'date'      => date('Y-m-d H:i:s'),
    'callerid'  => $agi->request['agi_callerid']
];

$file_log = '/storage/usbdisk1/quality/'.$linkedId.'.log';
Util::mwMkdir(dirname($file_log));
file_put_contents($file_log, json_encode($arr)."\n", FILE_APPEND);