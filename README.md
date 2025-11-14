# pLBOT-API v2.0

🚀 Modernizované REST API pro IRC bota pLBOT

[![PHP Version](https://img.shields.io/badge/PHP-8.4+-blue.svg)](https://php.net)
[![Nette](https://img.shields.io/badge/Nette-3.x%2F4.x-green.svg)](https://nette.org)
[![Apitte](https://img.shields.io/badge/Apitte-0.8-orange.svg)](https://contributte.org/apitte/)

---

## 🔥 Co je nového ve verzi 2.0

- ✅ **PHP 8.4** s full type safety
- ✅ **Apitte/Contributte** REST API framework
- ✅ **PHP 8 Attributes** místo anotací
- ✅ **OpenAPI** dokumentace
- ✅ **Service Layer** architektura
- ✅ **DOMDocument** parsery (robustnější než regex)
- ✅ **Dependency Injection**
- ✅ **CORS support**
- ✅ **26+ API Endpoints** (rozšíření z 6 na 26+)
- ✅ **Rate Limiting** (100 req/min per IP)
- ✅ **21 nových API** (Joke, Crypto, Countries, ISS, RUIAN, QR, URL Shortener, Hash Tools, News RSS a další)

---

## 📦 Instalace

```bash
composer install
```

**Požadavky:**
- PHP >= 8.4
- Apache s mod_rewrite
- Extensions: curl, json, dom, libxml, simplexml

---

## 📚 Dokumentace

Kompletní API dokumentace: **[docs/API_DOCUMENTATION.md](docs/API_DOCUMENTATION.md)**

Nová API v2.0: **[docs/NEW_APIS.md](docs/NEW_APIS.md)**

Analýza datových zdrojů: **[docs/DATA_SOURCES_ANALYSIS.md](docs/DATA_SOURCES_ANALYSIS.md)**

---

## 🌐 API Endpointy (26+ APIs)

### 🇨🇿 České API (9 endpoints)

#### Svátky
```bash
GET /svatky           # Všechny dny
GET /svatky/dnes      # Dnešní svátek
GET /svatky/zitra     # Zítřejší svátek
```

#### Počasí
```bash
GET /pocasi                    # Pro Prahu (všechny dny)
GET /pocasi/dnes               # Dnes pro Prahu
GET /pocasi?mesto=brno         # Pro Brno
GET /pocasi/zitra?mesto=plzen  # Zítra pro Plzeň
```

#### Horoskopy
```bash
GET /horoskop/lev      # Horoskop pro lva
GET /horoskop/stir     # Podporuje i bez diakritiky
GET /horoskop/vodnář   # I s diakritikou
```

#### TV Program
```bash
GET /tv           # Seznam stanic
GET /tv/vse       # Aktuální program všech stanic
GET /tv/nova      # Aktuální program TV Nova
GET /tv/ct1       # Aktuální program ČT1
```

#### Místnost (Chatujme.cz)
```bash
GET /mistnost/{id}    # Info o místnosti
```

#### ČNB Kurzy
```bash
GET /cnb/kurzy              # Všechny kurzy měn
GET /cnb/kurzy/USD          # Kurz dolaru
GET /cnb/prevod?amount=100&from=USD&to=CZK  # Převod měn
```

#### RUIAN - Registr adres
```bash
GET /ruian/obce?nazev=Praha           # Vyhledání obcí
GET /ruian/ulice?nazev=Karlova        # Vyhledání ulic
GET /ruian/adresy?query=Karlova       # Vyhledání adres
GET /ruian/validate?ulice=Karlova&cislo=1&obec=Praha  # Validace adresy
```

#### Zásilkovna
```bash
GET /zasilkovna/track/Z123456789      # Sledování balíku
```

#### České zprávy (RSS)
```bash
GET /news/sources                     # Seznam RSS zdrojů (ČT24, Novinky, Aktuálně, Blesk)
GET /news/latest?source=ct24&limit=10 # Poslední zprávy ze zdroje
GET /news/search?query=Babiš          # Vyhledávání ve zprávách
```

---

### 🎉 Fun APIs (8 endpoints)

```bash
GET /joke/                    # Náhodný vtip
GET /joke/programming         # Programátorský vtip
GET /catfact/                 # Zajímavost o kočkách
GET /dog/                     # Obrázek psa
GET /dog/?breed=husky         # Obrázek konkrétního plemene
GET /advice/                  # Životní rada
GET /quotes/                  # Inspirativní citát
GET /chucknorris/             # Chuck Norris vtip
GET /bored/                   # Nápad na aktivitu
GET /fox/                     # Obrázek lišky
```

---

### 📊 Data APIs (5 endpoints)

```bash
GET /crypto/price/bitcoin              # Cena kryptoměny
GET /crypto/popular?currency=czk       # Populární kryptoměny
GET /countries/CZ                      # Informace o zemi
GET /countries/region/europe           # Země v regionu
GET /numbers/42                        # Zajímavost o čísle
GET /numbers/today                     # Historický fakt o dnešku
GET /iss/position                      # Poloha ISS
GET /iss/astronauts                    # Astronauti ve vesmíru
GET /trivia/                           # Trivia otázky
```

---

### 🔧 Utility APIs (8 endpoints)

#### QR Kódy
```bash
GET /qr/generate?data=https://example.com&size=300  # QR kód pro URL/text
POST /qr/vcard                                      # vCard kontakt QR
GET /qr/wifi?ssid=WiFi&password=pass                # WiFi QR kód
```

#### URL Shortener
```bash
GET /url/shorten?url=https://long-url.com           # Zkrátit URL (is.gd/TinyURL)
GET /url/shorten?url=...&alias=mylink               # S vlastním aliasem (is.gd)
GET /url/stats?short_url=https://is.gd/abc         # Statistiky (is.gd only)
```

#### Hash & Encoding
```bash
GET /hash/?data=password&algo=sha256                # Hash (MD5, SHA*, ...)
GET /hash/base64/encode?data=Hello                  # Base64 encode
GET /hash/base64/decode?data=SGVsbG8                # Base64 decode
GET /hash/hex/encode?data=Test                      # HEX encode
GET /hash/hmac?data=msg&key=secret&algo=sha256      # HMAC signature
GET /hash/algorithms                                # Seznam algoritmů
```

#### UUID
```bash
GET /uuid/                    # Vygenerování UUID
GET /uuid/?count=5            # 5 UUID najednou
GET /uuid/validate/{uuid}     # Validace UUID
```

---

## 🔒 Rate Limiting

API implementuje rate limiting pro ochranu proti zneužití:

- **Limit:** 100 requestů za minutu per IP adresu
- **Headers:** Každý response obsahuje rate limit headers (`X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`)
- **429 Error:** Při překročení limitu se vrátí HTTP 429 s informací kdy můžete zkusit znovu

```bash
# Response headers
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1699874460
```

---

## 📊 API Features

### Caching Strategy
- **Czech APIs:** 1 den až 1 týden (RUIAN, ČNB)
- **Fun APIs:** Většinou bez cache (vždy náhodné)
- **Crypto APIs:** 5 minut (ceny se rychle mění)
- **ISS Position:** 1 minuta (rychlý pohyb)
- **Countries:** 1 týden (data se nemění často)

### Supported Features
- ✅ Full type safety (PHP 8.4)
- ✅ OpenAPI documentation
- ✅ CORS support
- ✅ Rate limiting
- ✅ Comprehensive error handling
- ✅ HTTP cache headers
- ✅ Request/Response logging
- ✅ Service layer architecture

---

## 🏗️ Architektura

```
app/
├── Controllers/     # API Controllers (Apitte)
│   ├── BaseController.php
│   ├── SvatkyController.php
│   ├── PocasiController.php
│   ├── HoroskopyController.php
│   ├── TvController.php
│   └── MistnostController.php
├── Services/        # Business Logic
│   ├── HttpClientService.php
│   ├── SvatkyService.php
│   ├── PocasiService.php
│   ├── HoroskopyService.php
│   ├── TvProgramService.php
│   └── MistnostService.php
└── model/
    └── xmltv.php    # Modernizovaný XMLTV parser
```

**Design Patterns:**
- Service Layer Pattern
- Dependency Injection
- Repository Pattern (HttpClientService)

---

## 📝 Changelog

### v2.0.0 (2025-11-13)
- Kompletní refaktor na PHP 8.4
- Migrace na Apitte REST API framework
- PHP 8 attributes místo anotací
- Service layer architektura
- DOMDocument parsery místo regex
- OpenAPI dokumentace
- Type safety (strict types everywhere)
- **Přidáno 17+ nových API endpoints:**
  - Fun APIs: Joke, Cat Facts, Dog, Advice, Quotes, Chuck Norris, Bored, Fox
  - Data APIs: Crypto (CoinGecko), Countries, Numbers, Trivia, ISS Tracker
  - Czech APIs: ČNB Kurzy, RUIAN, Zásilkovna
  - Utility APIs: UUID Generator
- Rate limiting implementace (100 req/min per IP)
- Odebrání cache z náhodných API pro lepší user experience
- Komplexní testy pro všechny nové API
- Kompletní dokumentace včetně příkladů použití v IRC botu

### v1.0.0
- Původní verze (PHP 5.4, Nette 2.3)

---

## 📧 Kontakt

- **Autor**: LuRy <lury@lury.cz>
- **Refaktoring v2.0**: pLBOT-API Team
- **Repository**: [GitHub](https://github.com/Chatujme/pLBOT-API)

---

## 📄 Licence

MIT, BSD-3-Clause, GPL-2.0, GPL-3.0