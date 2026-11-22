<?php 
/*
 * diag.php — FICHIER TEMPORAIRE DE DIAGNOSTIC
 * 1. Changez la clé ci-dessous.
 * 2. Déposez ce fichier à côté de index.php.
 * 3. Ouvrez  https://votre-site/diag.php?key=VOTRE_CLE
 *    puis    https://votre-site/diag.php?key=VOTRE_CLE&index=1
 *    (ajoutez &action=loginPage, &action=accounts, etc. pour tester une page précise)
 * 4. SUPPRIMEZ CE FICHIER dès que le problème est trouvé : il affiche des détails internes.
 */
const DIAG_KEY = 'changez-moi-avant-envoi';

if (DIAG_KEY === 'changez-moi-avant-envoi' || ($_GET['key'] ?? '') !== DIAG_KEY) {
    http_response_code(404);
    exit;
}

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/diag-errors.log'); // au cas où l'hébergeur bloque l'affichage
error_reporting(E_ALL);

// Affiche aussi les erreurs fatales (fichier manquant, erreur de syntaxe, classe introuvable…)
register_shutdown_function(static function (): void {
    $e = error_get_last();
    if ($e !== null && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        echo "\n\n=== ERREUR FATALE ===\n" . $e['message'] . "\nFichier : " . $e['file'] . "\nLigne   : " . $e['line'] . "\n";
    }
});

$root = __DIR__;

// ---------- Mode 2 : exécuter index.php avec les erreurs visibles ----------
if (isset($_GET['index'])) {
    unset($_GET['key'], $_GET['index']);
    require $root . '/index.php';
    exit;
}

// ---------- Mode 1 : vérification de l'environnement ----------
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
$mark = static fn(bool $ok): string => $ok ? '[OK]  ' : '[!!]  ';

echo "=== PHP ===\n";
echo $mark(PHP_VERSION_ID >= 70400) . 'Version : ' . PHP_VERSION . " (minimum 7.4, idéalement 8.1+)\n";
foreach (['pdo_mysql', 'fileinfo', 'openssl', 'mbstring', 'json', 'ctype'] as $ext) {
    echo $mark(extension_loaded($ext)) . "Extension $ext\n";
}

echo "\n=== Fichiers attendus ===\n";
$expected = [
    'vendor/autoload.php', '.env', 'configs/security.php', 'configs/database.php',
    'configs/constants.php', 'configs/config.php', 'configs/utils.php',
    'views/layout/header.php', 'views/layout/sidebar.php', 'views/layout/footer.php',
    'public/js/sidebar.js',
];
foreach ($expected as $file) {
    $path = "$root/$file";
    $line = $mark(is_file($path) && is_readable($path)) . $file;
    if (!is_file($path)) {
        $line .= '  ← introuvable';
    } elseif (!is_readable($path)) {
        $line .= '  ← illisible (permissions)';
    }
    echo $line . "\n";
}

echo "\n=== Majuscules/minuscules dans les require (Linux y est sensible) ===\n";
$allFiles = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($root)));
    if (strpos($rel, '/vendor/') === 0 || strpos($rel, '/.git/') === 0) {
        continue;
    }
    $allFiles[] = $rel;
}
$lowerFiles = array_map('strtolower', $allFiles);
$problems = 0;
foreach ($allFiles as $rel) {
    if (substr($rel, -4) !== '.php') {
        continue;
    }
    $code = (string) @file_get_contents($root . $rel);
    if (!preg_match_all('#(?:require|include)(?:_once)?[^;]*?[\'"](/[^\'"]+\.php)[\'"]#i', $code, $m)) {
        continue;
    }
    foreach (array_unique($m[1]) as $target) {
        $exact = false;
        $caseOnly = null;
        foreach ($allFiles as $i => $candidate) {
            if (substr($candidate, -strlen($target)) === $target) {
                $exact = true;
                break;
            }
            if ($caseOnly === null && substr($lowerFiles[$i], -strlen($target)) === strtolower($target)) {
                $caseOnly = $candidate;
            }
        }
        if (!$exact) {
            $problems++;
            echo '[!!]  ' . ltrim($rel, '/') . " demande « $target »"
                . ($caseOnly !== null ? " — le fichier existe mais s'écrit « $caseOnly »" : ' — fichier introuvable') . "\n";
        }
    }
}
echo $problems === 0 ? "[OK]  Aucun problème trouvé\n" : '';

echo "\n=== Composer et base de données ===\n";
if (!is_file("$root/vendor/autoload.php")) {
    echo "[!!]  vendor/ absent : lancez « composer install --no-dev » ou envoyez le dossier vendor/\n";
} else {
    try {
        require_once "$root/vendor/autoload.php"; // affiche une erreur si la version de PHP est trop basse pour vos paquets
        if (class_exists(\Dotenv\Dotenv::class)) {
            \Dotenv\Dotenv::createImmutable($root)->safeLoad();
        }
        echo "[OK]  Autoload Composer chargé\n";
        if (is_file("$root/configs/database.php")) {
            require_once "$root/configs/database.php";
            getPDO()->query('SELECT 1');
            echo "[OK]  Connexion à la base de données réussie\n";
        }
    } catch (Throwable $e) {
        echo '[!!]  ' . get_class($e) . ' : ' . $e->getMessage() . "\n      (" . $e->getFile() . ':' . $e->getLine() . ")\n";
    }
}

echo "\nÉtape suivante : ouvrez cette même adresse avec &index=1 pour voir l'erreur exacte de index.php.\n";
echo "N'oubliez pas de SUPPRIMER diag.php (et diag-errors.log s'il existe) ensuite.\n";