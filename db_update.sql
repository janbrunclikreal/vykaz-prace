-- Aktualizace tabulky uživatelů o sloupec s rolí
ALTER TABLE `janbrunclik_vykaz_prace_uzivatele` 
ADD COLUMN `role` VARCHAR(20) NOT NULL DEFAULT 'employee' AFTER `heslo`;

-- Nastavení role admin pro prvního uživatele (pokud existuje)
UPDATE `janbrunclik_vykaz_prace_uzivatele` 
SET `role` = 'admin' 
ORDER BY `id` ASC 
LIMIT 1;
