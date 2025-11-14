# Analýza možností rozšíření pLBOT API

**Datum:** 2025-11-14
**Autor:** pLBOT API Expansion Analysis
**Verze:** 1.0

---

## 📊 Executive Summary

Tento dokument analyzuje možnosti rozšíření pLBOT API projektu o další zdroje dat a funkce. Analýza se zaměřuje na 4 hlavní kategorie:

1. **České Free APIs** - priorita pro český IRC bot
2. **International Free APIs** - mezinárodní datové zdroje
3. **Utility & Tools APIs** - užitečné nástroje
4. **Technical Improvements** - technická vylepšení

**Současný stav:** 22+ API endpointů (8 českých, 8 zábavných, 5 datových, 1 utility)

**Doporučení:** Implementace 20+ nových endpointů v průběhu dalších verzí s prioritou na české APIs a technická vylepšení.

---

## 🇨🇿 A) České Free APIs (TOP 5)

### 1. ARES API - Registr ekonomických subjektů ⭐⭐⭐⭐⭐

**Popis:**
Administrativní registr ekonomických subjektů (ARES) poskytuje informace o firmách, IČO, sídlech, právních formách a dalších údajích.

**API Endpoint:**
- URL: `https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/`
- Typ: JSON REST API
- Dokumentace: https://ares.gov.cz/

**Užitečnost pro IRC bot:**
- Vyhledávání firem podle IČO: `!ico 12345678`
- Vyhledávání podle názvu: `!firma "Google Czech"`
- Kontrola DIČ: `!dic CZ12345678`
- Zjištění adresy firmy, právní formy, stavu (aktivní/zaniklá)
- Užitečné pro business uživatele v CZ

**Složitost implementace:** Low
- Jednoduchý REST API call
- JSON response
- Není potřeba API klíč
- Podobné jako RUIAN implementace

**Závislosti:**
- Žádné (public API bez registrace)

**Příklad použití v IRC:**
```
<user> !ico 27082440
<bot> 🏢 Google Czech Republic s.r.o.
<bot> IČO: 27082440 | DIČ: CZ27082440
<bot> Sídlo: Karla Engliše 519/11, Praha 5
<bot> Stav: Aktivní | Právní forma: Společnost s ručením omezeným
```

**Doporučené endpointy:**
- `GET /ares/ico/{ico}` - Info o firmě podle IČO
- `GET /ares/search?query=Google` - Vyhledání firem
- `GET /ares/dic/{dic}` - Info podle DIČ

---

### 2. ČHMÚ API - Počasí a varování ⭐⭐⭐⭐⭐

**Popis:**
Český hydrometeorologický ústav poskytuje oficiální meteorologická data, předpovědi a varování.

**API Endpoint:**
- URL: `https://www.chmi.cz/`
- Typ: XML/RSS (některá data i JSON)
- Alternativa: OpenWeatherMap API (mezinárodní, free tier)

**Užitečnost pro IRC bot:**
- Přesnější předpověď než Centrum.cz
- Meteorologická varování (bouřky, povodně)
- Aktuální teplota na stanicích
- Radar srážek
- UV index, tlak, vlhkost

**Složitost implementace:** Medium
- XML parsing (podobné jako TV program)
- Některé endpointy vyžadují scraping
- Alternativně OpenWeatherMap (jednodušší, JSON)

**Závislosti:**
- ČHMÚ: bez API klíče (scraping)
- OpenWeatherMap: free API klíč (60 calls/min zdarma)

**Příklad použití v IRC:**
```
<user> !pocasi praha
<bot> 🌤️ Praha: 15°C (pocitově 13°C)
<bot> Předpověď: Polojasno, 20% srážky
<bot> Tlak: 1013 hPa | Vlhkost: 65% | Vítr: 12 km/h SV
<bot> ⚠️ Varování: Silný vítr (stupeň 2) platné do 18:00
```

**Doporučené endpointy:**
- `GET /weather/current?city=praha` - Aktuální počasí
- `GET /weather/forecast?city=praha&days=3` - Předpověď
- `GET /weather/warnings` - Meteorologická varování
- `GET /weather/radar` - Radar srážek (obrázek)

---

### 3. DPP/IDOS/Golemio API - MHD a vlaky ⭐⭐⭐⭐⭐

**Popis:**
API pro městskou hromadnou dopravu, vlakové spoje a dopravní informace.

**API Endpointy:**
- Golemio (Praha): `https://api.golemio.cz/v2/` (API klíč zdarma)
- IDOS API: Neoficiální scraping
- PID Lítačka: `https://api.pidlitacka.cz/`

**Užitečnost pro IRC bot:**
- Odjezdy MHD: `!mhd "Hlavní nádraží"`
- Vlakové spoje: `!vlak praha brno`
- Zpoždění vlaků: `!vlak R1234`
- Dopravní situace v Praze
- Info o zastávkách

**Složitost implementace:** Medium-High
- Golemio: JSON REST API (snadné)
- IDOS: scraping (složitější)
- Potřeba API klíč (Golemio zdarma)

**Závislості:**
- Golemio: API klíč (registrace zdarma)
- IDOS: bez API (scraping)

