<?php
session_start();
require 'config/config.php';
require 'lib/Database.php';
require 'lib/Api.php';

$api = new Api($db);
$api->ajax();