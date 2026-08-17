# Výkaz práce (v4.0)

Moderní, bezpečná a modulární webová aplikace pro správu a generování výkazů práce zaměstnanců. Projekt byl zrefaktorován z monolitického PHP do podoby **Single Page Application (SPA)** s odděleným **REST API** na backendu a asynchronní komunikací na frontendu.

## Hlavní přednosti a nové funkce

- **Moderní architektura (OOP backend):** Kód je rozčleněn podle osvědčených návrhových vzorů (Controllers, Services, Repositories, Database Singleton a Router).
- **Asynchronní klientský JavaScript (Fetch API):** Veškerá komunikace s API probíhá na pozadí. Stránka se po přihlášení nikdy celá nepřenačítá, což zajišťuje bleskovou odezvu.
- **Hromadná editace směn:** Nový tabulkový editor pro celý měsíc najednou. Zaměstnanec nebo administrátor může pohodlně vyplnit či upravit časy pro všechny dny v měsíci a uložit je jedním tlačítkem. Smazání směny se provádí jednoduše vymazáním časů.
- **Oprava výpočtu nočních směn (přechod přes půlnoc):** Výpočet odpracovaných hodin korektně funguje i pro směny přesahující do dalšího dne (např. 22:00 - 06:00). Započtení 24h korekce a automatické odečtení 0,5 hodiny pauzy je aplikováno jak v grafech/tabulkách, tak v generovaném PDF.
- **Bezpečnost na prvním místě:**
  - Hesla jsou bezpečně hashována pomocí `password_hash()` a ověřována přes `password_verify()`.
  - Ochrana proti CSRF útokům prostřednictvím hlavičky `X-CSRF-Token` pro všechny změnové požadavky.
  - Všechny vstupy jsou validovány, sanitovány a databázové dotazy jsou chráněny pomocí prepared statements (PDO).
  - Přihlašování je založeno na bezpečné PHP Session s nastavením `httponly` a `SameSite=Strict`.
- **Kontrola rolí (RBAC):**
  - **Běžný zaměstnanec (`employee`):** Vidí, spravuje a stahuje PDF výkazy výhradně pro své vlastní směny.
  - **Administrátor (`admin`):** Má přístup k úplné správě uživatelů (přidávání, úprava, mazání, změna rolí a hesel) a může spravovat a generovat výkazy pro všechny zaměstnance v systému.

---

## Přehled REST API Endpointů

Všechny API požadavky vrací standardizované odpovědi ve formátu JSON.

| Metoda | Endpoint | Popis | Vyžaduje roli |
| :--- | :--- | :--- | :--- |
| **POST** | `/api/auth/login` | Přihlášení uživatele (vrací údaje a CSRF token) | Nepřihlášený |
| **POST** | `/api/auth/logout` | Odhlášení uživatele | Přihlášený |
| **GET** | `/api/auth/me` | Ověření přihlášení a získání detailu uživatele | Přihlášený |
| **GET** | `/api/shifts` | Seznam směn (podporuje filtry `employee_id`, `month`, `year`) | Přihlášený (role omezena) |
| **POST** | `/api/shifts` | Vytvoření nové samostatné směny | Přihlášený (role omezena) |
| **PUT** | `/api/shifts/{id}` | Aktualizace konkrétní směny | Přihlášený (role omezena) |
| **DELETE** | `/api/shifts/{id}` | Smazání konkrétní směny | Přihlášený (role omezena) |
| **POST** | `/api/shifts/bulk` | Hromadné uložení/úprava směn pro celý měsíc v transakci | Přihlášený (role omezena) |
| **GET** | `/api/users` | Seznam uživatelů v systému | Přihlášený (role omezena) |
| **GET** | `/api/users/{id}` | Detail konkrétního uživatele | Pouze administrátor |
| **POST** | `/api/users` | Vytvoření nového uživatele | Pouze administrátor |
| **PUT** | `/api/users/{id}` | Aktualizace uživatele (včetně volitelné změny hesla) | Pouze administrátor |
| **DELETE** | `/api/users/{id}` | Smazání uživatele (chráněno proti smazání sebe sama a uživatelů se směnami) | Pouze administrátor |

---

## Struktura projektu

Projekt je navržen s ohledem na čistou architekturu, modularitu a snadnou přenositelnost.

```text
vykaz-prace/
├── composer.json               # Konfigurace Composer závislostí
├── config.php                  # Konfigurační soubor připojení k databázi
├── index.php                   # Jednotný vstupní bod (API router / SPA šablona)
├── migrate.php                 # PHP skript pro spuštění DB migrací
├── db.sql                      # Základní SQL schéma databáze
├── db_update.sql               # SQL migrační skript (sloupec 'role')
├── css/
│   └── style.css               # Moderní responzivní Mobile-first design
├── js/
│   ├── api.js                  # Asynchronní API klient (včetně CSRF ochrany)
│   └── app.js                  # Klientská správa stavu, vykreslování tabulek a modalů
└── src/                        # OOP Zdrojové kódy backendu (PSR-4)
    ├── Core/
    │   ├── Database.php        # Databázové připojení přes Singleton (PDO)
    │   ├── Router.php          # REST API Router
    │   ├── Session.php         # Správa relací, přihlášení a autorizace
    │   └── CSRF.php            # Generování a ověřování CSRF tokenů
    ├── Repository/
    │   ├── UserRepository.php  # Databázové operace pro tabulku uživatelů
    │   └── ShiftRepository.php # Databázové operace pro tabulku směn
    ├── Service/
    │   ├── ShiftService.php    # Business logika směn (výpočet nočních, bulk save transakce)
    │   └── PdfService.php      # Služba pro generování PDF výkazu práce (TCPDF)
    └── Controller/
        ├── BaseController.php  # Společný předek kontrolerů (validace, JSON odpovědi)
        ├── AuthController.php  # Přihlašování a odhlašování uživatelů
        ├── ShiftController.php # Správa směn a bulk operace
        ├── UserController.php  # Kompletní administrace uživatelů
        └── PdfController.php   # Generátor PDF ke stažení
```

---

## Požadavky a instalace

### Požadavky:
- PHP 8.2+
- MySQL/MariaDB databáze
- Composer (pro stažení knihovny TCPDF)

### Postup instalace:
1. **Klonování repozitáře:**
   ```bash
   git clone https://github.com/janbrunclikreal/vykaz-prace.git
   cd vykaz-prace
   ```
2. **Instalace knihoven přes Composer:**
   ```bash
   composer install
   ```
3. **Konfigurace databáze:**
   - Vytvořte novou databázi v MySQL/MariaDB.
   - Importujte soubor `db.sql`.
   - Upravte soubor `config.php` s přihlašovacími údaji k vaší databázi.
4. **Spuštění databázových migrací:**
   Spusťte migrační skript pro přidání sloupce `role`:
   ```bash
   php migrate.php
   ```
5. **Výchozí administrátorský účet:**
   Migrační skript automaticky nastaví prvnímu uživateli v databázi roli `admin`.

---

## Podpora a licence

V případě dotazů, chyb nebo žádostí o podporu kontaktujte vývojáře:
- **E-mail:** janbrunclikreal@gmail.com
- **Autor:** Jan Brunclík

Copyright (C) Jan Brunclík