**Příklad použití v IRC:**
```
<user> !mhd "Karlovo náměstí"
<bot> 🚇 Metro linka B: 2 min, 5 min, 8 min
<bot> 🚊 Tram 18: 3 min → Nádraží Holešovice
<bot> 🚊 Tram 24: 7 min → Palmovka

<user> !vlak EC 170
<bot> 🚆 EC 170 "Hungaria" (Budapest → Praha)
<bot> Aktuální poloha: Břeclav
<bot> Zpoždění: +12 minut
<bot> Příjezd Praha hl.n.: 14:42 (místo 14:30)
```

**Doporučené endpointy:**
- `GET /transport/departures?stop=Karlovo+namesti` - Odjezdy
- `GET /transport/train/{trainNumber}` - Info o vlaku
- `GET /transport/route?from=praha&to=brno` - Plánování cesty
- `GET /transport/disruptions` - Mimořádnosti

---

### 4. Zprávy RSS (iRozhlas, ČT24, Seznam Zprávy) ⭐⭐⭐⭐⭐

**Popis:**
RSS feedy českých zpravodajských serverů pro aktuální zprávy.

**API Endpointy:**
- iRozhlas: `https://www.irozhlas.cz/rss/irozhlas/section/zpravy-domov`
- ČT24: `https://ct24.ceskatelevize.cz/rss/hlavni-zpravy`
- Seznam Zprávy: `https://www.seznamzpravy.cz/rss`
- Novinky.cz: `https://www.novinky.cz/rss`

**Užitečnost pro IRC bot:**
- Top zprávy: `!zpravy`
- Zprávy z kategorie: `!zpravy sport`
- Poslední headline: `!news`
- Upozornění na breaking news

**Složitost implementace:** Low
- RSS/XML parsing (již máme u TV programu)
- Žádná autentizace
- Cache na 5-15 minut

**Závislosti:**
- Žádné (public RSS)

**Příklad použití v IRC:**
```
<user> !zpravy
<bot> 📰 TOP zprávy (iRozhlas):
<bot> [1] Vláda schválila zvýšení platů učitelů o 10%
<bot> [2] Nehoda na D1: Kolona 8 km
<bot> [3] ČNB zvýšila úrokové sazby na 7%
<bot> Aktualizováno: 14:35

<user> !zpravy sport
<bot> ⚽ Sport (ČT Sport):
<bot> Sparta porazila Slavii 2:1 v derby
```

**Doporučené endpointy:**
- `GET /news/latest?source=irozhlas&limit=5` - Nejnovější zprávy
- `GET /news/category/{category}` - Zprávy z kategorie
- `GET /news/search?q=keyword` - Vyhledání zpráv

---

### 5. Registr živností (RŽP) ⭐⭐⭐⭐

**Popis:**
Registr živnostenského podnikání - informace o živnostenských oprávněních.

**API Endpoint:**
- URL: `https://www.rzp.cz/` (scraping)
- Alternativa: data z ARES obsahují i živnosti

**Užitečnost pro IRC bot:**
- Kontrola živností firmy: `!zivnosti 12345678`
- Zjištění oprávnění
- Datum vzniku/zániku živností

**Složitost implementace:** Medium
- Data dostupná přes ARES API
- Případně scraping RŽP

**Závislosti:**
- Žádné (public data)

**Příklad použití v IRC:**
```
<user> !zivnosti 27082440
<bot> 🏪 Živnosti (Google Czech Republic):
<bot> ✅ Výroba, obchod a služby neuvedené v přílohách 1-3
<bot> Vznik: 15.8.2006 | Stav: Aktivní
```

**Doporučené endpointy:**
- `GET /rzp/ico/{ico}` - Živnosti podle IČO
- Integrovat do ARES endpointu

---

## 🌍 B) International Free APIs (TOP 5)

### 1. OpenWeatherMap - Weather API ⭐⭐⭐⭐⭐

**Popis:**
Komplexní weather API s aktuálním počasím, předpovědí a historickými daty.

**API Endpoint:**
- URL: `https://api.openweathermap.org/data/2.5/`
- Typ: JSON REST API
- Free tier: 60 calls/min, 1,000,000 calls/měsíc

**Užitečnost pro IRC bot:**
- Počasí pro jakékoliv město na světě
- 5denní předpověď
- Aktuální podmínky
- Lepší než současné Centrum.cz API

**Složitost implementace:** Low
- Jednoduchý JSON REST API
- Dobrá dokumentace
- Cachování na 10-30 minut

**Závislosti:**
- API klíč (registrace zdarma)

**Příklad použití v IRC:**
```
<user> !weather london
<bot> 🌧️ London, UK: 12°C (feels like 10°C)
<bot> Conditions: Light rain
<bot> Humidity: 78% | Wind: 15 km/h W
<bot> Forecast: Rain continues, high 14°C

<user> !weather new york
<bot> ☀️ New York, US: 22°C (feels like 21°C)
<bot> Conditions: Clear sky
```

**Doporučené endpointy:**
- `GET /weather/current?city={city}` - Aktuální počasí
- `GET /weather/forecast?city={city}` - 5denní předpověď
- `GET /weather/alerts?city={city}` - Varování

---

### 2. News API - Zpravodajství ⭐⭐⭐⭐⭐

**Popis:**
Agregátor zpráv z tisíců zdrojů po celém světě.

**API Endpoint:**
- URL: `https://newsapi.org/v2/`
- Typ: JSON REST API
- Free tier: 100 requests/den

**Užitečnost pro IRC bot:**
- Mezinárodní zprávy: `!worldnews`
- Zprávy podle tématu: `!news technology`
- Top headlines ze země: `!news us`
- Vyhledávání zpráv

