<?php declare(strict_types=1);

namespace Onvardgmbh\CalendarHelper;

use Carbon\Carbon;
use DOMDocument;
use Eluceo\iCal\Component;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Eluceo\iCal\Property\Event\Organizer;
use LogicException;
use UnexpectedValueException;

/**
 * CalendarHelper class
 */
class CalendarHelper
{
    /**
     * createCalendar
     *
     * @param  string $productId
     * @param  array $components
     *
     * @return Calendar
     */
    public static function createCalendar(string $productId, array $components): Calendar
    {
        if (empty($productId)) {
            throw new UnexpectedValueException("\033[1;31m\$productId cannot be empty!\033[0m");
        }

        if (empty($components)) {
            throw new UnexpectedValueException("\033[1;31m\$components cannot be empty!\033[0m");
        }

        $calendar = new Calendar($productId);
        $uniqueIdentifiers = [];
        foreach ($components as $key => $component) {
            if (!($component instanceof Component)) {
                $key = var_export($key, true);
                throw new UnexpectedValueException("\033[1;31m\$components[$key] must from type Component!\033[0m");
            }

            if (method_exists($component, 'getUniqueId')) {
                $uniqueIdentifier = $component->getUniqueId();
                if (in_array($uniqueIdentifier, $uniqueIdentifiers)) {
                    error_log((string)new LogicException("\033[1;33mUnique identifier is not unique!\033[0m"));
                }
                $uniqueIdentifiers[] = $uniqueIdentifier;
            }

            $calendar->addComponent($component);
        }
        return $calendar;
    }

    /**
     * createEventComponent
     *
     * (If necessary, HTML is converted to a plaintext without loss of anchor or image tags)
     *
     * @param  array $params
     *  $params = [
     *      'identifier'    => (string) This property defines the persistent, globally unique identifier for the calendar component.    (optional)
     *      'start'         => [    (array) This property specifies when the calendar component begins.
     *          'dateTime'  =>      (Everything that Carbon can understand.)
     *          'timeZone'  =>      (string) e.g. 'Europe/Berlin'
     *      ]
     *      'end'           => [    (array) This property specifies the date and time that a calendar component ends.       (optional)
     *          'dateTime'  =>      (Everything that Carbon can understand.)
     *          'timeZone'  =>      (string) e.g. 'Europe/Berlin'
     *      ]
     *      'summary'       => (string) This property defines a short summary or subject for the calendar component. (html2text)        (optional)
     *      'location'      => (string|array) The property defines the intended venue for the activity defined by a calendar component. (optional)
     *      'description'   => (string) This property provides a more complete description of the calendar component,
     *                                  than that provided by the "SUMMARY" property. (html2text)                                       (optional)
     *      'organizer'     => (string|array) The property defines the organizer for a calendar component.                              (optional)
     *                                        ↪︎ ['name' => 'Max Mustermann', 'email' => 'mustermann@mustermail.de']
     *                                        ↪︎ 'Max Mustermann <mustermann@mustermail.de>'
     *      'status'        => (string) This property defines the overall status or confirmation for the calendar component.            (optional)
     *                                  allowed values: null, 'TENTATIVE', 'CONFIRMED', 'CANCELLED' (lowercase allowed)
     *  ]
     *
     * @return Event
     */
    public static function createEventComponent(array $params): Event
    {
        $identifier = $params['identifier'] ?? null;
        $identifier = empty($identifier) ? null : "$identifier";

        $event = new Event($identifier);
        $event->setUseTimezone(true);

        $startTime = Self::dateTimeWithTimeZone($params['start'] ?? null);
        if (!empty($startTime)) {
            $event->setDtStart($startTime);
        } else {
            throw new UnexpectedValueException("\033[1;31m\$params['start'] cannot be empty!\033[0m");
        }

        $endTime = Self::dateTimeWithTimeZone($params['end'] ?? null);
        if (!empty($endTime)) {
            $event->setDtEnd($endTime);
        }

        $event->setNoTime(isset($params['time']) ? !$params['time'] : false);

        $summary = $params['summary'] ?? null;
        if (!empty($summary)) {
            $event->setSummary(Self::html2text($summary));
        }

        $description = $params['description'] ?? null;
        if (!empty($description)) {
            $event->setDescription(Self::html2text($description));
            $event->setDescriptionHTML($description);
        }

        $location = $params['location'] ?? null;

        if (!empty($location)) {
            if (is_array($location)) {
                $event->setLocation(
                    $location['location'] ?? null,
                    $location['title'] ?? null,
                    $location['geo'] ?? null
                );
            } else {
                $event->setLocation($location);
            }
        }

        $organizer = $params['organizer'] ?? null;
        if (!empty($organizer)) {
            if (is_string($organizer)) {
                $regex = '/\s*(?:"([^"]*)"|(?P<name>[^,""<>]*))?\s*(?:(?:,|<|\s+|^)(?P<mail>[^<@\s,]+@[^>@\s,]+)>?)\s*/m';
                preg_match($regex, $organizer, $matches, PREG_OFFSET_CAPTURE, 0);
                $organizer = [
                    'mail' => $matches['mail'][0] ?? null,
                    'name' => $matches['name'][0] ?? null,
                ];
            }

            if (empty($organizer['mail'])) {
                throw new UnexpectedValueException("\033[1;31m\$params['organizer']['mail'] cannot be empty!\033[0m");
            }

            if (empty($organizer['name'])) {
                throw new UnexpectedValueException("\033[1;31m\$params['organizer']['name'] cannot be empty!\033[0m");
            }

            $event->setOrganizer(new Organizer(
                'MAILTO:' . trim($organizer['mail']),
                ['CN' => trim($organizer['name'])]
            ));
        }
        $status = $params['status'] ?? null;
        if (!empty($status)) {
            try {
                $event->setStatus($status);
            } catch (InvalidArgumentException $exception) {
                throw new UnexpectedValueException("\033[1;31m\$params['status'] Can only have one of the following values: 'TENTATIVE', 'CONFIRMED', 'CANCELLED'!\033[0m");
            }
        }

        return $event;
    }

