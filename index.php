<?php
// Výkaz práce - monolitický PHP skript s CRUD operacemi a správou uživatelů
// Verze: 3.2
// Autor: Jan Brunclík

// Nastavení hlavičky pro UTF-8
header('Content-Type: text/html; charset=utf-8');

// Načtení konfigurace databáze
require_once 'config.php';

// Načtení TCPDF přes Composer
require_once 'vendor/autoload.php';

// Zkontrolujeme, jakou akci máme provést
 $action = $_GET['action'] ?? 'form';

if ($action === 'generate_pdf') {
    // Režim generování PDF
    generatePdf();
} elseif ($action === 'data_management') {
    // Režim správy dat
    dataManagement();
} elseif ($action === 'add_record') {
    // Režim přidání záznamu
    addRecordForm();
} elseif ($action === 'edit_record') {
    // Režim úpravy záznamu
    editRecordForm();
} elseif ($action === 'save_record') {
    // Uložení záznamu (nového nebo upraveného)
    saveRecord();
} elseif ($action === 'delete_record') {
    // Smazání záznamu
    deleteRecord();
} elseif ($action === 'user_management') {
    // Režim správy uživatelů
    userManagement();
} elseif ($action === 'add_user') {
    // Režim přidání uživatele
    addUserForm();
} elseif ($action === 'edit_user') {
    // Režim úpravy uživatele
    editUserForm();
} elseif ($action === 'save_user') {
    // Uložení uživatele (nového nebo upraveného)
    saveUser();
} elseif ($action === 'delete_user') {
    // Smazání uživatele
    deleteUser();
} else {
    // Výchozí režim - zobrazení formuláře pro generování PDF
    showMainForm();
}

/**
 * Funkce pro zobrazení hlavního formuláře pro generování PDF
 */