**Složitost implementace:** Low
- JSON REST API
- Jednoduchá integrace
- Cache na 15-30 minut

**Závislosti:**
- API klíč (free tier 100 req/den)

**Příklad použití v IRC:**
```
<user> !worldnews
<bot> 🌍 World News (Top Headlines):
<bot> [BBC] UK Prime Minister announces new climate plan
<bot> [CNN] US markets hit record high
<bot> [Reuters] China launches new space station module

<user> !news technology
<bot> 💻 Tech News:
<bot> [TechCrunch] Apple announces new M4 chip
<bot> [Verge] Microsoft releases Windows 12
```

**Doporučené endpointy:**
- `GET /news/headlines?country=us` - Top headlines
- `GET /news/search?q=bitcoin` - Vyhledání zpráv
- `GET /news/sources` - Seznam zdrojů

---

### 3. TMDB (The Movie Database) API ⭐⭐⭐⭐⭐

**Popis:**
Databáze filmů, seriálů, herců a TV show s hodnoceními a informacemi.

**API Endpoint:**
- URL: `https://api.themoviedb.org/3/`
- Typ: JSON REST API
- Free tier: 40 requests/10 sec

**Užitečnost pro IRC bot:**
- Vyhledání filmu: `!movie Inception`
- Info o seriálu: `!tv breaking bad`
- Hodnocení, herecké obsazení
- Doporučení podobných filmů
- Aktuálně populární filmy

**Složitost implementace:** Low-Medium
- JSON REST API
- Dobrá dokumentace
- Cache na 1 den (data se nemění často)

**Závislosti:**
- API klíč (registrace zdarma)

**Příklad použití v IRC:**
```
<user> !movie Inception
<bot> 🎬 Inception (2010)
<bot> Rating: 8.8/10 (TMDB) | IMDb: 8.8/10
<bot> Director: Christopher Nolan
<bot> Stars: Leonardo DiCaprio, Tom Hardy, Ellen Page
<bot> A thief who steals corporate secrets through dream-sharing technology...

<user> !tvshow breaking bad
<bot> 📺 Breaking Bad (2008-2013)
<bot> Rating: 9.5/10 | 5 seasons, 62 episodes
<bot> Creator: Vince Gilligan
```

**Doporučené endpointy:**
- `GET /movies/search?query={title}` - Vyhledání filmu
- `GET /movies/{id}` - Detail filmu
- `GET /tv/search?query={title}` - Vyhledání seriálu
- `GET /movies/popular` - Populární filmy
- `GET /movies/{id}/recommendations` - Podobné filmy

---

### 4. Reddit API (read-only) ⭐⭐⭐⭐

**Popis:**
Reddit API pro čtení postů, komentářů a subredditů.

**API Endpoint:**
- URL: `https://www.reddit.com/r/{subreddit}.json`
- Typ: JSON (veřejné, read-only)
- No auth: `https://www.reddit.com/.json`

**Užitečnost pro IRC bot:**
- Top posty z subredditu: `!reddit programming`
- Hot topics: `!reddit worldnews hot`
- Náhodný post: `!reddit random`
- Frontpage

**Složitost implementace:** Low
- JSON API bez autentizace (read-only)
- Rate limit: 60 req/min bez auth
- Cache na 5-15 minut

**Závislosti:**
- Žádné (public read-only)
- Optional: Reddit API klíč pro vyšší limity

**Příklad použití v IRC:**
```
<user> !reddit programming top
<bot> 🔥 r/programming (Top today):
<bot> [1] ⬆️2.3k | Why I switched from VSCode to Neovim
<bot> [2] ⬆️1.8k | Understanding Rust's ownership model
<bot> [3] ⬆️1.5k | GitHub Copilot now supports GPT-4

<user> !reddit todayilearned
<bot> 🧠 TIL: Honey never spoils. Archaeologists have found 3000-year-old honey in Egyptian tombs that was still edible.
```

**Doporučené endpointy:**
- `GET /reddit/{subreddit}/hot` - Hot posty
- `GET /reddit/{subreddit}/top` - Top posty
- `GET /reddit/{subreddit}/new` - Nové posty
- `GET /reddit/random` - Náhodný post

---

### 5. Spotify Web API ⭐⭐⭐⭐

**Popis:**
API pro vyhledávání hudby, alb, umělců a playlistů.

**API Endpoint:**
- URL: `https://api.spotify.com/v1/`
- Typ: JSON REST API
- Vyžaduje OAuth, ale read-only operace jsou jednoduché

**Užitečnost pro IRC bot:**
- Vyhledání skladby: `!spotify bohemian rhapsody`
- Info o umělci: `!artist queen`
- Top skladby umělce
- Náhled skladby (30s)
- Aktuálně populární skladby

**Složitost implementace:** Medium
- OAuth flow (Client Credentials pro read-only)
- JSON REST API
- Cache na 1 hodinu

**Závislosti:**
- Client ID & Secret (registrace zdarma)

**Příklad použití v IRC:**
```
<user> !spotify bohemian rhapsody
<bot> 🎵 Bohemian Rhapsody - Queen
<bot> Album: A Night at the Opera (1975)
<bot> Duration: 5:55
<bot> Popularity: 91/100
<bot> Listen: https://open.spotify.com/track/...

<user> !artist drake
<bot> 🎤 Drake
<bot> Genres: Hip hop, R&B, Rap
<bot> Followers: 78.2M
<bot> Top tracks: One Dance, God's Plan, Hotline Bling
```

