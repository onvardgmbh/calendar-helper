#!/usr/bin/env php
<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use Onvardgmbh\CalendarHelper\CalendarHelper;
use Carbon\Carbon;

echo CalendarHelper::createCalendar('Example', [
    'test' => CalendarHelper::createEventComponent([
        'start' => Carbon::parse('2019-02-07T12:00:00+04:00'),
        'end' => ['dateTime' => '2019-02-07 14:00:00', 'timeZone' => 'Europe/Berlin'],
        'location' => 'Husemannpl. 1 44787 Bochum',
        'organizer' => 'Christan Cavasin <cavasin@onvard.de>',
        'description' => '<b>Hello, world!</b> <a href="https://onvard.de">Onvard</a>'
    ]),
    'fisch' => CalendarHelper::createEventComponent([
        'start' => ['dateTime' => '2019-02-07 12:00:00', 'timeZone' => 'Europe/Berlin'],
        'end' => ['dateTime' => '2019-02-07 14:00:00', 'timeZone' => 'Europe/Berlin'],
        'location' => 'Husemannpl. 1 44787 Bochum',
        'organizer' => 'Christan Cavasin <cavasin@onvard.de>',
        'description' => '<b>Hello, world!</b> <a href="https://onvard.de">Onvard</a>'
    ]),
])->render();