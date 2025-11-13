# Testování pLBOT API v2.0

## Příprava prostředí

### 1. Instalace závislostí

```bash
composer install
```

### 2. Nastavení konfigurace

Vytvořte `app/config/config.local.neon` (volitelné):

```neon
parameters:

services:
    # Případné lokální override
```

### 3. Nastavení Apache

Příklad VirtualHost konfigurace:

```apache
<VirtualHost *:80>
    ServerName api.plbot.local
    DocumentRoot /path/to/pLBOT-API/www

    <Directory /path/to/pLBOT-API/www>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/plbot-api-error.log
    CustomLog ${APACHE_LOG_DIR}/plbot-api-access.log combined
</VirtualHost>
```

Nezapomeňte přidat do `/etc/hosts`:
```
127.0.0.1 api.plbot.local
```

---

## Testování API Endpointů

### Automatické testování

Použijte přiložený test script:

```bash
php tests/test-endpoints.php
```

### Manuální testování pomocí cURL

#### 1. Svátky API

**Všechny dny:**
```bash
curl http://api.plbot.local/svatky
```

Očekávaná odpověď:
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

**Konkrétní den:**
```bash
curl http://api.plbot.local/svatky/dnes
```

Očekávaná odpověď:
```json
{
  "data": "Tibor"
}
```

---

#### 2. Počasí API

**Praha - všechny dny:**
```bash
curl http://api.plbot.local/pocasi
```

**Brno - dnes:**
```bash
curl http://api.plbot.local/pocasi/dnes?mesto=brno
```

**Plzeň - zítra:**
```bash
curl "http://api.plbot.local/pocasi/zitra?mesto=plzen"
```

Očekávaná odpověď:
```json
{
  "data": {
    "datum": "2025-11-14",
    "predpoved": "Polojasno",
    "nyni": "12°C",
    "den": "15°C",
    "noc": "8°C",
    "pro": "Pro Brno"
  }
}
```

---

#### 3. Horoskopy API

**Lev:**
```bash
curl http://api.plbot.local/horoskop/lev
```

**Štír (bez diakritiky):**
```bash
curl http://api.plbot.local/horoskop/stir
```

**Vodnář (s diakritikou):**
```bash
curl "http://api.plbot.local/horoskop/vodna%C5%99"
```

Očekávaná odpověď:
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

#### 4. TV Program API

**Seznam stanic:**
```bash
curl http://api.plbot.local/tv
```

**Všechny aktuální programy:**
```bash
curl http://api.plbot.local/tv/vse
```

**Konkrétní stanice (ČT1):**
```bash
curl http://api.plbot.local/tv/ct1
```

**Nova:**
```bash
curl http://api.plbot.local/tv/nova
```

Očekávaná odpověď:
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

#### 5. Místnost API (Chatujme.cz)

**Informace o místnosti:**
```bash
curl http://api.plbot.local/mistnost/12345
```

Očekávaná odpověď (úspěch):
```json
{
  "data": {
    "mistnost": "Název místnosti",
    "popis": "Popis",
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

Očekávaná odpověď (404):
```json
{
  "error": {
    "message": "Místnost 99999 nebyla nalezena",
    "code": 404
  }
}
```

---

## Testování Error Responses

### 400 Bad Request

```bash
curl http://api.plbot.local/horoskop/
```

Očekáváno:
```json
{
  "error": {
    "message": "Není zadáno znamení",
    "code": 400
  }
}
```

### 404 Not Found

```bash
curl http://api.plbot.local/tv/neexistujici-stanice
```

Očekáváno:
```json
{
  "error": {
    "message": "Stanice neexistujici-stanice neexistuje v seznamu televizí",
    "code": 404
  }
}
```

### 500 Internal Server Error

Pokud dojde k interní chybě (např. nedostupný externí API):

```json
{
  "error": {
    "message": "Nepodařilo se získat data o počasí: ...",
    "code": 500
  }
}
```

---

## Performance Testing

### Testování cache

**První request (necachovaný):**
```bash
time curl http://api.plbot.local/svatky/dnes
```

**Druhý request (z cache):**
```bash
time curl http://api.plbot.local/svatky/dnes
```

Druhý request by měl být výrazně rychlejší.

---

## CORS Testing

```bash
curl -H "Origin: https://example.com" \
     -H "Access-Control-Request-Method: GET" \
     -X OPTIONS \
     http://api.plbot.local/svatky/dnes -i
```

Očekávané headers:
```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
```

---

## Debugging

### Zapnout Tracy bar

V `app/config/config.local.neon`:

```neon
parameters:
    debugMode: true
```

### Logování

Logy jsou v `errorlog/` složce.

### PHP error log

```bash
tail -f /var/log/apache2/plbot-api-error.log
```

---

## Checklist kompletního testování

- [ ] Svátky - všechny dny
- [ ] Svátky - konkrétní den
- [ ] Počasí - Praha
- [ ] Počasí - jiné město
- [ ] Počasí - všechny dny
- [ ] Počasí - konkrétní den
- [ ] Horoskop - všechna znamení (12x)
- [ ] Horoskop - s diakritikou
- [ ] Horoskop - bez diakritiky
- [ ] TV - seznam stanic
- [ ] TV - všechny programy
- [ ] TV - konkrétní stanice (min. 5 stanic)
- [ ] Místnost - existující ID
- [ ] Místnost - neexistující ID (404)
- [ ] Error responses (400, 404, 500)
- [ ] CORS headers
- [ ] Cache funguje
- [ ] Response time < 2s (necachované)
- [ ] Response time < 100ms (cachované)

---

## Known Issues & Troubleshooting

### Issue: 500 Error při všech requestech

**Řešení:**
1. Zkontrolujte PHP error log
2. Ověřte, že `composer install` proběhl úspěšně
3. Zkontrolujte, že cache složky jsou writable: `chmod -R 777 temp/`

### Issue: TV program vrací prázdná data

**Řešení:**
- XMLTV zdroj může být dočasně nedostupný
- Zkontrolujte URL: `http://xmltv.tvpc.cz/xmltv.xml`
- Cache může obsahovat stará data, smažte: `rm -rf temp/cache/*`

### Issue: Svátky/Počasí/Horoskopy vracejí chybu

**Řešení:**
- Externí weby mohou změnit HTML strukturu
- Zkontrolujte, zda jsou weby dostupné
- Možná je potřeba aktualizovat parsery v Services

---

## Automatizované testy (volitelné)

Pro continuous integration můžete použít:

```bash
# PHPUnit testy
vendor/bin/phpunit tests/

# PHPStan static analysis
vendor/bin/phpstan analyse app/
```

---

## Production Checklist

Před nasazením do produkce:

- [ ] `debugMode: false` v config.neon
- [ ] SSL certifikát nakonfigurován
- [ ] Rate limiting implementován (pokud potřeba)
- [ ] Monitoring nastavený
- [ ] Backup strategie definována
- [ ] Error logy rotované
- [ ] Cache strategie ověřena
