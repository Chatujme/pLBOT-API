# pLBOT API v2.0 - Dokumentace

Modernizované REST API pro IRC bota pLBOT

## 🚀 Co je nového ve verzi 2.0

- ✅ **PHP 8.4** - Využití nejnovějších funkcí PHP
- ✅ **Apitte/Contributte** - Moderní REST API framework
- ✅ **Type hints** - Plná typová bezpečnost
- ✅ **Dependency Injection** - Čistá architektura
- ✅ **Service Layer** - Oddělení business logiky
- ✅ **DOMDocument Parser** - Robustní parsování HTML místo regex
- ✅ **Error Handling** - Konzistentní error responses
- ✅ **CORS Support** - Podpora pro cross-origin requests
- ✅ **OpenAPI** - Automatická dokumentace API
- ✅ **22+ API Endpoints** - Rozšíření z 6 na 22+ různých API
- ✅ **Rate Limiting** - Ochrana proti zneužití API
- ✅ **Czech APIs** - RUIAN, Zásilkovna, ČNB kurzy
- ✅ **International APIs** - Joke, Quotes, Crypto, Countries, ISS a další

## 📋 Požadavky

- PHP >= 8.4
- Apache s mod_rewrite
- Composer
- Extensions: curl, json, dom, libxml, simplexml

## 🔧 Instalace

```bash
composer install
```

## 🌐 API Endpointy

### Svátky

Získávání informací o českých svátkách.

#### Všechny dny
```
GET /svatky
```

**Response:**
```json
{
  "data": {
    "predevcirem": "Martin",
    "vcera": "Benedikt",
    "dnes": "Tibor",
    "zitra": "Sáva"
  }
}
```

#### Konkrétní den
```
GET /svatky/{den}
```

Podporované hodnoty: `predevcirem`, `vcera`, `dnes`, `zitra`

**Response:**
```json
{
  "data": "Tibor"
}
```

---

### Počasí

Předpověď počasí z Centrum.cz API.

#### Celá předpověď
```
GET /pocasi?mesto=praha
```

**Query parametry:**
- `mesto` (optional) - Název města (default: praha)

**Response:**
```json
{
  "data": {
    "dnes": {
      "datum": "2025-11-13",
      "predpoved": "Polojasno",
      "nyni": "12°C",
      "den": "15°C",
      "noc": "8°C",
      "pro": "Pro Praha"
    },
    "zitra": { ... },
    "pozitri": { ... }
  }
}
```

#### Konkrétní den
```
GET /pocasi/{den}?mesto=brno
```

Podporované hodnoty: `dnes`, `zitra`, `pozitri`

---

### Horoskopy

Denní horoskopy z Horoskopy.cz.

```
GET /horoskop/{znameni}
```

**Podporovaná znamení:**
- beran, byk, blizenci, rak, lev, panna
- vahy, stir, strelec, kozoroh, vodnar, ryby

Podporuje i diakritiku (šťír, vodnář) - bude automaticky normalizováno.

**Response:**
```json
{
  "data": {
    "znameni": "Lev",
    "datum": "13.11.2025",
    "horoskop": "Dnes bude...",
    "laska-a-pratelstvi": "V lásce...",
    "penize-a-prace": "V práci...",
    "rodina-a-vztahy": "V rodině...",
    "zdravi-a-kondice": "Co se týče zdraví...",
    "vhodne-aktivity-na-dnes": "Doporučujeme..."
  }
}
```

---

### TV Program

TV program z XMLTV zdroje.

#### Seznam stanic
```
GET /tv
```

**Response:**
```json
{
  "data": {
    "ct1": "/tv/ct1",
    "ct2": "/tv/ct2",
    "nova": "/tv/nova",
    ...
  }
}
```

#### Všechny aktuální programy
```
GET /tv/vse
```

**Response:**
```json
{
  "data": {
    "ct1": [{
      "program": "Večerníček",
      "popis": "Pohádka pro děti",
      "zacatek": "18:45",
      "konec": "19:00",
      "zacatek-full": "13.11.2025 18:45",
      "konec-full": "13.11.2025 19:00"
    }],
    "nova": [ ... ],
    ...
  }
}
```

