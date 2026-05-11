<?php
require_once __DIR__ . '/../vendor/autoload.php';

function tls_rpt_org_add(mysqli $db, $rpt): false|int
{
    $org_name = $rpt['organization-name'];
    $contact = $rpt['contact-info'];

    $stmt = $db->prepare('SELECT id FROM tls_rpt_report_organization WHERE organization_name = ? AND contact_info = ? LIMIT 1');
    $stmt->bind_param('ss', $org_name, $contact);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res === false) {
        $errors = $stmt->error;
        print('Failed to find org info: ' . $errors);
        return false;
    }
    if ($res->num_rows === 0) {
        $stmt = $db->prepare('INSERT INTO tls_rpt_report_organization(organization_name, contact_info) VALUES (?, ?) RETURNING id');
        $stmt->bind_param('ss', $org_name, $contact);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res === false) {
            $errors = $stmt->error;
            print('Failed to insert org info: ' . $errors);
            return false;
        }
        return $res->fetch_column(0);
    } else {
        return $res->fetch_column(0);
    }
}

function general_domain_add(mysqli $db, string $domain): false|int {
    $stmt = $db->prepare('SELECT id FROM general_domain WHERE domain_name = ? LIMIT 1');
    $stmt->bind_param('s', $domain);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res === false) {
        $errors = $stmt->error;
        print('Failed to find domain: ' . $errors);
        return false;
    }
    if ($res->num_rows === 0) {
        $stmt = $db->prepare('INSERT INTO general_domain(domain_name) VALUES (?) RETURNING id');
        $stmt->bind_param('s', $domain);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res === false) {
            $errors = $stmt->error;
            print('Failed to insert domain: ' . $errors);
            return false;
        }
        return $res->fetch_column(0);
    } else {
        return $res->fetch_column(0);
    }
}

function tls_rpt_mx_host_pattern_add(mysqli $db, $mx_host): int|false {
    $stmt = $db->prepare('SELECT id FROM tls_rpt_mx_host_pattern WHERE pattern = ? LIMIT 1');
    $stmt->bind_param('s', $mx_host);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res === false) {
        $errors = $stmt->error;
        print('Failed to find mx host pattern: ' . $errors);
        return false;
    }
    if ($res->num_rows === 0) {
        $stmt = $db->prepare('INSERT INTO tls_rpt_mx_host_pattern(pattern) VALUES (?) RETURNING id');
        $stmt->bind_param('s', $mx_host);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res === false) {
            $errors = $stmt->error;
            print('Failed to insert mx host pattern: ' . $errors);
            return false;
        }
        return $res->fetch_column(0);
    } else {
        return $res->fetch_column(0);
    }
}

function tls_rpt_mx_host_pattern_and_and_assign(mysqli $db, $policy_id, $mx_host): bool {
    $mx_host_id = tls_rpt_mx_host_pattern_add($db, $mx_host);
    if ($mx_host_id === false) {
        return false;
    }
    
    $stmt = $db->prepare('INSERT INTO tls_rpt_policy_mx_host_pattern_assign(tls_rpt_policy_id, tls_rpt_mx_host_pattern_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $policy_id, $mx_host_id);
    $res = $stmt->execute();
    if ($res === false) {
        $errors = $stmt->error;
        print('Failed to insert mx host pattern assign: ' . $errors);
        return false;
    }
    return true;
}

function general_ip_add(mysqli $db, $ip): int|false {
    if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $ip = "::ffff:$ip";
    }
    
    $stmt = $db->prepare('SELECT id FROM general_ip WHERE ip_address = ? LIMIT 1');
    $stmt->bind_param('s', $ip);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res === false) {
        $errors = $stmt->error;
        print('Failed to find ip: ' . $errors);
        return false;
    }
    if ($res->num_rows === 0) {
        $stmt = $db->prepare('INSERT INTO general_ip(ip_address) VALUES (?) RETURNING id');
        $stmt->bind_param('s', $ip);
        $stmt->execute();
        try {
            $res = $stmt->get_result();
        } catch (\Throwable $t) {
            \Sentry\captureException($t);
            print('Failed to insert ip: ' . $ip . "\n");
            return false;
        }
        if ($res === false) {
            $errors = $stmt->error;
            print('Failed to insert ip: ' . $errors . "\n");
            return false;
        }
        return $res->fetch_column(0);
    } else {
        return $res->fetch_column(0);
    }
}

