<?php
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('pelapor');

$reporterId = (int) $_SESSION['user_id'];
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verifyCsrfToken();
    $title = trim($_POST['title'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $issue = trim($_POST['issue'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($title === '' || mb_strlen($title) > 150 || $location === '' || mb_strlen($location) > 100
        || $issue === '' || mb_strlen($issue) > 100 || $description === '') {
        $error = 'Lengkapi semua kolom dengan panjang yang sesuai.';
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO reports (ticket, title, location, issue, description, reporter_id)
             VALUES (:ticket, :title, :location, :issue, :description, :reporter_id)'
        );
        $statement->execute([
            'ticket' => 'TKT-' . strtoupper(bin2hex(random_bytes(4))),
            'title' => $title,
            'location' => $location,
            'issue' => $issue,
            'description' => $description,
            'reporter_id' => $reporterId,
        ]);
        header('Location: Dasbor.php?submitted=1');
        exit;
    }
}

$statement = $pdo->prepare(
    'SELECT ticket, title, reported_at, location, status
     FROM reports
     WHERE reporter_id = :reporter_id
     ORDER BY reported_at DESC'
);
$statement->execute(['reporter_id' => $reporterId]);
$reports = $statement->fetchAll(PDO::FETCH_ASSOC);
$statusClasses = [
    'Diterima' => 'status-diterima',
    'Dijadwalkan' => 'status-dijadwalkan',
    'Selesai' => 'status-selesai',
    'Ditolak' => 'status-ditolak',
];
$activeReports = count(array_filter($reports, static fn ($report) => $report['status'] !== 'Selesai' && $report['status'] !== 'Ditolak'));
$completedReports = count(array_filter($reports, static fn ($report) => $report['status'] === 'Selesai'));
$displayName = (string) $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pelapor / Dasbor - Servitory</title>
    <link rel="stylesheet" href="../assets/css/dasbor.css">
</head>
<body>
    <header class="navbar">
        <div class="nav-brand">
            <div class="logo-box">S</div>
            <span class="brand-title">Servitory<span class="brand-dot">.</span></span>
        </div>
        <div class="nav-user">
            <div class="user-avatar" aria-hidden="true"><?= h(mb_strtoupper(mb_substr($displayName, 0, 1))) ?></div>
            <div class="user-info">
                <span class="user-name"><?= h($displayName) ?></span>
                <span class="user-role">Pelapor</span>
            </div>
            <form class="logout-form" method="post" action="../Keluar.php">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                <button class="btn-logout" type="submit">Keluar</button>
            </form>
        </div>
    </header>

    <main class="main-container">
        <section class="welcome-banner">
            <div>
                <span class="eyebrow">PORTAL PELAPOR</span>
                <h1>Halo, <?= h(explode(' ', $displayName)[0]) ?> <span aria-hidden="true">👋</span></h1>
                <p>Pantau laporan fasilitas dan peralatan Anda dengan mudah.</p>
            </div>
            <div class="welcome-mark" aria-hidden="true">S</div>
        </section>

        <?php if (isset($_GET['submitted'])): ?>
            <p class="form-message form-message-success" role="status">Laporan berhasil dikirim.</p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="form-message form-message-error" role="alert"><?= h($error) ?></p>
        <?php endif; ?>

        <section class="summary-grid" aria-label="Ringkasan laporan">
            <article class="summary-card">
                <span class="summary-icon icon-total" aria-hidden="true">▤</span>
                <div><p>Total laporan</p><strong><?= count($reports) ?></strong></div>
            </article>
            <article class="summary-card">
                <span class="summary-icon icon-progress" aria-hidden="true">◷</span>
                <div><p>Sedang diproses</p><strong><?= $activeReports ?></strong></div>
            </article>
            <article class="summary-card">
                <span class="summary-icon icon-done" aria-hidden="true">✓</span>
                <div><p>Selesai</p><strong><?= $completedReports ?></strong></div>
            </article>
        </section>

        <button class="create-report-card" id="openModalBtn" type="button">
            <span class="plus-icon" aria-hidden="true">+</span>
            <span class="create-report-copy">
                <strong>Buat laporan baru</strong>
                <span>Laporkan kendala peralatan atau fasilitas di ruangan Anda.</span>
            </span>
            <span class="create-report-arrow" aria-hidden="true">→</span>
        </button>

        <section class="history-section">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">PEMANTAUAN</span>
                    <h2>Riwayat laporan</h2>
                    <p>Lihat perkembangan laporan yang pernah Anda kirim.</p>
                </div>
                <span class="report-count"><?= count($reports) ?> laporan</span>
            </div>

            <div class="table-responsive">
                <table class="report-table">
                    <thead>
                        <tr><th>Nomor Tiket</th><th>Masalah</th><th>Tanggal</th><th>Lokasi</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($reports === []): ?>
                            <tr><td colspan="5">Anda belum mengirim laporan.</td></tr>
                        <?php else: ?>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td class="ticket-cell"><?= h($report['ticket']) ?></td>
                                    <td class="issue-cell"><?= h($report['title']) ?></td>
                                    <td><?= h(date('d M Y', strtotime($report['reported_at']))) ?></td>
                                    <td><?= h($report['location']) ?></td>
                                    <td><span class="badge <?= h($statusClasses[$report['status']] ?? 'status-ditolak') ?>"><?= h($report['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div class="modal-overlay" id="reportModal" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <div class="modal-header">
                <div>
                    <span class="eyebrow">LAPORAN BARU</span>
                    <h2 id="modalTitle">Buat laporan baru</h2>
                </div>
                <button class="close-btn" id="closeModalBtn" type="button" aria-label="Tutup">&times;</button>
            </div>

            <form class="modal-form" method="post" action="Dasbor.php">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                <div class="form-group">
                    <label for="judul">Judul Laporan</label>
                    <input type="text" id="judul" name="title" class="form-control" maxlength="150" placeholder="Contoh: Proyektor tidak menyala" required>
                </div>
                <div class="form-group">
                    <label for="lokasi">Lokasi Ruang</label>
                    <input type="text" id="lokasi" name="location" class="form-control" maxlength="100" placeholder="Contoh: RT 01" required>
                </div>
                <div class="form-group">
                    <label for="kendala">Jenis Kendala</label>
                    <input type="text" id="kendala" name="issue" class="form-control" maxlength="100" placeholder="Contoh: Peralatan rusak" required>
                </div>
                <div class="form-group">
                    <label for="deskripsi">Deskripsi Masalah</label>
                    <textarea id="deskripsi" name="description" class="form-control textarea-control" placeholder="Jelaskan kendala yang terjadi..." rows="4" required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" id="cancelBtn">Batal</button>
                    <button type="submit" class="btn-submit">Kirim laporan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('reportModal');
        const openBtn = document.getElementById('openModalBtn');
        const closeBtn = document.getElementById('closeModalBtn');
        const cancelBtn = document.getElementById('cancelBtn');

        function closeModal() {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        }

        openBtn.addEventListener('click', function() {
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            document.getElementById('judul').focus();
        });
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        window.addEventListener('click', function(event) {
            if (event.target === modal) closeModal();
        });
        window.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal.style.display === 'flex') closeModal();
        });
    </script>
</body>
</html>
