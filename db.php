<?php
// MongoDB Atlas connection.
// 1. Create free cluster at https://cloud.mongodb.com
// 2. Database Access -> add user + password
// 3. Network Access -> allow your IP (or 0.0.0.0/0 for learning)
// 4. Connect -> Drivers -> PHP -> copy the connection string
// 5. Paste it below (keep the quotes):
$MONGO_URI = "mongodb+srv://bankazioles_db_user:tcUQhfJjTL2Ews6R@innsta.kkclwyw.mongodb.net/?appName=innsta";
$MONGO_DB  = "instagram_clone";
$MONGO_COL = "logins";

$manager = new MongoDB\Driver\Manager($MONGO_URI);