function general_helo_add(mysqli $db, $helo) {
    $stmt = $db->prepare('SELECT id FROM general_helo WHERE helo_string = ? LIMIT 1');
    $stmt->bind_param('s', $helo);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res === false) {
        $errors = $stmt->error;
        print('Failed to find helo: ' . $errors);
        return false;
    }
    if ($res->num_rows === 0) {
        $stmt = $db->prepare('INSERT INTO general_helo(helo_string) VALUES (?) RETURNING id');
        $stmt->bind_param('s', $helo);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res === false) {
            $errors = $stmt->error;
            print('Failed to insert helo: ' . $errors);
            return false;
        }
        return $res->fetch_column(0);
    } else {
        return $res->fetch_column(0);
    }
}

function tls_rpt_additional_information_add(mysqli $db, $additional_information): int|false {
    $stmt = $db->prepare('SELECT id FROM tls_rpt_policy_additional_information WHERE additional_information = ? LIMIT 1');
    $stmt->bind_param('s', $additional_information);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res === false) {
        $errors = $stmt->error;
        print('Failed to find additional information: ' . $errors);
        return false;
    }
    if ($res->num_rows === 0) {
        $stmt = $db->prepare('INSERT INTO tls_rpt_policy_additional_information(additional_information) VALUES (?) RETURNING id');
        $stmt->bind_param('s', $additional_information);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res === false) {
            $errors = $stmt->error;
            print('Failed to insert additional information: ' . $errors);
            return false;
        }
        return $res->fetch_column(0);
    } else {
        return $res->fetch_column(0);
    }
}

function tls_rpt_failure_reason_code_add(mysqli $db, $failure_reason_code) {
    $stmt = $db->prepare('SELECT id FROM tls_rpt_policy_failure_reason_code WHERE failure_reason_code = ? LIMIT 1');
    $stmt->bind_param('s', $failure_reason_code);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res === false) {
        $errors = $stmt->error;
        print('Failed to find failure reason code: ' . $errors);
        return false;
    }
    if ($res->num_rows === 0) {
        $stmt = $db->prepare('INSERT INTO tls_rpt_policy_failure_reason_code(failure_reason_code) VALUES (?) RETURNING id');
        $stmt->bind_param('s', $failure_reason_code);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res === false) {
            $errors = $stmt->error;
            print('Failed to insert failure reason code: ' . $errors);
            return false;
        }
        return $res->fetch_column(0);
    } else {
        return $res->fetch_column(0);
    }
}

function tls_rpt_failed_detail_add(mysqli $db, $policy_id, $failed_detail): bool {
    $result_type = $failed_detail['result-type'] ?? null;

    $sending_mta_ip_id = null;
    if (isset($failed_detail['sending-mta-ip'])) {
        $sending_mta_ip_id = general_ip_add($db, $failed_detail['sending-mta-ip']);
        if ($sending_mta_ip_id === false) {
            return false;
        }
    }

    $receiving_mx_hostname_id = null;
    if (isset($failed_detail['receiving-mx-hostname'])) {
        $receiving_mx_hostname_id = general_domain_add($db, $failed_detail['receiving-mx-hostname']);
        if ($receiving_mx_hostname_id === false) {
            return false;
        }
    }

    $receiving_mx_helo_id = null;
    if (isset($failed_detail['receiving-mx-helo'])) {
        $receiving_mx_helo_id = general_helo_add($db, $failed_detail['receiving-mx-helo']);
        if ($receiving_mx_helo_id === false) {
            return false;
        }
    }

    $receiving_ip_id = null;
    if (isset($failed_detail['receiving-ip'])) {
        $receiving_ip_id = general_ip_add($db, $failed_detail['receiving-ip']);
        if ($receiving_ip_id === false) {
            return false;
        }
    }

    $failed_session_count = $failed_detail['failed-session-count'] ?? null;

    $additional_information_id = null;
    if (isset($failed_detail['additional-information'])) {
        $additional_information_id = tls_rpt_additional_information_add($db, $failed_detail['additional-information']);
        if ($additional_information_id === false) {
            return false;
        }
    }

    $failure_reason_code_id = null;
    if (isset($failed_detail['failure-reason-code'])) {
        $failure_reason_code_id = tls_rpt_failure_reason_code_add($db, $failed_detail['failure-reason-code']);
        if ($failure_reason_code_id === false) {
            return false;
        }
    }

    $stmt = $db->prepare('INSERT INTO tls_rpt_policy_failure_details(
        tls_rpt_policy_id, 
        result_type, 
        sending_mta_ip_id, 
        receiving_mx_hostname_id,
        receiving_mx_helo_id,
        receiving_ip_id,
        failed_session_count,
        additional_information_id,
        failure_reason_code_id
    ) VALUES (
        ?, ?, ?, ?, ?, 
        ?, ?, ?, ?
    ) RETURNING id');
    $stmt->bind_param(
        'isiiiiiii',
        $policy_id,
        $result_type,
        $sending_mta_ip_id,
        $receiving_mx_hostname_id,
        $receiving_mx_helo_id,

        $receiving_ip_id,
        $failed_session_count,
        $additional_information_id,
        $failure_reason_code_id
    );
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res === false) {
        $errors = $stmt->error;
        print('Failed to insert tls_rpt_policy_failure_details: ' . $errors);
        return false;
    }
    return true;
}

