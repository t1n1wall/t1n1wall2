#!/usr/local/bin/php
<?php
/*
	$Id: diag_ping.php 521 2012-10-19 15:07:13Z mkasper $
	part of m0n0wall (http://m0n0.ch/wall)

	Copyright (C) 2003-2012 Bob Zoller (bob@kludgebox.com) and Manuel Kasper <mk@neon1.net>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	notice, this list of conditions and the following disclaimer in the
	documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

$pgtitle = array("Diagnostics", "Ping");
require("guiconfig.inc");

define('MAX_COUNT', 10);
define('DEFAULT_COUNT', 3);

if ($_POST) {
	unset($input_errors);
	unset($do_ping);

	/* input validation */
	$reqdfields = explode(" ", "host count");
	$reqdfieldsn = explode(",", "Host,Count");
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if (!is_numericint($_POST['count']) || ($_POST['count'] < 1) || ($_POST['count'] > MAX_COUNT)) {
		$input_errors[] = "Count must be between 1 and {MAX_COUNT}";
	}

	if (!$input_errors) {
		$do_ping = true;
		$host = $_POST['host'];
		$interface = $_POST['interface'];
		$count = $_POST['count'];
		$ipv6 = $_POST['ipv6'];
	}
}
if (!isset($do_ping)) {
	$do_ping = false;
	$host = '';
	$count = DEFAULT_COUNT;
	$ipv6 = false;
}

function get_interface_addr($ifdescr, $ipv6 = false) {
	
	global $config, $g;
	
	/* find out interface name */
	if ($ifdescr == "wan")
		$if = get_real_wan_interface();
	else
		$if = $config['interfaces'][$ifdescr]['if'];
	
	/* try to determine IP address and netmask with ifconfig */
	unset($ifconfiginfo);
	exec("/sbin/ifconfig " . $if, $ifconfiginfo);
	
	foreach ($ifconfiginfo as $ici) {
		if (!$ipv6 && preg_match("/inet (\S+)/", $ici, $matches)) {
			return $matches[1];
		}
		if ($ipv6 && preg_match("/inet6 ([0-9a-f:]+) /", $ici, $matches)) {
			return $matches[1];
		}
	}
	
	return false;
}
?>
<?php include("fbegin.inc"); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tab pane">
  <tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php 
   	$tabs = array('Ping' => 'diag_ping.php',
           		  'Traceroute' => 'diag_traceroute.php');
	dynamic_tab_menu($tabs);
?> 
  </ul>
  </td></tr>
  <tr> 
    <td class="tabcont">
<?php if ($input_errors) print_input_errors($input_errors); ?>
			<form action="diag_ping.php" method="post" name="iform" id="iform">
			  <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
                <tr>
				  <td width="22%" valign="top" class="vncellreq">Host</td>
				  <td width="78%" class="vtable"> 
                    <?=$mandfldhtml;?><input name="host" type="text" class="formfld" id="host" size="20" value="<?=htmlspecialchars($host);?>">

					<?php if (ipv6enabled()): ?>
					<input type="checkbox" name="ipv6" value="1" <?php if ($ipv6) echo "checked"; ?>> IPv6
					<?php endif; ?></td>
				</tr>
				<tr>
				  <td width="22%" valign="top" class="vncellreq">Interface</td>
				  <td width="78%" class="vtable">
				  <select name="interface" class="formfld">
                      <?php $interfaces = array('wan' => 'WAN', 'lan' => 'LAN');
					  for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
					    if (isset($config['interfaces']['opt' . $i]['enable']) &&
							!$config['interfaces']['opt' . $i]['bridge'])
					  		$interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
					  }
					  foreach ($interfaces as $iface => $ifacename): ?>
                      <option value="<?=$iface;?>" <?php if ($iface == $interface) echo "selected"; ?>> 
                      <?=htmlspecialchars($ifacename);?>
                      </option>
                      <?php endforeach; ?>
                    </select>
				  </td>
				</tr>
				<tr>
				  <td width="22%" valign="top" class="vncellreq">Count</td>
				  <td width="78%" class="vtable">
					<select name="count" class="formfld" id="count">
					<?php for ($i = 1; $i <= MAX_COUNT; $i++): ?>
					<option value="<?=$i;?>" <?php if ($i == $count) echo "selected"; ?>><?=$i;?></option>
					<?php endfor; ?>
					</select></td>
				</tr>
				<tr>
				  <td width="22%" valign="top">&nbsp;</td>
				  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="Ping">
				</td>
				</tr>
				<tr>
				<td valign="top" colspan="2">
				<? if ($do_ping) {
					echo("<strong>Ping output:</strong><br>");
					echo('<pre>');
					ob_end_flush();
					$ifaddr = get_interface_addr($interface, $ipv6);
					$pingprog = $ipv6 ? "ping6" : "ping";
					if ($ifaddr)
						system("/sbin/$pingprog -S$ifaddr -c$count " . escapeshellarg($host));
					else
						system("/sbin/$pingprog -c$count " . escapeshellarg($host));
					echo('</pre>');
				}
				?>
				</td>
				</tr>
			</table>
</form>
</td></tr></table>
<?php include("fend.inc"); ?>
