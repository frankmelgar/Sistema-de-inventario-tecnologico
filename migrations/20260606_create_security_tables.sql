CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(80) NOT NULL,
    actor_user_id INT NULL,
    actor_role VARCHAR(50) NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NULL,
    entity_type VARCHAR(80) NULL,
    entity_id BIGINT NULL,
    metadata TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_event_created (event_type, created_at),
    INDEX idx_audit_actor_created (actor_user_id, created_at),
    INDEX idx_audit_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(190) NULL,
    successful TINYINT(1) NOT NULL DEFAULT 0,
    user_agent VARCHAR(255) NULL,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_ip_time (ip_address, attempted_at),
    INDEX idx_login_email_time (email, attempted_at),
    INDEX idx_login_success_time (successful, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