**Doporučené endpointy:**
- `GET /music/search?q={query}&type=track` - Vyhledání skladby
- `GET /music/artist/{id}` - Info o umělci
- `GET /music/artist/{id}/top-tracks` - Top skladby umělce
- `GET /music/playlists/featured` - Featured playlisty

---

## 🔧 C) Utility & Tools APIs (TOP 5)

### 1. QR Code Generator ⭐⭐⭐⭐⭐

**Popis:**
Generování QR kódů z textu, URL nebo dat.

**API Endpoint:**
- URL: `https://api.qrserver.com/v1/create-qr-code/`
- Alternativa: vlastní implementace (PHP knihovna)
- Typ: Vrací obrázek PNG

**Užitečnost pro IRC bot:**
- Vytvořit QR kód z URL: `!qr https://example.com`
- QR kód z textu: `!qr "Hello World"`
- Konfigurovatelná velikost
- Sdílení linků v QR formě

**Složitost implementace:** Low
- Jednoduchý GET request
- Nebo PHP knihovna (endroid/qr-code)
- Vrací obrázek

**Závislosti:**
- Žádné (public API)
- Nebo Composer package

**Příklad použití v IRC:**
```
<user> !qr https://github.com/pLBOT
<bot> 📱 QR Code generated: https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=https://github.com/pLBOT
```

**Doporučené endpointy:**
- `GET /qr/generate?data={text}` - Vygenerovat QR kód
- `GET /qr/generate?data={text}&size=500` - Vlastní velikost

---

### 2. URL Shortener ⭐⭐⭐⭐⭐

**Popis:**
Zkracování dlouhých URL na krátké odkazy.

**API Endpoint:**
- URL: `https://is.gd/api.php` (is.gd/v.gd - bez API klíče)
- Alternativa: `https://tinyurl.com/api-create.php`
- Typ: Simple GET request

**Užitečnost pro IRC bot:**
- Zkrátit URL: `!short https://very-long-url.com/page?param=value`
- Užitečné pro sdílení v IRC
- Statistiky kliknutí (některé služby)

**Složitost implementace:** Low
- Jednoduchý GET/POST request
- is.gd a TinyURL bez registrace

**Závislosti:**
- is.gd/TinyURL: žádné
- bit.ly: API klíč

**Příklad použití v IRC:**
```
<user> !short https://github.com/Chatujme/pLBOT-API/blob/main/docs/API_DOCUMENTATION.md
<bot> 🔗 Short URL: https://is.gd/pLBOTdocs

<user> !unshort https://is.gd/pLBOTdocs
<bot> 🔗 Original URL: https://github.com/Chatujme/pLBOT-API/blob/main/docs/API_DOCUMENTATION.md
```

**Doporučené endpointy:**
- `GET /url/shorten?url={longurl}` - Zkrátit URL
- `GET /url/expand?url={shorturl}` - Rozbalit krátké URL
- `GET /url/stats?url={shorturl}` - Statistiky (pokud dostupné)

---

### 3. Email & Phone Validation ⭐⭐⭐⭐

**Popis:**
Validace emailových adres a telefonních čísel.

**API Endpoint:**
- Email: vlastní implementace (PHP filter_var)
- Email advanced: `https://api.eva.pingutil.com/email` (free tier)
- Phone: `https://phonevalidation.abstractapi.com/v1/` (free tier 250/měsíc)

**Užitečnost pro IRC bot:**
- Kontrola emailu: `!validateemail test@example.com`
- Kontrola telefonu: `!validatephone +420123456789`
- Zjištění země z telefonního čísla
- Typ emailu (personal, business, disposable)

**Složitost implementace:** Low-Medium
- Email: jednoduchá validace v PHP
- Phone: API call s free tier

**Závislosti:**
- Email: žádné (vlastní implementace)
- Phone: API klíč (free tier omezený)

**Příklad použití v IRC:**
```
<user> !validateemail test@gmail.com
<bot> ✅ Email valid: test@gmail.com
<bot> Type: Personal | Provider: Gmail
<bot> Disposable: No | MX records: Valid

<user> !validatephone +420603123456
<bot> ✅ Phone valid: +420 603 123 456
<bot> Country: Czech Republic (CZ)
<bot> Type: Mobile | Carrier: T-Mobile CZ
```

**Doporučené endpointy:**
- `GET /validate/email?email={email}` - Validace emailu
- `GET /validate/phone?number={phone}` - Validace telefonu

---

### 4. IP Geolocation & WHOIS ⭐⭐⭐⭐

**Popis:**
Geolokace IP adres, WHOIS lookup, DNS informace.

**API Endpoint:**
- IP Geo: `https://ipapi.co/` (30k requests/měsíc zdarma)
- WHOIS: `https://www.whoisxmlapi.com/` (500 req/měsíc zdarma)
- DNS: vlastní implementace (PHP dns_get_record)

**Užitečnost pro IRC bot:**
- Info o IP: `!ip 8.8.8.8`
- WHOIS domény: `!whois google.com`
- DNS lookup: `!dns example.com`
- Geolokace uživatele (z IP)

**Složitost implementace:** Low-Medium
- IP Geo: JSON REST API
- WHOIS: API nebo vlastní implementace
- DNS: PHP funkce

**Závislosti:**
- IP Geo: API klíč pro více requestů
- WHOIS: API klíč (free tier)
- DNS: žádné

