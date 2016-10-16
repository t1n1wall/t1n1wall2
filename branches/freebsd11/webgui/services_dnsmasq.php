#!/usr/local/bin/php
<?php
/*
	$Id: services_dnsmasq.php 547 2013-11-14 12:40:38Z mkasper $
	part of m0n0wall (http://m0n0.ch/wall)
	
	Copyright (C) 2003-2005 Bob Zoller <bob@kludgebox.com> and Manuel Kasper <mk@neon1.net>.
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

$pgtitle = array("Services", "DNS forwarder");
require("guiconfig.inc");

$pconfig['enable'] = isset($config['dnsmasq']['enable']);
$pconfig['regdhcp'] = isset($config['dnsmasq']['regdhcp']);
$pconfig['allservers'] = isset($config['dnsmasq']['allservers']);
$pconfig['strictorder'] = isset($config['dnsmasq']['strictorder']);
$pconfig['stoprebind'] = isset($config['dnsmasq']['stoprebind']);
$pconfig['log'] = isset($config['dnsmasq']['log']);

if (!is_array($config['dnsmasq']['hosts'])) {
	$config['dnsmasq']['hosts'] = array();
}
if (!is_array($config['dnsmasq']['domainoverrides'])) {
	$config['dnsmasq']['domainoverrides'] = array();
}
hosts_sort();
domainoverrides_sort();
$a_hosts = &$config['dnsmasq']['hosts'];
$a_domainOverrides = &$config['dnsmasq']['domainoverrides'];

if ($_POST) {

	foreach ($_POST as $pn => $pv) {
		if (preg_match("/^del_(.+)_x$/", $pn, $matches)) {
			list($type,$id) = explode(":", $matches[1]);
			if ($type == 'host') {
				if ($a_hosts[$id]) {
					unset($a_hosts[$id]);
					write_config();
					touch($d_dnsmasqdirty_path);
					header("Location: services_dnsmasq.php");
					exit;
				}
			}
			elseif ($type == 'doverride') {
				if ($a_domainOverrides[$id]) {
					unset($a_domainOverrides[$id]);
					write_config();
					touch($d_dnsmasqdirty_path);
					header("Location: services_dnsmasq.php");
					exit;
				}
		 	}
		}
	}

	$pconfig = $_POST;

	$config['dnsmasq']['enable'] = ($_POST['enable']) ? true : false;
	$config['dnsmasq']['regdhcp'] = ($_POST['regdhcp']) ? true : false;
	$config['dnsmasq']['allservers'] = ($_POST['allservers']) ? true : false;
	$config['dnsmasq']['strictorder'] = ($_POST['strictorder']) ? true : false;
	$config['dnsmasq']['stoprebind'] = ($_POST['stoprebind']) ? true : false;
	$config['dnsmasq']['log'] = ($_POST['log']) ? true : false;

	write_config();
	
	$retval = 0;
	if (!file_exists($d_sysrebootreqd_path)) {
		config_lock();
		$retval = services_dnsmasq_configure();
		config_unlock();
	}
	$savemsg = get_std_save_message($retval);

	if ($retval == 0) {
		if (file_exists($d_dnsmasqdirty_path))
			unlink($d_dnsmasqdirty_path);
	}
}

?>
<?php include("fbegin.inc"); ?>
<form action="services_dnsmasq.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_dnsmasqdirty_path)): ?><p>
<?php print_info_box_np("The DNS forwarder configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
<input name="apply" type="submit" class="formbtn" id="apply" value="Apply changes"></p>
<?php endif; ?>
			  <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
                <tr> 
                  <td class="vtable">
                      <input name="enable" type="checkbox" id="enable" value="yes" <?php if ($pconfig['enable']) echo "checked";?>>
                      <strong>Enable DNS forwarder</strong></td>
                </tr>
				  <td class="vtable">
                      <input name="allservers" type="checkbox" id="allservers" value="yes" <?php if ($pconfig['allservers']) echo "checked";?>>
                      <strong>Enable All Servers</strong><br>
					  By default, when more than one upstream server is available, 
					  it will send queries to just one server. Setting this flag forces all
					  queries to all available servers. The reply from the server
					  which answers first will be returned to the original requestor. </td>
                </tr>
				<td class="vtable">
                      <input name="strictorder" type="checkbox" id="strictorder" value="yes" <?php if ($pconfig['strictorder']) echo "checked";?>>
                      <strong>Strict Order</strong><br>
					  By default, the DNS forwarder will send queries to any of the upstream servers it
					  knows about and tries to favour servers that are known to be up. Setting
					  this flag forces it to try each query with each server strictly in order.</td>
                </tr>
				<td class="vtable">
                      <input name="stoprebind" type="checkbox" id="stoprebind" value="yes" <?php if ($pconfig['stoprebind']) echo "checked";?>>
                      <strong>Block DNS Rebind attacks</strong><br>
					  If this option is set, the DNS forwarder will reject (and log) addresses from upstream nameservers 
					  which are in the private IP ranges. 
					  This blocks an attack where a browser behind a firewall is used to probe machines on the local network. 
					  If you use domain overrides, this may cause responses to be blocked if they resolve within private IP ranges.</td>
                </tr>
                <tr> 
                  <td class="vtable">
                      <input name="regdhcp" type="checkbox" id="regdhcp" value="yes" <?php if ($pconfig['regdhcp']) echo "checked";?>>
                      <strong>Register DHCP leases in DNS forwarder<br>
                      </strong>If this option is set, then machines that specify 
                      their hostname when requesting a DHCP lease will be registered 
                      in the DNS forwarder, so that their name can be resolved. 
                      You should also set the domain in <a href="system.php">System: 
                      General setup</a> to the proper value.
                    </td>
                </tr>
                <tr> 
                  <td class="vtable">
                      <input name="log" type="checkbox" id="log" value="yes" <?php if ($pconfig['log']) echo "checked";?>>
                      <strong>Log DNS requests to system log<br>
                      </strong>If this option is set, then every request
					  received by the DNS forwarder will be logged into the system log.
                    </td>
                </tr>
                <tr> 
                  <td> <input name="submit" type="submit" class="formbtn" value="Save"> 
                  </td>
                </tr>
                <tr> 
                  <td><p><span class="vexpl"><span class="red"><strong>Note:<br>
                      </strong></span>If the DNS forwarder is enabled, the DHCP 
                      service (if enabled) will automatically serve the LAN IP 
                      address as a DNS server to DHCP clients so they will use 
                      the forwarder. The DNS forwarder will use the DNS servers 
                      entered in <a href="system.php">System: General setup</a> 
                      or those obtained via DHCP or PPP on WAN if the &quot;Allow 
                      DNS server list to be overridden by DHCP/PPP on WAN&quot;</span> 
                      is checked. If you don't use that option (or if you use 
                      a static IP address on WAN), you must manually specify at 
                      least one DNS server on the <a href="system.php">System: 
                      General setup</a> page.<br>
                      <br>
                      You may enter records that override the results from the 
                      forwarders below.</p></td>
                </tr>
              </table>
              <table width="100%" border="0" cellpadding="0" cellspacing="0" summary="forwarder-override widget">
                <tr>
                  <td width="20%" class="listhdrr">Host</td>
                  <td width="25%" class="listhdrr">Domain</td>
                  <td width="20%" class="listhdrr">IP</td>
                  <td width="25%" class="listhdr">Description</td>
                  <td width="10%" class="list"></td>
				</tr>
			  <?php $i = 0; foreach ($a_hosts as $hostent): ?>
                <tr>
                  <td class="listlr">
                    <?=strtolower($hostent['host']);?>&nbsp;
                  </td>
                  <td class="listr">
                    <?=strtolower($hostent['domain']);?>&nbsp;
                  </td>
                  <td class="listr">
                    <?=$hostent['ip'];?>&nbsp;
                  </td>
                  <td class="listbg">
                    <?=htmlspecialchars($hostent['descr']);?>&nbsp;
                  </td>
                  <td valign="middle" nowrap class="list"> <a href="services_dnsmasq_edit.php?id=<?=$i;?>"><img src="e.png" title="edit host" width="17" height="17" border="0" alt="edit host"></a>
                     &nbsp;<input name="del_host:<?=$i;?>" type="image" src="x.png" width="17" height="17" title="delete host" alt="delete host" onclick="return confirm('Do you really want to delete this host?')"></td>
				</tr>
			  <?php $i++; endforeach; ?>
                <tr> 
                  <td class="list" colspan="4"></td>
                  <td class="list"> <a href="services_dnsmasq_edit.php"><img src="plus.png" title="add host" width="17" height="17" border="0" alt="add host"></a></td>
				</tr>
              </table>
			  <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="auth-dns-server widget">
                <tr> 
                  <td><p>Below you can override an entire domain by specifying an
                         authoritative DNS server to be queried for that domain.</p></td>
                </tr>
              </table>
              <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="35%" class="listhdrr">Domain</td>
                  <td width="20%" class="listhdrr">IP</td>
                  <td width="35%" class="listhdr">Description</td>
                  <td width="10%" class="list"></td>
				</tr>
			  <?php $i = 0; foreach ($a_domainOverrides as $doment): ?>
                <tr>
                  <td class="listlr">
                    <?=strtolower($doment['domain']);?>&nbsp;
                  </td>
                  <td class="listr">
                    <?=$doment['ip'];?>&nbsp;
                  </td>
                  <td class="listbg">
                    <?=htmlspecialchars($doment['descr']);?>&nbsp;
                  </td>
                  <td valign="middle" nowrap class="list"> <a href="services_dnsmasq_domainoverride_edit.php?id=<?=$i;?>"><img src="e.png" width="17" height="17" border="0" alt=""></a>
                     &nbsp;<input name="del_doverride:<?=$i;?>" type="image" src="x.png" width="17" height="17" title="delete domain override" alt="delete domain override" onclick="return confirm('Do you really want to delete this domain override?')"></td>
				</tr>
			  <?php $i++; endforeach; ?>
                <tr> 
                  <td class="list" colspan="3"></td>
                  <td class="list"> <a href="services_dnsmasq_domainoverride_edit.php"><img src="plus.png" width="17" height="17" border="0" alt=""></a></td>
				</tr>
			  </table>
            </form>
<?php include("fend.inc"); ?>
