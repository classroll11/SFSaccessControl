import os

import mysql.connector


def connect():
    return mysql.connector.connect(
        host=os.getenv("SFS_DB_HOST", "localhost"),
        port=int(os.getenv("SFS_DB_PORT", "3306")),
        database=os.getenv("SFS_DB_NAME", "sfs_access_control"),
        user=os.getenv("SFS_DB_USER", "root"),
        password=os.getenv("SFS_DB_PASSWORD", ""),
        charset="utf8mb4",
        collation="utf8mb4_unicode_ci",
        autocommit=False,
    )