#### Konkrétní stanice
```
GET /tv/{stanice}
```

Příklady: `/tv/ct1`, `/tv/nova`, `/tv/prima-cool`

**Response:**
```json
{
  "data": {
    "program": "Večerníček",
    "popis": "Pohádka pro děti",
    "zacatek": "18:45",
    "konec": "19:00",
    "zacatek-full": "13.11.2025 18:45",
    "konec-full": "13.11.2025 19:00",
    "stanice": "ct1"
  }
}
```

---

### Místnost (Chatujme.cz)

Informace o místnostech z Chatujme.cz.

```
GET /mistnost/{id}
```

**Response:**
```json
{
  "data": {
    "mistnost": "Název místnosti",
    "popis": "Popis místnosti",
    "ss": ["user1", "user2"],
    "celkovy-cas": "12345",
    "aktualni-den": "100",
    "aktualne-prochatovano": "50",
    "web": "https://...",
    "limit": {
      "mistnost-limit": true,
      "splneny-limit": true,
      "limit-hodin": "24"
    },
    "zalozeno": "01.01.2020"
  }
}
```

**Error response (404):**
```json
{
  "error": {
    "message": "Místnost 999 nebyla nalezena",
    "code": 404
  }
}
```

---

### ČNB Kurzy

Oficiální kurzy měn České národní banky. Data jsou aktualizována 1x denně po 14:30 (pracovní dny).

#### Všechny kurzy
```
GET /cnb/kurzy
```

**Response:**
```json
{
  "data": {
    "datum": "13.11.2025",
    "kurzy": [
      {
        "kod": "USD",
        "mena": "dolar",
        "zeme": "USA",
        "mnozstvi": 1,
        "kurz": 23.456
      },
      {
        "kod": "EUR",
        "mena": "euro",
        "zeme": "EMU",
        "mnozstvi": 1,
        "kurz": 25.123
      }
    ]
  }
}
```

#### Konkrétní měna
```
GET /cnb/kurzy/{mena}
```

**Příklady:** `/cnb/kurzy/USD`, `/cnb/kurzy/EUR`

**Response:**
```json
{
  "data": {
    "kod": "USD",
    "mena": "dolar",
    "zeme": "USA",
    "mnozstvi": 1,
    "kurz": 23.456,
    "datum": "13.11.2025"
  }
}
```

#### Převod měn
```
GET /cnb/prevod?amount=100&from=USD&to=CZK
```

**Query parametry:**
- `amount` (required) - Částka k převodu
- `from` (required) - Zdrojová měna (kód)
- `to` (required) - Cílová měna (kód)

**Response:**
```json
{
  "data": {
    "amount": 100,
    "from": "USD",
    "to": "CZK",
    "result": 2345.60,
    "rate": 23.456,
    "datum": "13.11.2025"
  }
}
```

---

### Joke API

Náhodné vtipy z JokeAPI. Data jsou cachována 1 hodinu.

#### Náhodný vtip
```
GET /joke/
```

**Query parametry:**
- `category` (optional) - Kategorie (Programming, Misc, Dark, Pun, Any)
- `safe` (optional) - Pouze bezpečné vtipy (true/false, výchozí true)

**Response:**
```json
{
  "data": {
    "type": "single",
    "joke": "Why do programmers prefer dark mode? Because light attracts bugs!",
    "category": "Programming",
    "safe": true
  }
}
```

#### Programming vtipy
```
GET /joke/programming
```

Krátká cesta pro bezpečné programátorské vtipy.

---

### Cat Facts

Náhodné zajímavosti o kočkách z Cat Facts API.

```
GET /catfact/
```

**Response:**
```json
{
  "data": {
    "fact": "Cats sleep 70% of their lives.",
    "length": 32
  }
}
```

---

### Dog CEO API

Náhodné obrázky psů z Dog CEO API.

#### Náhodný pes
```
GET /dog/
```

**Query parametry:**
- `breed` (optional) - Plemeno (husky, beagle, corgi, atd.)

