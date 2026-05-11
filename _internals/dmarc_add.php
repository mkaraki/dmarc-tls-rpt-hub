<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/tls_rpt_add.php';

function dmarc_org_add(mysqli $db, SimpleXMLElement $rpt): int|false {
    $org_name = $rpt->org_name;
    $email = $rpt->email;
    $extra_contact_info = $rpt->extra_contact_info ?? null;
    
    $stmt = $db->prepare('SELECT id FROM dmarc_org WHERE org_name = ? AND email = ? AND extra_contact_info = ? LIMIT 1');
    $stmt->bind_param('sss', $org_name, $email, $extra_contact_info);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res === false) {
        $errors = $stmt->error;
        print('Failed to find org info: ' . $errors);
        return false;
    }
    if ($res->num_rows === 0) {
        $stmt = $db->prepare('INSERT INTO dmarc_org (org_name, email, extra_contact_info) VALUES (?, ?, ?) RETURNING id');
        $stmt->bind_param('sss', $org_name, $email, $extra_contact_info);
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

function dmarc_error_add(mysqli $db, $err): int|false {
    $stmt = $db->prepare('SELECT id FROM dmarc_errors WHERE error = ?');
    $stmt->bind_param('s', $err);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res === false) {
        $errors = $stmt->error;
        print('Failed to find error info: ' . $errors);
        return false;
    }
    if ($res->num_rows === 0) {
        $stmt = $db->prepare('INSERT INTO dmarc_errors (error) VALUES (?) RETURNING id');
        $stmt->bind_param('s', $err);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res === false) {
            $errors = $stmt->error;
            print('Failed to insert error info: ' . $errors);
            return false;
        }
        return $res->fetch_column(0);
    } else {
        return $res->fetch_column(0);
    }
}

function dmarc_error_add_and_assign(mysqli $db, $err): bool {
    $err_id = dmarc_error_add($db, $err);
    if ($err_id === false) {
        return false;
    }
    
    $stmt = $db->prepare('INSERT INTO dmarc_report_errors (dmarc_error_id, dmarc_report_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $err_id, $report_id);
    $res = $stmt->execute();
    if ($res === false) {
        $errors = $stmt->error;
        print('Failed to assign error info: ' . $errors);
        return false;
    }
    return true;
}

function dmarc_record_add(mysqli $db, int $report_id, SimpleXMLElement $record): int|false {
    $row_source_ip_id = general_ip_add($db, $record->row->source_ip);
    if ($row_source_ip_id === false) {
        return false;
    }
    
    $row_count = $record->row->count;
    
    // ToDo: Add policy evaluated
    
    $identifiers_envelope_to_id = null;
    if (isset($record->identifiers->envelope_to)) {
        $identifiers_envelope_to_id = general_domain_add($db, $record->identifiers->envelope_to);
        if ($identifiers_envelope_to_id === false) {
            return false;
        }
    }
    
    $identifiers_header_from_id = general_domain_add($db, $record->identifiers->header_from);
    if ($identifiers_header_from_id === false) {
        return false;
    }

    $stmt = $db->prepare('INSERT INTO dmarc_record (dmarc_report_id, row_source_ip_id, row_count, identifiers_envelope_to_id, identifiers_header_from_id)
    VALUES (?, ?, ?, ?, ?) RETURNING id');
    $stmt->bind_param('iiiii', $report_id, $row_source_ip_id, $row_count, $identifiers_envelope_to_id, $identifiers_header_from_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res === false) {
        $errors = $stmt->error;
        print('Failed to insert dmarc_record: ' . $errors);
        return false;
    }
    return $res->fetch_column(0);
}

function dmarc_spf_result_add(mysqli $db, int $record_id, SimpleXMLElement $spf): bool {
    $domain_id = general_domain_add($db, $spf->domain);
    if ($domain_id === false) {
        return false;
    }

    $spf_result = $spf->result;

    $stmt = $db->prepare('INSERT INTO dmarc_spf_result (dmarc_record_id, domain_id, spf_result) VALUES (?, ?, ?)');
    $stmt->bind_param('iis', $record_id, $domain_id, $spf_result);
    $res = $stmt->execute();
    if ($res === false) {
        $errors = $stmt->error;
        print('Failed to insert dmarc_spf_result: ' . $errors);
        return false;
    }
    return true;
}

function dmarc_dkim_selector_add(mysqli $db, $selector): int|false {
    $stmt = $db->prepare('SELECT id FROM dmarc_dkim_selector WHERE selector = ?');
    $stmt->bind_param('s', $selector);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res === false) {
        $errors = $stmt->error;
        print('Failed to find dkim: ' . $errors);
        return false;
    }
    if ($res->num_rows === 0) {
        $stmt = $db->prepare('INSERT INTO dmarc_dkim_selector (selector) VALUES (?) RETURNING id');
        $stmt->bind_param('s', $selector);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res === false) {
            $errors = $stmt->error;
            print('Failed to insert dkim: ' . $errors);
            return false;
        }
        return $res->fetch_column(0);
    } else {
        return $res->fetch_column(0);
    }
}

