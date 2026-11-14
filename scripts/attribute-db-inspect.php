<?php

use App\Services\AttributeDuplicateAudit;

// Read-only preflight. Never boots Laravel or executes seeders/migrations.
require dirname(__DIR__).'/vendor/autoload.php';

$target = $argv[1] ?? '';
if (! in_array($target, ['webv3_testing', 'webv3_attributes_test'], true)) {
    throw new RuntimeException('Explicit test database required.');
}
$env = Dotenv\Dotenv::createArrayBacked(dirname(__DIR__), $target === 'webv3_testing' ? '.env.testing' : '.env')->load();
$pdo = new PDO('mysql:host='.($env['DB_HOST'] ?? '127.0.0.1').';port='.($env['DB_PORT'] ?? '3306').';dbname='.$target.';charset=utf8mb4', $env['DB_USERNAME'], $env['DB_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== $target) {
    throw new RuntimeException('Actual database mismatch.');
}
$pdo->exec('SET TRANSACTION READ ONLY');
$pdo->beginTransaction();
try {
    echo json_encode(['database' => $target, 'account' => $pdo->query('SELECT CURRENT_USER()')->fetchColumn()], JSON_UNESCAPED_UNICODE).PHP_EOL;
    foreach (['attributes', 'attribute_category', 'attribute_values', 'attribute_color'] as $table) {
        echo $table.': '.$pdo->query('SELECT COUNT(*) FROM '.$table)->fetchColumn().PHP_EOL;
    }
    $attributes = $pdo->query('SELECT * FROM attributes ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $links = $pdo->query('SELECT * FROM attribute_category')->fetchAll(PDO::FETCH_ASSOC);
    $values = $pdo->query('SELECT av.*, p.category_id FROM attribute_values av LEFT JOIN products p ON p.id = av.product_id ORDER BY av.id')->fetchAll(PDO::FETCH_ASSOC);
    $report = (new AttributeDuplicateAudit)->report($attributes, $links, $values, $pdo->query('SELECT * FROM attribute_color')->fetchAll(PDO::FETCH_ASSOC));
    if (! in_array('--full', $argv, true)) {
        $history = $report['historical_values'];
        $candidateIds = array_merge([], ...array_column($report['duplicate_groups'], 'candidate_ids'));
        $candidateUnused = array_intersect($candidateIds, $report['unused_attribute_ids']);
        $report = ['duplicate_groups' => count($report['duplicate_groups']),
            'candidate_removals' => $report['candidate_attribute_removals_after_review'],
            'candidate_removals_without_category_or_value_links' => count($candidateUnused),
            'conflict_groups' => array_values(array_filter($report['duplicate_groups'], fn ($group) => $group['conflicts'] !== [])),
            'unused_count' => count($report['unused_attribute_ids']),
            'historical_count' => count($report['historical_values']),
            'exact_match_history' => array_values(array_filter($report['historical_values'], fn ($v) => $v['exact_candidates'] !== [])),
            'orphan_value_count' => $report['orphan_value_count']];
        $categoryIds = array_values(array_unique(array_map(fn ($v) => (int) $v['category_id'], $history)));
        $report['historical_categories'] = $categoryIds === [] ? [] : $pdo->query('SELECT c.id, c.slug, (SELECT COUNT(*) FROM categories child WHERE child.parent_id = c.id) AS child_count FROM categories c WHERE c.id IN ('.implode(',', $categoryIds).')')->fetchAll(PDO::FETCH_ASSOC);
        $report['history_with_changed_same_name_definition'] = count(array_filter($history, fn ($v) => $v['same_name_different_definition'] !== []));
    }
    echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
} finally {
    $pdo->rollBack();
}
