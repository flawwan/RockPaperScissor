<?php
require 'config/config.php';
require 'lib/Database.php';
require 'lib/Server.php';
$server = new Server($db);
$server->authenticateServer(API_KEY);