function dmarc_dkim_human_result_add(mysqli $db, $human_result): int|false {
    $stmt = $db->prepare('SELECT id FROM dmarc_dkim_human_result WHERE human_result = ?');
    $stmt->bind_param('s', $human_result);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res === false) {
        $errors = $stmt->error;
        print('Failed to find dkim human result: ' . $errors);
        return false;
    }
    if ($res->num_rows === 0) {
        $stmt = $db->prepare('INSERT INTO dmarc_dkim_human_result (human_result) VALUES (?) RETURNING id');
        $stmt->bind_param('s', $human_result);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res === false) {
            $errors = $stmt->error;
            print('Failed to insert dkim human result: ' . $errors);
            return false;
        }
        return $res->fetch_column(0);
    } else {
        return $res->fetch_column(0);
    }
}

function dmarc_dkim_result_add(mysqli $db, int $record_id, SimpleXMLElement $dkim): bool {
    $domain_id = general_domain_add($db, $dkim->domain);
    if ($domain_id === false) {
        return false;
    }
    
    $selector_id = null;
    if (isset($dkim->selector)) {
        $selector_id = dmarc_dkim_selector_add($db, $dkim->selector);
        if ($selector_id === false) {
            return false;
        }
    }
    
    $result = $dkim->result;
    
    $human_result_id = null;
    if (isset($dkim->human_result)) {
        $human_result_id = dmarc_dkim_human_result_add($db, $dkim->human_result);
        if ($human_result_id === false) {
            return false;
        }
    }
    
    $stmt = $db->prepare('INSERT INTO dmarc_dkim_result (dmarc_record_id, domain_id, selector_id, dkim_result, human_result_id)
    VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('iiisi', $record_id, $domain_id, $selector_id, $result, $human_result_id);
    $res = $stmt->execute();
    if ($res === false) {
        $errors = $stmt->error;
        print('Failed to insert dmarc_dkim_result: ' . $errors);
        return false;
    }
    return true;
}

function dmarc_add(mysqli $db, SimpleXMLElement $rpt): bool {
    $trs_res = $db->begin_transaction();
    if ($trs_res === false) {
        $errors = $db->error;
        print('Failed to begin transaction: ' . $errors);
        return false;
    }
    
    try {
        $org_id = dmarc_org_add($db, $rpt->report_metadata);
        if ($org_id === false) {
            $db->rollback();
            return false;
        }
        
        $report_id = $rpt->report_metadata->report_id;
        $date_range_begin = $rpt->report_metadata->date_range->begin;
        $date_range_end = $rpt->report_metadata->date_range->end;
        
        if (isset($rpt->report_records->error)) {
            $errors = $rpt->report_records->error;
            foreach ($errors as $error) {
                $res = dmarc_error_add_and_assign($db, $error);
                if ($res === false) {
                    $db->rollback();
                    return false;
                }
            }
        }
        
        // ToDo: Add policy_published
        
        // Check with org_id + report_id.
        $stmt = $db->prepare('SELECT id FROM dmarc_report WHERE metadata_org_id = ? AND metadata_report_id = ?');
        $stmt->bind_param('is', $org_id, $report_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res === false) {
            $errors = $stmt->error;
            print('Failed to find dmarc_report: ' . $errors);
            $db->rollback();
            return false;
        }
        if ($res->num_rows > 0) {
            print('Already exists.');
            $db->rollback();
            return false;
        }
        
        $stmt = $db->prepare('INSERT INTO dmarc_report (metadata_org_id, metadata_report_id, date_range_begin, date_range_end)
        VALUES (?, ?, FROM_UNIXTIME(?), FROM_UNIXTIME(?)) RETURNING id');
        $stmt->bind_param('isii', $org_id, $report_id, $date_range_begin, $date_range_end);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res === false) {
            $errors = $stmt->error;
            print('Failed to insert dmarc_report: ' . $errors);
        }
        $report_id = $res->fetch_column(0);
        
        foreach ($rpt->record as $record) {
            $record_id = dmarc_record_add($db, $report_id, $record);
            if ($record_id === false) {
                $db->rollback();
                return false;
            }
            
            foreach($record->auth_results->spf as $spf) {
                $res = dmarc_spf_result_add($db, $record_id, $spf);
                if ($res === false) {
                    $db->rollback();
                    return false;
                }
            }
            
            if (isset($record->auth_results->dkim)) {
                foreach($record->auth_results->dkim as $dkim) {
                    $res = dmarc_dkim_result_add($db, $record_id, $dkim);
                    if ($res === false) {
                        $db->rollback();
                        return false;
                    }
                }
            }
        }
    } catch (\Throwable $e) {
        \Sentry\captureException($e);
        $db->rollback();
        throw $e;
    }
    
    $db->commit();
    return true;
}