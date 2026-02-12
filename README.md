# Výkaz práce

Jednoduchý webový systém pro správu a generování výkazů práce zaměstnanců. Aplikace umožňuje správu uživatelů, evidence pracovních směn a generování PDF reportů.

## Verze

3.2

## Funkce

- **Správa uživatelů**: Přidávání, úprava a mazání zaměstnanců s jejich osobními údaji
- **Evidence pracovních směn**: Záznamy o začátku a konci směn, poznámky a označení nočních směn
- **Generování PDF výkazů**: Automatické generování měsíčních výkazů práce s přehlednou tabulkou
- **Filtrování dat**: Možnost filtrovat záznamy podle měsíce, roku a zaměstnance
- **Responzivní design**: Optimalizováno pro použití na desktopových i mobilních zařízeních

## Požadavky

- PHP 8.3.21 nebo vyšší
- MySQL/MariaDB databáze
- Composer (pro instalaci TCPDF)
- Webový server (Apache, Nginx, atd.)

## Instalace

1. **Klonování repozitáře**:
   ```bash
   git clone https://github.com/vas-uzivatel/vykaz-prace.git
   cd vykaz-prace
   ```

2. **Instalace závislostí**:
   ```bash
   composer install
   ```

3. **Nastavení databáze**:
   - Vytvořte databázi v MySQL/MariaDB
   - Upravte soubor `config.php` s přihlašovacími údaji k databázi
   - Spusťte SQL skript pro vytvoření tabulek (viz sekce Databáze)

4. **Nastavení webového serveru**:
   - Nasměrujte váš webový server na adresář s projektem
   - Ujistěte se, že PHP má oprávnění zapisovat do adresáře

## Databáze

Vytvořte tabulky pomocí následujícího SQL skriptu:

```sql
-- Tabulka pro uživatele
CREATE TABLE IF NOT EXISTS `janbrunclik_vykaz_prace_uzivatele` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jmeno` varchar(50) NOT NULL,
  `prijmeni` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `heslo` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabulka pro pracovní výkazy
CREATE TABLE IF NOT EXISTS `janbrunclik_vykaz_prace_pracovni_vykaz` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_zamestnance` int(11) NOT NULL,
  `datum` date NOT NULL,
  `cas_zacatku` time NOT NULL DEFAULT '07:00:00',
  `cas_konce` time NOT NULL DEFAULT '19:00:00',
  `poznamka` varchar(255) DEFAULT NULL,
  `noni` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_zamestnance` (`id_zamestnance`),
  CONSTRAINT `janbrunclik_vykaz_prace_pracovni_vykaz_ibfk_1` FOREIGN KEY (`id_zamestnance`) REFERENCES `janbrunclik_vykaz_prace_uzivatele` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

1. **Generování PDF výkazu**:
   - Vyberte zaměstnance, měsíc a rok
   - Klikněte na "Generovat PDF"
   - Systém automaticky stáhne PDF soubor s výkazem práce

2. **Správa dat**:
   - Přidejte nové záznamy o pracovních směnách
   - Upravte existující záznamy
   - Smažte nepotřebné záznamy
   - Filtrování dat podle různých kritérií

3. **Správa uživatelů**:
   - Přidejte nové zaměstnance
   - Upravte osobní údaje existujících zaměstnanců
   - Spravujte přístupové údaje

### Výchozí hodnoty

- Výchozí začátek směny: 07:00:00
- Výchozí konec směny: 19:00:00
- Výpočet odpracovaných hodin automaticky odečítá 0.5 hodiny pauzy

## Struktura souborů

```
vykaz-prace/
├── index.php              # Hlavní soubor aplikace
├── config.php             # Konfigurační soubor databáze
├── composer.json          # Konfigurace Composer
├── README.md              # Tento soubor
└── vendor/                # Instalované závislosti (TCPDF)
```

## Bezpečnost

- Všechny vstupy jsou validovány a ošetřeny proti SQL injection pomocí prepared statements
- Hesla jsou bezpečně hashována pomocí funkce password_hash()
- Aplikace používá UTF-8 kódování pro správné zobrazení českých znaků

## Kompatibilita

Aplikace je plně funkční v prostředí Android KSWEB s PHP 8.3.21.

## Podpora

V případě problémů nebo dotazů kontaktujte:
- Email: janbrunclikreal@gmail.com

## Licence

Copyright (C) Jan Brunclík