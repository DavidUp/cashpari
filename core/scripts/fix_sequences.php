<?php
/**
 * Fix missing PostgreSQL sequences for all BetLab tables
 * Run with: php scripts/fix_sequences.php
 */

$host     = 'aws-1-us-west-2.pooler.supabase.com';
$port     = '5432';
$dbname   = 'postgres';
$user     = 'postgres.fihbufjinlxndpdsfijk';
$password = '$ucess_Business_10Y';

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
$pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Get all tables with an 'id' column that has NO default sequence
$tables = $pdo->query("
    SELECT t.table_name
    FROM information_schema.tables t
    JOIN information_schema.columns c 
        ON c.table_name = t.table_name 
        AND c.table_schema = t.table_schema
    WHERE t.table_schema = 'public'
        AND t.table_type = 'BASE TABLE'
        AND c.column_name = 'id'
        AND c.column_default IS NULL
    ORDER BY t.table_name
")->fetchAll(PDO::FETCH_COLUMN);

echo "Found " . count($tables) . " tables missing sequences:\n\n";

$fixed   = [];
$skipped = [];
$errors  = [];

foreach ($tables as $table) {
    $seqName = "{$table}_id_seq";

    try {
        // Get current max id to set sequence correctly
        $maxId = $pdo->query("SELECT COALESCE(MAX(id), 0) FROM \"$table\"")->fetchColumn();
        $startVal = (int)$maxId + 1;

        $pdo->exec("
            CREATE SEQUENCE IF NOT EXISTS \"$seqName\" START $startVal;
            ALTER TABLE \"$table\" ALTER COLUMN id SET DEFAULT nextval('\"$seqName\"'::regclass);
            SELECT setval('\"$seqName\"', $startVal, false);
        ");

        $fixed[] = $table;
        echo "  ✅ $table  (sequence starts at $startVal)\n";
    } catch (Exception $e) {
        $errors[] = "$table: " . $e->getMessage();
        echo "  ❌ $table: " . $e->getMessage() . "\n";
    }
}

echo "\n--- SUMMARY ---\n";
echo "Fixed:   " . count($fixed) . " tables\n";
echo "Errors:  " . count($errors) . " tables\n";

if ($errors) {
    echo "\nErrors:\n";
    foreach ($errors as $e) echo "  - $e\n";
}
