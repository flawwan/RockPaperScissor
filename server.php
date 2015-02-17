<?php
require 'lib/Database.php';
require 'lib/Server.php';
$server = new Server($db);
$server->authenticateServer();