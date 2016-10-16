#!/usr/local/bin/php
<?php 
/*
	$Id: diag_logs_vpn.php 297 2008-08-24 18:24:04Z mkasper $
	part of m0n0wall (http://m0n0.ch/wall)
	
	Copyright (C) 2003-2007 Manuel Kasper <mk@neon1.net>.
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

$pgtitle = array("Diagnostics", "Logs");
require("guiconfig.inc");

$nentries = $config['syslog']['nentries'];
if (!$nentries)
	$nentries = 50;

if ($_POST['clear']) {
	exec("/usr/sbin/clog -i -s 65536 /var/log/vpn.log");
	/* redirect to avoid reposting form data on refresh */
	header("Location: diag_logs_vpn.php");
	exit;
}

function dump_clog($logfile, $tail) {
	global $g, $config;

	$sor = isset($config['syslog']['reverse']) ? "-r" : "";

	exec("/usr/sbin/clog " . $logfile . " | tail {$sor} -n " . $tail, $logarr);
	
	foreach ($logarr as $logent) {
		$logent = preg_split("/\s+/", $logent, 6);
		$llent = explode(",", $logent[5]);
		
		echo "<tr>\n";
		echo "<td class=\"listlr\" nowrap>" . htmlspecialchars(join(" ", array_slice($logent, 0, 3))) . "</td>\n";
		
		if ($llent[0] == "login")
			echo "<td class=\"listr\"><img src=\"in.png\" width=\"11\" height=\"11\" title=\"login\"></td>\n";
		else
			echo "<td class=\"listr\"><img src=\"out.png\" width=\"11\" height=\"11\" title=\"logout\"></td>\n";
		
		echo "<td class=\"listr\">" . htmlspecialchars($llent[3]) . "</td>\n";
		echo "<td class=\"listr\">" . htmlspecialchars($llent[2]) . "&nbsp;</td>\n";
		echo "</tr>\n";
	}
}

?>
<?php include("fbegin.inc"); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tab pane">
  <tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php 
   	$tabs = array('System' => 'diag_logs.php',
           		  'Firewall' => 'diag_logs_filter.php',
           		  'DHCP/DNS' => 'diag_logs_dhcp.php',
           		  'Captive portal' => 'diag_logs_portal.php',
           		  'PPTP/L2TP VPN' => 'diag_logs_vpn.php',
           		  'Settings' => 'diag_logs_settings.php');
	dynamic_tab_menu($tabs);
?> 
  </ul>
  </td></tr>
  <tr>
    <td class="tabcont">
		<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="content pane"><tr>
		  <td colspan="4" class="listtopic"> 
			    Last <?=$nentries;?> PPTP/L2TP VPN log entries</td>
			<form action="diag_logs_vpn.php" method="post">
			<input name="clear" type="submit" class="formbtn" value="Clear log">
			<br><br></form>
			</tr>
			<tr>
			  <td class="listhdrr">Time</td>
			  <td class="listhdrr">Action</td>
			  <td class="listhdrr">User</td>
			  <td class="listhdrr">IP address</td>
			</tr>
			<?php dump_clog("/var/log/vpn.log", $nentries); ?>
          </table>
		<br><form action="diag_logs_vpn.php" method="post">
<input name="clear" type="submit" class="formbtn" value="Clear log">
</form>
	</td>
  </tr>
</table>
<?php include("fend.inc"); ?>