function showMainForm() {
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generátor výkazu práce</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .nav-buttons {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .nav-button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 0 5px 10px;
            text-decoration: none;
            display: inline-block;
        }
        .nav-button:hover {
            background-color: #45a049;
        }
        .nav-button.secondary {
            background-color: #2196F3;
        }
        .nav-button.secondary:hover {
            background-color: #0b7dda;
        }
        .nav-button.warning {
            background-color: #ff9800;
        }
        .nav-button.warning:hover {
            background-color: #e68900;
        }
        form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select, input[type="number"], input[type="date"], input[type="time"], input[type="text"], input[type="password"], input[type="email"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        button:hover {
            background-color: #45a049;
        }
        .info {
            margin-top: 20px;
            padding: 10px;
            background-color: #e7f3fe;
            border-left: 6px solid #2196F3;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
        }
        .checkbox-group input {
            width: auto;
            margin-right: 10px;
        }
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 3px;
            text-decoration: none;
            color: white;
        }
        .btn-edit {
            background-color: #2196F3;
        }
        .btn-delete {
            background-color: #f44336;
        }
        .error {
            color: #f44336;
            margin-top: 5px;
        }
        .success {
            color: #4CAF50;
            margin-top: 5px;
        }
        .warning {
            color: #ff9800;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <h1>Generátor výkazu práce</h1>
    
    <div class="nav-buttons">
        <a href="?action=data_management" class="nav-button secondary">Správa dat</a>
        <a href="?action=user_management" class="nav-button warning">Správa uživatelů</a>
    </div>
    
    <form method="GET" action="">
        <!-- Skryté pole pro spuštění generování -->
        <input type="hidden" name="action" value="generate_pdf">
        
        <div class="form-group">
            <label for="id_zamestnance">Zaměstnanec:</label>
            <select id="id_zamestnance" name="id_zamestnance" required>
                <?php
                try {
                    $pdo = new PDO(
                        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                        DB_USER,
                        DB_PASSWORD,
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false
                        ]
                    );
                    
                    $stmt = $pdo->prepare("SELECT id, jmeno, prijmeni FROM janbrunclik_vykaz_prace_uzivatele ORDER BY prijmeni, jmeno");
                    $stmt->execute();
                    $uzivatele = $stmt->fetchAll();
                    
                    foreach ($uzivatele as $uzivatel) {
                        echo '<option value="' . $uzivatel['id'] . '">' . htmlspecialchars($uzivatel['prijmeni'] . ', ' . $uzivatel['jmeno']) . '</option>';
                    }
                } catch (PDOException $e) {
                    echo '<option value="1">Chyba při načítání uživatelů</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="mesic">Měsíc:</label>
            <select id="mesic" name="mesic" required>
                <?php
                $mesice = [
                    1 => 'Leden', 2 => 'Únor', 3 => 'Březen', 4 => 'Duben',
                    5 => 'Květen', 6 => 'Červen', 7 => 'Červenec', 8 => 'Srpen',
                    9 => 'Září', 10 => 'Říjen', 11 => 'Listopad', 12 => 'Prosinec'
                ];
                
                $aktualni_mesic = date('n');
                foreach ($mesice as $cislo => $nazev) {
                    $selected = ($cislo == $aktualni_mesic) ? 'selected' : '';
                    echo '<option value="' . $cislo . '" ' . $selected . '>' . $nazev . '</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="rok">Rok:</label>
            <input type="number" id="rok" name="rok" value="<?php echo date('Y'); ?>" min="2000" max="2100" required>
        </div>
        
        <button type="submit">Generovat PDF</button>
    </form>
    
    <div class="info">
        <p><strong>Informace:</strong></p>
        <p>Tento nástroj generuje výkaz práce pro zadané období a zaměstnance.</p>
        <p>Výkaz bude obsahovat všechny směny z databáze pro vybraný měsíc a rok.</p>
        <p>Pokud pro daný den neexistuje záznam, bude v PDF zobrazen prázdný řádek s datem.</p>
    </div>
</body>
</html>
<?php
}

/**
 * Funkce pro zobrazení správy uživatelů
 */
function userManagement() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
        $stmt = $pdo->prepare("SELECT * FROM janbrunclik_vykaz_prace_uzivatele ORDER BY prijmeni, jmeno");
        $stmt->execute();
        $uzivatele = $stmt->fetchAll();
        
        $zprava = '';
        if (isset($_GET['msg'])) {
            switch ($_GET['msg']) {
                case 'added':
                    $zprava = '<div class="success">Uživatel byl úspěšně přidán.</div>';
                    break;
                case 'updated':
                    $zprava = '<div class="success">Uživatel byl úspěšně aktualizován.</div>';
                    break;
                case 'deleted':
                    $zprava = '<div class="success">Uživatel byl úspěšně smazán.</div>';
                    break;
                case 'error':
                    $zprava = '<div class="error">Došlo k chybě při zpracování požadavku.</div>';
                    break;
            }
        }
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Správa uživatelů - Výkaz práce</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .nav-buttons {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .nav-button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 0 5px 10px;
            text-decoration: none;
            display: inline-block;
        }
        .nav-button:hover {
            background-color: #45a049;
        }
        .nav-button.secondary {
            background-color: #2196F3;
        }
        .nav-button.secondary:hover {
            background-color: #0b7dda;
        }
        .nav-button.warning {
            background-color: #ff9800;
        }
        .nav-button.warning:hover {
            background-color: #e68900;
        }
        .table-container {
            overflow-x: auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 3px;
            text-decoration: none;
            color: white;
        }
        .btn-edit {
            background-color: #2196F3;
        }
        .btn-delete {
            background-color: #f44336;
        }
        .error {
            color: #f44336;
            margin-top: 5px;
        }
        .success {
            color: #4CAF50;
            margin-top: 5px;
        }
        .warning {
            color: #ff9800;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <h1>Správa uživatelů</h1>
    
    <div class="nav-buttons">
        <a href="?action=add_user" class="nav-button">Přidat uživatele</a>
        <a href="?action=data_management" class="nav-button secondary">Správa dat</a>
        <a href="?" class="nav-button">Hlavní stránka</a>
    </div>
    
    <?php echo $zprava; ?>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Jméno</th>
                    <th>Příjmení</th>
                    <th>Email</th>
                    <th>Telefon</th>
                    <th>Akce</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($uzivatele as $uzivatel): ?>
                <tr>
                    <td><?php echo $uzivatel['id']; ?></td>
                    <td><?php echo htmlspecialchars($uzivatel['jmeno']); ?></td>
                    <td><?php echo htmlspecialchars($uzivatel['prijmeni']); ?></td>
                    <td><?php echo htmlspecialchars($uzivatel['email']); ?></td>
                    <td><?php echo htmlspecialchars($uzivatel['telefon']); ?></td>
                    <td>
                        <div class="action-buttons">
                            <a href="?action=edit_user&id=<?php echo $uzivatel['id']; ?>" class="btn-small btn-edit">Upravit</a>
                            <a href="?action=delete_user&id=<?php echo $uzivatel['id']; ?>" class="btn-small btn-delete" onclick="return confirm('Opravdu chcete smazat tohoto uživatele?')">Smazat</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php
    } catch (PDOException $e) {
        echo '<div class="error">Chyba databáze: ' . $e->getMessage() . '</div>';
        echo '<a href="?">Zpět na hlavní stránku</a>';
    }
}

/**
 * Funkce pro zobrazení formuláře pro přidání uživatele
 */
function addUserForm() {
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přidat uživatele - Výkaz práce</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .nav-buttons {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .nav-button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 0 5px 10px;
            text-decoration: none;
            display: inline-block;
        }
        .nav-button:hover {
            background-color: #45a049;
        }
        .nav-button.secondary {
            background-color: #2196F3;
        }
        .nav-button.secondary:hover {
            background-color: #0b7dda;
        }
        .nav-button.warning {
            background-color: #ff9800;
        }
        .nav-button.warning:hover {
            background-color: #e68900;
        }
        form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select, input[type="number"], input[type="date"], input[type="time"], input[type="text"], input[type="password"], input[type="email"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        button:hover {
            background-color: #45a049;
        }
        .error {
            color: #f44336;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <h1>Přidat uživatele</h1>
    
    <div class="nav-buttons">
        <a href="?action=user_management" class="nav-button warning">Zpět na správu uživatelů</a>
        <a href="?" class="nav-button">Hlavní stránka</a>
    </div>
    
    <form method="POST" action="?action=save_user">
        <div class="form-group">
            <label for="jmeno">Jméno:</label>
            <input type="text" id="jmeno" name="jmeno" required>
        </div>
        
        <div class="form-group">
            <label for="prijmeni">Příjmení:</label>
            <input type="text" id="prijmeni" name="prijmeni" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="telefon">Telefon:</label>
            <input type="text" id="telefon" name="telefon">
        </div>
        
        <div class="form-group">
            <label for="heslo">Heslo:</label>
            <input type="password" id="heslo" name="heslo" required>
        </div>
        
        <button type="submit">Uložit uživatele</button>
    </form>
</body>
</html>
<?php
}

/**
 * Funkce pro zobrazení formuláře pro úpravu uživatele
 */
function editUserForm() {
    $id = $_GET['id'] ?? 0;
    
    if ($id <= 0) {
        echo '<p>Neplatné ID uživatele.</p>';
        echo '<a href="?action=user_management">Zpět na správu uživatelů</a>';
        return;
    }
    
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
        $stmt = $pdo->prepare("SELECT * FROM janbrunclik_vykaz_prace_uzivatele WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $uzivatel = $stmt->fetch();
        
        if (!$uzivatel) {
            echo '<p>Uživatel nebyl nalezen.</p>';
            echo '<a href="?action=user_management">Zpět na správu uživatelů</a>';
            return;
        }
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upravit uživatele - Výkaz práce</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .nav-buttons {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .nav-button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 0 5px 10px;
            text-decoration: none;
            display: inline-block;
        }
        .nav-button:hover {
            background-color: #45a049;
        }
        .nav-button.secondary {
            background-color: #2196F3;
        }
        .nav-button.secondary:hover {
            background-color: #0b7dda;
        }
        .nav-button.warning {
            background-color: #ff9800;
        }
        .nav-button.warning:hover {
            background-color: #e68900;
        }
        form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select, input[type="number"], input[type="date"], input[type="time"], input[type="text"], input[type="password"], input[type="email"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        button:hover {
            background-color: #45a049;
        }
        .error {
            color: #f44336;
            margin-top: 5px;
        }
        .info {
            color: #2196F3;
            margin-top: 5px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <h1>Upravit uživatele</h1>
    
    <div class="nav-buttons">
        <a href="?action=user_management" class="nav-button warning">Zpět na správu uživatelů</a>
        <a href="?" class="nav-button">Hlavní stránka</a>
    </div>
    
    <form method="POST" action="?action=save_user">
        <input type="hidden" name="id" value="<?php echo $uzivatel['id']; ?>">
        
        <div class="form-group">
            <label for="jmeno">Jméno:</label>
            <input type="text" id="jmeno" name="jmeno" value="<?php echo htmlspecialchars($uzivatel['jmeno']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="prijmeni">Příjmení:</label>
            <input type="text" id="prijmeni" name="prijmeni" value="<?php echo htmlspecialchars($uzivatel['prijmeni']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($uzivatel['email']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="telefon">Telefon:</label>
            <input type="text" id="telefon" name="telefon" value="<?php echo htmlspecialchars($uzivatel['telefon']); ?>">
        </div>
        
        <div class="form-group">
            <label for="heslo">Heslo:</label>
            <input type="password" id="heslo" name="heslo">
            <div class="info">Nechte prázdné, pokud nechcete měnit heslo.</div>
        </div>
        
        <button type="submit">Uložit změny</button>
    </form>
</body>
</html>
<?php
    } catch (PDOException $e) {
        echo '<div class="error">Chyba databáze: ' . $e->getMessage() . '</div>';
        echo '<a href="?action=user_management">Zpět na správu uživatelů</a>';
    }
}

/**
 * Funkce pro uložení uživatele (nového nebo upraveného)
 */
function saveUser() {
    $id = $_POST['id'] ?? 0;
    $jmeno = $_POST['jmeno'] ?? '';
    $prijmeni = $_POST['prijmeni'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefon = $_POST['telefon'] ?? '';
    $heslo = $_POST['heslo'] ?? '';
    
    if (empty($jmeno) || empty($prijmeni) || empty($email)) {
        header('Location: ?action=user_management&msg=error');
        exit;
    }
    
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
        if ($id > 0) {
            // Úprava existujícího uživatele
            if (!empty($heslo)) {
                // S heslem
                $heslo_hash = password_hash($heslo, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE janbrunclik_vykaz_prace_uzivatele SET jmeno = :jmeno, prijmeni = :prijmeni, email = :email, telefon = :telefon, heslo = :heslo WHERE id = :id");
                $stmt->bindParam(':heslo', $heslo_hash);
            } else {
                // Bez hesla
                $stmt = $pdo->prepare("UPDATE janbrunclik_vykaz_prace_uzivatele SET jmeno = :jmeno, prijmeni = :prijmeni, email = :email, telefon = :telefon WHERE id = :id");
            }
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':jmeno', $jmeno);
            $stmt->bindParam(':prijmeni', $prijmeni);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':telefon', $telefon);
            
            $stmt->execute();
            header('Location: ?action=user_management&msg=updated');
        } else {
            // Přidání nového uživatele
            if (empty($heslo)) {
                header('Location: ?action=user_management&msg=error');
                exit;
            }
            
            $heslo_hash = password_hash($heslo, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO janbrunclik_vykaz_prace_uzivatele (jmeno, prijmeni, email, telefon, heslo) VALUES (:jmeno, :prijmeni, :email, :telefon, :heslo)");
            
            $stmt->bindParam(':jmeno', $jmeno);
            $stmt->bindParam(':prijmeni', $prijmeni);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':telefon', $telefon);
            $stmt->bindParam(':heslo', $heslo_hash);
            
            $stmt->execute();
            header('Location: ?action=user_management&msg=added');
        }
    } catch (PDOException $e) {
        header('Location: ?action=user_management&msg=error');
    }
    exit;
}

/**
 * Funkce pro smazání uživatele
 */
function deleteUser() {
    $id = $_GET['id'] ?? 0;
    
    if ($id <= 0) {
        header('Location: ?action=user_management&msg=error');
        exit;
    }
    
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
        // Nejprve zkontrolujeme, zda uživatel nemá přiřazené záznamy
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM janbrunclik_vykaz_prace_pracovni_vykaz WHERE id_zamestnance = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            header('Location: ?action=user_management&msg=has_records');
            exit;
        }
        
        // Pokud nemá žádné záznamy, můžeme ho smazat
        $stmt = $pdo->prepare("DELETE FROM janbrunclik_vykaz_prace_uzivatele WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        header('Location: ?action=user_management&msg=deleted');
    } catch (PDOException $e) {
        header('Location: ?action=user_management&msg=error');
    }
    exit;
}

/**
 * Funkce pro zobrazení správy dat
 */
function dataManagement() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
        // Získání filtrů
        $mesic = $_GET['filter_mesic'] ?? date('n');
        $rok = $_GET['filter_rok'] ?? date('Y');
        $id_zamestnance = $_GET['filter_id_zamestnance'] ?? 0;
        
        // Příprava SQL dotazu
        $sql = "SELECT pv.*, u.jmeno, u.prijmeni FROM janbrunclik_vykaz_prace_pracovni_vykaz pv 
                LEFT JOIN janbrunclik_vykaz_prace_uzivatele u ON pv.id_zamestnance = u.id 
                WHERE 1=1";
        $params = [];
        
        if ($mesic > 0) {
            $sql .= " AND MONTH(pv.datum) = :mesic";
            $params[':mesic'] = $mesic;
        }
        
        if ($rok > 0) {
            $sql .= " AND YEAR(pv.datum) = :rok";
            $params[':rok'] = $rok;
        }
        
        if ($id_zamestnance > 0) {
            $sql .= " AND pv.id_zamestnance = :id_zamestnance";
            $params[':id_zamestnance'] = $id_zamestnance;
        }
        
        $sql .= " ORDER BY pv.datum DESC";
        
        $stmt = $pdo->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $zaznamy = $stmt->fetchAll();
        
        // Získání seznamu uživatelů pro filtr
        $stmt_uzivatele = $pdo->prepare("SELECT id, jmeno, prijmeni FROM janbrunclik_vykaz_prace_uzivatele ORDER BY prijmeni, jmeno");
        $stmt_uzivatele->execute();
        $uzivatele = $stmt_uzivatele->fetchAll();
        
        $zprava = '';
        if (isset($_GET['msg'])) {
            switch ($_GET['msg']) {
                case 'added':
                    $zprava = '<div class="success">Záznam byl úspěšně přidán.</div>';
                    break;
                case 'updated':
                    $zprava = '<div class="success">Záznam byl úspěšně aktualizován.</div>';
                    break;
                case 'deleted':
                    $zprava = '<div class="success">Záznam byl úspěšně smazán.</div>';
                    break;
                case 'error':
                    $zprava = '<div class="error">Došlo k chybě při zpracování požadavku.</div>';
                    break;
            }
        }
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Správa dat - Výkaz práce</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .nav-buttons {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .nav-button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 0 5px 10px;
            text-decoration: none;
            display: inline-block;
        }
        .nav-button:hover {
            background-color: #45a049;
        }
        .nav-button.secondary {
            background-color: #2190F3;
        }
        .nav-button.secondary:hover {
            background-color: #0b7dda;
        }
        .nav-button.warning {
            background-color: yellow;
        }
        .nav-button.warning:hover {
            background-color: #e68900;
        }
        .filter-container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: end;
        }
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .filter-group select, .filter-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .filter-button {
            background-color: #2196F3;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            height: 36px;
        }
        .filter-button:hover {
            background-color: #0b7dda;
        }
        .table-container {
            overflow-x: auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 3px;
            text-decoration: none;
            color: white;
        }
        .btn-edit {
            background-color: #2196F3;
        }
        .btn-delete {
            background-color: #f44336;
        }
        .error {
            color: #f44336;
            margin-top: 5px;
        }
        .success {
            color: #4CAF50;
            margin-top: 5px;
        }
        .warning {
            color: #ff9800;
            margin-top: 5px;
        }
        .no-records {
            text-align: center;
            padding: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Správa dat</h1>
    
    <div class="nav-buttons">
        <a href="?action=add_record" class="nav-button">Přidat záznam</a>
        <a href="?action=user_management" class="nav-button warning">Správa uživatelů</a>
        <a href="?" class="nav-button secondary">Hlavní stránka</a>
    </div>
    
    <?php echo $zprava; ?>
    
    <div class="filter-container">
        <form method="GET" action="?action=data_management" class="filter-form">
            	<input type="hidden" name="action" value="data_management">
		<div class="filter-group">
                <label for="filter_mesic">Měsíc:</label>
                <select id="filter_mesic" name="filter_mesic">
                    <option value="0">Všechny</option>
                    <?php
                    $mesice = [
                        1 => 'Leden', 2 => 'Únor', 3 => 'Březen', 4 => 'Duben',
                        5 => 'Květen', 6 => 'Červen', 7 => 'Červenec', 8 => 'Srpen',
                        9 => 'Září', 10 => 'Říjen', 11 => 'Listopad', 12 => 'Prosinec'
                    ];
                    
                    foreach ($mesice as $cislo => $nazev) {
                        $selected = ($cislo == $mesic) ? 'selected' : '';
                        echo '<option value="' . $cislo . '" ' . $selected . '>' . $nazev . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter_rok">Rok:</label>
                <input type="number" id="filter_rok" name="filter_rok" value="<?php echo $rok; ?>" min="2025" max="2030">
            </div>
            
            <div class="filter-group">
                <label for="filter_id_zamestnance">Zaměstnanec:</label>
                <select id="filter_id_zamestnance" name="filter_id_zamestnance">
                    <option value="0">Všichni</option>
                    <?php foreach ($uzivatele as $uzivatel): ?>
                        <option value="<?php echo $uzivatel['id']; ?>" <?php echo ($uzivatel['id'] == $id_zamestnance) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($uzivatel['prijmeni'] . ', ' . $uzivatel['jmeno']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="filter-button">Filtrovat</button>
        </form>
    </div>
    
    <div class="table-container">
        <?php if (empty($zaznamy)): ?>
            <div class="no-records">Nebyly nalezeny žádné záznamy.</div>
        <?php else: ?>
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
                <tbody>
                    <?php foreach ($zaznamy as $zaznam): ?>
                        <?php
                        // Výpočet odpracovaných hodin
                        $cas_zacatku = strtotime($zaznam['cas_zacatku']);
                        $cas_konce = strtotime($zaznam['cas_konce']);
                        $odpracovano_hodin = ($cas_konce - $cas_zacatku) / 3600 - 0.5;
                        if ($odpracovano_hodin < 0) $odpracovano_hodin = 0;
                        $odpracovano_format = number_format($odpracovano_hodin, 2, ',', '');
                        
                        $jmeno_zamestnance = $zaznam['jmeno'] ? htmlspecialchars($zaznam['prijmeni'] . ', ' . $zaznam['jmeno']) : 'ID: ' . $zaznam['id_zamestnance'];
                        ?>
                        <tr>
                            <td><?php echo $zaznam['id']; ?></td>
                            <td><?php echo $jmeno_zamestnance; ?></td>
                            <td><?php echo date('d.m.Y', strtotime($zaznam['datum'])); ?></td>
                            <td><?php echo date('H:i', strtotime($zaznam['cas_zacatku'])); ?></td>
                            <td><?php echo date('H:i', strtotime($zaznam['cas_konce'])); ?></td>
                            <td><?php echo $odpracovano_format; ?></td>
                            <td><?php echo htmlspecialchars($zaznam['poznamka'] ?? ''); ?></td>
                            <td><?php echo $zaznam['noni'] == 1 ? 'Ano' : 'Ne'; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?action=edit_record&id=<?php echo $zaznam['id']; ?>" class="btn-small btn-edit">Upravit</a>
                                    <a href="?action=delete_record&id=<?php echo $zaznam['id']; ?>" class="btn-small btn-delete" onclick="return confirm('Opravdu chcete smazat tento záznam?')">Smazat</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
    } catch (PDOException $e) {
        echo '<div class="error">Chyba databáze: ' . $e->getMessage() . '</div>';
        echo '<a href="?">Zpět na hlavní stránku</a>';
    }
}

/**
 * Funkce pro zobrazení formuláře pro přidání záznamu
 */
function addRecordForm() {
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přidat záznam - Výkaz práce</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .nav-buttons {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .nav-button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 0 5px 10px;
            text-decoration: none;
            display: inline-block;
        }
        .nav-button:hover {
            background-color: #45a049;
        }
        .nav-button.secondary {
            background-color: #2196F3;
        }
        .nav-button.secondary:hover {
            background-color: #0b7dda;
        }
        .nav-button.warning {
            background-color: #ff9800;
        }
        .nav-button.warning:hover {
            background-color: #e68900;
        }
        form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select, input[type="number"], input[type="date"], input[type="time"], input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        button:hover {
            background-color: #45a049;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
        }
        .checkbox-group input {
            width: auto;
            margin-right: 10px;
        }
        .error {
            color: #f44336;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <h1>Přidat záznam</h1>
    
    <div class="nav-buttons">
        <a href="?action=data_management" class="nav-button secondary">Zpět na správu dat</a>
        <a href="?action=user_management" class="nav-button warning">Správa uživatelů</a>
        <a href="?" class="nav-button">Hlavní stránka</a>
    </div>
    
    <form method="POST" action="?action=save_record">
        <div class="form-group">
            <label for="id_zamestnance">Zaměstnanec:</label>
            <select id="id_zamestnance" name="id_zamestnance" required>
                <?php
                try {
                    $pdo = new PDO(
                        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                        DB_USER,
                        DB_PASSWORD,
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false
                        ]
                    );
                    
                    $stmt = $pdo->prepare("SELECT id, jmeno, prijmeni FROM janbrunclik_vykaz_prace_uzivatele ORDER BY prijmeni, jmeno");
                    $stmt->execute();
                    $uzivatele = $stmt->fetchAll();
                    
                    foreach ($uzivatele as $uzivatel) {
                        echo '<option value="' . $uzivatel['id'] . '">' . htmlspecialchars($uzivatel['prijmeni'] . ', ' . $uzivatel['jmeno']) . '</option>';
                    }
                } catch (PDOException $e) {
                    echo '<option value="1">Chyba při načítání uživatelů</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="datum">Datum:</label>
            <input type="date" id="datum" name="datum" required>
        </div>
        
        <div class="form-group">
            <label for="cas_zacatku">Čas začátku:</label>
            <input type="time" id="cas_zacatku" name="cas_zacatku" value="07:00" required>
        </div>
        
        <div class="form-group">
            <label for="cas_konce">Čas konce:</label>
            <input type="time" id="cas_konce" name="cas_konce" value="19:00" required>
        </div>
        
        <div class="form-group">
            <label for="poznamka">Poznámka:</label>
            <input type="text" id="poznamka" name="poznamka">
        </div>
        
        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" id="noni" name="noni" value="1">
                <label for="noni" style="margin-bottom: 0;">Noční směna</label>
            </div>
        </div>
        
        <button type="submit">Uložit záznam</button>
    </form>
</body>
</html>
<?php
}

/**
 * Funkce pro zobrazení formuláře pro úpravu záznamu
 */
function editRecordForm() {
    $id = $_GET['id'] ?? 0;
    
    if ($id <= 0) {
        echo '<p>Neplatné ID záznamu.</p>';
        echo '<a href="?action=data_management">Zpět na správu dat</a>';
        return;
    }
    
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
        $stmt = $pdo->prepare("SELECT * FROM janbrunclik_vykaz_prace_pracovni_vykaz WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $zaznam = $stmt->fetch();
        
        if (!$zaznam) {
            echo '<p>Záznam nebyl nalezen.</p>';
            echo '<a href="?action=data_management">Zpět na správu dat</a>';
            return;
        }
        
        // Formátování data pro formulář
        $datum_format = date('Y-m-d', strtotime($zaznam['datum']));
        $noni_checked = $zaznam['noni'] == 1 ? 'checked' : '';
        
        // Získání seznamu uživatelů
        $stmt_uzivatele = $pdo->prepare("SELECT id, jmeno, prijmeni FROM janbrunclik_vykaz_prace_uzivatele ORDER BY prijmeni, jmeno");
        $stmt_uzivatele->execute();
        $uzivatele = $stmt_uzivatele->fetchAll();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upravit záznam - Výkaz práce</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .nav-buttons {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .nav-button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 0 5px 10px;
            text-decoration: none;
            display: inline-block;
        }
        .nav-button:hover {
            background-color: #45a049;
        }
        .nav-button.secondary {
            background-color: #2196F3;
        }
        .nav-button.secondary:hover {
            background-color: #0b7dda;
        }
        .nav-button.warning {
            background-color: #ff9800;
        }
        .nav-button.warning:hover {
            background-color: #e68900;
        }
        form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select, input[type="number"], input[type="date"], input[type="time"], input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        button:hover {
            background-color: #45a049;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
        }
        .checkbox-group input {
            width: auto;
            margin-right: 10px;
        }
        .error {
            color: #f44336;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <h1>Upravit záznam</h1>
    
    <div class="nav-buttons">
        <a href="?action=data_management" class="nav-button secondary">Zpět na správu dat</a>
        <a href="?action=user_management" class="nav-button warning">Správa uživatelů</a>
        <a href="?" class="nav-button">Hlavní stránka</a>
    </div>
    
    <form method="POST" action="?action=save_record">
        <input type="hidden" name="id" value="<?php echo $zaznam['id']; ?>">
        
        <div class="form-group">
            <label for="id_zamestnance">Zaměstnanec:</label>
            <select id="id_zamestnance" name="id_zamestnance" required>
                <?php foreach ($uzivatele as $uzivatel): ?>
                    <option value="<?php echo $uzivatel['id']; ?>" <?php echo ($uzivatel['id'] == $zaznam['id_zamestnance']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($uzivatel['prijmeni'] . ', ' . $uzivatel['jmeno']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="datum">Datum:</label>
            <input type="date" id="datum" name="datum" value="<?php echo $datum_format; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="cas_zacatku">Čas začátku:</label>
            <input type="time" id="cas_zacatku" name="cas_zacatku" value="<?php echo $zaznam['cas_zacatku']; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="cas_konce">Čas konce:</label>
            <input type="time" id="cas_konce" name="cas_konce" value="<?php echo $zaznam['cas_konce']; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="poznamka">Poznámka:</label>
            <input type="text" id="poznamka" name="poznamka" value="<?php echo htmlspecialchars($zaznam['poznamka'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" id="noni" name="noni" value="1" <?php echo $noni_checked; ?>>
                <label for="noni" style="margin-bottom: 0;">Noční směna</label>
            </div>
        </div>
        
        <button type="submit">Uložit změny</button>
    </form>
</body>
</html>
<?php
    } catch (PDOException $e) {
        echo '<div class="error">Chyba databáze: ' . $e->getMessage() . '</div>';
        echo '<a href="?action=data_management">Zpět na správu dat</a>';
    }
}

/**
 * Funkce pro uložení záznamu (nového nebo upraveného)
 */
function saveRecord() {
    $id = $_POST['id'] ?? 0;
    $id_zamestnance = $_POST['id_zamestnance'] ?? 0;
    $datum = $_POST['datum'] ?? '';
    $cas_zacatku = $_POST['cas_zacatku'] ?? '';
    $cas_konce = $_POST['cas_konce'] ?? '';
    $poznamka = $_POST['poznamka'] ?? '';
    $noni = isset($_POST['noni']) ? 1 : 0;
    
    if (empty($id_zamestnance) || empty($datum) || empty($cas_zacatku) || empty($cas_konce)) {
        header('Location: ?action=data_management&msg=error');
        exit;
    }
    
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
        if ($id > 0) {
            // Úprava existujícího záznamu
            $stmt = $pdo->prepare("UPDATE janbrunclik_vykaz_prace_pracovni_vykaz SET id_zamestnance = :id_zamestnance, datum = :datum, cas_zacatku = :cas_zacatku, cas_konce = :cas_konce, poznamka = :poznamka, noni = :noni WHERE id = :id");
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':id_zamestnance', $id_zamestnance, PDO::PARAM_INT);
            $stmt->bindParam(':datum', $datum);
            $stmt->bindParam(':cas_zacatku', $cas_zacatku);
            $stmt->bindParam(':cas_konce', $cas_konce);
            $stmt->bindParam(':poznamka', $poznamka);
            $stmt->bindParam(':noni', $noni, PDO::PARAM_INT);
            
            $stmt->execute();
            header('Location: ?action=data_management&msg=updated');
        } else {
            // Přidání nového záznamu
            $stmt = $pdo->prepare("INSERT INTO janbrunclik_vykaz_prace_pracovni_vykaz (id_zamestnance, datum, cas_zacatku, cas_konce, poznamka, noni) VALUES (:id_zamestnance, :datum, :cas_zacatku, :cas_konce, :poznamka, :noni)");
            
            $stmt->bindParam(':id_zamestnance', $id_zamestnance, PDO::PARAM_INT);
            $stmt->bindParam(':datum', $datum);
            $stmt->bindParam(':cas_zacatku', $cas_zacatku);
            $stmt->bindParam(':cas_konce', $cas_konce);
            $stmt->bindParam(':poznamka', $poznamka);
            $stmt->bindParam(':noni', $noni, PDO::PARAM_INT);
            
            $stmt->execute();
            header('Location: ?action=data_management&msg=added');
        }
    } catch (PDOException $e) {
        header('Location: ?action=data_management&msg=error');
    }
    exit;
}

/**
 * Funkce pro smazání záznamu
 */
function deleteRecord() {
    $id = $_GET['id'] ?? 0;
    
    if ($id <= 0) {
        header('Location: ?action=data_management&msg=error');
        exit;
    }
    
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
        $stmt = $pdo->prepare("DELETE FROM janbrunclik_vykaz_prace_pracovni_vykaz WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        header('Location: ?action=data_management&msg=deleted');
    } catch (PDOException $e) {
        header('Location: ?action=data_management&msg=error');
    }
    exit;
}

/**
 * Funkce pro generování PDF
 */
function generatePdf() {
    // Validace vstupních parametrů
    $mesic = filter_input(INPUT_GET, 'mesic', FILTER_VALIDATE_INT);
    $rok = filter_input(INPUT_GET, 'rok', FILTER_VALIDATE_INT);
    $id_zamestnance = filter_input(INPUT_GET, 'id_zamestnance', FILTER_VALIDATE_INT);
    
    // Kontrola, zda jsou všechny parametry platné
    if ($mesic === false || $rok === false || $id_zamestnance === false || 
        $mesic < 1 || $mesic > 12 || $rok < 2000 || $rok > 2100 || $id_zamestnance < 1) {
        die('Chybné parametry. Zkontrolujte zadané hodnoty.');
    }
    
    try {
        // Připojení k databázi přes PDO
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
        // Získání informací o zaměstnanci
        $stmt_zamestnanec = $pdo->prepare("SELECT * FROM janbrunclik_vykaz_prace_uzivatele WHERE id = :id");
        $stmt_zamestnanec->bindParam(':id', $id_zamestnance, PDO::PARAM_INT);
        $stmt_zamestnanec->execute();
        $zamestnanec = $stmt_zamestnanec->fetch();
        
        if (!$zamestnanec) {
            die('Zaměstnanec nebyl nalezen.');
        }
        
        // Příprava SQL dotazu s prepared statements
        $stmt = $pdo->prepare(
            "SELECT datum, cas_zacatku, cas_konce, poznamka, noni 
             FROM janbrunclik_vykaz_prace_pracovni_vykaz 
             WHERE id_zamestnance = :id_zamestnance 
             AND MONTH(datum) = :mesic 
             AND YEAR(datum) = :rok 
             ORDER BY datum ASC"
        );
        
        // Bind parametrů
        $stmt->bindParam(':id_zamestnance', $id_zamestnance, PDO::PARAM_INT);
        $stmt->bindParam(':mesic', $mesic, PDO::PARAM_INT);
        $stmt->bindParam(':rok', $rok, PDO::PARAM_INT);
        
        // Provedení dotazu
        $stmt->execute();
        $zaznamy = $stmt->fetchAll();
        
        // Vytvoření pole pro všechny dny v měsíci
        $dny_v_mesici = cal_days_in_month(CAL_GREGORIAN, $mesic, $rok);
        $vsechny_dny = [];
        
        // Naplnění pole všemi dny v měsíci
        for ($den = 1; $den <= $dny_v_mesici; $den++) {
            $datum = sprintf('%04d-%02d-%02d', $rok, $mesic, $den);
            $vsechny_dny[$datum] = null;
        }
        
        // Naplnění pole existujícími záznamy
        foreach ($zaznamy as $zaznam) {
            $vsechny_dny[$zaznam['datum']] = $zaznam;
        }
        
        // Zpracování dat a výpočty
        $data_pro_tabulku = [];
        $celkem_hodin = 0;
        $celkem_vikend_hodin = 0;
        
        foreach ($vsechny_dny as $datum => $zaznam) {
            if ($zaznam === null) {
                // Prázdný den - pouze datum
                $radek = [
                    'den' => date('d', strtotime($datum)),
                    'zacatek' => '',
                    'konec' => '',
                    'celkem' => '',
                    'poznamka' => '',
                    'noni' => '',
                    'vikend_hodiny' => ''
                ];
            } else {
                // Den se záznamem
                // Převedení časů na timestamp pro výpočet
                $cas_zacatku = strtotime($zaznam['cas_zacatku']);
                $cas_konce = strtotime($zaznam['cas_konce']);
                
                // Výpočet odpracovaných hodin (odečtení 0.5 hodiny pauzy)
                $odpracovano_hodin = ($cas_konce - $cas_zacatku) / 3600 - 0.5;
                
                // Kontrola, zda není výsledek záporný
                if ($odpracovano_hodin < 0) {
                    $odpracovano_hodin = 0;
                }
                
                // Formátování času
                $cas_zacatku_format = date('H:i', $cas_zacatku);
                $cas_konce_format = date('H:i', $cas_konce);
                $odpracovano_format = number_format($odpracovano_hodin, 2, ',', '');
                
                // Zjištění, zda je den víkend
                $den_v_tydnu = date('N', strtotime($datum));
                $je_vikend = ($den_v_tydnu == 6 || $den_v_tydnu == 7);
                
                // Příprava dat pro tabulku
                $radek = [
                    'den' => date('d', strtotime($datum)),
                    'zacatek' => $cas_zacatku_format,
                    'konec' => $cas_konce_format,
                    'celkem' => $odpracovano_format,
                    'poznamka' => $zaznam['poznamka'] ?? '',
                    'noni' => $zaznam['noni'] == 1 ? 'Ano' : '',
                    'vikend_hodiny' => $je_vikend ? $odpracovano_format : ''
                ];
                
                $celkem_hodin += $odpracovano_hodin;
                
                if ($je_vikend) {
                    $celkem_vikend_hodin += $odpracovano_hodin;
                }
            }
            
            $data_pro_tabulku[] = $radek;
        }
        
        // Formátování celkového součtu
        $celkem_hodin_format = number_format($celkem_hodin, 2, ',', '');
        $celkem_vikend_hodin_format = number_format($celkem_vikend_hodin, 2, ',', '');
        
        // Názvy měsíců v češtině
        $nazvy_mesicu = [
            1 => 'Leden', 2 => 'Únor', 3 => 'Březen', 4 => 'Duben',
            5 => 'Květen', 6 => 'Červen', 7 => 'Červenec', 8 => 'Srpen',
            9 => 'Září', 10 => 'Říjen', 11 => 'Listopad', 12 => 'Prosinec'
        ];
        
        $nazev_mesice = $nazvy_mesicu[$mesic];
        
        // Vytvoření PDF pomocí TCPDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Nastavení metadat dokumentu
        $pdf->SetCreator('Výkaz práce v3.2');
        $pdf->SetAuthor('Jan Brunclík');
        $pdf->SetTitle('Výkaz práce - ' . $zamestnanec['prijmeni'] . ', ' . $zamestnanec['jmeno'] . ' - ' . $nazev_mesice . ' ' . $rok);
        
        // Nastavení záhlaví a zápatí
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Nastavení okrajů pro zmenšení tabulky
        $pdf->SetMargins(10, 10, 10);
        
        // Přidání stránky
        $pdf->AddPage();
        
        // Nastavení písma
        $pdf->SetFont('freeserif', '', 10);
        
        // A) Záhlaví dokumentu
        $pdf->SetFont('freeserif', 'B', 14);
        $pdf->Cell(0, 10, 'Výkaz práce', 0, 1, 'C');
        
        $pdf->SetFont('freeserif', '', 10);
        $pdf->Cell(0, 7, 'Objekt - Trojická 20, Praha', 0, 1, 'C');
        
        // Informace o zaměstnanci a období
        $pdf->Cell(95, 7, 'Příjmení, jméno : ' . $zamestnanec['prijmeni'] . ', ' . $zamestnanec['jmeno'], 0, 0, 'L');
        $pdf->Cell(95, 7, 'Tel. Číslo : ' . ($zamestnanec['telefon'] ?? ''), 0, 1, 'R');
        
        $pdf->Cell(95, 7, 'Měsíc: ' . $nazev_mesice, 0, 0, 'L');
        $pdf->Cell(95, 7, 'Rok : ' . $rok, 0, 1, 'R');
        
        $pdf->Ln(3); // Zmenšení prázdného řádku
        
        // B) Tabulka výkazu
        $pdf->SetFont('freeserif', 'B', 8);
        
        // Hlavička tabulky - zmenšení šířky sloupců
        $pdf->Cell(15, 6, 'Den', 1, 0, 'C');
        $pdf->Cell(20, 6, 'Začátek', 1, 0, 'C');
        $pdf->Cell(20, 6, 'Konec', 1, 0, 'C');
        $pdf->Cell(20, 6, 'Celkem', 1, 0, 'C');
        $pdf->Cell(45, 6, 'Poznámka', 1, 0, 'C');
        $pdf->Cell(15, 6, 'Noční', 1, 0, 'C');
        $pdf->Cell(25, 6, 'Soboty - neděle', 1, 1, 'C');
        
        // Data tabulky - zmenšení velikosti písma a výšky řádku
        $pdf->SetFont('freeserif', 'B', 8);
        
        foreach ($data_pro_tabulku as $radek) {
            $pdf->Cell(15, 5, $radek['den'], 1, 0, 'C');
            $pdf->Cell(20, 5, $radek['zacatek'], 1, 0, 'C');
            $pdf->Cell(20, 5, $radek['konec'], 1, 0, 'C');
            $pdf->Cell(20, 5, $radek['celkem'], 1, 0, 'C');
            $pdf->Cell(45, 5, $radek['poznamka'], 1, 0, 'L');
            $pdf->Cell(15, 5, $radek['noni'], 1, 0, 'C');
            $pdf->Cell(25, 5, $radek['vikend_hodiny'], 1, 1, 'C');
        }
        
        // C) Patička a souhrny
        $pdf->Ln(3); // Zmenšení prázdného řádku
        
        $pdf->SetFont('freeserif', '', 9);
        $pdf->Cell(0, 6, 'Odpracováno celkem hodin : ' . $celkem_hodin_format, 0, 0, 'L');
        $pdf->Cell(0, 6, 'Podpis pracovníka :', 0, 1, 'R');
        
        $pdf->Cell(0, 6, 'Víkendové hodiny : ' . $celkem_vikend_hodin_format, 0, 1, 'L');
        
        $pdf->Ln(3); // Zmenšení prázdného řádku
        
        $pdf->Cell(0, 6, 'Podpis provozního manažera', 0, 1, 'L');
        
        $pdf->Ln(3); // Zmenšení prázdného řádku
        
        $pdf->SetFont('freeserif', '', 8);
        $pdf->Cell(0, 5, 'Vygenerováno ' . date('d.m.Y'), 0, 1, 'C');
        $pdf->Cell(0, 5, 'Vytvořeno přes Výkaz práce v3.2', 0, 1, 'C');
        $pdf->Cell(0, 5, '(C) Jan Brunclík', 0, 1, 'C');
        
        // Nastavení HTTP hlaviček pro PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="vykaz_prace_' . $zamestnanec['prijmeni'] . '_' . $nazev_mesice . '_' . $rok . '.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        // Výstup PDF
        $pdf->Output('vykaz_prace_' . $zamestnanec['prijmeni'] . '_' . $nazev_mesice . '_' . $rok . '.pdf', 'D');
        
        // Ukončení skriptu
        exit;
        
    } catch (PDOException $e) {
        die('Chyba databáze: ' . $e->getMessage());
    } catch (Exception $e) {
        die('Chyba při generování PDF: ' . $e->getMessage());
    }
}
?>
