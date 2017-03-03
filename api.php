<?php
/* Pi-hole: A black hole for Internet advertisements
*    (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*    Network-wide ad blocking via your own hardware.
*
*    This file is copyright under the latest version of the EUPL.
*    Please see LICENSE file for your rights under this license. */

    $api = true;
    require "scripts/pi-hole/php/password.php";
    require "scripts/pi-hole/php/auth.php";

    check_cors();

    include('scripts/pi-hole/php/data.php');
    header('Content-type: application/json');

    $data = array();

    // Non-Auth

    if (isset($_GET['type'])) {
        $data["type"] = "PHP";
    }

    if (isset($_GET['version'])) {
        $data["version"] = 2;
    }

    if (isset($_GET['summaryRaw'])) {
        $data = array_merge($data,  getSummaryData());
    }

    if (isset($_GET['summary']) || !count($_GET)) {
        $sum = getSummaryData();
        $sum['ads_blocked_today'] = number_format( $sum['ads_blocked_today']);
        $sum['dns_queries_today'] = number_format( $sum['dns_queries_today']);
        $sum['ads_percentage_today'] = number_format( $sum['ads_percentage_today'], 1, '.', '');
        $sum['domains_being_blocked'] = number_format( $sum['domains_being_blocked']);
        $data = array_merge($data,  $sum);
    }

    if (isset($_GET['overTimeData'])) {
        $data = array_merge($data,  getOverTimeData());
    }

    if (isset($_GET['overTimeData10mins'])) {
        $data = array_merge($data,  getOverTimeData10mins());
    }

    // Auth Required

    if (isset($_GET['topItems']) && $auth) {
        $data = array_merge($data,  getTopItems($_GET['topItems']));
    }

    if (isset($_GET['recentItems']) && $auth) {
        if (is_numeric($_GET['recentItems'])) {
            $data = array_merge($data,  getRecentItems($_GET['recentItems']));
        }
    }

    if (isset($_GET['getQueryTypes']) && $auth) {
        $data = array_merge($data, getIpvType());
    }

    if (isset($_GET['getForwardDestinations']) && $auth) {
        $data = array_merge($data, getForwardDestinations());
    }

    if (isset($_GET['getQuerySources']) && $auth) {
        $data = array_merge($data, getQuerySources());
    }

    if (isset($_GET['getAllQueries']) && $auth) {
        $data = array_merge($data, getAllQueries($_GET['getAllQueries']));
    }

    if (isset($_GET['enable']) && $auth) {
        if(isset($_GET["auth"]))
        {
            if($_GET["auth"] !== $pwhash)
                die("Not authorized!");
        }
        else
        {
            // Skip token validation if explicit auth string is given
            check_csrf($_GET['token']);
        }
        exec('sudo pihole enable');
        $data = array_merge($data, array("status" => "enabled"));
        unlink("../custom_disable_timer");
    }
    elseif (isset($_GET['disable']) && $auth) {
        if(isset($_GET["auth"]))
        {
            if($_GET["auth"] !== $pwhash)
                die("Not authorized!");
        }
        else
        {
            // Skip token validation if explicit auth string is given
            check_csrf($_GET['token']);
        }
        $disable = intval($_GET['disable']);
        // intval returns the integer value on success, or 0 on failure
        if($disable > 0)
        {
            $timestamp = time();
            exec("sudo pihole disable ".$disable."s");
            file_put_contents("../custom_disable_timer",($timestamp+$disable)*1000);
        }
        else
        {
            exec('sudo pihole disable');
            unlink("../custom_disable_timer");
        }
        $data = array_merge($data, array("status" => "disabled"));
    }

    if (isset($_GET['getGravityDomains'])) {
        $data = array_merge($data, getGravity());
    }

    if (isset($_GET['tailLog']) && $auth) {
        $data = array_merge($data, tailPiholeLog($_GET['tailLog']));
    }

    function filterArray(&$inArray) {
        $outArray = array();
        foreach ($inArray as $key=>$value) {
            if (is_array($value)) {
                $outArray[htmlspecialchars($key)] = filterArray($value);
            } else {
                $outArray[htmlspecialchars($key)] = !is_numeric($value) ? htmlspecialchars($value) : $value;
            }
        }
        return $outArray;
    }

    $data = filterArray($data);

    if(isset($_GET["jsonForceObject"]))
    {
        echo json_encode($data, JSON_FORCE_OBJECT);
    }
    else
    {
        echo json_encode($data);
    }
?>
