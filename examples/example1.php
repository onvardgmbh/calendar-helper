#!/usr/bin/env php
<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use Onvardgmbh\CalendarHelper\CalendarHelper;

echo CalendarHelper::createCalendar('Example', [
    CalendarHelper::createEventComponent([
        'start' => ['dateTime' => '2019-02-07 12:00:00', 'timeZone' => 'Europe/Berlin'],
        'end' => ['dateTime' => '2019-02-07 14:00:00', 'timeZone' => 'Europe/Berlin'],
        'location' => 'Husemannpl. 1 44787 Bochum',
        'organizer' => 'Christan Cavasin <cavasin@onvard.de>',
        'description' => '<b>Hello, world!</b> <a href="https://onvard.de">Onvard</a>'
    ]),
    CalendarHelper::createEventComponent([
        'start' => ['dateTime' => '2019-02-07 16:00:00', 'timeZone' => 'Europe/Berlin'],
        'end' => ['dateTime' => new \DateTime('2019-02-07 18:00:00'), 'timeZone' => 'Europe/Berlin'],
        'location' => 'Husemannpl. 1 44787 Bochum',
        'organizer' => 'Christan Cavasin <cavasin@onvard.de>',
        'summary' => 'Hello, world!',
        'description' => '<b>Hello, world!</b> <a href="https://onvard.de">Onvard</a>'
    ]),
])->render();
