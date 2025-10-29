<?php
require_once __DIR__ . '/vendor/autoload.php';

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

// Initialize Twig
$loader = new FilesystemLoader(__DIR__ . '/templates');
$twig = new Environment($loader);

// Handle session
session_start();

// Simple routing
$page = $_GET['page'] ?? 'landing';

// Mock login system
if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'];
    $pass = $_POST['password'];
    if ($user === 'admin' && $pass === '1234') {
        $_SESSION['logged_in'] = true;
        header("Location: ?page=dashboard");
        exit;
    } else {
        echo $twig->render('login.twig', ['error' => 'Invalid credentials']);
        exit;
    }
}

// Logout
if ($page === 'logout') {
    session_destroy();
    header("Location: ?page=landing");
    exit;
}

// Fetch tickets
$tickets = json_decode(file_get_contents(__DIR__ . '/data/tickets.json'), true) ?? [];

// Handle CRUD (add/delete)
if ($page === 'tickets' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $tickets[] = [
            'id' => uniqid('t'),
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'status' => 'open',
            'created_at' => date('Y-m-d H:i:s')
        ];
    } elseif (isset($_POST['delete'])) {
        $tickets = array_filter($tickets, fn($t) => $t['id'] !== $_POST['delete']);
    }
    file_put_contents(__DIR__ . '/data/tickets.json', json_encode(array_values($tickets)));
}

// Auth guard
if (!isset($_SESSION['logged_in']) && !in_array($page, ['landing', 'login'])) {
    header("Location: ?page=login");
    exit;
}

// Render pages
echo match ($page) {
    'landing' => $twig->render('landing.twig'),
    'login' => $twig->render('login.twig'),
    'dashboard' => $twig->render('dashboard.twig', [
        'tickets' => $tickets,
        'total' => count($tickets),
        'open' => count(array_filter($tickets, fn($t) => $t['status'] === 'open')),
        'resolved' => count(array_filter($tickets, fn($t) => $t['status'] !== 'open')),
    ]),
    'tickets' => $twig->render('tickets.twig', ['tickets' => $tickets]),
    default => $twig->render('landing.twig')
};
?>
