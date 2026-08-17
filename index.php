<?php

// Zaregistrování PSR-4 autoloaderu pro třídy pod App\ namespace
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Načtení konfigurace a závislostí
require_once 'config.php';
require_once 'vendor/autoload.php';

use App\Core\Router;
use App\Controller\AuthController;
use App\Controller\ShiftController;
use App\Controller\UserController;
use App\Controller\PdfController;

// Rozpoznání požadavku
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Odříznutí query parametrů pro směrování v routeru
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// 1. API endpointy
if (str_starts_with($requestPath, '/api/')) {
    $router = new Router();
    
    // Auth endpoints
    $router->add('POST', '/api/auth/login', [AuthController::class, 'login']);
    $router->add('POST', '/api/auth/logout', [AuthController::class, 'logout']);
    $router->add('GET', '/api/auth/me', [AuthController::class, 'me']);
    
    // Shifts endpoints
    $router->add('GET', '/api/shifts', [ShiftController::class, 'list']);
    $router->add('POST', '/api/shifts', [ShiftController::class, 'store']);
    $router->add('PUT', '/api/shifts/{id}', [ShiftController::class, 'update']);
    $router->add('DELETE', '/api/shifts/{id}', [ShiftController::class, 'delete']);
    $router->add('POST', '/api/shifts/bulk', [ShiftController::class, 'bulkSave']);
    
    // Users endpoints
    $router->add('GET', '/api/users', [UserController::class, 'list']);
    $router->add('GET', '/api/users/{id}', [UserController::class, 'show']);
    $router->add('POST', '/api/users', [UserController::class, 'store']);
    $router->add('PUT', '/api/users/{id}', [UserController::class, 'update']);
    $router->add('DELETE', '/api/users/{id}', [UserController::class, 'delete']);
    
    $router->handle($requestMethod, $requestUri);
    exit;
}

// 2. Generování PDF
if (str_starts_with($requestPath, '/generate-pdf')) {
    $pdfController = new PdfController();
    $pdfController->generate();
    exit;
}

