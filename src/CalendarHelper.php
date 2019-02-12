<?php

declare(strict_types=1);

namespace Onvardgmbh\CalendarHelper;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Eluceo\iCal\Component;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Eluceo\iCal\Property\Event\Organizer;
use DOMDocument;
use LogicException;
use UnexpectedValueException;
use InvalidArgumentException;

/**
 * CalendarHelper class.
 */
class CalendarHelper
{
    /**
     * Wrapper around the Eluceo Calendar constructor
     *
     * @param string $productId
     * @param array  $components
     *
     * @return Calendar
     */
    public static function createCalendar(string $productId, array $components): Calendar
    {
        if (empty($productId)) {
            throw new UnexpectedValueException('$productId cannot be empty!');
        }

        if (empty($components)) {
            throw new UnexpectedValueException('$components cannot be empty!');
        }

        $calendar = new Calendar($productId);
        $uniqueIds = [];
        foreach ($components as $key => $component) {
            if (!($component instanceof Component)) {
                $key = var_export($key, true);
                throw new UnexpectedValueException("\$components[$key] must be an instance of Component!");
            }

            if (method_exists($component, 'getUniqueId')) {
                $uniqueId = $component->getUniqueId();
                if (in_array($uniqueId, $uniqueIds)) {
                    throw new LogicException('Id is not unique!');
                }
                $uniqueIds[] = $uniqueId;
            }

            $calendar->addComponent($component);
        }

        return $calendar;
    }

    /**
     * Wrapper around the Eluceo Event component
     * If necessary, HTML is converted to a plaintext without loss of anchor or image tags
     *
     * @param array $params
     *
     * @return Event
     */
    public static function createEventComponent(array $params): Event
    {
        $event = new Event(
            !empty($params['id'])
                ? (string) $params['id']
                : null
        );
        $event->setUseTimezone(true);

        if (!empty($params['start'])) {
            $event->setDtStart(self::dateTimeWithTimeZone($params['start']));
        } else {
            throw new UnexpectedValueException("\$params['start'] cannot be empty!");
        }

        if (!empty($params['end'])) {
            $event->setDtEnd(self::dateTimeWithTimeZone($params['end']));
        }

        $event->setNoTime(isset($params['time']) ? !$params['time'] : false);

        $summary = $params['summary'] ?? null;
        if (!empty($summary)) {
            $event->setSummary(self::html2text($summary));
        }

        $description = $params['description'] ?? null;
        if (!empty($description)) {
            $event->setDescription(self::html2text($description));
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
                throw new UnexpectedValueException("\$params['organizer']['mail'] cannot be empty!");
            }

            if (empty($organizer['name'])) {
                throw new UnexpectedValueException("\$params['organizer']['name'] cannot be empty!");
            }

            $event->setOrganizer(new Organizer(
                'MAILTO:'.trim($organizer['mail']),
                ['CN' => trim($organizer['name'])]
            ));
        }
        $status = $params['status'] ?? null;
        if (!empty($status)) {
            try {
                $event->setStatus($status);
            } catch (InvalidArgumentException $exception) {
                throw new UnexpectedValueException("\$params['status'] Can only have one of the following values: 'TENTATIVE', 'CONFIRMED', 'CANCELLED'!");
            }
        }

        return $event;
    }

    /**
     * dateTimeWithTimeZone.
     *
     * @param string|array|CarbonInterface $params
     *
     * @return CarbonInterface
     */
    private static function dateTimeWithTimeZone($params)
    {
        if (is_array($params)) {
            if (empty($params['dateTime'])) {
                throw new UnexpectedValueException("\$params['dateTime'] cannot be empty!");
            }
            if (empty($params['timeZone'])) {
                throw new UnexpectedValueException("\$params['timeZone'] cannot be empty!");
            }
            $carbon = Carbon::parse($params['dateTime'], $params['timeZone']);
        } elseif ($params instanceof Carbon) {
            $carbon = $params;
        } else {
            throw new UnexpectedValueException('$params must be an array or instance of Carbon !');
        }

        return $carbon->setTimezone('UTC');
    }

    /**
     * Converts HTML to a plaintext without loss of anchor or image tags
     *
     * @param string $html
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
        $images = iterator_to_array($dom->getElementsByTagName('img'));
        foreach ($images as $image) {
            $alt = $image->getAttribute('alt');
            if (!empty(trim($alt))) {
                $text = $dom->createTextNode($alt);
                $image->parentNode->replaceChild($text, $image);
            }
        }

        // Replace anchor tags with a text like "$text ($href)"
        $links = iterator_to_array($dom->getElementsByTagName('a'));
        foreach ($links as $link) {
            $text = $link->textContent;
            $href = $link->getAttribute('href');
            if (!empty(trim($href))) {
                if (empty(trim($text))) {
                    $link->parentNode->removeChild($link);
                } else {
                    $text = $dom->createTextNode("$text ($href)");
                    $link->parentNode->replaceChild($text, $link);
                }
            }
        }

        // Convert charset to "ISO-8859-1" (because of problems with apple calendar)
        $textContentCharset = mb_detect_encoding($dom->textContent, mb_detect_order(), true);

        return iconv($textContentCharset, 'ISO-8859-1//TRANSLIT', $dom->textContent);
    }
}