**Příklad použití v IRC:**
```
<user> !ip 8.8.8.8
<bot> 🌍 IP: 8.8.8.8
<bot> Location: Mountain View, California, US
<bot> Organization: Google LLC
<bot> ASN: AS15169

<user> !whois google.com
<bot> 📋 Domain: google.com
<bot> Registrar: MarkMonitor Inc.
<bot> Created: 1997-09-15
<bot> Expires: 2028-09-14
<bot> Status: clientTransferProhibited

<user> !dns github.com
<bot> 🔍 DNS Records for github.com:
<bot> A: 140.82.121.4
<bot> MX: alt1.aspmx.l.google.com (priority 1)
<bot> NS: ns-1707.awsdns-21.co.uk
```

**Doporučené endpointy:**
- `GET /ip/lookup?ip={ip}` - IP geolocation
- `GET /whois?domain={domain}` - WHOIS lookup
- `GET /dns?domain={domain}` - DNS records

---

### 5. Hash & Encoding Tools ⭐⭐⭐⭐

**Popis:**
Hash generování (MD5, SHA), base64 encoding/decoding, URL encoding.

**API Endpoint:**
- Vlastní implementace v PHP (hash, base64_encode, urlencode)
- Není potřeba externí API

**Užitečnost pro IRC bot:**
- Hash generování: `!hash sha256 "hello world"`
- Base64 encode: `!base64 encode "text"`
- Base64 decode: `!base64 decode "dGV4dA=="`
- URL encode: `!urlencode "hello world"`
- Užitečné pro vývojáře

**Složitost implementace:** Low
- PHP built-in funkce
- Žádné externí závislosti

**Závislosti:**
- Žádné (PHP funkce)

**Příklad použití v IRC:**
```
<user> !hash md5 "hello"
<bot> 🔐 MD5: 5d41402abc4b2a76b9719d911017c592

<user> !hash sha256 "password123"
<bot> 🔐 SHA256: ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f

<user> !base64 encode "Hello World"
<bot> 📝 Base64: SGVsbG8gV29ybGQ=

<user> !base64 decode "SGVsbG8gV29ybGQ="
<bot> 📝 Decoded: Hello World
```

**Doporučené endpointy:**
- `GET /hash/{algorithm}?text={text}` - Hash generování
- `GET /encode/base64?text={text}` - Base64 encode
- `GET /decode/base64?text={text}` - Base64 decode
- `GET /encode/url?text={text}` - URL encode

---

## ⚙️ D) Technical Improvements (TOP 5)

### 1. WebSocket Support pro Real-time ⭐⭐⭐⭐⭐

**Popis:**
Implementace WebSocket serveru pro real-time komunikaci s IRC botem.

**Technologie:**
- Ratchet (PHP WebSocket library)
- ReactPHP event loop
- nebo Socket.io (pokud Node.js backend)

**Užitečnost:**
- Real-time notifikace do IRC
- Live updates (počasí, crypto ceny)
- Push notifications místo polling
- IRC bot může subskribovat eventy

**Složitost implementace:** High
- Vyžaduje WebSocket server
- Event-driven architektura
- Možnost využít ReactPHP/Ratchet
- Server musí běžet na pozadí (daemon)

**Závislosti:**
- Composer: ratchet/pawl, react/socket
- Server s podporou WebSocket (port otevřený)

**Příklad použití:**
```php
// IRC bot se připojí k WebSocket serveru
ws://api.plbot.cz:8080/events

// Subscribe k eventům
{"type": "subscribe", "channels": ["crypto.bitcoin", "news.breaking"]}

// Receive real-time updates
{"type": "crypto.update", "coin": "bitcoin", "price": 45234.50}
{"type": "news.breaking", "title": "Breaking news..."}
```

**Doporučené endpointy:**
- `WS /ws/connect` - WebSocket connection
- `WS /ws/events/{channel}` - Subscribe ke kanálu
- Channels: `crypto.*`, `news.*`, `weather.*`

**Priorita:** Medium-High (užitečné, ale složité)

---

### 2. GraphQL Endpoint ⭐⭐⭐⭐

**Popis:**
GraphQL API endpoint jako alternativa k REST API.

**Technologie:**
- webonyx/graphql-php
- GraphQL schema definition
- Single endpoint: `/graphql`

**Užitečnost:**
- Flexibilnější než REST
- Client určuje jaká data chce
- Jedno volání místo více REST callů
- Dobrá dokumentace (GraphiQL)

**Složitost implementace:** Medium-High
- Definice GraphQL schématu
- Resolvers pro každý typ
- Integrace s existujícími services
- Learning curve

**Závislosti:**
- Composer: webonyx/graphql-php

**Příklad použití:**
```graphql
# GraphQL query - získání více dat najednou
query {
  svatky {
    dnes
    zitra
  }
  pocasi(mesto: "praha") {
    dnes {
      teplota
      predpoved
    }
  }
  cnb {
    kurzy(meny: ["USD", "EUR"]) {
      kod
      kurz
    }
  }
}
```

**Doporučené endpointy:**
- `POST /graphql` - GraphQL endpoint
- `GET /graphql/playground` - GraphQL Playground UI

**Priorita:** Medium (nice-to-have pro větší flexibilitu)

---

### 3. API Authentication (JWT, API Keys) ⭐⭐⭐⭐⭐

**Popis:**
Implementace autentizace a autorizace API.

**Technologie:**
- JWT (JSON Web Tokens) - firebase/php-jwt
- API Keys v databázi
- Rate limiting per user/key

