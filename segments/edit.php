<?php
/**
 * /segments/edit.php
 *
 * This file is part of DomainMOD, an open source domain and internet asset manager.
 * Copyright (c) 2010-2017 Greg Chetcuti <greg@chetcuti.com>
 *
 * Project: http://domainmod.org   Author: http://chetcuti.com
 *
 * DomainMOD is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * DomainMOD is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with DomainMOD. If not, see
 * http://www.gnu.org/licenses/.
 *
 */
?>
<?php
require_once __DIR__ . '/../_includes/start-session.inc.php';
require_once __DIR__ . '/../_includes/init.inc.php';
require_once DIR_INC . '/config.inc.php';
require_once DIR_INC . '/software.inc.php';
require_once DIR_ROOT . '/vendor/autoload.php';

$assets = new DomainMOD\Assets();
$deeb = DomainMOD\Database::getInstance();
$form = new DomainMOD\Form();
$log = new DomainMOD\Log('/segments/edit.php');
$maint = new DomainMOD\Maintenance();
$system = new DomainMOD\System();
$time = new DomainMOD\Time();
$timestamp = $time->stamp();

require_once DIR_INC . '/head.inc.php';
require_once DIR_INC . '/debug.inc.php';
require_once DIR_INC . '/settings/segments-edit.inc.php';

$system->authCheck();
$pdo = $deeb->cnxx;

$segid = $_GET['segid'];

$del = $_GET['del'];
$really_del = $_GET['really_del'];

