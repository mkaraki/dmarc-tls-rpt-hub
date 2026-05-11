<?php
require_once __DIR__ . '/../_config.php';

function get_db(): mysqli {
    $db = mysqli_connect(
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME,
        DB_PORT
    );
    
    if ($db === false) {
        die('DB error');
    }
    
    return $db;
}

function get_mbox_file_with_ext($ext, $parts, $mbox, $msgno, $parentsection = ""): array|false {
    foreach($parts as $subsection => $part){
        $section = $parentsection . ($subsection + 1);
        if(isset($part->parts)){
            // some mails have one extra dimension
            $res = get_mbox_file_with_ext($ext, $part->parts, $mbox, $msgno, $section . "." );
            if ($res !== false) return $res;
        }
        elseif(isset($part->disposition)){
            if(
                !in_array(strtolower($part->disposition), array('attachment','inline'))
            ){
                continue;
            }
            $is_name_ok = false;
            $file_name_lower = '';
            if (!isset($part->dparameters)) {
                continue;
            }
            foreach ($part->dparameters as $dparam) {
                if (
                    strtolower($dparam->attribute) === 'filename'
                ) {
                    $file_name_lower = strtolower($dparam->value);
                    foreach ($ext as $e) {
                        if (str_ends_with($file_name_lower, $e)) {
                            $is_name_ok = true;
                            break;
                        }
                    }

                    // ToDo: Logging unknown file attachment
                }
            }

            if (!$is_name_ok) continue;

            $data = imap_fetchbody($mbox, $msgno, $section, FT_PEEK);
            if ($data === false) {
                $errors = imap_last_error();
                $errors = $errors === false ? 'NO ERROR' : $errors;
                print('Failed to fetch body for message ' . $msgno . ' section ' . $section . ': ' . $errors);
                return false;
            }
            return [$file_name_lower, $data];
        }
    }

    return false;
}