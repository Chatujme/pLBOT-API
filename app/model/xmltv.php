<?php

declare(strict_types=1);

use SimpleXMLElement;

/**
 * XMLTV Parser - modernizovaná verze pro PHP 8.4
 *
 * @author bcmoney (original)
 * @author pLBOT-API (refactored)
 */
final class XMLTV
{
    private ?SimpleXMLElement $epg = null;

    public function __construct(string $xml)
    {
        // Zjistíme, zda je to URL nebo XML string
        $isUrl = (bool) preg_match('|^https?://[a-z0-9\-]+(\.[a-z0-9\-]+)*(:[0-9]+)?(/.*)?$|i', $xml);

        try {
            $schedule = $isUrl ? simplexml_load_file($xml) : simplexml_load_string($xml);

            if ($schedule === false) {
                throw new \RuntimeException('Failed to parse XML');
            }

            $this->epg = $schedule;
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to load XMLTV data: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Získá root XMLTV element
     */
    public function getXMLTV(): ?SimpleXMLElement
    {
        return $this->epg;
    }

    /**
     * Získá TV element
     */
    public function getTV(SimpleXMLElement $xmltv): ?SimpleXMLElement
    {
        return $xmltv->tv ?? null;
    }

    /**
     * Získá název generátoru
     */
    public function getGeneratorInfoName(SimpleXMLElement $tv): string
    {
        return (string) ($tv['generator-info-name'] ?? '');
    }

    /**
     * Získá kanály
     */
    public function getChannel(SimpleXMLElement $tv): ?SimpleXMLElement
    {
        return $tv->channel ?? null;
    }

    /**
     * Získá ID kanálu
     */
    public function getChannelID(SimpleXMLElement $channel): string
    {
        return (string) ($channel['id'] ?? '');
    }

    /**
     * Získá ikonu kanálu
     */
    public function getChannelIcon(SimpleXMLElement $channel): string
    {
        return (string) ($channel->icon['src'] ?? '');
    }

    /**
     * Získá URL kanálu
     */
    public function getChannelURL(SimpleXMLElement $channel): string
    {
        return (string) ($channel->url ?? '');
    }

    /**
     * Získá zobrazované jméno kanálu
     */
    public function getChannelDisplayName(SimpleXMLElement $channel, string $language = 'en'): string
    {
        $i18n = !empty($language) ? $language : 'en';
        return (string) ($channel['display-name'][$i18n] ?? '');
    }

    /**
     * Získá programy
     */
    public function getProgramme(SimpleXMLElement $tv): ?SimpleXMLElement
    {
        return $tv->programme ?? null;
    }

    /**
     * Získá začátek programu
     */
    public function getProgrammeStart(SimpleXMLElement $programme): string
    {
        return (string) ($programme['start'] ?? '');
    }

    /**
     * Získá konec programu
     */
    public function getProgrammeStop(SimpleXMLElement $programme): string
    {
        return (string) ($programme['stop'] ?? '');
    }

    /**
     * Získá kanál programu
     */
    public function getProgrammeChannel(SimpleXMLElement $programme): string
    {
        return (string) ($programme['channel'] ?? '');
    }

    /**
     * Získá název programu
     */
    public function getProgrammeTitle(SimpleXMLElement $programme): string
    {
        return (string) ($programme->title ?? '');
    }

    /**
     * Získá podtitul programu
     */
    public function getProgrammeSubTitle(SimpleXMLElement $programme): string
    {
        return (string) ($programme['sub-title'] ?? '');
    }

    /**
     * Získá popis programu
     */
    public function getProgrammeDesc(SimpleXMLElement $programme): string
    {
        return (string) ($programme->desc ?? '');
    }

    /**
     * Získá datum programu
     */
    public function getProgrammeDate(SimpleXMLElement $programme): string
    {
        return (string) ($programme->date ?? '');
    }

    /**
     * Získá kategorii programu
     */
    public function getProgrammeCategory(SimpleXMLElement $programme): string
    {
        return (string) ($programme->category ?? '');
    }

    /**
     * Získá ID epizody
     */
    public function getProgrammeEpisodeID(SimpleXMLElement $programme): string
    {
        $epID = (string) ($programme['episode-num']['system'] ?? '');
        return $epID === 'dd_progid' ? (string) $programme['episode-num']['system'] : '';
    }

    /**
     * Získá číslo epizody
     */
    public function getProgrammeEpisodeNum(SimpleXMLElement $programme): string
    {
        $epNum = (string) ($programme['episode-num']['system'] ?? '');
        return $epNum === 'onscreen' ? (string) $programme['episode-num']['system'] : '';
    }

    /**
     * Získá audio stereo
     */
    public function getProgrammeAudioStereo(SimpleXMLElement $programme): string
    {
        return (string) ($programme->audio['stereo'] ?? '');
    }

    /**
     * Získá datum předchozího vysílání
     */
    public function getProgrammePreviouslyShownStart(SimpleXMLElement $programme): string
    {
        return (string) ($programme['previously-shown']['start'] ?? '');
    }

    /**
     * Získá typ titulků
     */
    public function getProgrammeSubtitlesType(SimpleXMLElement $programme): string
    {
        return (string) ($programme->subtitles['type'] ?? '');
    }
}