**Response:**
```json
{
  "data": {
    "image": "https://images.dog.ceo/breeds/husky/n02110185_1469.jpg",
    "breed": "husky"
  }
}
```

#### Seznam plemen
```
GET /dog/breeds
```

**Response:**
```json
{
  "data": {
    "breeds": ["affenpinscher", "african", "beagle", "corgi", "husky"],
    "count": 95
  }
}
```

---

### Advice Slip

Náhodné rady z Advice Slip API.

```
GET /advice/
```

**Response:**
```json
{
  "data": {
    "id": 117,
    "advice": "It is easy to sit up and take notice. What's difficult is getting up and taking action."
  }
}
```

---

### Crypto (CoinGecko)

Aktuální ceny kryptoměn z CoinGecko API. Data jsou cachována 5 minut.

#### Cena konkrétní kryptoměny
```
GET /crypto/price/{coin}
```

**Path parametry:**
- `coin` (required) - ID kryptoměny (bitcoin, ethereum, cardano, atd.)

**Query parametry:**
- `currency` (optional) - Měna (usd, eur, czk, gbp, btc) - výchozí USD

**Příklady:**
- `/crypto/price/bitcoin`
- `/crypto/price/ethereum?currency=czk`

**Response:**
```json
{
  "data": {
    "coin": "bitcoin",
    "price": 45123.50,
    "currency": "USD",
    "change_24h": 2.5,
    "timestamp": "2025-11-13T10:30:00Z"
  }
}
```

#### Populární kryptoměny
```
GET /crypto/popular
```

**Query parametry:**
- `currency` (optional) - Měna (usd, eur, czk, gbp) - výchozí USD

Vrací ceny: Bitcoin, Ethereum, Cardano, Ripple, Solana, Polkadot, Dogecoin, Litecoin.

**Response:**
```json
{
  "data": {
    "currency": "USD",
    "coins": [
      {"coin": "bitcoin", "price": 45123.50, "change_24h": 2.5},
      {"coin": "ethereum", "price": 3200.75, "change_24h": 1.8}
    ]
  }
}
```

---

### Quotes API

Inspirativní citáty z Quotable API.

#### Náhodný citát
```
GET /quotes/
```

**Query parametry:**
- `tag` (optional) - Téma (wisdom, inspirational, success, life, happiness, motivational)

**Response:**
```json
{
  "data": {
    "quote": "The only way to do great work is to love what you do.",
    "author": "Steve Jobs",
    "tags": ["inspirational", "success"]
  }
}
```

#### Více citátů
```
GET /quotes/multiple
```

**Query parametry:**
- `limit` (optional) - Počet citátů (1-50, výchozí 5)

**Response:**
```json
{
  "data": {
    "quotes": [
      {
        "quote": "...",
        "author": "...",
        "tags": [...]
      }
    ],
    "count": 5
  }
}
```

---

### Chuck Norris API

Chuck Norris vtipy z oficiálního Chuck Norris API.

#### Náhodný vtip
```
GET /chucknorris/
```

**Query parametry:**
- `category` (optional) - Kategorie (dev, movie, food, atd.)

**Response:**
```json
{
  "data": {
    "joke": "Chuck Norris can delete the Recycling Bin.",
    "category": "dev",
    "url": "https://api.chucknorris.io/jokes/abc123"
  }
}
```

#### Seznam kategorií
```
GET /chucknorris/categories
```

**Response:**
```json
{
  "data": {
    "categories": ["dev", "movie", "food", "celebrity", "science"]
  }
}
```

---

### Numbers API

Zajímavosti o číslech z NumbersAPI. Data jsou cachována 1 den.

#### Zajímavost o čísle
```
GET /numbers/{number}
```

**Path parametry:**
- `number` (required) - Číslo nebo "random"

**Query parametry:**
- `type` (optional) - Typ (trivia, math, year) - výchozí trivia

**Příklady:**
- `/numbers/42`
- `/numbers/1337?type=math`
- `/numbers/1969?type=year`
- `/numbers/random`

