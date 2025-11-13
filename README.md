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

Analýza datových zdrojů: **[docs/DATA_SOURCES_ANALYSIS.md](docs/DATA_SOURCES_ANALYSIS.md)**

---

## 🌐 API Endpointy (Quick Start)

### Svátky
```bash
GET /svatky           # Všechny dny
GET /svatky/dnes      # Dnešní svátek
GET /svatky/zitra     # Zítřejší svátek
```

### Počasí
```bash
GET /pocasi                    # Pro Prahu (všechny dny)
GET /pocasi/dnes               # Dnes pro Prahu
GET /pocasi?mesto=brno         # Pro Brno
GET /pocasi/zitra?mesto=plzen  # Zítra pro Plzeň
```

### Horoskopy
```bash
GET /horoskop/lev      # Horoskop pro lva
GET /horoskop/stir     # Podporuje i bez diakritiky
GET /horoskop/vodnář   # I s diakritikou
```

### TV Program
```bash
GET /tv           # Seznam stanic
GET /tv/vse       # Aktuální program všech stanic
GET /tv/nova      # Aktuální program TV Nova
GET /tv/ct1       # Aktuální program ČT1
```

### Místnost (Chatujme.cz)
```bash
GET /mistnost/{id}    # Info o místnosti
```

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