    /**
     * dateTimeWithTimeZone
     *
     * @param  string|array $params
     *
     * @return Carbon|null
     */
    private static function dateTimeWithTimeZone($params)
    {
        if (!empty($params)) {
            if (is_array($params)) {
                if (empty($params['dateTime'])) {
                    throw new UnexpectedValueException("\033[1;31m\$params['dateTime'] cannot be empty!\033[0m");
                }
                if (empty($params['timeZone'])) {
                    throw new UnexpectedValueException("\033[1;31m\$params['timeZone'] cannot be empty!\033[0m");
                }
                $carbon = Carbon::parse($params['dateTime'], $params['timeZone']);
            } else {
                throw new UnexpectedValueException("\033[1;31m\$params must be an array!\033[0m");
            }

            return $carbon->setTimezone('UTC');
        } else {
            return null;
        }
    }

    /**
     * html2text
     *
     * Converts HTML to a plaintext without loss of anchor or image tags
     *
     * @param  string $html
     *
     * @return string
     */
    private static function html2text(string $html): string
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        // Replace image tags with a text like "$alt"
        $links = $dom->getElementsByTagName('img');
        for ($i = $links->length - 1; $i >= 0; $i--) {
            $node = $links->item($i);
            $alt = $node->getAttribute('alt');
            if (!empty(trim($alt))) {
                $newTextNode = $dom->createTextNode("$alt");
                $node->parentNode->replaceChild($newTextNode, $node);
            }
        }

        // Replace anchor tags with a text like "$text ($href)"
        $links = $dom->getElementsByTagName('a');
        for ($i = $links->length - 1; $i >= 0; $i--) {
            $node = $links->item($i);
            $text = $node->textContent;
            $href = $node->getAttribute('href');
            if (!empty(trim($href))) {
                if (empty(trim($text))) {
                    $node->parentNode->removeChild($node);
                } else {
                    $newTextNode = $dom->createTextNode("$text ($href)");
                    $node->parentNode->replaceChild($newTextNode, $node);
                }
            }
        }

        // Convert charset to "ISO-8859-1" (because of problems with apple calendar)
        $textContentCharset = mb_detect_encoding($dom->textContent, mb_detect_order(), true);
        return iconv($textContentCharset, "ISO-8859-1//TRANSLIT", $dom->textContent);
    }
}