**Response:**
```json
{
  "data": {
    "number": 42,
    "type": "trivia",
    "text": "42 is the answer to the Ultimate Question of Life, the Universe, and Everything.",
    "found": true
  }
}
```

#### Zajímavost o dnešním datu
```
GET /numbers/today
```

**Response:**
```json
{
  "data": {
    "date": "11/13",
    "text": "November 13th is the day in 1985 that...",
    "year": 1985
  }
}
```

---

### REST Countries API

Informace o zemích světa z REST Countries API. Data jsou cachována 1 týden.

#### Informace o zemi
```
GET /countries/{country}
```

**Path parametry:**
- `country` (required) - Kód země (CZ, US, DE) nebo název (Czechia, Germany)

**Příklady:**
- `/countries/CZ`
- `/countries/US`
- `/countries/Germany`

**Response:**
```json
{
  "data": {
    "name": "Czechia",
    "official_name": "Czech Republic",
    "capital": "Prague",
    "region": "Europe",
    "population": 10510000,
    "languages": ["Czech"],
    "currencies": ["CZK"],
    "flag": "🇨🇿",
    "area": 78865
  }
}
```

#### Země podle regionu
```
GET /countries/region/{region}
```

**Path parametry:**
- `region` (required) - Region (europe, asia, africa, americas, oceania)

**Response:**
```json
{
  "data": {
    "region": "Europe",
    "count": 53,
    "countries": [
      {"name": "Czechia", "code": "CZ", "capital": "Prague"},
      {"name": "Germany", "code": "DE", "capital": "Berlin"}
    ]
  }
}
```

#### Všechny země
```
GET /countries/all
```

Vrací seznam všech zemí světa s základními informacemi.

---

### Bored API

Návrhy aktivit když se nudíte z Bored API.

#### Náhodná aktivita
```
GET /bored/
```

**Query parametry:**
- `type` (optional) - Typ aktivity (education, recreational, social, diy, charity, cooking, relaxation, music, busywork)
- `participants` (optional) - Počet účastníků (1-10)

**Příklady:**
- `/bored/`
- `/bored/?type=social`
- `/bored/?participants=2`
- `/bored/?type=cooking&participants=1`

**Response:**
```json
{
  "data": {
    "activity": "Learn Express.js",
    "type": "education",
    "participants": 1,
    "price": 0.1,
    "accessibility": 0.25,
    "key": "3943506"
  }
}
```

#### Aktivita podle klíče
```
GET /bored/activity/{key}
```

**Path parametry:**
- `key` (required) - Unikátní klíč aktivity

---

### ISS Tracker

Sledování Mezinárodní vesmírné stanice (ISS). Data o poloze jsou cachována 1 minutu.

#### Aktuální poloha ISS
```
GET /iss/position
```

**Response:**
```json
{
  "data": {
    "latitude": 50.0755,
    "longitude": 14.4378,
    "timestamp": 1699874400,
    "altitude": 408.5,
    "velocity": 27600
  }
}
```

#### Časy přeletů
```
GET /iss/pass?lat=50.0755&lon=14.4378
```

**Query parametry:**
- `lat` (required) - Zeměpisná šířka (-90 až 90)
- `lon` (required) - Zeměpisná délka (-180 až 180)
- `n` (optional) - Počet přeletů (1-100, výchozí 5)

**Response:**
```json
{
  "data": {
    "location": {"latitude": 50.0755, "longitude": 14.4378},
    "passes": [
      {"risetime": 1699874400, "duration": 540},
      {"risetime": 1699879800, "duration": 600}
    ]
  }
}
```

#### Astronauti ve vesmíru
```
GET /iss/astronauts
```

**Response:**
```json
{
  "data": {
    "number": 7,
    "people": [
      {"name": "Jasmin Moghbeli", "craft": "ISS"},
      {"name": "Andreas Mogensen", "craft": "ISS"}
    ]
  }
}
```

---

### Trivia API

Trivia otázky z Open Trivia Database. Data jsou cachována 1 hodinu.

#### Trivia otázky
```
GET /trivia/
```

