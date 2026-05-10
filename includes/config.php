<?php
date_default_timezone_set('Asia/Manila');
const DB_DSN  = 'mysql:host=sql207.infinityfree.com;dbname=if0_41453740_profitradar;charset=utf8mb4';
const DB_USER = 'if0_41453740';
const DB_PASS = 'Aiedgen2001';

const APP_NAME = 'ProfitRadar Inventory';

// <-- ADD THIS: path from http://localhost to your project
const APP_BASE = '';  // change if your folder name differs

// helper to build URLs consistently
function app_url(string $path): string {
    return '/' . ltrim($path, '/');
}
