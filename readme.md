# Calendar Helper

Calendar Helper is a php library for dealing with ical's.

## Installation

Use composer to install the Calendar Helper.

```bash
composer install onvardgmbh/calendar
```

## Usage

```php
<?php

use Onvardgmbh\CalendarHelper\CalendarHelper;

$calendar = CalendarHelper::createCalendar('Example', [
    CalendarHelper::createEventComponent([
        'start' => ['dateTime' => '2019-02-07 12:00:00', 'timeZone' => 'Europe/Berlin'],
        'end' => ['dateTime' => '2019-02-07 14:00:00', 'timeZone' => 'Europe/Berlin'],
        'location' => 'Husemannpl. 1 44787 Bochum',
        'organizer' => 'Christan Cavasin <cavasin@onvard.de>',
        'description' => '<b>Hello, world!</b> <a href="https://onvard.de">Onvard</a>'
    ]),
    // ...
]);

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="cal.ics"');
echo $calendar->render();
```

```php
<?php
use Onvardgmbh\CalendarHelper\CalendarHelper;

CalendarHelper::createEventComponent([
    // (string) This property defines the persistent, globally unique identifier for the calendar component.    (optional)
    'identifier'    => '..',
    
    // (array) This property specifies when the calendar component begins.
    'start'         => [
       'dateTime'   =>   '2019-02-07 14:00:00', //  (Everything that Carbon can understand.)
       'timeZone'   =>    'Europe/Berlin',      //  (string) e.g. 'Europe/Berlin'
    ],
    
    // (array) This property specifies the date and time that a calendar component ends.                        (optional)
    'end'           => [
       'dateTime'   =>   '2019-02-07 16:00:00', // (Everything that Carbon can understand.)
       'timeZone'   => 'Europe/Berlin',         // (string) e.g. 'Europe/Berlin'
    ],
    
    // (string) This property defines a short summary or subject for the calendar component. (html2text)        (optional)
    'summary'       => 'Hello, world!',
    
    // (string|array) The property defines the intended venue for the activity defined by a calendar component. (optional)
    'location'      => 'Husemannpl. 1 44787 Bochum',
    
    // (string) This property provides a more complete description of the calendar component,
    //          than that provided by the "SUMMARY" property. (html2text)                                       (optional)
    'description'   => '<b>Hello, world!</b> <a href="https://onvard.de">Onvard</a>',
                      
    
    // (string|array) The property defines the organizer for a calendar component.                              (optional)
    //                  ↪︎ ['name' => 'Max Mustermann', 'email' => 'mustermann@mustermail.de']
    //                  ↪︎ 'Max Mustermann <mustermann@mustermail.de>'
    'organizer'     => 'Christan Cavasin <cavasin@onvard.de>',
    
    // (string) This property defines the overall status or confirmation for the calendar component.            (optional)
    //          allowed values: null, 'TENTATIVE', 'CONFIRMED', 'CANCELLED' (lowercase allowed)
    'status'        => 'CONFIRMED',
]);