**Užitečnost:**
- Ochrana API před zneužitím
- Tracking usage per user
- Různé limity pro různé uživatele
- Monetizace API (premium tiers)
- Private endpointy

**Složitost implementace:** Medium
- JWT generování a validace
- Middleware pro ověření tokenu
- Databáze pro API keys
- User management

**Závislosti:**
- Composer: firebase/php-jwt
- Databáze (MySQL/PostgreSQL)

**Příklad použití:**
```bash
# Získání JWT tokenu
POST /auth/login
{
  "username": "user123",
  "password": "pass123"
}

Response:
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expires_in": 3600
}

# Použití API s tokenem
GET /api/svatky
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

# Nebo API Key
GET /api/svatky?api_key=abc123def456
```

**Doporučené endpointy:**
- `POST /auth/register` - Registrace uživatele
- `POST /auth/login` - Získání JWT tokenu
- `POST /auth/refresh` - Refresh tokenu
- `GET /auth/keys` - Správa API keys
- `POST /auth/keys/create` - Vytvoření nového API key

**Priorita:** High (důležité pro produkční API)

---

### 4. Admin Dashboard & Analytics ⭐⭐⭐⭐⭐

**Popis:**
Webové rozhraní pro správu API a monitoring využití.

**Technologie:**
- Frontend: Vue.js / React nebo PHP template (Latte)
- Backend: Apitte REST API
- Charts: Chart.js
- Databáze: MySQL/PostgreSQL

**Užitečnost:**
- Monitoring API usage
- Statistiky endpointů
- Error tracking
- Rate limit management
- User management
- API key management

**Složitost implementace:** High
- Frontend development
- Backend API pro admin
- Databáze design
- Autentizace
- Charts a reporting

**Závislosti:**
- Frontend framework (optional)
- Charting library
- Session management

**Features:**
```
Dashboard:
- Request count (today, week, month)
- Most used endpoints
- Error rate
- Response time graph
- Active users

Endpoints:
- List všech endpointů
- Usage statistics per endpoint
- Error logs per endpoint
- Enable/disable endpoints

Users (pokud je auth):
- List uživatelů
- API keys per user
- Usage per user
- Ban/unban users
```

**Doporučené endpointy:**
- `GET /admin/stats` - Celkové statistiky
- `GET /admin/endpoints` - List endpointů
- `GET /admin/users` - List uživatelů
- `GET /admin/logs` - Error logs
- `GET /admin/analytics?from=date&to=date` - Časová analýza

**Priorita:** High (důležité pro správu a monitoring)

---

### 5. Health Check & Monitoring ⭐⭐⭐⭐⭐

**Popis:**
Health check endpointy a monitoring stavu API.

**Technologie:**
- Health check endpoint
- Prometheus metrics export
- Status page
- Uptime monitoring

**Užitečnost:**
- Monitoring stavu API
- Alert při pádu služby
- Metriky pro Grafana/Prometheus
- Public status page
- Dependency health checks

**Složitost implementace:** Low-Medium
- Health endpoint - jednoduché
- Prometheus metrics - medium
- Status page - medium

**Závislosti:**
- Optional: Prometheus PHP client
- Optional: Status page framework

**Příklad použití:**
```bash
# Health check
GET /health
{
  "status": "healthy",
  "timestamp": "2025-11-14T10:30:00Z",
  "version": "2.0.0",
  "uptime": 86400,
  "dependencies": {
    "database": "healthy",
    "cache": "healthy",
    "external_apis": {
      "cnb": "healthy",
      "centrum_cz": "degraded",
      "coingecko": "healthy"
    }
  }
}

# Prometheus metrics
GET /metrics
# TYPE api_requests_total counter
api_requests_total{endpoint="/svatky",method="GET"} 1523
api_requests_total{endpoint="/pocasi",method="GET"} 892

# TYPE api_request_duration_seconds histogram
api_request_duration_seconds_bucket{endpoint="/svatky",le="0.1"} 1450
```

**Doporučené endpointy:**
- `GET /health` - Basic health check
- `GET /health/ready` - Readiness probe (Kubernetes)
- `GET /health/live` - Liveness probe (Kubernetes)
- `GET /metrics` - Prometheus metrics
- `GET /status` - Public status page

**Monitoring Features:**
- API uptime
- Response times
- Error rates
- External API status
- Cache status
- Rate limit stats

**Priorita:** High (kritické pro production)

---

## 📊 Prioritizovaný Seznam (TOP 20 celkově)

### 🔥 Highest Priority (Implementovat první)

#### P1 - Kritické pro production
1. **API Authentication (JWT, API Keys)** - Ochrana API
2. **Health Check & Monitoring** - Monitoring stavu
3. **Admin Dashboard & Analytics** - Správa a analytics

#### P2 - České APIs (priorita pro CZ IRC bot)
4. **ARES API** - Registr firem (Low complexity)
5. **Zprávy RSS** - České zpravodajství (Low complexity)
6. **ČHMÚ / OpenWeatherMap** - Lepší počasí (Medium complexity)

### ⭐ High Priority (Implementovat brzy)

#### P3 - Utility APIs (jednoduché, užitečné)
7. **QR Code Generator** - QR kódy (Low complexity)
8. **URL Shortener** - Zkracování URL (Low complexity)
9. **Hash & Encoding Tools** - Hash, base64, atd. (Low complexity)

