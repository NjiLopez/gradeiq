<?php
define('DB_HOST',    'localhost');   
define('DB_PORT',    '3306');        
define('DB_NAME',    'gradeiq');     
define('DB_USER',    'root');        
define('DB_PASS',    '');            
define('DB_CHARSET', 'utf8mb4');

// ── PDO Singleton for database connection
function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // Show a friendly error instead of leaking credentials
        http_response_code(500);
        die('<style>body{font-family:sans-serif;background:#0d0f14;color:#e8eaf0;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:24px}</style>
             <div style="background:#181c26;border:1px solid #e74c3c55;border-radius:16px;padding:32px;max-width:480px;text-align:center">
               <div style="font-size:2rem;margin-bottom:12px">⚠️</div>
               <h2 style="color:#e74c3c;margin-bottom:8px">Database Connection Failed</h2>
               <p style="color:#8b92a8;font-size:0.9rem">Could not connect to the database. Please check your credentials in <code>includes/config.php</code> and ensure MySQL is running.</p>
               <p style="color:#555e78;font-size:0.8rem;margin-top:12px">' . htmlspecialchars($e->getMessage()) . '</p>
             </div>');
    }

    return $pdo;
}