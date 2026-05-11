-- Prepare TLS-RPT report table
-- Based on: RFC 8640 (https://www.rfc-editor.org/rfc/rfc8460#section-4)
CREATE TABLE IF NOT EXISTS tls_rpt_report_organization (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    organization_name TEXT NOT NULL,
    contact_info TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS tls_rpt (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    -- organization_name TEXT NOT NULL,
    -- contact_info TEXT NOT NULL,
    tls_rpt_report_organization_id BIGINT UNSIGNED NOT NULL,
    date_range_start TIMESTAMP NOT NULL,
    date_range_end TIMESTAMP NOT NULL,
    report_id TEXT NOT NULL,
    -- received_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    FOREIGN KEY (tls_rpt_report_organization_id) REFERENCES tls_rpt_report_organization(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS general_domain (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    domain_name VARCHAR(256) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS tls_rpt_mx_host_pattern (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    pattern VARCHAR(256) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS tls_rpt_policy (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tls_rpt_id BIGINT UNSIGNED NOT NULL,
    policy_type ENUM(
        'tlsa', 'sts', 'no-policy-found'
    ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
    -- policy_string ...,
    policy_domain_id BIGINT UNSIGNED,
    -- policy_mx_host ...,
    summary_total_successful_sessions BIGINT UNSIGNED NOT NULL,
    summary_total_failed_sessions BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (tls_rpt_id) REFERENCES tls_rpt(id) ON DELETE CASCADE,
    FOREIGN KEY (policy_domain_id) REFERENCES general_domain(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tls_rpt_policy_mx_host_pattern_assign (
    tls_rpt_policy_id BIGINT UNSIGNED NOT NULL,
    tls_rpt_mx_host_pattern_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (tls_rpt_policy_id, tls_rpt_mx_host_pattern_id),
    FOREIGN KEY (tls_rpt_policy_id) REFERENCES tls_rpt_policy(id) ON DELETE CASCADE,
    FOREIGN KEY (tls_rpt_mx_host_pattern_id) REFERENCES tls_rpt_mx_host_pattern(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tls_rpt_policy_string (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    policy_string TEXT NOT NULL
);

-- To reuse policy string across multiple policies for storage optimization.
CREATE TABLE IF NOT EXISTS tls_rpt_policy_string_assign (
    tls_rpt_policy_id BIGINT UNSIGNED NOT NULL,
    tls_rpt_policy_string_id BIGINT UNSIGNED NOT NULL,
    policy_string_order INT NOT NULL,
    PRIMARY KEY (tls_rpt_policy_id, tls_rpt_policy_string_id),
    FOREIGN KEY (tls_rpt_policy_id) REFERENCES tls_rpt_policy(id) ON DELETE CASCADE,
    FOREIGN KEY (tls_rpt_policy_string_id) REFERENCES tls_rpt_policy_string(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS general_helo(
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    helo_string VARCHAR(256) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS general_ip(
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    ip_address INET6 NOT NULL UNIQUE -- save IPv4 address too
);

CREATE TABLE IF NOT EXISTS tls_rpt_policy_additional_information (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    additional_information TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS tls_rpt_policy_failure_reason_code (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    failure_reason_code TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL
);

CREATE TABLE IF NOT EXISTS tls_rpt_policy_failure_details (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tls_rpt_policy_id BIGINT UNSIGNED NOT NULL,
    -- See: https://www.rfc-editor.org/rfc/rfc8460#section-4.3
    result_type ENUM(
        -- 4.3.1. Negotiation Failures
        'starttls-not-supported', 'certificate-host-mismatch', 'certificate-expired', 'certificate-not-trusted', 'validation-failure',
        -- 4.3.2. Policy Failures
        -- 4.3.2.1 DANE-Specific Policy Failures
        'tlsa-invalid', 'dnssec-invalid', 'dane-requred',
        -- 4.3.2.2 MTA-STS-specific Policy Failures
        'sts-policy-fetch-error', 'sts-policy-invalid', 'sts-webpki-invalid'
        -- 4.3.3 General Failures
        -- 'validation-failure' (already included in 4.3.1)
        -- 4.3.4 Transient Failures
        -- Noting
    ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
    sending_mta_ip_id BIGINT UNSIGNED, -- This is not in Microsoft's reporting
    receiving_mx_hostname_id BIGINT UNSIGNED, -- This is not in Microsoft's reporting
    receiving_mx_helo_id BIGINT UNSIGNED, -- This is not in Google and Microsoft's reporting
    receiving_ip_id BIGINT UNSIGNED, -- This is not in Microsoft's reporting
    failed_session_count BIGINT UNSIGNED NOT NULL,
    additional_information_id BIGINT UNSIGNED, -- This is not in Google's reporting
    failure_reason_code_id BIGINT UNSIGNED, -- This is not in Google's reporting
    
    FOREIGN KEY (tls_rpt_policy_id) REFERENCES tls_rpt_policy(id) ON DELETE CASCADE,
    FOREIGN KEY (receiving_mx_hostname_id) REFERENCES general_domain(id) ON DELETE CASCADE,
    FOREIGN KEY (receiving_mx_helo_id) REFERENCES general_helo(id) ON DELETE CASCADE,
    FOREIGN KEY (receiving_ip_id) REFERENCES general_ip(id) ON DELETE CASCADE,
    FOREIGN KEY (sending_mta_ip_id) REFERENCES general_ip(id) ON DELETE CASCADE,
    FOREIGN KEY (additional_information_id) REFERENCES tls_rpt_policy_additional_information(id) ON DELETE CASCADE,
    FOREIGN KEY (failure_reason_code_id) REFERENCES tls_rpt_policy_failure_reason_code(id) ON DELETE CASCADE
);
