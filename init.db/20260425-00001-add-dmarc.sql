CREATE TABLE IF NOT EXISTS dmarc_org (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    org_name TEXT NOT NULL,
    email TEXT NOT NULL,
    extra_contact_info TEXT
);

CREATE TABLE IF NOT EXISTS dmarc_report (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    metadata_org_id BIGINT UNSIGNED NOT NULL,
    metadata_report_id TEXT NOT NULL,
    date_range_begin TIMESTAMP NOT NULL,
    date_range_end TIMESTAMP NOT NULL,
    -- ToDo: Add policy later
    created_at TIMESTAMP DEFAULT NOW(),
    FOREIGN KEY (metadata_org_id) REFERENCES dmarc_org(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS dmarc_errors (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    error TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS dmarc_report_error_assign (
    dmarc_report_id BIGINT UNSIGNED NOT NULL,
    dmarc_errors_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (dmarc_report_id, dmarc_errors_id),
    FOREIGN KEY (dmarc_report_id) REFERENCES dmarc_report(id) ON DELETE CASCADE,
    FOREIGN KEY (dmarc_errors_id) REFERENCES dmarc_errors(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS dmarc_record (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    dmarc_report_id BIGINT UNSIGNED NOT NULL,
    row_source_ip_id BIGINT UNSIGNED NOT NULL,
    row_count BIGINT UNSIGNED NOT NULL,
    -- ToDo: Add row.policy_evaluated later
    identifiers_envelope_to_id BIGINT UNSIGNED,
    identifiers_header_from_id BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (dmarc_report_id) REFERENCES dmarc_report(id) ON DELETE CASCADE,
    FOREIGN KEY (row_source_ip_id) REFERENCES general_ip(id) ON DELETE CASCADE,
    FOREIGN KEY (identifiers_envelope_to_id) REFERENCES general_domain(id) ON DELETE CASCADE,
    FOREIGN KEY (identifiers_header_from_id) REFERENCES general_domain(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS dmarc_spf_result (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    dmarc_record_id BIGINT UNSIGNED NOT NULL,
    domain_id BIGINT UNSIGNED NOT NULL,
    spf_result ENUM(
        'none', 'neutral', 'pass', 'fail', 'softfail', 'temperror', 'permerror',
        'unknown', 'error',
        'hardfail' -- In some provider
    ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
    FOREIGN KEY (dmarc_record_id) REFERENCES dmarc_record(id) ON DELETE CASCADE,
    FOREIGN KEY (domain_id) REFERENCES general_domain(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS dmarc_dkim_selector (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    selector TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS dmarc_dkim_human_result (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    human_result TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS dmarc_dkim_result (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    dmarc_record_id BIGINT UNSIGNED NOT NULL,
    domain_id BIGINT UNSIGNED NOT NULL,
    selector_id BIGINT UNSIGNED,
    dkim_result ENUM(
        'none', 'pass', 'fail', 'policy', 'neutral', 'temperror', 'permerror'
    ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
    human_result_id BIGINT UNSIGNED,
    FOREIGN KEY (dmarc_record_id) REFERENCES dmarc_record(id) ON DELETE CASCADE,
    FOREIGN KEY (domain_id) REFERENCES general_domain(id) ON DELETE CASCADE,
    FOREIGN KEY (selector_id) REFERENCES dmarc_dkim_selector(id) ON DELETE CASCADE,
    FOREIGN KEY (human_result_id) REFERENCES dmarc_dkim_human_result(id) ON DELETE CASCADE
);