<?php

/**
 * OpenAPI Examples Generator
 *
 * This file returns an array of example responses for each API endpoint.
 * These examples are used to enhance the OpenAPI specification.
 */

return [
    // Advice
    '/advice' => [
        'get' => [
            'data' => [
                'id' => 42,
                'advice' => 'It is easy to sit up and take notice, what is difficult is getting up and taking action.'
            ]
        ]
    ],

    // ARES
    '/ares/ico/{ico}' => [
        'get' => [
            'data' => [
                'ico' => '27074358',
                'obchodni_jmeno' => 'Alza.cz a.s.',
                'pravni_forma' => 'Akciová společnost',
                'sidlo' => [
                    'ulice' => 'Jankovcova',
                    'cislo_domovni' => '1522',
                    'cislo_orientacni' => '53',
                    'obec' => 'Praha',
                    'cast_obce' => 'Holešovice',
                    'psc' => '17000',
                    'stat' => 'Česká republika'
                ],
                'datum_vzniku' => '2003-09-12',
                'dic' => 'CZ27074358'
            ]
        ]
    ],
    '/ares/vyhledat' => [
        'get' => [
            'data' => [
                [
                    'ico' => '27074358',
                    'obchodni_jmeno' => 'Alza.cz a.s.',
                    'sidlo' => 'Jankovcova 1522/53, Holešovice, 170 00 Praha 7'
                ],
                [
                    'ico' => '24825484',
                    'obchodni_jmeno' => 'Alza Services s.r.o.',
                    'sidlo' => 'Jankovcova 1522/53, Holešovice, 170 00 Praha 7'
                ]
            ],
            'count' => 2,
            'query' => 'Alza'
        ]
    ],

    // Bored
    '/bored' => [
        'get' => [
            'data' => [
                'activity' => 'Learn to write in a new language',
                'type' => 'education',
                'participants' => 1,
                'price' => 0.1,
                'accessibility' => 0.2,
                'key' => '8324534'
            ]
        ]
    ],
    '/bored/activity/{key}' => [
        'get' => [
            'data' => [
                'activity' => 'Learn to write in a new language',
                'type' => 'education',
                'participants' => 1,
                'price' => 0.1,
                'accessibility' => 0.2,
                'key' => '8324534'
            ]
        ]
    ],

    // Cat Facts
    '/catfact' => [
        'get' => [
            'data' => [
                'fact' => 'Cats have over 20 vocalizations, including the purr, meow, and chirp.',
                'length' => 71
            ]
        ]
    ],

    // Chuck Norris
    '/chucknorris' => [
        'get' => [
            'data' => [
                'id' => 'abc123xyz',
                'value' => 'Chuck Norris can divide by zero.',
                'url' => 'https://api.chucknorris.io/jokes/abc123xyz',
                'icon_url' => 'https://api.chucknorris.io/img/avatar/chuck-norris.png',
                'categories' => []
            ]
        ]
    ],
    '/chucknorris/categories' => [
        'get' => [
            'data' => ['animal', 'career', 'celebrity', 'dev', 'explicit', 'fashion', 'food', 'history', 'money', 'movie', 'music', 'political', 'religion', 'science', 'sport', 'travel']
        ]
    ],

    // CNB (Czech National Bank)
    '/cnb/kurzy' => [
        'get' => [
            'data' => [
                'datum' => '2026-01-05',
                'kurzy' => [
                    ['kod' => 'EUR', 'mena' => 'euro', 'mnozstvi' => 1, 'kurz' => 25.125],
                    ['kod' => 'USD', 'mena' => 'dolar', 'mnozstvi' => 1, 'kurz' => 24.285],
                    ['kod' => 'GBP', 'mena' => 'libra', 'mnozstvi' => 1, 'kurz' => 30.425]
                ]
            ]
        ]
    ],
    '/cnb/kurzy/{mena}' => [
        'get' => [
            'data' => [
                'kod' => 'EUR',
                'mena' => 'euro',
                'mnozstvi' => 1,
                'kurz' => 25.125,
                'datum' => '2026-01-05'
            ]
        ]
    ],
    '/cnb/prevod' => [
        'get' => [
            'data' => [
                'castka' => 100,
                'z_meny' => 'EUR',
                'na_menu' => 'CZK',
                'vysledek' => 2512.50,
                'kurz' => 25.125,
                'datum' => '2026-01-05'
            ]
        ]
    ],

    // Countries
    '/countries/all' => [
        'get' => [
            'data' => [
                [
                    'name' => 'Czechia',
                    'official_name' => 'Czech Republic',
                    'capital' => 'Prague',
                    'region' => 'Europe',
                    'subregion' => 'Central Europe',
                    'population' => 10698896,
                    'area' => 78865.0,
                    'languages' => ['Czech'],
                    'currencies' => [['code' => 'CZK', 'name' => 'Czech koruna', 'symbol' => 'Kč']],
                    'flag' => '🇨🇿'
                ]
            ],
            'count' => 1
        ]
    ],
    '/countries/{country}' => [
        'get' => [
            'data' => [
                'name' => 'Czechia',
                'official_name' => 'Czech Republic',
                'capital' => 'Prague',
                'region' => 'Europe',
                'subregion' => 'Central Europe',
                'population' => 10698896,
                'area' => 78865.0,
                'languages' => ['Czech', 'Slovak'],
                'currencies' => [['code' => 'CZK', 'name' => 'Czech koruna', 'symbol' => 'Kč']],
                'flag' => '🇨🇿',
                'timezones' => ['UTC+01:00'],
                'borders' => ['AUT', 'DEU', 'POL', 'SVK']
            ]
        ]
    ],
    '/countries/region/{region}' => [
        'get' => [
            'data' => [
                [
                    'name' => 'Germany',
                    'capital' => 'Berlin',
                    'population' => 83240525,
                    'flag' => '🇩🇪'
                ],
                [
                    'name' => 'France',
                    'capital' => 'Paris',
                    'population' => 67390000,
                    'flag' => '🇫🇷'
                ]
            ],
            'count' => 2,
            'region' => 'Europe'
        ]
    ],

    // Crypto
    '/crypto/popular' => [
        'get' => [
            'data' => [
                [
                    'id' => 'bitcoin',
                    'symbol' => 'btc',
                    'name' => 'Bitcoin',
                    'current_price' => 98245.50,
                    'market_cap' => 1934567890123,
                    'price_change_24h' => 1234.56,
                    'price_change_percentage_24h' => 1.27
                ],
                [
                    'id' => 'ethereum',
                    'symbol' => 'eth',
                    'name' => 'Ethereum',
                    'current_price' => 3456.78,
                    'market_cap' => 415678901234,
                    'price_change_24h' => 45.67,
                    'price_change_percentage_24h' => 1.34
                ]
            ],
            'timestamp' => '2026-01-05 10:30:00'
        ]
    ],
    '/crypto/price/{coin}' => [
        'get' => [
            'data' => [
                'id' => 'bitcoin',
                'symbol' => 'btc',
                'name' => 'Bitcoin',
                'current_price' => [
                    'usd' => 98245.50,
                    'eur' => 90234.25,
                    'czk' => 2467890.00
                ],
                'market_cap' => 1934567890123,
                'total_volume' => 45678901234,
                'high_24h' => 99000.00,
                'low_24h' => 96500.00,
                'price_change_24h' => 1234.56,
                'price_change_percentage_24h' => 1.27,
                'last_updated' => '2026-01-05T10:30:00Z'
            ]
        ]
    ],

    // Dog
    '/dog' => [
        'get' => [
            'data' => [
                'image_url' => 'https://images.dog.ceo/breeds/retriever-golden/n02099601_1234.jpg',
                'breed' => 'golden retriever'
            ]
        ]
    ],
    '/dog/breeds' => [
        'get' => [
            'data' => [
                'breeds' => [
                    'affenpinscher',
                    'akita',
                    'beagle',
                    'bulldog',
                    'collie',
                    'dalmatian',
                    'husky',
                    'labrador',
                    'poodle',
                    'retriever'
                ],
                'count' => 10
            ]
        ]
    ],

    // Fox
    '/fox' => [
        'get' => [
            'data' => [
                'image_url' => 'https://randomfox.ca/images/123.jpg'
            ]
        ]
    ],

    // Hash
    '/hash' => [
        'get' => [
            'data' => [
                'input' => 'hello',
                'algorithm' => 'sha256',
                'hash' => '2cf24dba5fb0a30e26e83b2ac5b9e29e1b161e5c1fa7425e73043362938b9824'
            ]
        ]
    ],
    '/hash/algorithms' => [
        'get' => [
            'data' => ['md5', 'sha1', 'sha256', 'sha384', 'sha512', 'crc32', 'adler32']
        ]
    ],
    '/hash/base64/encode' => [
        'get' => [
            'data' => [
                'input' => 'hello',
                'encoded' => 'aGVsbG8='
            ]
        ]
    ],
    '/hash/base64/decode' => [
        'get' => [
            'data' => [
                'input' => 'aGVsbG8=',
                'decoded' => 'hello'
            ]
        ]
    ],
    '/hash/hex/encode' => [
        'get' => [
            'data' => [
                'input' => 'hello',
                'encoded' => '68656c6c6f'
            ]
        ]
    ],
    '/hash/hex/decode' => [
        'get' => [
            'data' => [
                'input' => '68656c6c6f',
                'decoded' => 'hello'
            ]
        ]
    ],
    '/hash/hmac' => [
        'get' => [
            'data' => [
                'input' => 'hello',
                'key' => 'secret',
                'algorithm' => 'sha256',
                'hmac' => '88aab3ede8d3adf94d26ab90d3bafd4a2083070c3bcce9c014ee04a443847c0b'
            ]
        ]
    ],

    // Horoskop
    '/horoskop/{znameni}' => [
        'get' => [
            'data' => [
                'znameni' => 'beran',
                'datum' => '2026-01-05',
                'text' => 'Dnes vás čeká plodný den plný nových příležitostí. Hvězdy naznačují pozitivní změny v osobním životě.',
                'laska' => 'V lásce se vám dnes bude dařit. Očekávejte romantické překvapení.',
                'prace' => 'V práci budete mít skvělé nápady. Nebojte se je sdílet s kolegy.',
                'zdravi' => 'Věnujte pozornost svému zdraví a odpočinku.'
            ]
        ]
    ],

    // ISS
    '/iss/position' => [
        'get' => [
            'data' => [
                'latitude' => 45.6789,
                'longitude' => -123.4567,
                'altitude' => 408.5,
                'velocity' => 27600,
                'timestamp' => '2026-01-05T10:30:00Z',
                'visibility' => 'daylight'
            ]
        ]
    ],
    '/iss/astronauts' => [
        'get' => [
            'data' => [
                'count' => 7,
                'astronauts' => [
                    ['name' => 'Oleg Kononenko', 'craft' => 'ISS'],
                    ['name' => 'Nikolai Chub', 'craft' => 'ISS'],
                    ['name' => 'Tracy Caldwell Dyson', 'craft' => 'ISS'],
                    ['name' => 'Matthew Dominick', 'craft' => 'ISS'],
                    ['name' => 'Michael Barratt', 'craft' => 'ISS'],
                    ['name' => 'Jeanette Epps', 'craft' => 'ISS'],
                    ['name' => 'Alexander Grebenkin', 'craft' => 'ISS']
                ]
            ]
        ]
    ],
    '/iss/pass' => [
        'get' => [
            'data' => [
                'location' => [
                    'latitude' => 50.08,
                    'longitude' => 14.42
                ],
                'passes' => [
                    [
                        'risetime' => '2026-01-05T18:30:00Z',
                        'duration' => 542,
                        'max_elevation' => 67.5
                    ],
                    [
                        'risetime' => '2026-01-05T20:05:00Z',
                        'duration' => 623,
                        'max_elevation' => 85.2
                    ]
                ]
            ]
        ]
    ],

    // Joke
    '/joke' => [
        'get' => [
            'data' => [
                'type' => 'twopart',
                'category' => 'Programming',
                'setup' => 'Why do programmers prefer dark mode?',
                'delivery' => 'Because light attracts bugs!'
            ]
        ]
    ],
    '/joke/programming' => [
        'get' => [
            'data' => [
                'type' => 'single',
                'category' => 'Programming',
                'joke' => 'A SQL query walks into a bar, walks up to two tables and asks... "Can I join you?"'
            ]
        ]
    ],

    // Mistnost (Room Temperature)
    '/mistnost/{id}' => [
        'get' => [
            'data' => [
                'id' => 123,
                'name' => 'Obývací pokoj',
                'temperature' => 22.5,
                'humidity' => 45,
                'last_update' => '2026-01-05T10:30:00Z'
            ]
        ]
    ],

    // News
    '/news/latest' => [
        'get' => [
            'data' => [
                [
                    'title' => 'Nové technologie v roce 2026',
                    'description' => 'Přehled nejnovějších technologických trendů.',
                    'link' => 'https://example.com/clanek1',
                    'published' => '2026-01-05T09:00:00Z',
                    'source' => 'novinky'
                ],
                [
                    'title' => 'Ekonomické zprávy',
                    'description' => 'Aktuální stav ekonomiky.',
                    'link' => 'https://example.com/clanek2',
                    'published' => '2026-01-05T08:30:00Z',
                    'source' => 'idnes'
                ]
            ],
            'count' => 2
        ]
    ],
    '/news/sources' => [
        'get' => [
            'data' => [
                ['id' => 'novinky', 'name' => 'Novinky.cz', 'slug' => 'novinky', 'url' => 'https://www.novinky.cz'],
                ['id' => 'idnes', 'name' => 'iDnes.cz', 'slug' => 'idnes', 'url' => 'https://www.idnes.cz'],
                ['id' => 'aktualne', 'name' => 'Aktuálně.cz', 'slug' => 'aktualne', 'url' => 'https://www.aktualne.cz']
            ]
        ]
    ],
    '/news/search' => [
        'get' => [
            'data' => [
                [
                    'title' => 'Politická situace v Česku',
                    'description' => 'Aktuální politické dění.',
                    'link' => 'https://example.com/politika1',
                    'published' => '2026-01-05T08:00:00Z',
                    'source' => 'novinky'
                ]
            ],
            'count' => 1,
            'query' => 'politika'
        ]
    ],
    '/news/timeline' => [
        'get' => [
            'data' => [
                [
                    'time' => '09:00',
                    'title' => 'Ranní zprávy',
                    'source' => 'novinky'
                ],
                [
                    'time' => '10:00',
                    'title' => 'Hlavní zprávy',
                    'source' => 'idnes'
                ]
            ]
        ]
    ],

    // Numbers
    '/numbers/{number}' => [
        'get' => [
            'data' => [
                'number' => 42,
                'text' => '42 is the answer to the Ultimate Question of Life, the Universe, and Everything.',
                'type' => 'trivia',
                'found' => true
            ]
        ]
    ],
    '/numbers/today' => [
        'get' => [
            'data' => [
                'date' => 'January 5',
                'text' => 'January 5th is the day in 1933 that construction begins on the Golden Gate Bridge in San Francisco Bay.',
                'type' => 'date',
                'year' => 1933
            ]
        ]
    ],

    // Pocasi (Weather)
    '/pocasi' => [
        'get' => [
            'data' => [
                'mesto' => 'Praha',
                'datum' => '2026-01-05',
                'teplota' => [
                    'min' => -2,
                    'max' => 5,
                    'aktualni' => 3
                ],
                'pocasi' => 'Oblačno',
                'vitr' => '15 km/h',
                'vlhkost' => '75%',
                'tlak' => '1015 hPa'
            ]
        ]
    ],
    '/pocasi/{den}' => [
        'get' => [
            'data' => [
                'mesto' => 'Praha',
                'datum' => '2026-01-06',
                'den' => 'zítra',
                'teplota' => [
                    'min' => -3,
                    'max' => 4
                ],
                'pocasi' => 'Sněžení',
                'srazky' => '80%'
            ]
        ]
    ],

    // QR Code
    '/qr/generate' => [
        'get' => [
            'data' => [
                'text' => 'Hello World',
                'qr_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=Hello%20World',
                'size' => '200x200'
            ]
        ]
    ],
    '/qr/wifi' => [
        'get' => [
            'data' => [
                'ssid' => 'MyWiFi',
                'encryption' => 'WPA',
                'qr_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=WIFI:T:WPA;S:MyWiFi;P:***;',
                'wifi_string' => 'WIFI:T:WPA;S:MyWiFi;P:***;;'
            ]
        ]
    ],
    '/qr/vcard' => [
        'get' => [
            'data' => [
                'name' => 'Jan Novák',
                'phone' => '+420123456789',
                'email' => 'jan@example.com',
                'qr_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=BEGIN:VCARD...'
            ]
        ]
    ],

    // Quotes
    '/quotes' => [
        'get' => [
            'data' => [
                'quote' => 'The only way to do great work is to love what you do.',
                'author' => 'Steve Jobs'
            ]
        ]
    ],
    '/quotes/multiple' => [
        'get' => [
            'data' => [
                [
                    'quote' => 'The only way to do great work is to love what you do.',
                    'author' => 'Steve Jobs'
                ],
                [
                    'quote' => 'Innovation distinguishes between a leader and a follower.',
                    'author' => 'Steve Jobs'
                ],
                [
                    'quote' => 'Stay hungry, stay foolish.',
                    'author' => 'Steve Jobs'
                ]
            ],
            'count' => 3
        ]
    ],

    // RUIAN
    '/ruian/obce' => [
        'get' => [
            'data' => [
                [
                    'kod' => 554782,
                    'nazev' => 'Praha',
                    'okres' => 'Hlavní město Praha',
                    'kraj' => 'Hlavní město Praha'
                ]
            ],
            'count' => 1
        ]
    ],
    '/ruian/ulice' => [
        'get' => [
            'data' => [
                [
                    'kod' => 123456,
                    'nazev' => 'Václavské náměstí',
                    'obec' => 'Praha'
                ]
            ],
            'count' => 1
        ]
    ],
    '/ruian/adresy' => [
        'get' => [
            'data' => [
                [
                    'adresa' => 'Václavské náměstí 1, 110 00 Praha 1',
                    'kod_adresniho_mista' => 21729348,
                    'psc' => '11000'
                ]
            ],
            'count' => 1
        ]
    ],
    '/ruian/validate' => [
        'get' => [
            'data' => [
                'adresa' => 'Praha 1',
                'valid' => true,
                'suggestions' => []
            ]
        ]
    ],

    // Svatky (Name Days)
    '/svatky' => [
        'get' => [
            'data' => [
                'datum' => '2026-01-05',
                'jmeno' => 'Dalimil',
                'den_v_tydnu' => 'pondělí'
            ]
        ]
    ],
    '/svatky/{den}' => [
        'get' => [
            'data' => [
                'datum' => '2026-01-06',
                'jmeno' => 'Kašpar, Melichar, Baltazar',
                'den_v_tydnu' => 'úterý',
                'svatek' => 'Tři králové'
            ]
        ]
    ],

    // Trivia
    '/trivia' => [
        'get' => [
            'data' => [
                'category' => 'Science',
                'difficulty' => 'medium',
                'question' => 'What is the chemical symbol for gold?',
                'correct_answer' => 'Au',
                'incorrect_answers' => ['Ag', 'Fe', 'Cu']
            ]
        ]
    ],
    '/trivia/categories' => [
        'get' => [
            'data' => [
                ['id' => 9, 'name' => 'General Knowledge'],
                ['id' => 17, 'name' => 'Science & Nature'],
                ['id' => 18, 'name' => 'Science: Computers'],
                ['id' => 21, 'name' => 'Sports'],
                ['id' => 23, 'name' => 'History']
            ]
        ]
    ],

    // TV Program
    '/tv' => [
        'get' => [
            'data' => [
                'ct1' => ['name' => 'ČT1', 'current' => 'Události', 'next' => 'Počasí'],
                'nova' => ['name' => 'Nova', 'current' => 'Televizní noviny', 'next' => 'Sportovní noviny'],
                'prima' => ['name' => 'Prima', 'current' => 'Zprávy FTV Prima', 'next' => 'Krimi zprávy']
            ]
        ]
    ],
    '/tv/vse' => [
        'get' => [
            'data' => [
                [
                    'stanice' => 'ČT1',
                    'program' => [
                        ['cas' => '19:00', 'nazev' => 'Události'],
                        ['cas' => '19:50', 'nazev' => 'Počasí'],
                        ['cas' => '20:00', 'nazev' => 'Film']
                    ]
                ]
            ]
        ]
    ],
    '/tv/{stanice}' => [
        'get' => [
            'data' => [
                'stanice' => 'ČT1',
                'program' => [
                    ['cas' => '19:00', 'nazev' => 'Události', 'popis' => 'Hlavní zpravodajská relace'],
                    ['cas' => '19:50', 'nazev' => 'Počasí', 'popis' => 'Předpověď počasí'],
                    ['cas' => '20:00', 'nazev' => 'Film', 'popis' => 'Večerní film']
                ]
            ]
        ]
    ],

    // UUID
    '/uuid' => [
        'get' => [
            'data' => [
                'uuid' => '550e8400-e29b-41d4-a716-446655440000',
                'version' => 4
            ]
        ]
    ],
    '/uuid/nil' => [
        'get' => [
            'data' => [
                'uuid' => '00000000-0000-0000-0000-000000000000',
                'version' => 'nil'
            ]
        ]
    ],
    '/uuid/validate/{uuid}' => [
        'get' => [
            'data' => [
                'uuid' => '550e8400-e29b-41d4-a716-446655440000',
                'valid' => true,
                'version' => 4,
                'variant' => 'RFC 4122'
            ]
        ]
    ],

    // URL Shortener
    '/url/shorten' => [
        'get' => [
            'data' => [
                'original_url' => 'https://example.com/very/long/url/path',
                'short_code' => 'abc123',
                'short_url' => 'https://plbot.example.com/s/abc123',
                'created_at' => '2026-01-05T10:30:00Z'
            ]
        ]
    ],
    '/url/stats' => [
        'get' => [
            'data' => [
                'short_code' => 'abc123',
                'original_url' => 'https://example.com/very/long/url/path',
                'clicks' => 42,
                'created_at' => '2026-01-05T10:30:00Z',
                'last_clicked' => '2026-01-05T12:45:00Z'
            ]
        ]
    ],

    // VAT
    '/vat/countries' => [
        'get' => [
            'data' => [
                ['code' => 'CZ', 'name' => 'Czech Republic', 'rate' => 21],
                ['code' => 'DE', 'name' => 'Germany', 'rate' => 19],
                ['code' => 'AT', 'name' => 'Austria', 'rate' => 20],
                ['code' => 'SK', 'name' => 'Slovakia', 'rate' => 20],
                ['code' => 'PL', 'name' => 'Poland', 'rate' => 23]
            ]
        ]
    ],
    '/vat/validate/{countryCode}/{vatNumber}' => [
        'get' => [
            'data' => [
                'country_code' => 'CZ',
                'vat_number' => '12345678',
                'valid' => true,
                'name' => 'Example Company s.r.o.',
                'address' => 'Example Street 123, 110 00 Praha'
            ]
        ]
    ],
    '/vat/format/{countryCode}/{vatNumber}' => [
        'get' => [
            'data' => [
                'country_code' => 'CZ',
                'vat_number' => '12345678',
                'formatted' => 'CZ12345678'
            ]
        ]
    ],
    '/vat/check/{fullVat}' => [
        'get' => [
            'data' => [
                'full_vat' => 'CZ12345678',
                'country_code' => 'CZ',
                'vat_number' => '12345678',
                'valid' => true
            ]
        ]
    ],

    // Zasilkovna
    '/zasilkovna/track/{packageId}' => [
        'get' => [
            'data' => [
                'package_id' => 'Z1234567890',
                'status' => 'V přepravě',
                'last_update' => '2026-01-05T14:30:00Z',
                'history' => [
                    ['date' => '2026-01-03T10:00:00Z', 'status' => 'Zásilka přijata'],
                    ['date' => '2026-01-04T08:00:00Z', 'status' => 'Odesláno z depa'],
                    ['date' => '2026-01-05T14:30:00Z', 'status' => 'V přepravě']
                ],
                'estimated_delivery' => '2026-01-06'
            ]
        ]
    ],

    // Twitch
    '/twitch/streams' => [
        'get' => [
            'data' => [
                [
                    'id' => '315919746022',
                    'user_id' => '35669163',
                    'user_login' => 'patrikturi',
                    'user_name' => 'Patrikturi',
                    'game_id' => '458912',
                    'game_name' => 'Kingdom Come: Deliverance',
                    'type' => 'live',
                    'title' => '#5 | KINGDOM COME: BEZ JEDINÉ SMRTI',
                    'viewer_count' => 856,
                    'started_at' => '2026-01-05T06:00:54Z',
                    'language' => 'cs',
                    'thumbnail_url' => 'https://static-cdn.jtvnw.net/previews-ttv/live_user_patrikturi-440x248.jpg',
                    'tags' => ['Čeština', 'Hardcore', 'Deathless'],
                    'is_mature' => false
                ]
            ],
            'pagination' => ['cursor' => 'eyJiIjp7IkN1cnNvciI6...'],
            'timestamp' => '2026-01-05 11:30:00'
        ]
    ],
    '/twitch/streams/czech' => [
        'get' => [
            'data' => [
                [
                    'id' => '315919746022',
                    'user_id' => '35669163',
                    'user_login' => 'patrikturi',
                    'user_name' => 'Patrikturi',
                    'game_id' => '458912',
                    'game_name' => 'Kingdom Come: Deliverance',
                    'type' => 'live',
                    'title' => '#5 | KINGDOM COME: BEZ JEDINÉ SMRTI',
                    'viewer_count' => 856,
                    'started_at' => '2026-01-05T06:00:54Z',
                    'language' => 'cs',
                    'thumbnail_url' => 'https://static-cdn.jtvnw.net/previews-ttv/live_user_patrikturi-440x248.jpg',
                    'tags' => ['Čeština', 'Hardcore'],
                    'is_mature' => false
                ]
            ],
            'pagination' => ['cursor' => null],
            'timestamp' => '2026-01-05 11:30:00'
        ]
    ],
    '/twitch/games/top' => [
        'get' => [
            'data' => [
                [
                    'id' => '509658',
                    'name' => 'Just Chatting',
                    'box_art_url' => 'https://static-cdn.jtvnw.net/ttv-boxart/509658-285x380.jpg',
                    'igdb_id' => null
                ],
                [
                    'id' => '32982',
                    'name' => 'Grand Theft Auto V',
                    'box_art_url' => 'https://static-cdn.jtvnw.net/ttv-boxart/32982-285x380.jpg',
                    'igdb_id' => '1020'
                ],
                [
                    'id' => '21779',
                    'name' => 'League of Legends',
                    'box_art_url' => 'https://static-cdn.jtvnw.net/ttv-boxart/21779-285x380.jpg',
                    'igdb_id' => '115'
                ]
            ],
            'pagination' => ['cursor' => 'eyJiIjp7...'],
            'timestamp' => '2026-01-05 11:30:00'
        ]
    ],
    '/twitch/user/{login}' => [
        'get' => [
            'data' => [
                'id' => '35669163',
                'login' => 'patrikturi',
                'display_name' => 'Patrikturi',
                'type' => '',
                'broadcaster_type' => 'partner',
                'description' => 'Jednoruký hráč, který se videohrám věnuje již od osmi let. Od klasických RPG po brutální Soulsovky.',
                'profile_image_url' => 'https://static-cdn.jtvnw.net/jtv_user_pictures/8179dda2-e04a-4aae-b00a-1d4eb5e871e3-profile_image-300x300.png',
                'offline_image_url' => 'https://static-cdn.jtvnw.net/jtv_user_pictures/38a37b6c-d2e8-4762-85c6-bc610bf9b418-channel_offline_image-1920x1080.jpeg',
                'view_count' => 0,
                'created_at' => '2012-08-24T10:31:04Z',
                'follower_count' => 84367
            ],
            'timestamp' => '2026-01-05 11:30:00'
        ]
    ],
    '/twitch/channel/{login}/status' => [
        'get' => [
            'data' => [
                'user' => [
                    'id' => '35669163',
                    'login' => 'patrikturi',
                    'display_name' => 'Patrikturi',
                    'broadcaster_type' => 'partner',
                    'description' => 'Jednoruký hráč...',
                    'profile_image_url' => 'https://static-cdn.jtvnw.net/jtv_user_pictures/...',
                    'follower_count' => 84367
                ],
                'is_live' => true,
                'stream' => [
                    'id' => '315919746022',
                    'game_id' => '458912',
                    'game_name' => 'Kingdom Come: Deliverance',
                    'title' => '#5 | KINGDOM COME: BEZ JEDINÉ SMRTI',
                    'viewer_count' => 856,
                    'started_at' => '2026-01-05T06:00:54Z',
                    'language' => 'cs',
                    'thumbnail_url' => 'https://static-cdn.jtvnw.net/previews-ttv/live_user_patrikturi-440x248.jpg'
                ]
            ],
            'timestamp' => '2026-01-05 11:30:00'
        ]
    ],
    '/twitch/search/channels' => [
        'get' => [
            'data' => [
                [
                    'id' => '35669163',
                    'broadcaster_login' => 'patrikturi',
                    'display_name' => 'Patrikturi',
                    'game_id' => '458912',
                    'game_name' => 'Kingdom Come: Deliverance',
                    'is_live' => true,
                    'title' => '#5 | KINGDOM COME: BEZ JEDINÉ SMRTI',
                    'started_at' => '2026-01-05T06:00:54Z',
                    'broadcaster_language' => 'cs',
                    'thumbnail_url' => 'https://static-cdn.jtvnw.net/jtv_user_pictures/...',
                    'tags' => ['Čeština']
                ]
            ],
            'pagination' => ['cursor' => null],
            'query' => 'patrik',
            'live_only' => false,
            'timestamp' => '2026-01-05 11:30:00'
        ]
    ],
    '/twitch/search/games' => [
        'get' => [
            'data' => [
                [
                    'id' => '458912',
                    'name' => 'Kingdom Come: Deliverance',
                    'box_art_url' => 'https://static-cdn.jtvnw.net/ttv-boxart/458912-285x380.jpg'
                ],
                [
                    'id' => '1285413178',
                    'name' => 'Kingdom Come: Deliverance II',
                    'box_art_url' => 'https://static-cdn.jtvnw.net/ttv-boxart/1285413178-285x380.jpg'
                ]
            ],
            'pagination' => ['cursor' => null],
            'query' => 'Kingdom',
            'timestamp' => '2026-01-05 11:30:00'
        ]
    ],
    '/twitch/game/{name}' => [
        'get' => [
            'data' => [
                'id' => '509658',
                'name' => 'Just Chatting',
                'box_art_url' => 'https://static-cdn.jtvnw.net/ttv-boxart/509658-285x380.jpg',
                'igdb_id' => null
            ],
            'timestamp' => '2026-01-05 11:30:00'
        ]
    ],
    '/twitch/game/{game_id}/streams' => [
        'get' => [
            'data' => [
                [
                    'id' => '316905065973',
                    'user_id' => '545050196',
                    'user_login' => 'kato_junichi0817',
                    'user_name' => '加藤純一',
                    'game_id' => '509658',
                    'game_name' => 'Just Chatting',
                    'type' => 'live',
                    'title' => '雑談',
                    'viewer_count' => 31510,
                    'started_at' => '2026-01-05T06:44:38Z',
                    'language' => 'ja',
                    'thumbnail_url' => 'https://static-cdn.jtvnw.net/previews-ttv/live_user_kato_junichi0817-440x248.jpg',
                    'tags' => ['日本語'],
                    'is_mature' => false
                ]
            ],
            'pagination' => ['cursor' => 'eyJiIjp7...'],
            'timestamp' => '2026-01-05 11:30:00'
        ]
    ],
    '/twitch/game/{game_id}/clips' => [
        'get' => [
            'data' => [
                [
                    'id' => 'SparklyNaiveSandwichOptimizePrime',
                    'url' => 'https://clips.twitch.tv/SparklyNaiveSandwichOptimizePrime',
                    'embed_url' => 'https://clips.twitch.tv/embed?clip=SparklyNaiveSandwichOptimizePrime',
                    'broadcaster_id' => '35669163',
                    'broadcaster_name' => 'Patrikturi',
                    'creator_id' => '12345678',
                    'creator_name' => 'ClipCreator',
                    'game_id' => '509658',
                    'language' => 'cs',
                    'title' => 'Amazing moment!',
                    'view_count' => 15420,
                    'created_at' => '2025-12-15T18:30:00Z',
                    'thumbnail_url' => 'https://clips-media-assets2.twitch.tv/...',
                    'duration' => 29.5
                ]
            ],
            'pagination' => ['cursor' => null],
            'timestamp' => '2026-01-05 11:30:00'
        ]
    ],
    '/twitch/channel/{broadcaster_id}/clips' => [
        'get' => [
            'data' => [
                [
                    'id' => 'SparklyNaiveSandwichOptimizePrime',
                    'url' => 'https://clips.twitch.tv/SparklyNaiveSandwichOptimizePrime',
                    'embed_url' => 'https://clips.twitch.tv/embed?clip=SparklyNaiveSandwichOptimizePrime',
                    'broadcaster_id' => '35669163',
                    'broadcaster_name' => 'Patrikturi',
                    'creator_id' => '12345678',
                    'creator_name' => 'ClipCreator',
                    'game_id' => '458912',
                    'language' => 'cs',
                    'title' => 'Boys jsem ready.',
                    'view_count' => 31820,
                    'created_at' => '2025-12-20T20:15:00Z',
                    'thumbnail_url' => 'https://clips-media-assets2.twitch.tv/...',
                    'duration' => 29.5
                ]
            ],
            'pagination' => ['cursor' => 'eyJiIjp7...'],
            'timestamp' => '2026-01-05 11:30:00'
        ]
    ],
    '/twitch/channel/{broadcaster_id}/videos' => [
        'get' => [
            'data' => [
                [
                    'id' => '2661179172',
                    'stream_id' => '315919746022',
                    'user_id' => '35669163',
                    'user_login' => 'patrikturi',
                    'user_name' => 'Patrikturi',
                    'title' => '#4 | KINGDOM COME: BEZ JEDINÉ SMRTI',
                    'description' => '',
                    'created_at' => '2026-01-04T06:00:00Z',
                    'published_at' => '2026-01-04T14:30:00Z',
                    'url' => 'https://www.twitch.tv/videos/2661179172',
                    'thumbnail_url' => 'https://static-cdn.jtvnw.net/cf_vods/...',
                    'viewable' => 'public',
                    'view_count' => 94315,
                    'language' => 'cs',
                    'type' => 'archive',
                    'duration' => '8h3m5s'
                ]
            ],
            'pagination' => ['cursor' => 'eyJiIjp7...'],
            'timestamp' => '2026-01-05 11:30:00'
        ]
    ],
    '/twitch/channel/{broadcaster_id}/emotes' => [
        'get' => [
            'data' => [
                [
                    'id' => '104582',
                    'name' => 'patrikCry',
                    'images' => [
                        'url_1x' => 'https://static-cdn.jtvnw.net/emoticons/v2/104582/static/light/1.0',
                        'url_2x' => 'https://static-cdn.jtvnw.net/emoticons/v2/104582/static/light/2.0',
                        'url_4x' => 'https://static-cdn.jtvnw.net/emoticons/v2/104582/static/light/3.0'
                    ],
                    'tier' => '1000',
                    'emote_type' => 'subscriptions',
                    'emote_set_id' => '123456',
                    'format' => ['static'],
                    'scale' => ['1.0', '2.0', '3.0'],
                    'theme_mode' => ['light', 'dark']
                ]
            ],
            'template' => 'https://static-cdn.jtvnw.net/emoticons/v2/{{id}}/{{format}}/{{theme_mode}}/{{scale}}',
            'timestamp' => '2026-01-05 11:30:00'
        ]
    ],
    '/twitch/channel/{broadcaster_id}/badges' => [
        'get' => [
            'data' => [
                [
                    'set_id' => 'subscriber',
                    'versions' => [
                        [
                            'id' => '0',
                            'image_url_1x' => 'https://static-cdn.jtvnw.net/badges/v1/...',
                            'image_url_2x' => 'https://static-cdn.jtvnw.net/badges/v1/...',
                            'image_url_4x' => 'https://static-cdn.jtvnw.net/badges/v1/...',
                            'title' => 'Subscriber',
                            'description' => 'Subscriber'
                        ],
                        [
                            'id' => '3',
                            'image_url_1x' => 'https://static-cdn.jtvnw.net/badges/v1/...',
                            'title' => '3-Month Subscriber'
                        ],
                        [
                            'id' => '12',
                            'image_url_1x' => 'https://static-cdn.jtvnw.net/badges/v1/...',
                            'title' => '1-Year Subscriber'
                        ]
                    ]
                ]
            ],
            'timestamp' => '2026-01-05 11:30:00'
        ]
    ],
    '/twitch/channel/{broadcaster_id}/schedule' => [
        'get' => [
            'data' => [
                'segments' => [
                    [
                        'id' => 'eyJzZWdtZW50SUQiOiI...',
                        'start_time' => '2026-01-06T17:00:00Z',
                        'end_time' => '2026-01-06T23:00:00Z',
                        'title' => 'Kingdom Come: Deliverance',
                        'canceled_until' => null,
                        'category' => [
                            'id' => '458912',
                            'name' => 'Kingdom Come: Deliverance'
                        ]
                    ]
                ],
                'broadcaster_id' => '35669163',
                'broadcaster_name' => 'Patrikturi',
                'broadcaster_login' => 'patrikturi',
                'vacation' => null
            ],
            'timestamp' => '2026-01-05 11:30:00'
        ]
    ],
    '/twitch/emotes/global' => [
        'get' => [
            'data' => [
                [
                    'id' => '354',
                    'name' => '4Head',
                    'images' => [
                        'url_1x' => 'https://static-cdn.jtvnw.net/emoticons/v2/354/static/light/1.0',
                        'url_2x' => 'https://static-cdn.jtvnw.net/emoticons/v2/354/static/light/2.0',
                        'url_4x' => 'https://static-cdn.jtvnw.net/emoticons/v2/354/static/light/3.0'
                    ],
                    'format' => ['static'],
                    'scale' => ['1.0', '2.0', '3.0'],
                    'theme_mode' => ['light', 'dark']
                ],
                [
                    'id' => '425618',
                    'name' => 'LUL',
                    'images' => [
                        'url_1x' => 'https://static-cdn.jtvnw.net/emoticons/v2/425618/static/light/1.0',
                        'url_2x' => 'https://static-cdn.jtvnw.net/emoticons/v2/425618/static/light/2.0',
                        'url_4x' => 'https://static-cdn.jtvnw.net/emoticons/v2/425618/static/light/3.0'
                    ],
                    'format' => ['static', 'animated'],
                    'scale' => ['1.0', '2.0', '3.0'],
                    'theme_mode' => ['light', 'dark']
                ]
            ],
            'template' => 'https://static-cdn.jtvnw.net/emoticons/v2/{{id}}/{{format}}/{{theme_mode}}/{{scale}}',
            'timestamp' => '2026-01-05 11:30:00'
        ]
    ],
    '/twitch/cheermotes' => [
        'get' => [
            'data' => [
                [
                    'prefix' => 'Cheer',
                    'tiers' => [
                        [
                            'min_bits' => 1,
                            'id' => '1',
                            'color' => '#979797',
                            'images' => [
                                'dark' => [
                                    'animated' => ['1' => 'https://...', '2' => 'https://...'],
                                    'static' => ['1' => 'https://...', '2' => 'https://...']
                                ],
                                'light' => [
                                    'animated' => ['1' => 'https://...'],
                                    'static' => ['1' => 'https://...']
                                ]
                            ]
                        ],
                        [
                            'min_bits' => 100,
                            'id' => '100',
                            'color' => '#9c3ee8'
                        ],
                        [
                            'min_bits' => 1000,
                            'id' => '1000',
                            'color' => '#1db2a5'
                        ]
                    ]
                ]
            ],
            'timestamp' => '2026-01-05 11:30:00'
        ]
    ],

    // Image Processing
    '/image/info' => [
        'get' => [
            'data' => [
                'width' => 1920,
                'height' => 1080,
                'format' => 'jpeg',
                'size' => 245678,
                'mime_type' => 'image/jpeg'
            ]
        ]
    ],
    '/image/resize' => [
        'get' => [
            'data' => [
                'original' => ['width' => 1920, 'height' => 1080],
                'resized' => ['width' => 800, 'height' => 450],
                'image_url' => 'https://example.com/resized/abc123.jpg'
            ]
        ]
    ],
    '/image/crop' => [
        'get' => [
            'data' => [
                'original' => ['width' => 1920, 'height' => 1080],
                'cropped' => ['width' => 500, 'height' => 500, 'x' => 100, 'y' => 100],
                'image_url' => 'https://example.com/cropped/abc123.jpg'
            ]
        ]
    ],
    '/image/rotate' => [
        'get' => [
            'data' => [
                'angle' => 90,
                'image_url' => 'https://example.com/rotated/abc123.jpg'
            ]
        ]
    ],
    '/image/flip' => [
        'get' => [
            'data' => [
                'direction' => 'horizontal',
                'image_url' => 'https://example.com/flipped/abc123.jpg'
            ]
        ]
    ],
    '/image/convert' => [
        'get' => [
            'data' => [
                'original_format' => 'png',
                'target_format' => 'jpeg',
                'quality' => 85,
                'image_url' => 'https://example.com/converted/abc123.jpg'
            ]
        ]
    ],
    '/image/watermark' => [
        'get' => [
            'data' => [
                'watermark_text' => 'Copyright 2026',
                'position' => 'bottom-right',
                'image_url' => 'https://example.com/watermarked/abc123.jpg'
            ]
        ]
    ],

    // OpenAPI
    '/openapi/spec' => [
        'get' => [
            'openapi' => '3.0.2',
            'info' => [
                'title' => 'pLBOT API',
                'version' => '2.0',
                'description' => 'Modernizované API pro IRC bota pLBOT'
            ],
            'paths' => '...'
        ]
    ],
];
