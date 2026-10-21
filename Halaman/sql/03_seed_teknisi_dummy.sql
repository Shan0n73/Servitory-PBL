INSERT INTO users (name, username, email, password_hash, role)
VALUES
    ('Teknisi Demo 01', 'teknisi01', 'teknisi01@servitory.local', '$2y$10$Cs//TdPq1CEfaRYVojJ4X.A/JucpCEdUcOTvD9aVQWjRpgx5dgXD.', 'teknisi'),
    ('Teknisi Demo 02', 'teknisi02', 'teknisi02@servitory.local', '$2y$10$Cs//TdPq1CEfaRYVojJ4X.A/JucpCEdUcOTvD9aVQWjRpgx5dgXD.', 'teknisi'),
    ('Teknisi Demo 03', 'teknisi03', 'teknisi03@servitory.local', '$2y$10$Cs//TdPq1CEfaRYVojJ4X.A/JucpCEdUcOTvD9aVQWjRpgx5dgXD.', 'teknisi'),
    ('Teknisi Demo 04', 'teknisi04', 'teknisi04@servitory.local', '$2y$10$Cs//TdPq1CEfaRYVojJ4X.A/JucpCEdUcOTvD9aVQWjRpgx5dgXD.', 'teknisi'),
    ('Teknisi Demo 05', 'teknisi05', 'teknisi05@servitory.local', '$2y$10$Cs//TdPq1CEfaRYVojJ4X.A/JucpCEdUcOTvD9aVQWjRpgx5dgXD.', 'teknisi'),
    ('Teknisi Demo 06', 'teknisi06', 'teknisi06@servitory.local', '$2y$10$Cs//TdPq1CEfaRYVojJ4X.A/JucpCEdUcOTvD9aVQWjRpgx5dgXD.', 'teknisi'),
    ('Teknisi Demo 07', 'teknisi07', 'teknisi07@servitory.local', '$2y$10$Cs//TdPq1CEfaRYVojJ4X.A/JucpCEdUcOTvD9aVQWjRpgx5dgXD.', 'teknisi'),
    ('Teknisi Demo 08', 'teknisi08', 'teknisi08@servitory.local', '$2y$10$Cs//TdPq1CEfaRYVojJ4X.A/JucpCEdUcOTvD9aVQWjRpgx5dgXD.', 'teknisi'),
    ('Teknisi Demo 09', 'teknisi09', 'teknisi09@servitory.local', '$2y$10$Cs//TdPq1CEfaRYVojJ4X.A/JucpCEdUcOTvD9aVQWjRpgx5dgXD.', 'teknisi'),
    ('Teknisi Demo 10', 'teknisi10', 'teknisi10@servitory.local', '$2y$10$Cs//TdPq1CEfaRYVojJ4X.A/JucpCEdUcOTvD9aVQWjRpgx5dgXD.', 'teknisi')
ON CONFLICT (username) DO NOTHING;
