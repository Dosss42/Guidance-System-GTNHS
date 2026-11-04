<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup storage
    |--------------------------------------------------------------------------
    |
    | Encrypted backup archives are written here. Keep this folder outside the
    | public web root, and copy archives to a second protected location (an
    | external drive or approved storage) as part of the recovery procedure.
    |
    */

    'path' => env('BACKUP_PATH', storage_path('app/backups')),

    /*
    |--------------------------------------------------------------------------
    | Encryption password
    |--------------------------------------------------------------------------
    |
    | Every file inside a backup is AES-256 encrypted with this password.
    | Backups refuse to run without it. Store it separately from the server
    | (for example, sealed with the school head) — without it, a backup
    | cannot be restored.
    |
    */

    'password' => env('BACKUP_PASSWORD'),

    /*
    | How many of the newest archives to keep. Older ones are removed after
    | each successful backup.
    */

    'keep' => (int) env('BACKUP_KEEP', 14),

    /*
    | Client tools used for MySQL backups and restores. XAMPP keeps them in
    | C:\xampp\mysql\bin.
    */

    'mysqldump_binary' => env('BACKUP_MYSQLDUMP', 'mysqldump'),

    'mysql_binary' => env('BACKUP_MYSQL', 'mysql'),

];
