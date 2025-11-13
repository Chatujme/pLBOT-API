# Nová API v pLBOT API v2.0

Tento dokument popisuje všechna nově přidaná API v rámci modernizace pLBOT API na verzi 2.0.

## 📊 Přehled

Ve verzi 2.0 bylo přidáno **17 nových API endpoints**, čímž se celkový počet API zvýšil z původních 6 na **22+**.

---

## 🎯 Kategorie nových API

### 1. Fun APIs (8 nových)

Zábavná API pro zpestření IRC konverzací.

#### Joke API
- **Endpoint:** `/joke/`, `/joke/programming`
- **Zdroj:** JokeAPI (jokeapi.dev)
- **Popis:** Náhodné vtipy z různých kategorií včetně programátorských vtipů
- **Užitečnost pro IRC bot:**
  - Příkaz `!joke` pro náhodný vtip
  - `!joke programming` pro programátorský vtip
  - Odlehčení nálady v channelu
  - Podporuje filtrování bezpečného obsahu

**Příklad použití v IRC:**
```
<user> !joke
<bot> Why do programmers prefer dark mode? Because light attracts bugs!
```

---

#### Cat Facts API
- **Endpoint:** `/catfact/`
- **Zdroj:** Cat Facts API
- **Popis:** Náhodné zajímavosti o kočkách
- **Užitečnost pro IRC bot:**
  - Příkaz `!catfact` pro zajímavost o kočkách
  - Skvělé pro uživatele, kteří milují kočky
  - Edukativní obsah

**Příklad použití v IRC:**
```
<user> !catfact
<bot> Cats sleep 70% of their lives.
```

---

#### Dog CEO API
- **Endpoint:** `/dog/`, `/dog/breeds`
- **Zdroj:** Dog CEO's Dog API
- **Popis:** Náhodné obrázky psů, včetně konkrétních plemen
- **Užitečnost pro IRC bot:**
  - `!dog` pro náhodný obrázek psa
  - `!dog husky` pro obrázek konkrétního plemene
  - `!dogbreeds` pro seznam všech plemen
  - Vizuální obsah (pokud IRC klient podporuje)

**Příklad použití v IRC:**
```
<user> !dog corgi
<bot> 🐕 Corgi: https://images.dog.ceo/breeds/corgi/n02113186_123.jpg
```

---

#### Advice Slip API
- **Endpoint:** `/advice/`
- **Zdroj:** Advice Slip JSON API
- **Popis:** Náhodné životní rady
- **Užitečnost pro IRC bot:**
  - `!advice` pro náhodnou radu
  - Motivační a inspirativní obsah
  - Lehký, nezávazný formát

**Příklad použití v IRC:**
```
<user> !advice
<bot> 💡 Advice: It is easy to sit up and take notice. What's difficult is getting up and taking action.
```

---

#### Quotes API
- **Endpoint:** `/quotes/`, `/quotes/multiple`
- **Zdroj:** Quotable API
- **Popis:** Inspirativní citáty slavných osobností
- **Užitečnost pro IRC bot:**
  - `!quote` pro náhodný citát
  - `!quotes 5` pro 5 citátů najednou
  - Možnost filtrování podle témat (wisdom, success, life)
  - Inspirace a motivace

**Příklad použití v IRC:**
```
<user> !quote
<bot> 💭 "The only way to do great work is to love what you do." - Steve Jobs
```

---

#### Chuck Norris API
- **Endpoint:** `/chucknorris/`, `/chucknorris/categories`
- **Zdroj:** Chuck Norris API (api.chucknorris.io)
- **Popis:** Chuck Norris vtipy
- **Užitečnost pro IRC bot:**
  - `!chuck` pro náhodný Chuck Norris vtip
  - `!chuck dev` pro programátorský Chuck Norris vtip
  - Klasické internetové vtipy
  - Filtrování podle kategorií

**Příklad použití v IRC:**
```
<user> !chuck
<bot> 💪 Chuck Norris can delete the Recycling Bin.
```