// 3. Hlavní šablona (Single Page Application)
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Výkaz práce</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

    <!-- AUTHENTICATION LAYOUT (Zobrazí se pro nepřihlášené) -->
    <div id="auth-layout" class="auth-container" style="display: none;">
        <div class="auth-card">
            <h2>Výkaz práce – Přihlášení</h2>
            <div id="login-alert"></div>
            <form id="login-form">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="login-email">E-mailová adresa:</label>
                    <input type="email" id="login-email" required placeholder="name@example.com">
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="login-password">Heslo:</label>
                    <input type="password" id="login-password" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Přihlásit se</button>
            </form>
        </div>
    </div>

    <!-- APPLICATION LAYOUT (Zobrazí se pro přihlášené) -->
    <div id="app-layout" style="display: none;">
        <header class="app-header">
            <h1>Výkaz práce</h1>
            <div class="user-info">
                <span id="user-display-name">Načítání...</span>
                <button id="logout-btn" class="btn btn-danger btn-sm">Odhlásit se</button>
            </div>
        </header>

        <div class="container">
            <div id="app-alert"></div>

            <!-- TABS (Záložky) -->
            <nav class="tabs">
                <button id="tab-shifts" class="tab-btn active">Správa směn</button>
                <button id="tab-bulk" class="tab-btn">Hromadná editace</button>
                <button id="tab-users" class="tab-btn admin-only" style="display: none;">Správa uživatelů</button>
            </nav>

            <!-- FILTRY (Společné pro směny a hromadný editor) -->
            <div id="global-filters" class="card">
                <div class="form-grid">
                    <div class="form-group" id="filter-employee-container">
                        <label for="filter-employee">Zaměstnanec:</label>
                        <select id="filter-employee"></select>
                    </div>
                    <div class="form-group">
                        <label for="filter-month">Měsíc:</label>
                        <select id="filter-month">
                            <option value="1">Leden</option>
                            <option value="2">Únor</option>
                            <option value="3">Březen</option>
                            <option value="4">Duben</option>
                            <option value="5">Květen</option>
                            <option value="6">Červen</option>
                            <option value="7">Červenec</option>
                            <option value="8">Srpen</option>
                            <option value="9">Září</option>
                            <option value="10">Říjen</option>
                            <option value="11">Listopad</option>
                            <option value="12">Prosinec</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="filter-year">Rok:</label>
                        <input type="number" id="filter-year" min="2000" max="2100">
                    </div>
                </div>
            </div>

            <!-- SECTION 1: SPRÁVA SMĚN (Seznam & Jednotlivé CRUD) -->
            <section id="section-shifts">
                <div class="card">
                    <div class="card-title">
                        <span>Přehled směn</span>
                        <div style="display: flex; gap: 10px;">
                            <button id="add-shift-btn" class="btn btn-success">Přidat směnu</button>
                            <button id="pdf-btn" class="btn btn-primary">Generovat PDF</button>
                        </div>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Zaměstnanec</th>
                                    <th>Datum</th>
                                    <th>Začátek</th>
                                    <th>Konec</th>
                                    <th>Celkem hodin</th>
                                    <th>Poznámka</th>
                                    <th>Noční</th>
                                    <th>Akce</th>
                                </tr>
                            </thead>
                            <tbody id="shifts-tbody">
                                <tr>
                                    <td colspan="9" style="text-align: center;">Načítání směn...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- SECTION 2: HROMADNÁ EDITACE (Tabulkový bulk editor) -->
            <section id="section-bulk" style="display: none;">
                <div class="card">
                    <div class="card-title">
                        <span>Hromadný tabulkový editor měsíce</span>
                        <button id="bulk-save-btn" class="btn btn-success">Uložit celý měsíc</button>
                    </div>
                    <p style="margin-bottom: 15px; font-size: 14px; color: var(--text-muted);">
                        Zde můžete vyplnit nebo upravit časy pro všechny dny v měsíci najednou. Pro smazání směny v daný den stačí vymazat čas začátku a konce. Žluté řádky značí víkendy.
                    </p>
                    <div class="table-container">
                        <table class="bulk-table">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Den</th>
                                    <th style="width: 20%;">Začátek</th>
                                    <th style="width: 20%;">Konec</th>
                                    <th style="width: 10%;">Noční</th>
                                    <th>Poznámka</th>
                                </tr>
                            </thead>
                            <tbody id="bulk-tbody">
                                <!-- Dynamicky generováno JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- SECTION 3: SPRÁVA UŽIVATELŮ (Admin only) -->
            <section id="section-users" style="display: none;">
                <div class="card">
                    <div class="card-title">
                        <span>Správa uživatelů</span>
                        <button id="add-user-btn" class="btn btn-success">Přidat uživatele</button>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Příjmení</th>
                                    <th>Jméno</th>
                                    <th>E-mail</th>
                                    <th>Telefon</th>
                                    <th>Role</th>
                                    <th>Akce</th>
                                </tr>
                            </thead>
                            <tbody id="users-tbody">
                                <!-- Dynamicky generováno JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- MODAL 1: SMĚNA (PŘIDÁNÍ/ÚPRAVA) -->
    <div id="shift-modal" class="modal-overlay">
        <div class="modal">
            <button class="modal-close">&times;</button>
            <h3 id="shift-modal-title" style="margin-bottom: 20px; font-size: 20px;">Přidat směnu</h3>
            <form id="shift-modal-form">
                <input type="hidden" id="shift-id">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="shift-employee">Zaměstnanec:</label>
                    <select id="shift-employee" required></select>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="shift-date">Datum:</label>
                    <input type="date" id="shift-date" required>
                </div>
                <div class="form-grid" style="margin-bottom: 15px;">
                    <div class="form-group">
                        <label for="shift-start">Čas začátku:</label>
                        <input type="time" id="shift-start" required>
                    </div>
                    <div class="form-group">
                        <label for="shift-end">Čas konce:</label>
                        <input type="time" id="shift-end" required>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="shift-note">Poznámka:</label>
                    <input type="text" id="shift-note" placeholder="Např. Trojická, Dovolená, Lékař...">
                </div>
                <div class="form-group checkbox" style="margin-bottom: 25px;">
                    <input type="checkbox" id="shift-noni">
                    <label for="shift-noni" style="margin-bottom: 0;">Noční směna</label>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary modal-close-btn" onclick="document.getElementById('shift-modal').style.display='none'">Zrušit</button>
                    <button type="submit" class="btn btn-success">Uložit směnu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: UŽIVATEL (PŘIDÁNÍ/ÚPRAVA) -->
    <div id="user-modal" class="modal-overlay">
        <div class="modal">
            <button class="modal-close">&times;</button>
            <h3 id="user-modal-title" style="margin-bottom: 20px; font-size: 20px;">Přidat uživatele</h3>
            <form id="user-modal-form">
                <input type="hidden" id="user-id">
                <div class="form-grid" style="margin-bottom: 15px;">
                    <div class="form-group">
                        <label for="user-firstname">Jméno:</label>
                        <input type="text" id="user-firstname" required placeholder="Jan">
                    </div>
                    <div class="form-group">
                        <label for="user-lastname">Příjmení:</label>
                        <input type="text" id="user-lastname" required placeholder="Novák">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="user-email">E-mailová adresa:</label>
                    <input type="email" id="user-email" required placeholder="novak@example.com">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="user-phone">Telefon:</label>
                    <input type="text" id="user-phone" placeholder="+420 123 456 789">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="user-password">Heslo:</label>
                    <input type="password" id="user-password" placeholder="••••••••">
                    <small id="user-password-help" style="color: var(--text-muted); font-size: 11px; display: none;">Nechte prázdné, pokud nechcete měnit stávající heslo.</small>
                </div>
                <div class="form-group" style="margin-bottom: 25px;">
                    <label for="user-role">Role v systému:</label>
                    <select id="user-role" required>
                        <option value="employee">Zaměstnanec</option>
                        <option value="admin">Administrátor</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('user-modal').style.display='none'">Zrušit</button>
                    <button type="submit" class="btn btn-success">Uložit uživatele</button>
                </div>
            </form>
        </div>
    </div>

    <!-- NAČTENÍ KLIENTSKÉHO KÓDU -->
    <script src="/js/api.js"></script>
    <script src="/js/app.js"></script>
</body>
</html>
