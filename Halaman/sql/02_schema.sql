CREATE TABLE users (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role VARCHAR(20) NOT NULL CHECK (role IN ('pelapor', 'teknisi')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reports (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    ticket VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(150) NOT NULL,
    location VARCHAR(100) NOT NULL,
    issue VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Diterima'
        CHECK (status IN ('Diterima', 'Dijadwalkan', 'Selesai', 'Ditolak')),
    reporter_id BIGINT NOT NULL REFERENCES users (id),
    technician_id BIGINT REFERENCES users (id),
    reported_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX reports_reporter_id_idx ON reports (reporter_id);
CREATE INDEX reports_status_idx ON reports (status);