---

#### Bored API
- **Endpoint:** `/bored/`, `/bored/activity/{key}`
- **Zdroj:** Bored API
- **Popis:** Návrhy aktivit pro nudu
- **Užitečnost pro IRC bot:**
  - `!bored` když nevíte co dělat
  - Filtrování podle typu (social, education, cooking)
  - Filtrování podle počtu lidí
  - Praktické nápady

**Příklad použití v IRC:**
```
<user> !bored
<bot> 💡 Activity: Learn Express.js (Education, 1 person, Low cost)
<user> !bored social 2
<bot> 💡 Activity: Have a picnic with a friend (Social, 2 people)
```

---

#### Fox API
- **Endpoint:** `/fox/`
- **Zdroj:** randomfox.ca
- **Popis:** Náhodné obrázky lišek
- **Užitečnost pro IRC bot:**
  - `!fox` pro roztomilý obrázek lišky
  - Alternativa k cat/dog API
  - Možnost získat více obrázků najednou

**Příklad použití v IRC:**
```
<user> !fox
<bot> 🦊 Fox: https://randomfox.ca/images/23.jpg
```

---

### 2. Data APIs (5 nových)

API pro získávání užitečných dat a informací.

#### Crypto (CoinGecko) API
- **Endpoint:** `/crypto/price/{coin}`, `/crypto/popular`
- **Zdroj:** CoinGecko API
- **Popis:** Aktuální ceny kryptoměn
- **Užitečnost pro IRC bot:**
  - `!btc` pro cenu Bitcoinu
  - `!eth czk` pro cenu Etherea v CZK
  - `!crypto` pro přehled populárních kryptoměn
  - Aktuální finanční informace
  - Cache 5 minut (dostatečně aktuální)

**Příklad použití v IRC:**
```
<user> !btc
<bot> 💰 Bitcoin: $45,123.50 USD (↑2.5%)
<user> !eth czk
<bot> 💰 Ethereum: 73,215 CZK (↑1.8%)
```

---

#### REST Countries API
- **Endpoint:** `/countries/{country}`, `/countries/region/{region}`, `/countries/all`
- **Zdroj:** REST Countries API
- **Popis:** Detailní informace o zemích světa
- **Užitečnost pro IRC bot:**
  - `!country CZ` pro informace o České republice
  - `!countries europe` pro země v Evropě
  - Hlavní města, jazyky, měny, populace
  - Edukativní obsah

**Příklad použití v IRC:**
```
<user> !country CZ
<bot> 🇨🇿 Czechia: Capital: Prague | Population: 10.51M | Region: Europe | Currency: CZK
```

---

#### Numbers API
- **Endpoint:** `/numbers/{number}`, `/numbers/today`
- **Zdroj:** NumbersAPI
- **Popis:** Zajímavosti o číslech a datech
- **Užitečnost pro IRC bot:**
  - `!number 42` pro zajímavost o čísle 42
  - `!number random` pro náhodné číslo
  - `!numbertoday` pro historický fakt o dnešním dni
  - Trivia, matematické fakty, historické události

**Příklad použití v IRC:**
```
<user> !number 42
<bot> 🔢 42 is the answer to the Ultimate Question of Life, the Universe, and Everything.
<user> !numbertoday
<bot> 📅 November 13th is the day in 1985 that...
```

---

#### ISS Tracker API
- **Endpoint:** `/iss/position`, `/iss/pass`, `/iss/astronauts`
- **Zdroj:** Open Notify API
- **Popis:** Sledování Mezinárodní vesmírné stanice
- **Užitečnost pro IRC bot:**
  - `!iss` pro aktuální polohu ISS
  - `!isspass praha` pro časy přeletů nad městem
  - `!astronauts` pro seznam lidí ve vesmíru
  - Zajímavý vědecký obsah

