<?php
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('teknisi');

$technicianId = (int) $_SESSION['user_id'];
$message = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verifyCsrfToken();
    $action = $_POST['action'] ?? '';
    $reportId = filter_var($_POST['report_id'] ?? null, FILTER_VALIDATE_INT);
    $transitions = [
        'accept' => ['Diterima', 'Diterima', true],
        'reject' => ['Diterima', 'Ditolak', false],
        'schedule' => ['Diterima', 'Dijadwalkan', false],
        'complete' => ['Dijadwalkan', 'Selesai', false],
    ];

    if (!$reportId || !isset($transitions[$action])) {
        $error = 'Aksi laporan tidak valid.';
    } else {
        [$fromStatus, $toStatus, $assignOnAccept] = $transitions[$action];
        if ($assignOnAccept) {
            $statement = $pdo->prepare(
                "UPDATE reports SET technician_id = :technician_id
                 WHERE id = :id AND status = :status AND technician_id IS NULL"
            );
        } else {
            $statement = $pdo->prepare(
                'UPDATE reports SET status = :new_status, technician_id = :technician_id
                 WHERE id = :id AND status = :status
                   AND (technician_id IS NULL OR technician_id = :technician_match)'
            );
        }

        $parameters = [
            'technician_id' => $technicianId,
            'id' => $reportId,
            'status' => $fromStatus,
        ];
        if (!$assignOnAccept) {
            $parameters['new_status'] = $toStatus;
            $parameters['technician_match'] = $technicianId;
        }
        $statement->execute($parameters);

        if ($statement->rowCount() === 1) {
            $message = [
                'accept' => 'Laporan berhasil diterima dan ditugaskan kepada Anda.',
                'reject' => 'Laporan berhasil ditolak.',
                'schedule' => 'Perbaikan berhasil dijadwalkan.',
                'complete' => 'Laporan berhasil ditandai selesai.',
            ][$action];
        } else {
            $error = 'Laporan sudah diproses teknisi lain atau statusnya telah berubah.';
        }
    }
}

$statement = $pdo->prepare(
    "SELECT r.id, r.ticket, r.title, r.location, r.issue, r.description,
            r.status, r.reported_at, u.name AS reporter_name,
            technician.name AS technician_name
     FROM reports r
     JOIN users u ON u.id = r.reporter_id
     LEFT JOIN users technician ON technician.id = r.technician_id
     WHERE r.status IN ('Diterima', 'Dijadwalkan')
       AND (r.technician_id IS NULL OR r.technician_id = :technician_id)
     ORDER BY r.reported_at ASC"
);
$statement->execute(['technician_id' => $technicianId]);
$reports = $statement->fetchAll(PDO::FETCH_ASSOC);
$pendingCount = count(array_filter($reports, static fn ($report) => $report['status'] === 'Diterima' && $report['technician_name'] === null));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teknisi / Dasbor - Servitory</title>
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
            <a href="dasbor.php" class="tab-item active">Dasbor</a>
            <a href="riwayat.php" class="tab-item">Riwayat Seluruh Laporan</a>
        </div>
    </nav>

    <main class="main-container">
        <?php if ($message !== ''): ?>
            <p class="form-message form-message-success" role="status"><?= h($message) ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="form-message form-message-error" role="alert"><?= h($error) ?></p>
        <?php endif; ?>

        <section class="section-block">
            <div class="section-header-top">
                <h2>Laporan aktif</h2>
                <span class="info-text"><?= $pendingCount ?> laporan baru menunggu verifikasi</span>
            </div>
            <div class="reports-grid">
                <?php if ($reports === []): ?>
                    <p class="empty-state">Belum ada laporan aktif untuk ditangani.</p>
                <?php else: ?>
                    <?php foreach ($reports as $report): ?>
                        <article class="report-card">
                            <div class="card-top-row">
                                <span class="ticket-id"><?= h($report['ticket']) ?></span>
                                <span class="badge-baru"><?= h($report['status']) ?></span>
                            </div>
                            <h3 class="report-title"><?= h($report['title']) ?></h3>
                            <p class="report-detail"><strong>Pelapor:</strong> <?= h($report['reporter_name']) ?></p>
                            <p class="report-detail"><strong>Lokasi:</strong> <?= h($report['location']) ?></p>
                            <p class="report-detail"><strong>Jenis:</strong> <?= h($report['issue']) ?></p>
                            <p class="report-detail"><strong>Tanggal:</strong> <?= h(date('d M Y', strtotime($report['reported_at']))) ?></p>
                            <details class="report-description">
                                <summary>Lihat deskripsi</summary>
                                <p><?= nl2br(h($report['description'])) ?></p>
                            </details>
                            <div class="card-actions">
                                <?php if ($report['status'] === 'Diterima' && $report['technician_name'] === null): ?>
                                    <form method="post" action="dasbor.php">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
                                        <button class="btn-terima" type="submit" name="action" value="accept">Terima</button>
                                    </form>
                                    <form method="post" action="dasbor.php" data-confirm="Tolak laporan ini?">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
                                        <button class="btn-tolak" type="submit" name="action" value="reject">Tolak</button>
                                    </form>
                                <?php elseif ($report['status'] === 'Diterima'): ?>
                                    <form method="post" action="dasbor.php">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
                                        <button class="btn-jadwal" type="submit" name="action" value="schedule">Jadwalkan Perbaikan</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="dasbor.php" data-confirm="Tandai laporan ini selesai?">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
                                        <button class="btn-terima" type="submit" name="action" value="complete">Tandai Selesai</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="section-block scheduling-section">
            <div class="scheduling-content">
                <h2>Alur penanganan</h2>
                <div class="workflow-flow">
                    <span class="status-box">Diterima</span>
                    <span aria-hidden="true">&rarr;</span>
                    <span class="status-box">Dijadwalkan</span>
                    <span aria-hidden="true">&rarr;</span>
                    <span class="status-box">Selesai</span>
                </div>
            </div>
            <a class="btn-jadwal" href="riwayat.php?status=Dijadwalkan">Lihat Riwayat Laporan</a>
        </section>
    </main>
    <script>
        document.querySelectorAll('form[data-confirm]').forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!window.confirm(form.dataset.confirm)) event.preventDefault();
            });
        });
    </script>
</body>
</html>