**Query parametry:**
- `amount` (optional) - Počet otázek (1-50, výchozí 10)
- `category` (optional) - ID kategorie (viz /trivia/categories)
- `difficulty` (optional) - Obtížnost (easy, medium, hard)
- `type` (optional) - Typ otázky (multiple, boolean)

**Příklady:**
- `/trivia/`
- `/trivia/?amount=5&difficulty=easy`
- `/trivia/?category=9&type=multiple`

**Response:**
```json
{
  "data": {
    "count": 10,
    "questions": [
      {
        "category": "General Knowledge",
        "type": "multiple",
        "difficulty": "easy",
        "question": "What is the capital of France?",
        "correct_answer": "Paris",
        "incorrect_answers": ["London", "Berlin", "Madrid"]
      }
    ]
  }
}
```

#### Seznam kategorií
```
GET /trivia/categories
```

**Response:**
```json
{
  "data": {
    "categories": [
      {"id": 9, "name": "General Knowledge"},
      {"id": 18, "name": "Science: Computers"},
      {"id": 21, "name": "Sports"}
    ]
  }
}
```

---

### UUID Generator

Generování a validace UUID (Universally Unique Identifier).

#### Generování UUID
```
GET /uuid/
```

**Query parametry:**
- `count` (optional) - Počet UUID (1-100, výchozí 1)

**Response (count=1):**
```json
{
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "version": 4
  }
}
```

**Response (count>1):**
```json
{
  "data": {
    "uuids": [
      "550e8400-e29b-41d4-a716-446655440000",
      "6ba7b810-9dad-11d1-80b4-00c04fd430c8"
    ],
    "count": 2
  }
}
```

#### Validace UUID
```
GET /uuid/validate/{uuid}
```

**Response:**
```json
{
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "valid": true,
    "version": 4,
    "variant": "RFC 4122"
  }
}
```

#### NIL UUID
```
GET /uuid/nil
```

Vrací NIL UUID (všechny nuly): `00000000-0000-0000-0000-000000000000`

---

### Fox API

Náhodné obrázky lišek z randomfox.ca.

```
GET /fox/
```

**Query parametry:**
- `count` (optional) - Počet obrázků (1-10, výchozí 1)

**Response (count=1):**
```json
{
  "data": {
    "image": "https://randomfox.ca/images/23.jpg",
    "link": "https://randomfox.ca/?i=23"
  }
}
```

**Response (count>1):**
```json
{
  "data": {
    "images": [
      {"image": "https://randomfox.ca/images/23.jpg", "link": "..."},
      {"image": "https://randomfox.ca/images/45.jpg", "link": "..."}
    ],
    "count": 2
  }
}
```

---

### RUIAN - Registr adres

Registr územní identifikace, adres a nemovitostí (RUIAN). Data jsou cachována 1 týden.

#### Vyhledání obcí
```
GET /ruian/obce?nazev=Praha
```

**Query parametry:**
- `nazev` (required) - Název obce (min. 2 znaky)
- `limit` (optional) - Max. počet výsledků (výchozí 10)

**Response:**
```json
{
  "data": {
    "query": "Praha",
    "count": 3,
    "obce": [
      {
        "kod": "554782",
        "nazev": "Praha",
        "okres": "Hlavní město Praha",
        "kraj": "Hlavní město Praha"
      }
    ]
  }
}
```

#### Vyhledání ulic
```
GET /ruian/ulice?nazev=Karlova
```

**Query parametry:**
- `nazev` (required) - Název ulice (min. 2 znaky)
- `obec` (optional) - Název obce pro upřesnění
- `limit` (optional) - Max. počet výsledků (výchozí 10)

**Response:**
```json
{
  "data": {
    "query": "Karlova",
    "count": 15,
    "ulice": [
      {
        "kod": "123456",
        "nazev": "Karlova",
        "obec": "Praha",
        "cast_obce": "Praha 1"
      }
    ]
  }
}
```

#### Vyhledání adres
```
GET /ruian/adresy?query=Karlova
```

**Query parametry:**
- `query` (required) - Hledaný výraz (min. 3 znaky)
- `limit` (optional) - Max. počet výsledků (výchozí 10)