**Příklad použití v IRC:**
```
<user> !iss
<bot> 🛰️ ISS Position: Lat: 50.08°, Lon: 14.44° (above Prague) | Alt: 408.5 km | Speed: 27,600 km/h
<user> !astronauts
<bot> 👨‍🚀 7 people in space: Jasmin Moghbeli (ISS), Andreas Mogensen (ISS), ...
```

---

#### Trivia API
- **Endpoint:** `/trivia/`, `/trivia/categories`
- **Zdroj:** Open Trivia Database
- **Popis:** Trivia otázky pro kvízy
- **Užitečnost pro IRC bot:**
  - `!trivia` pro kvízovou otázku
  - `!trivia easy` pro lehké otázky
  - Možnost spuštění IRC kvízu
  - Různé kategorie a obtížnosti

**Příklad použití v IRC:**
```
<user> !trivia
<bot> ❓ [General Knowledge - Easy] What is the capital of France?
<bot> A) London  B) Paris  C) Berlin  D) Madrid
<user> B
<bot> ✅ Correct! Paris is the capital of France.
```

---

### 3. Czech APIs (3 nové)

API specifická pro český trh a česká data.

#### ČNB Kurzy API
- **Endpoint:** `/cnb/kurzy`, `/cnb/kurzy/{mena}`, `/cnb/prevod`
- **Zdroj:** Česká národní banka
- **Popis:** Oficiální kurzy měn ČNB
- **Užitečnost pro IRC bot:**
  - `!kurz USD` pro aktuální kurz dolaru
  - `!kurzy` pro přehled všech kurzů
  - `!prevod 100 USD CZK` pro převod měn
  - Důležité pro české uživatele
  - Aktualizováno 1x denně po 14:30

**Příklad použití v IRC:**
```
<user> !kurz EUR
<bot> 💶 EUR: 25.123 CZK (1 EUR = 25.123 CZK) | ČNB 13.11.2025
<user> !prevod 100 USD CZK
<bot> 💱 100 USD = 2,345.60 CZK (rate: 23.456)
```

---

#### RUIAN API
- **Endpoint:** `/ruian/obce`, `/ruian/ulice`, `/ruian/adresy`, `/ruian/validate`
- **Zdroj:** RUIAN (Registr územní identifikace, adres a nemovitostí)
- **Popis:** Vyhledávání a validace českých adres
- **Užitečnost pro IRC bot:**
  - `!obec Praha` pro vyhledání obce
  - `!ulice Karlova` pro vyhledání ulic
  - `!adresa Karlova 1 Praha` pro validaci adresy
  - Pomoc s českými adresami
  - GPS souřadnice

**Příklad použití v IRC:**
```
<user> !adresa Karlova 1 Praha
<bot> 📍 Karlova 1, 110 00 Praha 1 ✅ (Valid) | GPS: 50.086, 14.414
```

---

#### Zásilkovna API
- **Endpoint:** `/zasilkovna/track/{packageId}`
- **Zdroj:** Zásilkovna (Packeta)
- **Popis:** Sledování balíků přes Zásilkovnu
- **Užitečnost pro IRC bot:**
  - `!balik Z123456789` pro sledování balíku
  - Informace o stavu doručení
  - Historie stavů zásilky
  - Praktické pro české e-shopy

**Příklad použití v IRC:**
```
<user> !balik Z123456789
<bot> 📦 Balík Z123456789: Doručeno ✅ (10.11.2025 14:30)
<bot> Výdejní místo: Zásilkovna Praha 1, Karlova 1
```

---

### 4. Utility APIs (1 nové)

Pomocné API pro generování dat.

#### UUID Generator API
- **Endpoint:** `/uuid/`, `/uuid/validate/{uuid}`, `/uuid/nil`
- **Zdroj:** Vlastní implementace (PHP)
- **Popis:** Generování a validace UUID
- **Užitečnost pro IRC bot:**
  - `!uuid` pro vygenerování UUID
  - `!uuid 5` pro 5 UUID najednou
  - Validace UUID formátu
  - Užitečné pro vývojáře

