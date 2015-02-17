<?php
session_start();
require 'lib/Database.php';
require 'lib/Api.php';

$api = new Api($db);
$api->pick();