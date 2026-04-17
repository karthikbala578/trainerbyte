<?php
/* ======================
   COURSE DATA
====================== */

$course = [
    'title' => 'Master Strategic Operations',
    'subtitle' => 'MICRO-LEARNING SERIES',
    'description' => 'Master the fundamentals of supply chain management through interactive simulations.',
    'image' => 'https://picsum.photos/400/260'
];

/* ======================
   MODULES DATA
====================== */

$modules = [
    [
        'id' => 1,
        'title' => 'The Basics',
        'status' => 'completed'
    ],
    [
        'id' => 2,
        'title' => 'Resource Planning',
        'status' => 'completed'
    ],
    [
        'id' => 3,
        'title' => 'Logistics Flow',
        'status' => 'active'
    ],
    [
        'id' => 4,
        'title' => 'Market Analysis',
        'status' => 'locked'
    ],
    [
        'id' => 5,
        'title' => 'Risk Mitigation',
        'status' => 'locked'
    ]
];

/* ======================
   PROGRESS CALCULATION
====================== */

$totalModules = count($modules);
$completedModules = count(array_filter($modules, fn($m) => $m['status'] === 'completed'));
$progressPercent = round(($completedModules / $totalModules) * 100);
?>