**Response:**
```json
{
  "data": {
    "query": "Karlova",
    "count": 20,
    "adresy": [
      {
        "adresa": "Karlova 1, 110 00 Praha 1",
        "ulice": "Karlova",
        "cislo": "1",
        "psc": "110 00",
        "obec": "Praha",
        "cast_obce": "Praha 1"
      }
    ]
  }
}
```

#### Validace adresy
```
GET /ruian/validate?ulice=Karlova&cislo=1&obec=Praha
```

**Query parametry:**
- `ulice` (required) - Název ulice
- `cislo` (required) - Číslo popisné
- `obec` (required) - Název obce
- `psc` (optional) - PSČ

**Response:**
```json
{
  "data": {
    "valid": true,
    "adresa": "Karlova 1, 110 00 Praha 1",
    "kod": "21879294",
    "psc": "110 00",
    "gps": {
      "latitude": 50.086,
      "longitude": 14.414
    }
  }
}
```

---

### Zásilkovna (Packeta)

Sledování zásilek přes Zásilkovnu. Data jsou cachována 1 hodinu.

```
GET /zasilkovna/track/{packageId}
```

**Path parametry:**
- `packageId` (required) - ID balíku (např. Z123456789)

**Příklady:**
- `/zasilkovna/track/Z123456789`
- `/zasilkovna/track/P987654321`

**Response:**
```json
{
  "data": {
    "package_id": "Z123456789",
    "status": "delivered",
    "status_text": "Doručeno",
    "delivered_at": "2025-11-10 14:30:00",
    "pickup_point": {
      "name": "Zásilkovna Praha 1",
      "address": "Karlova 1, Praha 1"
    },
    "history": [
      {
        "status": "created",
        "timestamp": "2025-11-08 10:00:00",
        "text": "Zásilka vytvořena"
      },
      {
        "status": "in_transit",
        "timestamp": "2025-11-09 08:00:00",
        "text": "Zásilka v přepravě"
      }
    ]
  }
}
```

---

## 🏗️ Architektura

### Struktura projektu

```
app/
├── Controllers/        # Apitte API controllers (22+ controllers)
│   ├── BaseController.php
│   # Czech APIs
│   ├── SvatkyController.php
│   ├── PocasiController.php
│   ├── HoroskopyController.php
│   ├── TvController.php
│   ├── MistnostController.php
│   ├── CnbController.php
│   ├── RuianController.php
│   ├── ZasilkovnaController.php
│   # Fun APIs
│   ├── JokeController.php
│   ├── CatFactsController.php
│   ├── DogController.php
│   ├── AdviceController.php
│   ├── QuotesController.php
│   ├── ChuckNorrisController.php
│   ├── NumbersController.php
│   ├── BoredController.php
│   ├── TriviaController.php
│   ├── FoxController.php
│   # Data APIs
│   ├── CryptoController.php
│   ├── CountriesController.php
│   ├── ISSController.php
│   └── UUIDController.php
├── Services/          # Business logic services (22+ services)
│   ├── HttpClientService.php
│   ├── RateLimiter.php
│   # Czech Services
│   ├── SvatkyService.php
│   ├── PocasiService.php
│   ├── HoroskopyService.php
│   ├── TvProgramService.php
│   ├── MistnostService.php
│   ├── CnbKurzyService.php
│   ├── RuianService.php
│   ├── ZasilkovnaService.php
│   # Fun Services
│   ├── JokeService.php
│   ├── CatFactsService.php
│   ├── DogService.php
│   ├── AdviceService.php
│   ├── QuotesService.php
│   ├── ChuckNorrisService.php
│   ├── NumbersService.php
│   ├── BoredService.php
│   ├── TriviaService.php
│   ├── FoxService.php
│   # Data Services
│   ├── CryptoService.php
│   ├── CountriesService.php
│   ├── ISSService.php
│   └── UUIDService.php
├── model/
│   └── xmltv.php     # Refactored XMLTV parser
├── config/
│   └── config.neon   # Nette/Apitte configuration
└── bootstrap.php     # Application bootstrap
```

