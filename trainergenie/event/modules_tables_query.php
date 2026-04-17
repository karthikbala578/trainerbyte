<?php
session_start();
require "../include/dataconnect.php";

/* ✅ ADD THIS HERE (TOP-LEVEL CONFIG) */
$moduleSources = [
    1 => ['table' => 'card_group', 'id' => 'cg_id', 'name' => 'cg_name'],
    2 => ['table' => 'card_group', 'id' => 'cg_id', 'name' => 'cg_name'],
    5 => ['table' => 'mg6_riskhop_matrix', 'id' => 'id', 'name' => 'game_name'],
    8 => ['table' => 'mg7_games', 'id' => 'id', 'name' => 'title'],
    9 => ['table' => 'mg8_games', 'id' => 'id', 'name' => 'title']
];