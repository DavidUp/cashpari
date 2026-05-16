<?php
/**
 * MySQL to PostgreSQL SQL Converter for BetLab
 * Converts the installer database.sql to PostgreSQL-compatible SQL
 */

$inputFile  = __DIR__ . '/../../../Files/install/database.sql';
$outputFile = __DIR__ . '/../../../Files/install/database_postgres.sql';

$sql = file_get_contents($inputFile);

// ── 1. Strip MySQL-only header lines ─────────────────────────────────────────
$skipPatterns = [
    '/^SET SQL_MODE.*$/m',
    '/^START TRANSACTION;/m',
    '/^SET time_zone.*$/m',
    '/^\/\*!.*\*\/;?$/m',
    '/^COMMIT;/m',
    '/^-- phpMyAdmin.*$/m',
    '/^-- version.*$/m',
    '/^-- https.*$/m',
    '/^-- Host:.*$/m',
    '/^-- Generation.*$/m',
    '/^-- Server version.*$/m',
    '/^-- PHP Version.*$/m',
    '/^-- Database:.*$/m',
];
foreach ($skipPatterns as $p) {
    $sql = preg_replace($p, '', $sql);
}

// ── 2. Replace backtick identifiers with double-quotes ────────────────────────
$sql = str_replace('`', '"', $sql);

// ── 3. Remove MySQL table options at end of CREATE TABLE ─────────────────────
$sql = preg_replace('/\)\s*ENGINE=\w+[^;]*/m', ')', $sql);

// ── 4. AUTO_INCREMENT primary keys → BIGSERIAL / SERIAL ─────────────────────
$sql = preg_replace('/"id" bigint UNSIGNED NOT NULL AUTO_INCREMENT/', '"id" BIGSERIAL PRIMARY KEY', $sql);
$sql = preg_replace('/"id" int UNSIGNED NOT NULL AUTO_INCREMENT/', '"id" SERIAL PRIMARY KEY', $sql);
$sql = preg_replace('/"(\w+_id)" bigint UNSIGNED NOT NULL AUTO_INCREMENT/', '"$1" BIGSERIAL PRIMARY KEY', $sql);

// ── 5. Remove leftover AUTO_INCREMENT references ─────────────────────────────
$sql = preg_replace('/\bAUTO_INCREMENT\b/i', '', $sql);

// ── 6. Remove UNSIGNED ───────────────────────────────────────────────────────
$sql = preg_replace('/\bUNSIGNED\b/i', '', $sql);

// ── 7. Data types ─────────────────────────────────────────────────────────────
$sql = preg_replace('/\btinyint\(1\)/i', 'smallint', $sql);
$sql = preg_replace('/\btinyint\(\d+\)/i', 'smallint', $sql);
$sql = preg_replace('/\btinyint\b/i', 'smallint', $sql);  // standalone tinyint
$sql = preg_replace('/\bbigint\(\d+\)/i', 'bigint', $sql);
$sql = preg_replace('/\bint\(\d+\)/i', 'integer', $sql);
$sql = preg_replace('/\bmediumint\(\d+\)/i', 'integer', $sql);
$sql = preg_replace('/\bsmallint\(\d+\)/i', 'smallint', $sql);
$sql = preg_replace('/\bdouble\b/i', 'double precision', $sql);
$sql = preg_replace('/\blongtext\b/i', 'text', $sql);
$sql = preg_replace('/\bmediumtext\b/i', 'text', $sql);
$sql = preg_replace('/\bdatetime\b/i', 'timestamp', $sql);
$sql = preg_replace('/\bbigint NOT NULL\b/i', 'bigint NOT NULL', $sql);

// Remove MySQL COMMENT clauses on columns (COMMENT 'text')
$sql = preg_replace("/\s+COMMENT\s+'[^']*(?:''[^']*)*'/", '', $sql);
$sql = preg_replace('/\s+COMMENT\s+"[^"]*"/', '', $sql);

// Escape backslashes in INSERT data for PostgreSQL
$sql = preg_replace_callback("/INSERT INTO[^;]+;/s", function($m) {
    // Replace \'  inside single-quoted strings with ''
    $insert = $m[0];
    $insert = str_replace("\\'", "''", $insert);
    $insert = str_replace('\\"', '"', $insert);
    return $insert;
}, $sql);

// ── 8. Remove CHARACTER SET / COLLATE / CHARSET from column defs ─────────────
$sql = preg_replace('/\s+CHARACTER SET \w+/i', '', $sql);
$sql = preg_replace('/\s+COLLATE \w+/i', '', $sql);
$sql = preg_replace('/\s+DEFAULT CHARSET=\w+/i', '', $sql);
$sql = preg_replace('/\s+COLLATION=\w+/i', '', $sql);

// ── 9. Remove MySQL-specific ALTER TABLE lines (index / key stuff) ────────────
$sql = preg_replace_callback('/^ALTER TABLE[^;]+;/ms', function ($m) {
    // Only keep FK constraints
    if (stripos($m[0], 'FOREIGN KEY') !== false) {
        return $m[0];
    }
    return '';
}, $sql);

// ── 10. Remove inline KEY / UNIQUE KEY / PRIMARY KEY lines inside CREATE TABLE
// We'll let the BIGSERIAL handle primary keys
$sql = preg_replace('/^\s*PRIMARY KEY\s*\("[^"]+"\),?\s*$/m', '', $sql);
$sql = preg_replace('/^\s*UNIQUE KEY\s+"[^"]+"\s*\([^)]+\),?\s*$/m', '', $sql);
$sql = preg_replace('/^\s*KEY\s+"[^"]+"\s*\([^)]+\),?\s*$/m', '', $sql);

// ── 11. Clean up trailing commas before closing paren of CREATE TABLE ─────────
$sql = preg_replace('/,\s*\n(\s*\))/m', "\n$1", $sql);

// ── 12. Fix AUTO_INCREMENT = N table option ───────────────────────────────────
$sql = preg_replace('/\bAUTO_INCREMENT\s*=\s*\d+/i', '', $sql);

// ── 13. Wrap in a transaction ─────────────────────────────────────────────────
$header = "-- BetLab PostgreSQL Schema\n-- Converted from MySQL dump\n-- Import into Supabase SQL Editor\n\n" .
          "SET standard_conforming_strings = off;\n" .  // Accept MySQL-style \' escapes
          "SET client_encoding = 'UTF8';\n\n" .
          "BEGIN;\n\n";
$footer = "\nCOMMIT;\n";

$output = $header . trim($sql) . $footer;

file_put_contents($outputFile, $output);

$tableCount = substr_count($output, 'CREATE TABLE');
$insertCount = substr_count($output, 'INSERT INTO');

echo "✅ Conversion complete!\n";
echo "   Output: $outputFile\n";
echo "   Tables found:  $tableCount\n";
echo "   INSERT blocks: $insertCount\n";
echo "   File size: " . round(filesize($outputFile) / 1024, 1) . " KB\n";
