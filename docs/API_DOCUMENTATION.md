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

## 🏗️ Architektura

### Struktura projektu

```
app/
├── Controllers/        # Apitte API controllers
│   ├── BaseController.php
│   ├── SvatkyController.php
│   ├── PocasiController.php
│   ├── HoroskopyController.php
│   ├── TvController.php
│   └── MistnostController.php
├── Services/          # Business logic services
│   ├── HttpClientService.php
│   ├── SvatkyService.php
│   ├── PocasiService.php
│   ├── HoroskopyService.php
│   ├── TvProgramService.php
│   └── MistnostService.php
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

Všechny služby používají Nette Cache:
- Svátky: 1 den
- Počasí: 1 den
- Horoskopy: 1 den
- TV Program: 1 hodina
- Místnost: 5 minut

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
