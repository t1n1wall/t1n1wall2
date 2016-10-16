#!/usr/local/bin/php
<?php
/*
	$Id: diag_arp.php 521 2012-10-19 15:07:13Z mkasper $
	part of m0n0wall (http://m0n0.ch/wall)

	Copyright (C) 2005-2012 Paul Taylor (paultaylor@winndixie.com) and Manuel Kasper <mk@neon1.net>.
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

require("guiconfig.inc");

if (ipv6enabled()){
	$pgtitle = array("Diagnostics", "ARP/NDP table");
} else {
	$pgtitle = array("Diagnostics", "ARP table");
}

if (isset($_POST['del_x'])) {
	if (is_array($_POST['entries']) && count($_POST['entries']) > 0) {
		foreach ($_POST['entries'] as $entry) {
			/* remove arp entry from arp table */
			mwexec("/usr/sbin/arp -d " . escapeshellarg($entry));
		}
	} else {
		/* remove all entries from arp table */
		mwexec("/usr/sbin/arp -d -a");
	}
	
	/* redirect to avoid reposting form data on refresh */
	header("Location: diag_arp.php");
	exit;
}

$resolve = isset($config['syslog']['resolve']);
?>

<?php include("fbegin.inc"); ?>

<?php

$fp = @fopen("{$g['vardb_path']}/dnsmasq.dhcpd.leases","r");

if ($fp) {

	$i=0;
	while ($line = fgets($fp)) {
		#1451696413 08:00:27:61:7f:f7 192.168.1.101 SunLamb 01:08:00:27:61:7f:f7
		$leases[$i] = array();
		list($leases[$i]['end'],$leases[$i]['mac'],$leases[$i]['ip'],$leases[$i]['hostname'],$leases[$i]['cid']) = explode(" ",$line);
		$i++;
	}

	fclose($fp);
	
	// Put this in an easy to use form
	$dhcpmac = array();
	$dhcpip = array();
	
	foreach ($leases as $value) {
		$dhcpmac[$value['mac']] = $value['hostname'];	
		$dhcpip[$value['ip']] = $value['hostname'];	
	}
	
	unset($data);
}


$i = 0; 
$ifdescrs = array('wan' => 'WAN', 'lan' => 'LAN');
						
for ($j = 1; isset($config['interfaces']['opt' . $j]); $j++) {
	$ifdescrs['opt' . $j] = $config['interfaces']['opt' . $j]['descr'];
}

foreach ($ifdescrs as $key =>$interface) {
	$hwif[$config['interfaces'][$key]['if']] = $interface;
}

$data = array();

exec("/usr/sbin/arp -an",$rawdata);

foreach ($rawdata as $line) {
	$elements = explode(' ',$line);
	
	if ($elements[3] != "(incomplete)") {
		$arpent = array();
		$arpent['ip'] = trim(str_replace(array('(',')'),'',$elements[1]));
		$arpent['mac'] = trim($elements[3]);
		$arpent['interface'] = trim($elements[5]);
		$data[] = $arpent;
	}
}

if (ipv6enabled()){
	
	exec("/usr/sbin/ndp -an",$ndprawdata);
	//remove headers from table
	array_shift($ndprawdata);
	
	foreach ($ndprawdata as $line) {
		$elements = explode(" ", eregi_replace(" +", " ",$line));
		//check if linklocal and remove %if		
		$ip6element = explode("%", $elements[0]);
		
		if ($elements[1] != "(incomplete)") {
			$ndpent = array();
			$ndpent['ip'] = trim($ip6element[0]);
			$ndpent['mac'] = "0" . trim($elements[1]);
			$ndpent['interface'] = trim($elements[2]);
			$data[] = $ndpent;
		}
	}
}

function getHostName($mac,$ip)
{
	global $dhcpmac, $dhcpip, $resolve;
	
	if ($dhcpmac[$mac])
		return $dhcpmac[$mac];
	else if ($dhcpip[$ip])
		return $dhcpip[$ip];
	else if ($resolve && is_ipaddr4or6($ip)) 
		return gethostbyaddr($ip);
	else
		return "&nbsp;";
}

?>

<form action="diag_arp.php" method="post">
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="inner content pane">
  <tr>
    <td class="list">&nbsp;</td>
    <td class="listhdrr">IP address</td>
    <td class="listhdrr">MAC address</td>
    <td class="listhdrr">Hostname</td>
    <td class="listhdr">Interface</td>
    <td class="list"></td>
  </tr>
<?php $i = 0; foreach ($data as $entry): ?>
  <tr>
	<td class="listt"><input type="checkbox" name="entries[]" value="<?=$entry['ip'];?>" style="margin: 0; padding: 0; width: 15px; height: 15px;"></td>
    <td class="listlr"><?=$entry['ip'];?></td>
    <td class="listr"><?=$entry['mac'];?></td>
    <td class="listr"><?=getHostName($entry['mac'], $entry['ip']);?></td>
    <td class="listr"><?=$hwif[$entry['interface']];?></td>
  </tr>
<?php $i++; endforeach; ?>
  <tr> 
    <td></td>
  </tr> 
  <tr> 
    <td class="list" colspan="5"></td>
    <td class="list"><input name="del" type="image" src="x.png" width="17" height="17" title="delete selected ARP entries (or all if none selected)" alt="delete selected ARP entries (or all if none selected)"></td>
  </tr>
  <tr>
    <td colspan="4">
      <span class="vexpl"><span class="red"><strong>Hint:<br>
      </strong></span>IP addresses are resolved to hostnames if
      &quot;Resolve IP addresses to hostnames&quot; 
      is checked on the <a href="diag_logs_settings.php">
      Diagnostics: Logs</a> page.</span>
    </td>
  </tr>
</table>
</form>

<?php include("fend.inc"); ?>
