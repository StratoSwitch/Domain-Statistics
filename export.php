<?php
/*
 Domain Statistics for FusionPBX
 Version: MPL 1.1

 The contents of this file are subject to the Mozilla Public License Version
 1.1 (the "License"); you may not use this file except in compliance with
 the License. You may obtain a copy of the License at
 http://www.mozilla.org/MPL/

 Software distributed under the License is distributed on an "AS IS" basis,
 WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 for the specific language governing rights and limitations under the
 License.

 The Original Code is FusionPBX
 The Initial Developer of the Original Code is
 Dan Ryan <dan@acceleratenetworks.com>
 Portions created by the Initial Developer are Copyright (C) 2020
 the Initial Developer. All Rights Reserved.
 Contributor(s):
 None (yet)
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

require_once "utils.php";
//check permissions
if (permission_exists('domain_statistics')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//get http post variables and set them to php variables
$costpermin = 0.0069;
if (!empty($_POST["costpermin"])) {
	$costpermin = $_POST["costpermin"];
}
$start_stamp_begin = date("Y-m-d", strtotime("-1 months"));
if (!empty($_POST["start_stamp_begin"])) {
	$start_stamp_begin = $_POST["start_stamp_begin"];
}
$start_stamp_end = date("Y-m-d");
if (!empty($_POST["start_stamp_end"])) {
	$start_stamp_end = $_POST["start_stamp_end"];
}

header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=file.csv");

$output = fopen("php://output", "w");
$costpermin = 0.0069;
// Set spreadsheet header
fputcsv($output, array("Domain", "Local Minutes Used", "Inbound Minutes Used", "Outbound Minutes", "Total Inbound/Outbound Minutes", "Total Cost")); // here you can change delimiter/enclosure
foreach(do_sql($db, "SELECT domain_uuid, domain_name FROM v_domains;") as $domains) {
  $domain_uuid = $domains['domain_uuid'];
  $domain_calltime = array();
  foreach(do_sql($db, "SELECT v_xml_cdr.direction, SUM(v_xml_cdr.duration) FROM v_xml_cdr WHERE v_xml_cdr.domain_uuid = :domain_uuid AND v_xml_cdr.start_stamp > date(:start_stamp_begin) AND v_xml_cdr.start_stamp < date(:start_stamp_end) GROUP BY v_xml_cdr.direction;", array(":domain_uuid" => $domain_uuid, ":start_stamp_end" => $start_stamp_end, ":start_stamp_begin" => $start_stamp_begin)) as $domainrow) {
    $domain_calltime[$domainrow['direction']] = $domainrow['sum']/60;
    $domain_calltime[$domainrow['direction']] = $domainrow['sum']/60;
    $domain_calltime[$domainrow['direction']] = $domainrow['sum']/60;

  }
  $totalinout = $domain_calltime['inbound'] + $domain_calltime['outbound'];
  $totalcost = $totalinout * $costpermin;
  fputcsv($output, array($domains['domain_name'],
                    round($domain_calltime['local'], 2),
                    round($domain_calltime['inbound'], 2),
                    round($domain_calltime['outbound'], 2),
                    round($totalinout, 2),
                    round($totalcost, 2))); // here you can change delimiter/enclosure
}
fclose($output);
