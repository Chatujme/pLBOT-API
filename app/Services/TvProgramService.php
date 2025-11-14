<?php

declare(strict_types=1);

namespace App\Services;

use DateTime;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Utils\Strings;
use SimpleXMLElement;

/**
 * Service pro získávání TV programu
 */
final class TvProgramService
{
    private Cache $cache;

    private const URL_XMLTV = 'http://xmltv.tvpc.cz/xmltv.xml';
    private const CACHE_EXPIRATION = '1 hour';

    private const CHANNEL_NAMES = [
        'ctd' => 'ČT : D',
        'plus' => 'Plus',
        'radiozurnal' => 'Radiožurnál',
        'jazz' => 'Jazz',
        'dvojka' => 'Dvojka',
        'ct1' => 'ČT1',
        'vltava' => 'Vltava',
        'ct2' => 'ČT2',
        'wave' => 'Wave',
        'ct24' => 'ČT24',
        'dur' => 'Dur',
        'ct4' => 'ČT SPORT',
        'junior' => 'Junior',
        'novacinema' => 'Nova Cinema',
        'primacool' => 'Prima Cool',
        'nova' => 'TV Nova',
        'primafamily' => 'Prima',
        'tvbarrandov' => 'TV Barrandov',
        '780.dvb.guide' => 'Prima Love',
        '6914.dvb.guide' => 'Televize Seznam',
        'proglas' => 'Proglas',
        '788.dvb.guide' => 'Prima Krimi',
        '2053.dvb.guide' => 'Kino Barrandov',
        'primazoom' => 'Prima Zoom',
        'slagrtv' => 'Šlágr TV',
        '2052.dvb.guide' => 'Barrandov Krimi',
        '779.dvb.guide' => 'Prima Max',
        'ocko' => 'Óčko',
        '2562.dvb.guide' => 'JOJ Family',
        'pohoda' => 'Pohoda',
        'telka' => 'Telka',
        'fanda' => 'Fanda',
        '2818.dvb.guide' => 'Rebel',
        'smichov' => 'Smíchov',
        '801.dvb.guide' => 'Prima Comedy Central',
    ];

    public function __construct(
        private readonly HttpClientService $httpClient,
        Storage $storage
    ) {
        $this->cache = new Cache($storage, self::class);
    }

    /**
     * Získá seznam TV stanic
     *
     * @return array<string, string>
     */
    public function getStanice(): array
    {
        $programmes = $this->getEPGData();
        $result = [];

        foreach (array_keys($programmes) as $stanice) {
            $result[$stanice] = "/tv/{$stanice}";
        }

        return $result;
    }

    /**
     * Získá aktuální program pro všechny stanice
     *
     * @return array{data: array<string, array<mixed>>}
     */
    public function getAllCurrentPrograms(): array
    {
        $programmes = $this->getEPGData();
        $result = ['data' => []];

        foreach ($programmes as $stanice => $programs) {
            $currentProgram = $this->findCurrentProgram($programs);
            $result['data'][$stanice] = $currentProgram !== null ? [$currentProgram] : [
                [
                    'program' => '??',
                    'zacatek' => '??',
                    'konec' => '??',
                    'zacatek-full' => '??',
                    'konec-full' => '??',
                ],
            ];
        }

        return $result;
    }

    /**
     * Získá aktuální program pro konkrétní stanici
     *
     * @return array{data: array<string, mixed>|null}
     */
    public function getCurrentProgram(string $stanice): array
    {
        $stanice = $this->normalizeStaniceName($stanice);
        $programmes = $this->getEPGData();

        if (!isset($programmes[$stanice])) {
            return [
                'message' => "Stanice {$stanice} neexistuje v seznamu televizí",
                'data' => null,
            ];
        }

        $currentProgram = $this->findCurrentProgram($programmes[$stanice]);

        if ($currentProgram === null) {
            return [
                'data' => [
                    'program' => '??',
                    'zacatek' => '??',
                    'konec' => '??',
                    'stanice' => $stanice,
                ],
            ];
        }

        return [
            'data' => [
                ...$currentProgram,
                'stanice' => $stanice,
            ],
        ];
    }

    /**
     * Načte a parsuje EPG data
     *
     * @return array<string, array<array<string, mixed>>>
     */
    private function getEPGData(): array
    {
        $cacheKey = 'tv_epg_' . date('Y-m-d_H');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $xml = $this->httpClient->get(self::URL_XMLTV);
        $programmes = $this->parseXMLTV($xml);

        $this->cache->save($cacheKey, $programmes, [
            Cache::EXPIRE => self::CACHE_EXPIRATION,
        ]);

        return $programmes;
    }

    /**
     * Parsuje XMLTV formát
     *
     * @return array<string, array<array<string, mixed>>>
     */
    private function parseXMLTV(string $xml): array
    {
        $xmlObj = simplexml_load_string($xml);

        if ($xmlObj === false) {
            throw new \RuntimeException('Failed to parse XMLTV data');
        }

        $programmes = [];

        foreach ($xmlObj->programme as $programme) {
            $attrs = $programme->attributes();
            $channelId = (string) $attrs['channel'];

            // Normalizujeme název stanice
            $channelName = self::CHANNEL_NAMES[$channelId] ?? $channelId;
            $channelKey = $this->normalizeStaniceName($channelName);

            if (!isset($programmes[$channelKey])) {
                $programmes[$channelKey] = [];
            }

            $title = (string) $programme->title;
            if (is_array($programme->title)) {
                $title = (string) $programme->title[0];
            }

            $subTitle = isset($programme->{'sub-title'}) ? (string) $programme->{'sub-title'} : '';
            $desc = isset($programme->desc) ? (string) $programme->desc : '';

            $start = DateTime::createFromFormat('YmdHis O', (string) $attrs['start']);
            $stop = DateTime::createFromFormat('YmdHis O', (string) $attrs['stop']);

            if ($start === false || $stop === false) {
                continue;
            }

            $programmes[$channelKey][] = [
                'program' => $title,
                'popis' => $subTitle . ' ' . $desc,
                'zacatek' => $start,
                'konec' => $stop,
            ];
        }

        return $programmes;
    }

    /**
     * Najde aktuálně běžící program
     *
     * @param array<array<string, mixed>> $programs
     * @return array<string, mixed>|null
     */
    private function findCurrentProgram(array $programs): ?array
    {
        $now = time();

        foreach ($programs as $program) {
            if (!isset($program['zacatek']) || !isset($program['konec'])) {
                continue;
            }

            if ($program['zacatek'] instanceof DateTime && $program['konec'] instanceof DateTime) {
                if ($program['zacatek']->getTimestamp() <= $now && $program['konec']->getTimestamp() >= $now) {
                    return [
                        'program' => $program['program'],
                        'popis' => $program['popis'] ?? '',
                        'zacatek' => $program['zacatek']->format('H:i'),
                        'konec' => $program['konec']->format('H:i'),
                        'zacatek-full' => $program['zacatek']->format('d.m.Y H:i'),
                        'konec-full' => $program['konec']->format('d.m.Y H:i'),
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Normalizuje název stanice do URL-friendly formátu
     */
    private function normalizeStaniceName(string $name): string
    {
        return strtolower(str_replace(' ', '-', Strings::toAscii($name)));
    }
}
