-- job definition
CREATE TABLE job (
     id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
     name TEXT(25) NOT NULL,
     "data" TEXT NOT NULL,
     interval INTEGER,
     nextrun INTEGER,
     lastrun INTEGER,
     running INTEGER
);


-- "user" definition
CREATE TABLE "user" (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    email TEXT(50) NOT NULL,
    password TEXT(72) NOT NULL,
    sendmail INTEGER NOT NULL
);

-- run definition
CREATE TABLE run (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    job_id INTEGER NOT NULL,
    exitcode TEXT NOT NULL,
    output TEXT NOT NULL,
    runtime REAL NOT NULL,
    timestamp INTEGER NOT NULL,
    flags TEXT NOT NULL
);