#### P4 - International APIs (velmi užitečné)
10. **News API** - Mezinárodní zprávy (Low complexity)
11. **TMDB API** - Filmy a seriály (Medium complexity)
12. **Reddit API** - Reddit posty (Low complexity)

### 💡 Medium Priority (Nice to have)

#### P5 - Doprava a MHD
13. **DPP/Golemio API** - MHD Praha (Medium complexity)
14. **IDOS API** - Vlaky (Medium-High complexity)

#### P6 - Social & Entertainment
15. **Spotify API** - Hudba (Medium complexity)

#### P7 - Utilities pokračování
16. **Email & Phone Validation** - Validace (Low-Medium complexity)
17. **IP Geolocation & WHOIS** - IP info (Medium complexity)

### 🔮 Lower Priority (Budoucnost)

#### P8 - Advanced Features
18. **GraphQL Endpoint** - Flexibilnější API (Medium-High complexity)
19. **WebSocket Support** - Real-time (High complexity)

#### P9 - České APIs pokračování
20. **Registr živností (RŽP)** - Živnosti (Medium complexity)

---

## 📈 Implementační Roadmap

### Verze 2.1 (Q1 2026) - Security & Monitoring
**Focus:** Produkční připravenost

**Features:**
- ✅ API Authentication (JWT + API Keys)
- ✅ Health Check & Monitoring
- ✅ Admin Dashboard (basic version)
- ✅ Rate limiting improvements

**Odhadovaný čas:** 3-4 týdny

---

### Verze 2.2 (Q2 2026) - České APIs
**Focus:** Rozšíření pro český trh

**Features:**
- ✅ ARES API (firmy, IČO)
- ✅ Zprávy RSS (iRozhlas, ČT24, Seznam)
- ✅ OpenWeatherMap (lepší počasí)
- ✅ QR Code Generator
- ✅ URL Shortener

**Odhadovaný čas:** 2-3 týdny

---

### Verze 2.3 (Q3 2026) - International & Utility
**Focus:** Mezinárodní rozšíření

**Features:**
- ✅ News API
- ✅ TMDB API (filmy/seriály)
- ✅ Reddit API
- ✅ Hash & Encoding Tools
- ✅ Email & Phone Validation

**Odhadovaný čas:** 2-3 týdny

---

### Verze 2.4 (Q4 2026) - Transport & Social
**Focus:** Doprava a sociální sítě

**Features:**
- ✅ Golemio API (MHD Praha)
- ✅ Spotify API
- ✅ IP Geolocation & WHOIS
- ✅ Admin Dashboard (advanced version)

**Odhadovaný čas:** 3-4 týdny

---

### Verze 3.0 (2027) - Advanced Features
**Focus:** Pokročilé funkce

**Features:**
- ✅ GraphQL Endpoint
- ✅ WebSocket Support
- ✅ Machine Learning integrace (?)
- ✅ Custom plugins system

**Odhadovaný čas:** 6-8 týdnů

---

## 🎯 Doporučení podle Use Case

### Pro IRC bot zaměřený na:

#### 🇨🇿 České uživatele
**Priorita:**
1. ARES API (firmy)
2. Zprávy RSS (české zpravodajství)
3. ČHMÚ/OpenWeather (počasí)
4. Golemio (MHD Praha)
5. Registr živností

#### 🌍 Mezinárodní uživatele
**Priorita:**
1. News API (světové zprávy)
2. TMDB (filmy/seriály)
3. Reddit API
4. Spotify API
5. OpenWeatherMap (počasí celého světa)

#### 💻 Tech/Developer community
**Priorita:**
1. Hash & Encoding Tools
2. QR Code Generator
3. URL Shortener
4. IP WHOIS
5. Email/Phone Validation

#### 🎮 Gaming/Entertainment
**Priorita:**
1. TMDB (filmy/seriály)
2. Spotify (hudba)
3. Reddit (gaming subreddity)
4. Twitch API (streamers)
5. Steam API (games)

---

## 📋 Komplexita vs. Užitečnost Matrix

| API | Složitost | Užitečnost | Priorita | Dependencies |
|-----|-----------|------------|----------|--------------|
| **ARES** | Low | High | P2 | None |
| **Zprávy RSS** | Low | High | P2 | None |
| **QR Generator** | Low | High | P3 | None/Composer |
| **URL Shortener** | Low | High | P3 | None |
| **Hash Tools** | Low | Medium | P3 | None |
| **Auth (JWT)** | Medium | High | P1 | Composer + DB |
| **Health Check** | Low | High | P1 | None |
| **Admin Dashboard** | High | High | P1 | DB + Frontend |
| **News API** | Low | High | P4 | API key (free) |
| **Reddit** | Low | High | P4 | None (read-only) |
| **TMDB** | Medium | High | P4 | API key (free) |
| **OpenWeather** | Low | High | P2 | API key (free) |
| **Spotify** | Medium | Medium | P5 | OAuth (free) |
| **Golemio** | Medium | Medium | P5 | API key (free) |
| **IDOS** | High | Medium | P5 | None (scraping) |
| **Email Valid** | Low | Medium | P6 | Partial API key |
| **IP Geo/WHOIS** | Medium | Medium | P6 | API key (free tier) |
| **GraphQL** | High | Medium | P8 | Composer |
| **WebSocket** | High | Medium | P8 | Composer + Server |
| **RŽP** | Medium | Low | P9 | None (via ARES) |