### Design Patterns

- **Service Layer Pattern** - Business logika oddělena od presenterů
- **Dependency Injection** - Všechny závislosti injektované
- **Repository Pattern** - HttpClientService jako abstrakce nad cURL
- **Factory Pattern** - Pro vytváření response objektů

### Caching

Všechny služby používají Nette Cache s různými TTL podle typu dat:

**Czech APIs:**
- Svátky: 1 den
- Počasí: 1 den
- Horoskopy: 1 den
- TV Program: 1 hodina
- Místnost: 5 minut
- ČNB Kurzy: 1 den
- RUIAN: 1 týden (adresy se mění zřídka)
- Zásilkovna: 1 hodina

**Fun APIs (bez cache - vždy náhodné):**
- Joke API: 1 hodina
- Cat Facts: bez cache
- Dog CEO: bez cache
- Advice Slip: bez cache
- Quotes: bez cache
- Chuck Norris: bez cache
- Bored API: bez cache
- Fox API: bez cache

**Data APIs:**
- Crypto: 5 minut (ceny se mění rychle)
- Countries: 1 týden (data se mění zřídka)
- Numbers API: 1 den
- Trivia: 1 hodina
- ISS Position: 1 minuta (pozice se rychle mění)
- ISS Pass Times: 1 hodina
- ISS Astronauts: 1 den

**Utility APIs:**
- UUID: bez cache (vždy generovat nové)

### Rate Limiting

API implementuje rate limiting pro ochranu proti zneužití:

**Limity:**
- **Per IP:** 100 requestů za minutu
- **Globální:** Automatické throttling při vysokém zatížení

**Headers v response:**
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1699874460
```

**Rate limit exceeded (429):**
```json
{
  "error": {
    "message": "Rate limit exceeded. Try again in 30 seconds.",
    "code": 429,
    "retry_after": 30
  }
}
```

---

## ⚡ Error Handling

Všechny chyby jsou vráceny v konzistentním formátu:

```json
{
  "error": {
    "message": "Popis chyby",
    "code": 500
  }
}
```

**HTTP Status kódy:**
- `200` - Úspěch
- `400` - Špatný request (chybí parametry)
- `404` - Nenalezeno
- `500` - Interní chyba serveru

---

## 🔒 Security

- ✅ Input sanitization
- ✅ XSS protection pomocí DOMDocument
- ✅ SQL injection prevence (pokud bude DB)
- ✅ CORS headers konfigurovatelné
- ✅ SSL/TLS verification pro externí requesty

---

## 🧪 Testing

Spusťte testy pomocí:

```bash
composer test
```

Pro statickou analýzu:

```bash
composer phpstan
```

---

## 📝 Changelog

### v2.0.0 (2025-11-13)
- Kompletní refaktor na PHP 8.4
- Migrace z Nette presenters na Apitte REST API
- Modernizace parsování (DOMDocument místo regex)
- Přidání type hints a strict types
- Service layer architektura
- Zlepšení error handlingu
- OpenAPI dokumentace
- **Přidáno 17+ nových API endpoints:**
  - Fun APIs: Joke, Cat Facts, Dog, Advice, Quotes, Chuck Norris, Bored, Fox
  - Data APIs: Crypto (CoinGecko), Countries, Numbers, Trivia, ISS Tracker
  - Czech APIs: ČNB Kurzy, RUIAN, Zásilkovna
  - Utility APIs: UUID Generator
- Rate limiting implementace (100 req/min per IP)
- Odebrání cache z náhodných API (Joke, Cat Facts, atd.)
- Přidání testů pro všechny nové API
- Komplexní dokumentace všech endpointů

### v1.0.0
- Původní verze s PHP 5.4 a Nette 2.3

---

## 📧 Kontakt

- **Autor:** LuRy <lury@lury.cz>
- **Refaktoring:** pLBOT-API v2.0 Team
- **Repositář:** [GitHub](https://github.com/Chatujme/pLBOT-API)

---

## 📄 Licence

MIT, BSD-3-Clause, GPL-2.0, GPL-3.0
