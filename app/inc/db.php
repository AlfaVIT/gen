<?php

function db()
{
    static $pdo = null;
    if ($pdo === null) {
        $file = cfg('db_file');
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (!file_exists($file)) {
            touch($file);
        }
        $pdo = new PDO('sqlite:' . $file, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA busy_timeout = 5000');
        ensureSchema($pdo);
    }
    return $pdo;
}

function ensureSchema(PDO $pdo)
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL UNIQUE,
        name TEXT NOT NULL DEFAULT '',
        password_hash TEXT,
        role TEXT NOT NULL DEFAULT 'guest',
        status TEXT NOT NULL DEFAULT 'pending',
        activation_token TEXT,
        activation_expires TEXT,
        reset_token TEXT,
        reset_expires TEXT,
        consent INTEGER NOT NULL DEFAULT 0,
        consent_at TEXT,
        last_login TEXT,
        created_at TEXT NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS trees (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        owner_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        title TEXT NOT NULL,
        description TEXT NOT NULL DEFAULT '',
        is_public INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS persons (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        tree_id INTEGER NOT NULL REFERENCES trees(id) ON DELETE CASCADE,
        name TEXT NOT NULL,
        gender TEXT NOT NULL DEFAULT 'u',
        birth_date TEXT NOT NULL DEFAULT '',
        birth_place TEXT NOT NULL DEFAULT '',
        death_date TEXT NOT NULL DEFAULT '',
        death_place TEXT NOT NULL DEFAULT '',
        is_public INTEGER NOT NULL DEFAULT 1,
        description TEXT NOT NULL DEFAULT '',
        social TEXT NOT NULL DEFAULT '[]',
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL
    )");

    try {
        $pdo->exec("ALTER TABLE persons ADD COLUMN avatar TEXT NOT NULL DEFAULT ''");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE persons ADD COLUMN avatar_full TEXT NOT NULL DEFAULT ''");
    } catch (Exception $e) {
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS unions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        tree_id INTEGER NOT NULL REFERENCES trees(id) ON DELETE CASCADE,
        start_date TEXT NOT NULL DEFAULT '',
        end_date TEXT NOT NULL DEFAULT '',
        note TEXT NOT NULL DEFAULT '',
        created_at TEXT NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS union_members (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        union_id INTEGER NOT NULL REFERENCES unions(id) ON DELETE CASCADE,
        person_id INTEGER NOT NULL REFERENCES persons(id) ON DELETE CASCADE,
        UNIQUE(union_id, person_id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS union_children (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        union_id INTEGER NOT NULL REFERENCES unions(id) ON DELETE CASCADE,
        person_id INTEGER NOT NULL REFERENCES persons(id) ON DELETE CASCADE,
        UNIQUE(union_id, person_id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS media (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        tree_id INTEGER NOT NULL REFERENCES trees(id) ON DELETE CASCADE,
        kind TEXT NOT NULL,
        title TEXT NOT NULL DEFAULT '',
        note TEXT NOT NULL DEFAULT '',
        archival INTEGER NOT NULL DEFAULT 0,
        url TEXT NOT NULL DEFAULT '',
        file TEXT NOT NULL DEFAULT '',
        original TEXT NOT NULL DEFAULT '',
        mime TEXT NOT NULL DEFAULT '',
        size INTEGER NOT NULL DEFAULT 0,
        created_by INTEGER,
        created_at TEXT NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS media_bindings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        media_id INTEGER NOT NULL REFERENCES media(id) ON DELETE CASCADE,
        person_id INTEGER NOT NULL REFERENCES persons(id) ON DELETE CASCADE,
        btype TEXT NOT NULL DEFAULT 'region',
        page INTEGER NOT NULL DEFAULT 0,
        x REAL NOT NULL DEFAULT 0,
        y REAL NOT NULL DEFAULT 0,
        w REAL NOT NULL DEFAULT 0,
        h REAL NOT NULL DEFAULT 0,
        tstart REAL NOT NULL DEFAULT 0,
        tend REAL NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL
    )");

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_trees_owner ON trees(owner_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_persons_tree ON persons(tree_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_unions_tree ON unions(tree_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_media_tree ON media(tree_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_bind_media ON media_bindings(media_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_bind_person ON media_bindings(person_id)');
}