$new_name = $_POST['new_name'];
$new_description = $_POST['new_description'];
$raw_domain_list = $_POST['raw_domain_list'];
$new_notes = $_POST['new_notes'];
$new_segid = $_POST['new_segid'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $system->readOnlyCheck($_SERVER['HTTP_REFERER']);

    $format = new DomainMOD\Format();
    $domain_array = $format->cleanAndSplitDomains($raw_domain_list);

    if ($new_name != "" && $raw_domain_list != "") {

        $number_of_domains = count($domain_array);

        $domain = new DomainMOD\Domain();

        list($invalid_to_display, $invalid_domains, $invalid_count, $temp_result_message) =
            $domain->findInvalidDomains($domain_array);

        if ($raw_domain_list == "" || $invalid_domains == 1) {

            if ($invalid_domains == 1) {

                if ($invalid_count == 1) {

                    $_SESSION['s_message_danger'] .= "There is " . number_format($invalid_count) . " invalid domain
                        on your list<BR><BR>" . $temp_result_message;

                } else {

                    $_SESSION['s_message_danger'] .= "There are " . number_format($invalid_count) . " invalid
                        domains on your list<BR><BR>" . $temp_result_message;

                    if (($invalid_count - $invalid_to_display) == 1) {

                        $_SESSION['s_message_danger'] .= "<BR>Plus " .
                            number_format($invalid_count - $invalid_to_display) . " other<BR>";

                    } elseif (($invalid_count - $invalid_to_display) > 1) {

                        $_SESSION['s_message_danger'] .= "<BR>Plus " .
                            number_format($invalid_count - $invalid_to_display) . " others<BR>";
                    }

                }

            }
            $submission_failed = 1;

        } else {

            try {

                $pdo->beginTransaction();

                $domain = new DomainMOD\Domain();

                while (list($key, $new_domain) = each($domain_array)) {

                    if (!$domain->checkFormat($new_domain)) {
                        echo 'invalid domain ' . $key;
                        exit;
                    }

                }

                $new_data_formatted = $format->formatForMysql($domain_array);

                $stmt = $pdo->prepare("
                    UPDATE segments
                    SET `name` = :new_name,
                        description = :new_description,
                        segment = :new_data_formatted,
                        number_of_domains = :number_of_domains,
                        notes = :new_notes,
                        update_time = :timestamp
                    WHERE id = :segid");
                $stmt->bindValue('new_name', $new_name, PDO::PARAM_STR);
                $stmt->bindValue('new_description', $new_description, PDO::PARAM_LOB);
                $stmt->bindValue('new_data_formatted', $new_data_formatted, PDO::PARAM_LOB);
                $stmt->bindValue('number_of_domains', $number_of_domains, PDO::PARAM_INT);
                $stmt->bindValue('new_notes', $new_notes, PDO::PARAM_LOB);
                $stmt->bindValue('timestamp', $timestamp, PDO::PARAM_STR);
                $stmt->bindValue('segid', $segid, PDO::PARAM_INT);
                $stmt->execute();

                $stmt = $pdo->prepare("
                    DELETE FROM segment_data
                    WHERE segment_id = :new_segid");
                $stmt->bindValue('new_segid', $new_segid, PDO::PARAM_INT);
                $stmt->execute();

                $stmt = $pdo->prepare("
                    INSERT INTO segment_data
                    (segment_id, domain, update_time)
                    VALUES
                    (:new_segid, :domain, :timestamp)");
                $stmt->bindValue('new_segid', $new_segid, PDO::PARAM_INT);
                $stmt->bindParam('domain', $bind_domain, PDO::PARAM_STR);
                $stmt->bindValue('timestamp', $timestamp, PDO::PARAM_STR);

                foreach ($domain_array as $domain) {

                    $bind_domain = $domain;
                    $stmt->execute();

                }

                $segid = $new_segid;

                $maint->updateSegments();

                $pdo->commit();

                $_SESSION['s_message_success'] .= "Segment " . $new_name . " Updated<BR>";

                header("Location: ../segments/");
                exit;

            } catch (Exception $e) {

                $pdo->rollback();

                $log_message = 'Unable to update segment';
                $log_extra = array('Error' => $e);
                $log->error($log_message, $log_extra);

                $_SESSION['s_message_danger'] .= $log_message . '<BR>';

                throw $e;

            }

        }

    } else {

        if ($new_name == "") $_SESSION['s_message_danger'] .= "Enter the segment name<BR>";
        if ($raw_domain_list == "") $_SESSION['s_message_danger'] .= "Enter the segment<BR>";

    }

} else {

    $stmt = $pdo->prepare("
        SELECT id, `name`, description, segment, notes
        FROM segments
        WHERE id = :segid");
    $stmt->bindValue('segid', $segid, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch();

    if ($result) {

        $new_id = $result->id;
        $new_name = $result->name;
        $new_description = $result->description;
        $domain_array_formatted = $result->segment;
        $new_notes = $result->notes;

    }

    $raw_domain_list = preg_replace("/', '/", "\r\n", $domain_array_formatted);
    $raw_domain_list = preg_replace("/','/", "\r\n", $raw_domain_list);
    $raw_domain_list = preg_replace("/'/", "", $raw_domain_list);

}

if ($del == "1") {

    $_SESSION['s_message_danger'] .= "Are you sure you want to delete this Segment?<BR><BR><a
        href=\"edit.php?segid=" . $segid . "&really_del=1\">YES, REALLY DELETE THIS SEGMENT</a><BR>";

}

if ($really_del == "1") {

    try {

        $pdo->beginTransaction();

        $segment = new DomainMOD\Segment();
        $temp_segment_name = $segment->getName($segid);

        $stmt = $pdo->prepare("
            DELETE FROM segments
            WHERE id = :segid");
        $stmt->bindValue('segid', $segid, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $pdo->prepare("
            DELETE FROM segment_data
            WHERE segment_id = :segid");
        $stmt->bindValue('segid', $segid, PDO::PARAM_INT);
        $stmt->execute();

        $pdo->commit();

        $_SESSION['s_message_success'] .= "Segment " . $temp_segment_name . " Deleted<BR>";

        header("Location: ../segments/");
        exit;

    } catch (Exception $e) {

        $pdo->rollback();

        $log_message = 'Unable to delete segment';
        $log_extra = array('Error' => $e);
        $log->error($log_message, $log_extra);

        $_SESSION['s_message_danger'] .= $log_message . '<BR>';

        throw $e;

    }

}
?>
<?php require_once DIR_INC . '/doctype.inc.php'; ?>
<html>
<head>
    <title><?php echo $system->pageTitle($page_title); ?></title>
    <?php require_once DIR_INC . '/layout/head-tags.inc.php'; ?>
</head>
<body class="hold-transition skin-red sidebar-mini">
<?php require_once DIR_INC . '/layout/header.inc.php'; ?>
<?php
echo $form->showFormTop('');
echo $form->showInputText('new_name', 'Segment Name (35)', '', $new_name, '35', '', '1', '', '');
echo $form->showInputTextarea('raw_domain_list', 'Segment Domains (one per line)', '', $raw_domain_list, '1', '', '');
echo $form->showInputTextarea('new_description', 'Description', '', $new_description, '', '', '');
echo $form->showInputTextarea('new_notes', 'Notes', '', $new_notes, '', '', '');
echo $form->showInputHidden('new_segid', $segid);
echo $form->showSubmitButton('Update Segment', '', '');
echo $form->showFormBottom('');
?>
<BR><a href="edit.php?segid=<?php echo urlencode($segid); ?>&del=1">DELETE THIS SEGMENT</a>
<?php require_once DIR_INC . '/layout/footer.inc.php'; ?>
</body>
</html>