function tls_rpt_policy_add(mysqli $db, $rpt_id, $policy): bool {
    $policy_domain = $policy['policy']['policy-domain'];
    $policy_domain_id = general_domain_add($db, $policy_domain);
    if ($policy_domain_id === false) {
        return false;
    }

    $policy_type = $policy['policy']['policy-type'];

    $sum_success = $policy['summary']['total-successful-session-count'];
    $sum_fail = $policy['summary']['total-failure-session-count'];

    // ToDo: Send alert if there are fail session.

    $stmt = $db->prepare('INSERT INTO tls_rpt_policy(tls_rpt_id, policy_type, policy_domain_id, summary_total_successful_sessions, summary_total_failed_sessions) VALUES (?, ?, ?, ?, ?) RETURNING id');
    $stmt->bind_param('isiii', $rpt_id, $policy_type, $policy_domain_id, $sum_success, $sum_fail);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res === false) {
        $errors = $stmt->error;
        print('Failed to insert tls_rpt_policy: ' . $errors);
        return false;
    }
    $policy_id = $res->fetch_column(0);

    // ToDo: Add policy strings if needed
    // $policy_strings = $policy['policy']['policy-string'];

    if (isset($policy['policy']['mx-host'])) {
        $mx_hosts = $policy['policy']['mx-host'];
        foreach ($mx_hosts as $mx_host) {
            $res = tls_rpt_mx_host_pattern_and_and_assign($db, $policy_id, $mx_host);
            if ($res === false) {
                return false;
            }
        }
    }

    if (isset($policy['failure-details'])) {
        $failure_details = $policy['failure-details'];
        foreach($failure_details as $failure_detail) {
            $res = tls_rpt_failed_detail_add($db, $policy_id, $failure_detail);
            if ($res === false) {
                return false;
            }
        }
    }

    return true;
}

function tls_rpt_add(mysqli $db, $rpt): bool {
    $trs_res = $db->begin_transaction();
    $tz = date_default_timezone_get();
    date_default_timezone_set('UTC');
    if ($trs_res === false) {
        $errors = $db->error;
        print('Failed to begin transaction: ' . $errors);
        return false;
    }

    try {
        $org_id = tls_rpt_org_add($db, $rpt);
        if ($org_id === false) {
            $db->rollback();
            return false;
        }

        $report_id = $rpt['report-id'];
        
        // Dedup with org_id + report_id.
        
        $stmt = $db->prepare('SELECT id FROM tls_rpt WHERE tls_rpt_report_organization_id = ? AND report_id = ? LIMIT 1');
        $stmt->bind_param('ii', $org_id, $report_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res === false) {
            $errors = $stmt->error;
            print('Failed to find tls_rpt: ' . $errors);
            $db->rollback();
            return false;
        }
        if ($res->num_rows > 0) {
            $db->rollback();
            print('Already added.');
            return true;
        }
        
        // Format: YYYY-mm-ddTHH:ii:ssZ (UTC)
        $date_start = $rpt['date-range']['start-datetime'];
        $date_start_unix = strtotime($date_start);
        $date_end = $rpt['date-range']['end-datetime'];
        $date_end_unix = strtotime($date_end);

        $stmt = $db->prepare('INSERT INTO tls_rpt(tls_rpt_report_organization_id, date_range_start, date_range_end, report_id)
         VALUES (?, FROM_UNIXTIME(?), FROM_UNIXTIME(?), ?) RETURNING id');
        $stmt->bind_param('iiis', $org_id, $date_start_unix, $date_end_unix, $report_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res === false) {
            $errors = $stmt->error;
            print('Failed to insert tls_rpt: ' . $errors);
            $db->rollback();
            return false;
        }
        $rep_id = $res->fetch_column(0);

        $policies = $rpt['policies'];
        foreach($policies as $policy) {
            $policy_add_res = tls_rpt_policy_add($db, $rep_id, $policy);
            if ($policy_add_res === false) {
                $db->rollback();
                return false;
            }
        }

        $db->commit();
    } catch (\Throwable $t) {
        \Sentry\captureException($t);
        $db->rollback();
        throw $t;
    } finally {
        date_default_timezone_set($tz);
    }
    return true;
}