**Legenda:**
- **Složitost:** Low (1-2 dny), Medium (3-5 dnů), High (1-2 týdny)
- **Užitečnost:** Low, Medium, High (pro IRC bot)
- **Priorita:** P1-P9 (viz prioritizovaný seznam)
- **Dependencies:** None, Composer, API key, Database

---

## 💰 Cost Analysis (API Keys)

### Zdarma bez omezení
- ✅ ARES - neomezené
- ✅ RUIAN - neomezené
- ✅ ČNB Kurzy - neomezené
- ✅ RSS Feeds - neomezené
- ✅ Reddit (read-only) - 60 req/min
- ✅ is.gd/TinyURL - neomezené
- ✅ QRServer - neomezené

### Zdarma s limity (dostačující)
- 🟡 OpenWeatherMap - 60 calls/min, 1M/měsíc
- 🟡 News API - 100 req/den (může být málo)
- 🟡 TMDB - 40 req/10sec (dostačující)
- 🟡 Golemio - 5000 req/den (dostačující)
- 🟡 ipapi.co - 30k req/měsíc
- 🟡 WHOIS API - 500 req/měsíc
- 🟡 Phone Valid - 250 req/měsíc

### Vyžaduje registraci (zdarma)
- 🔑 Spotify - Client Credentials
- 🔑 Všechny výše uvedené

### Paid tiers (pokud potřeba více)
- 💰 News API - $449/měsíc (business)
- 💰 OpenWeatherMap - $40/měsíc (1M+ calls)
- 💰 ipapi.co - $12/měsíc (150k req)

**Doporučení:** Začít s free tiers, monitorovat usage, případně upgradeovat.

---

## 🔒 Security Considerations

### API Keys Management
- ❌ **NIKDY** API keys v kódu
- ✅ Environment variables (.env)
- ✅ Config soubory mimo git (.gitignore)
- ✅ Rotace klíčů pravidelně
- ✅ Různé klíče pro dev/staging/production

### Rate Limiting
- ✅ Per-IP limiting (současný stav: 100/min)
- ✅ Per-User limiting (s auth)
- ✅ Per-Endpoint limiting (budoucnost)
- ✅ Graceful degradation při dosažení limitu

### Input Validation
- ✅ Validace všech vstupů
- ✅ Sanitizace před parsováním
- ✅ Type hints (PHP 8.4)
- ✅ Max length limits

### CORS
- ✅ Konfigurovatelné CORS headers
- ✅ Whitelist domains (production)
- ✅ Credentials handling

### Logging
- ✅ Log všechny requesty
- ✅ Error logging
- ✅ Suspicious activity detection
- ❌ NIKDY logovat citlivá data (passwords, API keys)

---

## 🧪 Testing Strategy

### Unit Tests
- Testy pro všechny Services
- Mock external API calls
- Edge cases testing
- Error handling tests

### Integration Tests
- Test API endpointů
- Test s reálnými daty (cache)
- Rate limiting tests
- Auth flow tests

### Performance Tests
- Load testing (Apache Bench, k6)
- Response time monitoring
- Cache efficiency tests
- Database query optimization

### Recommended Tools
- PHPUnit - unit testing
- Codeception - integration testing
- k6 - load testing
- PHPStan - static analysis (level 8)

---

## 📚 Documentation Strategy

### API Documentation
- ✅ OpenAPI/Swagger (už máme)
- ✅ Markdown docs (už máme)
- ➕ Postman collection
- ➕ Interactive examples (Swagger UI)

### Developer Documentation
- ➕ Contribution guide
- ➕ Architecture documentation
- ➕ Service documentation
- ➕ Deployment guide

### User Documentation
- ➕ IRC bot integration guide
- ➕ Examples pro každý endpoint
- ➕ Troubleshooting guide
- ➕ FAQ

---

## 🎓 Závěr a Next Steps

### Klíčová doporučení:

#### 1. **Immediate Actions (tento měsíc)**
- Implementovat **API Authentication** (JWT)
- Přidat **Health Check** endpoint
- Začít s **Admin Dashboard** (basic version)

#### 2. **Short-term (Q1 2026)**
- **ARES API** - vysoká priorita pro CZ
- **Zprávy RSS** - jednoduché, užitečné
- **QR Generator + URL Shortener** - utility APIs

#### 3. **Medium-term (Q2-Q3 2026)**
- **News API + TMDB** - international expansion
- **OpenWeatherMap** - lepší počasí
- **Reddit API** - social integration

#### 4. **Long-term (2026+)**
- **GraphQL** endpoint
- **WebSocket** support
- **Advanced monitoring** (Prometheus/Grafana)
- **Machine Learning** features

### Měřitelné cíle:

- **v2.1:** +5 endpointů (auth, monitoring, utils)
- **v2.2:** +10 endpointů celkem
- **v2.3:** +15 endpointů celkem
- **v2.4:** +20 endpointů celkem

### Success Metrics:

- API uptime > 99.5%
- Average response time < 200ms
- Error rate < 1%
- 1000+ daily requests
- 10+ active IRC bots using API

---

## 📞 Kontakt & Feedback

Pro feedback a návrhy dalších rozšíření:
- GitHub Issues: https://github.com/Chatujme/pLBOT-API/issues
- Email: lury@lury.cz

---

**Vytvořeno:** 2025-11-14
**Verze dokumentu:** 1.0
**Autor:** pLBOT API Expansion Team
**Next Review:** 2026-01-01
