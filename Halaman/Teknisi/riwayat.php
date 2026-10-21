<?php
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('teknisi');

$statuses = ['Diterima', 'Dijadwalkan', 'Selesai', 'Ditolak'];
$query = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$issue = $_GET['issue'] ?? '';
$location = $_GET['location'] ?? '';
$technicianId = $_GET['technician'] ?? '';
$dateFrom = $_GET['from'] ?? '';
$dateTo = $_GET['to'] ?? '';
$sort = ($_GET['sort'] ?? 'newest') === 'oldest' ? 'oldest' : 'newest';
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$perPage = 8;

if (!in_array($status, $statuses, true)) {
    $status = '';
}
foreach (['dateFrom', 'dateTo'] as $dateVariable) {
    if ($$dateVariable !== '') {
        $date = DateTime::createFromFormat('!Y-m-d', $$dateVariable);
        if (!$date || $date->format('Y-m-d') !== $$dateVariable) {
            $$dateVariable = '';
        }
    }
}
if ($technicianId !== '' && !ctype_digit((string) $technicianId)) {
    $technicianId = '';
}

$where = [];
$parameters = [];
if ($query !== '') {
    $where[] = '(r.ticket ILIKE :query_ticket OR r.title ILIKE :query_title OR r.description ILIKE :query_description)';
    $parameters['query_ticket'] = '%' . $query . '%';
    $parameters['query_title'] = '%' . $query . '%';
    $parameters['query_description'] = '%' . $query . '%';
}
if ($status !== '') {
    $where[] = 'r.status = :status';
    $parameters['status'] = $status;
}
if ($issue !== '') {
    $where[] = 'r.issue = :issue';
    $parameters['issue'] = $issue;
}
if ($location !== '') {
    $where[] = 'r.location = :location';
    $parameters['location'] = $location;
}
if ($technicianId !== '') {
    $where[] = 'r.technician_id = :technician_id';
    $parameters['technician_id'] = (int) $technicianId;
}
if ($dateFrom !== '') {
    $where[] = 'r.reported_at >= CAST(:date_from AS date)';
    $parameters['date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = 'r.reported_at < (CAST(:date_to AS date) + INTERVAL \'1 day\')';
    $parameters['date_to'] = $dateTo;
}

$whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
$orderSql = $sort === 'oldest' ? 'ASC' : 'DESC';
$fromSql = " FROM reports r
    JOIN users reporter ON reporter.id = r.reporter_id
    LEFT JOIN users technician ON technician.id = r.technician_id";
$selectSql = 'SELECT r.ticket, reporter.name AS reporter_name, r.title, r.issue,
                     r.reported_at, r.location, technician.name AS technician_name, r.status';

$issues = $pdo->query('SELECT DISTINCT issue FROM reports ORDER BY issue')->fetchAll(PDO::FETCH_COLUMN);
$locations = $pdo->query('SELECT DISTINCT location FROM reports ORDER BY location')->fetchAll(PDO::FETCH_COLUMN);
$technicians = $pdo->query("SELECT id, name FROM users WHERE role = 'teknisi' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

if (($_GET['export'] ?? '') === 'csv') {
    $statement = $pdo->prepare($selectSql . $fromSql . $whereSql . " ORDER BY r.reported_at $orderSql");
    $statement->execute($parameters);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="riwayat-laporan.csv"');
    $output = fopen('php://output', 'w');
    if ($output === false) {
        throw new RuntimeException('Tidak dapat menyiapkan file ekspor CSV.');
    }
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Nomor Tiket', 'Pelapor', 'Masalah', 'Jenis', 'Tanggal', 'Lokasi', 'Teknisi', 'Status']);
    while ($report = $statement->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $report['ticket'],
            $report['reporter_name'],
            $report['title'],
            $report['issue'],
            date('Y-m-d H:i', strtotime($report['reported_at'])),
            $report['location'],
            $report['technician_name'] ?? '-',
            $report['status'],
        ]);
    }
    fclose($output);
    exit;
}

$countStatement = $pdo->prepare('SELECT COUNT(*)' . $fromSql . $whereSql);
$countStatement->execute($parameters);
$totalReports = (int) $countStatement->fetchColumn();
$totalPages = max(1, (int) ceil($totalReports / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$statement = $pdo->prepare($selectSql . $fromSql . $whereSql . " ORDER BY r.reported_at $orderSql LIMIT $perPage OFFSET $offset");
$statement->execute($parameters);
$reports = $statement->fetchAll(PDO::FETCH_ASSOC);

$filterValues = [
    'q' => $query,
    'status' => $status,
    'issue' => $issue,
    'location' => $location,
    'technician' => $technicianId,
    'from' => $dateFrom,
    'to' => $dateTo,
    'sort' => $sort,
];
$exportUrl = 'riwayat.php?' . http_build_query(array_merge($filterValues, ['export' => 'csv']));
$statusClasses = [
    'Diterima' => 'status-diterima',
    'Dijadwalkan' => 'status-dijadwalkan',
    'Selesai' => 'status-selesai',
    'Ditolak' => 'status-ditolak',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teknisi / Riwayat Laporan - Servitory</title>
    <link rel="stylesheet" href="../assets/css/teknisi.css">
    <link rel="stylesheet" href="../assets/css/riwayat.css">
</head>
<body>
    <header class="navbar">
        <div class="nav-brand">
            <div class="logo-box">S</div>
            <span class="brand-title">Servitory</span>
        </div>
        <div class="nav-user">
            <div class="user-avatar" aria-hidden="true"><?= h(mb_strtoupper(mb_substr($_SESSION['user_name'], 0, 1))) ?></div>
            <div class="user-info">
                <span class="user-name"><?= h($_SESSION['user_name']) ?></span>
                <span class="user-role">Teknisi</span>
            </div>
            <form class="logout-form" method="post" action="../Keluar.php">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                <button class="btn-logout" type="submit">Keluar</button>
            </form>
        </div>
    </header>

    <nav class="sub-nav" aria-label="Navigasi teknisi">
        <div class="sub-nav-container">
            <a href="dasbor.php" class="tab-item">Dasbor</a>
            <a href="riwayat.php" class="tab-item active" aria-current="page">Riwayat Seluruh Laporan</a>
        </div>
    </nav>

    <main class="main-container">
        <div class="page-header-row">
            <div>
                <h2>Riwayat Seluruh Laporan</h2>
                <p class="info-text">Cari, saring, dan ekspor laporan yang tersimpan.</p>
            </div>
            <a class="btn-ekspor" href="<?= h($exportUrl) ?>">Ekspor CSV</a>
        </div>

        <form class="filter-card" method="get" action="riwayat.php">
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="filter-q">Pencarian</label>
                    <input id="filter-q" name="q" type="search" class="filter-input" placeholder="Nomor tiket / kata kunci..." value="<?= h($query) ?>">
                </div>
                <div class="filter-group">
                    <label for="filter-status">Status</label>
                    <select id="filter-status" name="status" class="filter-input">
                        <option value="">Semua Status</option>
                        <?php foreach ($statuses as $option): ?>
                            <option value="<?= h($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter-issue">Jenis Masalah</label>
                    <select id="filter-issue" name="issue" class="filter-input">
                        <option value="">Semua Jenis</option>
                        <?php foreach ($issues as $option): ?>
                            <option value="<?= h($option) ?>" <?= $issue === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter-location">Ruang</label>
                    <select id="filter-location" name="location" class="filter-input">
                        <option value="">Semua Ruang</option>
                        <?php foreach ($locations as $option): ?>
                            <option value="<?= h($option) ?>" <?= $location === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter-technician">Teknisi</label>
                    <select id="filter-technician" name="technician" class="filter-input">
                        <option value="">Semua Teknisi</option>
                        <?php foreach ($technicians as $option): ?>
                            <option value="<?= (int) $option['id'] ?>" <?= (string) $technicianId === (string) $option['id'] ? 'selected' : '' ?>><?= h($option['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="filter-bottom-row">
                <div class="filter-group">
                    <label for="filter-from">Dari tanggal</label>
                    <input id="filter-from" name="from" type="date" class="filter-input" value="<?= h($dateFrom) ?>">
                </div>
                <div class="filter-group">
                    <label for="filter-to">Sampai tanggal</label>
                    <input id="filter-to" name="to" type="date" class="filter-input" value="<?= h($dateTo) ?>">
                </div>
                <div class="filter-group">
                    <label for="filter-sort">Urutkan</label>
                    <select id="filter-sort" name="sort" class="filter-input">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Terbaru</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Terlama</option>
                    </select>
                </div>
                <a class="btn-reset" href="riwayat.php">Reset</a>
                <button class="btn-filter" type="submit">Terapkan Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="report-table">
                <thead>
                    <tr><th>Nomor Tiket</th><th>Pelapor</th><th>Masalah</th><th>Jenis</th><th>Tanggal</th><th>Lokasi</th><th>Teknisi</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php if ($reports === []): ?>
                        <tr><td colspan="8">Tidak ada laporan yang cocok dengan filter.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?= h($report['ticket']) ?></td>
                                <td><?= h($report['reporter_name']) ?></td>
                                <td><?= h($report['title']) ?></td>
                                <td><?= h($report['issue']) ?></td>
                                <td><?= h(date('d M Y', strtotime($report['reported_at']))) ?></td>
                                <td><?= h($report['location']) ?></td>
                                <td><?= h($report['technician_name'] ?? '-') ?></td>
                                <td><span class="badge <?= h($statusClasses[$report['status']] ?? 'status-ditolak') ?>"><?= h($report['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="table-footer">
                <span>Menampilkan <?= $totalReports === 0 ? 0 : $offset + 1 ?>-<?= min($offset + $perPage, $totalReports) ?> dari <?= $totalReports ?> laporan</span>
                <nav class="pagination" aria-label="Navigasi halaman">
                    <?php if ($page > 1): ?>
                        <a class="page-btn" href="?<?= h(http_build_query(array_merge($filterValues, ['page' => $page - 1]))) ?>">Sebelumnya</a>
                    <?php endif; ?>
                    <?php for ($pageNumber = max(1, $page - 2); $pageNumber <= min($totalPages, $page + 2); $pageNumber++): ?>
                        <a class="page-btn <?= $pageNumber === $page ? 'active' : '' ?>" href="?<?= h(http_build_query(array_merge($filterValues, ['page' => $pageNumber]))) ?>" <?= $pageNumber === $page ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a class="page-btn" href="?<?= h(http_build_query(array_merge($filterValues, ['page' => $page + 1]))) ?>">Berikutnya</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </main>
</body>
</html>