**Příklad použití v IRC:**
```
<user> !uuid
<bot> 🔑 UUID: 550e8400-e29b-41d4-a716-446655440000 (v4)
```

---

## 🎨 Příklady bot příkazů

Zde jsou příklady jak by mohly vypadat IRC bot příkazy využívající nová API:

### Zábavné příkazy
- `!joke` - Náhodný vtip
- `!chuck` - Chuck Norris vtip
- `!catfact` - Zajímavost o kočkách
- `!dog` - Obrázek psa
- `!fox` - Obrázek lišky
- `!advice` - Životní rada
- `!quote` - Inspirativní citát
- `!bored` - Nápad na aktivitu

### Informační příkazy
- `!btc` - Cena Bitcoinu
- `!country CZ` - Info o zemi
- `!number 42` - Zajímavost o čísle
- `!iss` - Poloha ISS
- `!astronauts` - Lidé ve vesmíru
- `!trivia` - Kvízová otázka

### České příkazy
- `!kurz EUR` - Kurz eura
- `!prevod 100 USD CZK` - Převod měny
- `!obec Praha` - Vyhledání obce
- `!adresa` - Validace adresy
- `!balik Z123` - Sledování balíku

### Utility příkazy
- `!uuid` - Vygenerování UUID

---

## 📈 Statistiky

### Rozložení nových API podle typu:
- **Fun APIs:** 8 (47%)
- **Data APIs:** 5 (29%)
- **Czech APIs:** 3 (18%)
- **Utility APIs:** 1 (6%)

### Rozložení podle cachování:
- **Bez cache (random):** 7 APIs (Cat Facts, Dog, Advice, Quotes, Chuck Norris, Bored, Fox)
- **Krátké cache (1-5 min):** 2 APIs (Crypto 5 min, ISS Position 1 min)
- **Střední cache (1 hod):** 3 APIs (Joke, Trivia, Zásilkovna)
- **Dlouhé cache (1+ den):** 5 APIs (ČNB 1 den, Countries 1 týden, Numbers 1 den, RUIAN 1 týden)

### Rozložení podle zdroje:
- **Externí public API:** 14 (82%)
- **České API:** 2 (12%)
- **Vlastní implementace:** 1 (6%)

---

## 🚀 Budoucí rozšíření

Potenciální nová API pro další verze:

### Plánované Fun APIs:
- Cat API (TheCatAPI) - podobné jako Dog API
- Meme API - náhodné memes
- Dad Jokes API - vtipné "dad jokes"
- Random User API - generování náhodných uživatelů

### Plánované Data APIs:
- Weather API (OpenWeatherMap) - modernější než Centrum.cz
- GitHub API - informace o repozitářích
- Reddit API - top posty z subredditů
- News API - zpravodajství

### Plánované Czech APIs:
- ČHMÚ API - oficiální počasí z ČHMÚ
- České dráhy API - vlakové spoje
- MHD API - městská hromadná doprava
- E15 burza API - kurzy akcií

---

## 💡 Výhody nových API

1. **Rozmanitost:** 17 různých API pokrývá širokou škálu použití
2. **Zábava:** 8 fun API zpestří konverzace v IRC channelu
3. **Užitečnost:** Crypto, Countries, ČNB kurzy poskytují reálná data
4. **Lokalizace:** 3 české API pro místní uživatele
5. **Kvalita:** Všechna API jsou z důvěryhodných zdrojů
6. **Performance:** Správné cachování zajišťuje rychlost
7. **Rate limiting:** Ochrana proti zneužití
8. **Dokumentace:** Kompletní dokumentace všech endpointů

---

## 🔗 Odkazy

- **Hlavní dokumentace:** [API_DOCUMENTATION.md](API_DOCUMENTATION.md)
- **Analýza zdrojů:** [DATA_SOURCES_ANALYSIS.md](DATA_SOURCES_ANALYSIS.md)
- **GitHub:** [pLBOT-API](https://github.com/Chatujme/pLBOT-API)

---

*Vytvořeno: 13.11.2025 | pLBOT API v2.0*
