-- job definition
CREATE TABLE job (
     id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
     name TEXT(25) NOT NULL,
     "data" TEXT NOT NULL,
     interval INTEGER,
     nextrun INTEGER,
     lastrun INTEGER
);


-- "user" definition
CREATE TABLE "user" (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    email TEXT(50) NOT NULL,
    password TEXT(72) NOT NULL
);