#!/usr/local/bin/php
<?php 
/*
	$Id: status_interfaces.php 552 2013-12-09 20:08:44Z pnast $
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

$pgtitle = array("Status", "Interfaces");
require("guiconfig.inc");
$wancfg = &$config['interfaces']['wan'];


if ($_POST) {
	if ($_POST['submit'] == "Disconnect" || $_POST['submit'] == "Release") {
		if ($wancfg['ipaddr'] == "dhcp")
			interfaces_wan_dhcp_down();
		else if ($wancfg['ipaddr'] == "pppoe")
			interfaces_wan_pppoe_down();
		else if ($wancfg['ipaddr'] == "pptp")
			interfaces_wan_pptp_down();
		else if ($wancfg['ipaddr'] == "modem")
			interfaces_wan_modem_down();
	} else if ($_POST['submit'] == "Connect" || $_POST['submit'] == "Renew") {
		if ($wancfg['ipaddr'] == "dhcp")
			interfaces_wan_dhcp_up();
		else if ($wancfg['ipaddr'] == "pppoe")
			interfaces_wan_pppoe_up();
		else if ($wancfg['ipaddr'] == "pptp")
			interfaces_wan_pptp_up();
		else if ($wancfg['ipaddr'] == "modem")
			interfaces_wan_modem_up();
	} else {
		header("Location: index.php");
		exit;
	}
}
function get_resolvers() {
	global $g;
	$resolvers = array();
	if (file_exists("{$g['etc_path']}/resolv.conf")) {
		$resolvconf = file_get_contents("{$g['etc_path']}/resolv.conf");
		preg_match_all("/nameserver (\S+)/",$resolvconf,$matches);
		$resolvers = $matches[1];
	} else {
		$resolvers[] = "Error reading {$g['etc_path']}/resolv.conf";
	}
	return $resolvers;
}
function get_interface_info($ifdescr) {
	
	global $config, $g;
	$ifaddr6s = array();
	$ifaddr4s = array();
	$ifinfo = array();
	
	/* find out interface name */
	$ifinfo['hwif'] = $config['interfaces'][$ifdescr]['if'];
	if ($ifdescr == "wan")
		$ifinfo['if'] = get_real_wan_interface();
	else
		$ifinfo['if'] = $ifinfo['hwif'];
	
	/* run netstat to determine link info */
	unset($linkinfo);
	if (($ifdescr != "wan" && $config['interfaces']['wan']['ipaddr'] != "modem")) {
		exec("/usr/bin/netstat -I " . $ifinfo['hwif'] . " -nWb -f link", $linkinfo);
	} else {
		exec("/usr/bin/netstat -I " . $ifinfo['if'] . " -nWb -f link", $linkinfo);
	}
	$linkinfo = preg_split("/\s+/", $linkinfo[1]);
	if (preg_match("/\*$/", $linkinfo[0]) || preg_match("/^$/", $linkinfo[0])) {
		$ifinfo['status'] = "down";
	} else {
		$ifinfo['status'] = "up";
	}
	
	/* netstat outputs no mac addr for some interface types */
	if (count($linkinfo) < 12) {
		array_splice($linkinfo, 3, 0, "Not Supported");
	}

	$ifinfo['macaddr'] = $linkinfo[3];
	$ifinfo['inpkts'] = $linkinfo[4];
	$ifinfo['inerrs'] = $linkinfo[5];
	$ifinfo['indrops'] = $linkinfo[6];
	$ifinfo['inbytes'] = $linkinfo[7];
	$ifinfo['outpkts'] = $linkinfo[8];
	$ifinfo['outerrs'] = $linkinfo[9];
	$ifinfo['outbytes'] = $linkinfo[10];
	$ifinfo['collisions'] = $linkinfo[11];
	
	/* DHCP? -> see if dhclient is up */
	if (($ifdescr == "wan") && ($config['interfaces']['wan']['ipaddr'] == "dhcp")) {
		/* see if dhclient is up */
		if ($ifinfo['status'] == "up" && file_exists("{$g['varrun_path']}/dhclient.pid"))
			$ifinfo['dhcplink'] = "up";
		else
			$ifinfo['dhcplink'] = "down";
	}
	
	/* PPPoE interface? -> get status from virtual interface */
	if (($ifdescr == "wan") && ($config['interfaces']['wan']['ipaddr'] == "pppoe")) {
		unset($linkinfo);
		exec("/usr/bin/netstat -I " . $ifinfo['if'] . " -nWb -f link", $linkinfo);
		$linkinfo = preg_split("/\s+/", $linkinfo[1]);
		if (preg_match("/\*$/", $linkinfo[0])) {
			$ifinfo['pppoelink'] = "down";
		} else {
			/* get PPPoE link status for dial on demand */
			unset($ifconfiginfo);
			exec("/sbin/ifconfig " . $ifinfo['if'], $ifconfiginfo);
	
			$ifinfo['pppoelink'] = "up";
	
			foreach ($ifconfiginfo as $ici) {
				if (strpos($ici, 'LINK0') !== false)
					$ifinfo['pppoelink'] = "down";
			}
		}
	}
	
	/* PPTP interface? -> get status from virtual interface */
	if (($ifdescr == "wan") && ($config['interfaces']['wan']['ipaddr'] == "pptp")) {
		unset($linkinfo);
		exec("/usr/bin/netstat -I " . $ifinfo['if'] . " -nWb -f link", $linkinfo);
		$linkinfo = preg_split("/\s+/", $linkinfo[1]);
		if (preg_match("/\*$/", $linkinfo[0])) {
			$ifinfo['pptplink'] = "down";
		} else {
			/* get PPTP link status for dial on demand */
			unset($ifconfiginfo);
			exec("/sbin/ifconfig " . $ifinfo['if'], $ifconfiginfo);
	
			$ifinfo['pptplink'] = "up";
	
			foreach ($ifconfiginfo as $ici) {
				if (strpos($ici, 'LINK0') !== false)
					$ifinfo['pptplink'] = "down";
			}
		}
	}
	
	/* modem interface? -> get status from ifconfig having an ipv4 addr */
	if (($ifdescr == "wan") && ($config['interfaces']['wan']['ipaddr'] == "modem")) {
		unset($linkinfo);
		
		exec("/sbin/ifconfig " . $ifinfo['if'], $ifconfiginfo);
			
		foreach ($ifconfiginfo as $ici) {
			if (!preg_match("/inet (\S+)/", $ici, $matches)) {
				$ifinfo['modemlink'] = "down";
			} else {
				$ifinfo['modemlink'] = "up";
				$ifinfo['status'] = "up";
				break;
			}
		}
	}
	
	if ($ifinfo['status'] == "up") {
		/* try to determine media with ifconfig */
		unset($ifconfiginfo);
		if ($ifdescr == "wan" && $config['interfaces']['wan']['ipaddr'] == "modem") {
			exec("/sbin/ifconfig " . $ifinfo['if'], $ifconfiginfo);
		} else {
			exec("/sbin/ifconfig " . $ifinfo['hwif'], $ifconfiginfo);
		}
		
		foreach ($ifconfiginfo as $ici) {
			if (!isset($config['interfaces'][$ifdescr]['wireless'])) {
				/* don't list media/speed for wireless cards, as it always
				   displays 2 Mbps even though clients can connect at 11 Mbps */
				if (preg_match("/media: .*? \((.*?)\)/", $ici, $matches)) {
					$ifinfo['media'] = $matches[1];
				} else if (preg_match("/media: Ethernet (.*)/", $ici, $matches)) {
					$ifinfo['media'] = $matches[1];
				}
			}
			if (preg_match("/status: (.*)$/", $ici, $matches)) {
				if ($matches[1] != "active")
					$ifinfo['status'] = $matches[1];
			}
			if (preg_match("/channel (\S*)/", $ici, $matches)) {
				$ifinfo['channel'] = $matches[1];
			}
			if (preg_match("/ssid (\".*?\"|\S*)/", $ici, $matches)) {
				if ($matches[1][0] == '"')
					$ifinfo['ssid'] = substr($matches[1], 1, -1);
				else
					$ifinfo['ssid'] = $matches[1];
			}
		}
		
		if ($ifinfo['pppoelink'] != "down" && $ifinfo['pptplink'] != "down" && $ifinfo['modemlink'] != "down") {
			/* try to determine IP address and netmask with ifconfig */
			unset($ifconfiginfo);
			exec("/sbin/ifconfig " . $ifinfo['if'], $ifconfiginfo);
			
			foreach ($ifconfiginfo as $ici) {
				if (preg_match("/inet (\S+)/", $ici, $matches)) {
					$ifinfo['ipaddr'] = $matches[1];
				}
				if (preg_match("/netmask (\S+)/", $ici, $matches)) {
					if (preg_match("/^0x/", $matches[1]))
						$ifaddr4s[] = $ifinfo['ipaddr'] . "/" . long2ip(hexdec($matches[1]));
				}
				if (ipv6enabled() && preg_match("/inet6 (\S+) prefixlen (\d+)/", $ici, $matches)) {
					$ifaddr6s[] = $matches[1] . "/" . $matches[2];
				}
			}
			
			if ($ifdescr == "wan") {
				/* run netstat to determine the default gateway */
				if ($ifinfo['modemlink']) {
					$ifinfo['gateway'] = "Modem";
				} else {
					unset($netstatrninfo);
					exec("/usr/bin/netstat -rnf inet", $netstatrninfo);
					
					foreach ($netstatrninfo as $nsr) {
						if (preg_match("/^default\s*(\S+)/", $nsr, $matches)) {
							$ifinfo['gateway'] = $matches[1];
						}
					}
				}
				if (ipv6enabled()) {
					unset($netstatrninfo);
					exec("/usr/bin/netstat -rnf inet6", $netstatrninfo);
					if ($ifinfo['modemlink']) {
						$ifinfo['gateway6'] = "Modem";
					} else { 
						foreach ($netstatrninfo as $nsr) {
							if (preg_match("/^default\s*(\S+)/", $nsr, $matches)) {
								$ifinfo['gateway6'] = $matches[1];
							}
						}
					}
					/* 6to4 on WAN? need to run ifconfig on stf0 then */
					if ($config['interfaces']['wan']['ipaddr6'] == "6to4") {
						unset($ifconfiginfo);
						exec("/sbin/ifconfig stf0", $ifconfiginfo);

						foreach ($ifconfiginfo as $ici) {
							if (ipv6enabled() && preg_match("/inet6 (\S+) prefixlen (\d+)/", $ici, $matches)) {
								$ifaddr6s[] = $matches[1] . "/" . $matches[2];
							}
						}
					}

					/* GRE tunnel on WAN? need to run ifconfig on gif0 then */
					if ($config['interfaces']['wan']['tunnel6'] || ($config['interfaces']['wan']['ipaddr6'] == "aiccu" ) ) {
						$tunif = 'gif0';
						if  ($config['interfaces']['wan']['ipaddr6'] == "aiccu" && isset($config['interfaces']['wan']['aiccu']['ayiya']) ) {
							$tunif = 'tun0';
						}
						unset($ifconfiginfo);

						exec("/sbin/ifconfig $tunif 2>/dev/null", $ifconfiginfo, $error);
						if (!$error){
							foreach ($ifconfiginfo as $ici) {
								if (preg_match("/inet6 (\S+) prefixlen (\d+)/", $ici, $matches)) {
									$ifaddr6a[] = $matches[1] . "/" . $matches[2];
								}
								if (preg_match("/inet6 (\S+) --> (\S+) prefixlen (\d+)/", $ici, $matches)) {
									$ifaddr6a[] = $matches[1] . "/" . $matches[3];
								}
							}
						} else {
							$ifaddr6a[] = "AICCU down ? check logs. $tunif not found";
						}
					}
				}
			}
		}
	}
	
	return array ($ifinfo, $ifaddr6s,$ifaddr4s,$ifaddr6a);
}

?>
<?php include("fbegin.inc"); ?>
<form action="" method="post">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" summary="content pane">
              <?php $resolvers = get_resolvers();
					$i = 0; $ifdescrs = array('wan' => 'WAN', 'lan' => 'LAN');
						
					for ($j = 1; isset($config['interfaces']['opt' . $j]); $j++) {
						$ifdescrs['opt' . $j] = $config['interfaces']['opt' . $j]['descr'];
					}
					
			      foreach ($ifdescrs as $ifdescr => $ifname): 
				 list( $ifinfo, $ifaddr6s, $ifaddr4s, $ifaddr6a) = get_interface_info($ifdescr);
				  ?>
              <?php if ($i): ?>
              <tr>
				  <td colspan="8" class="list" height="12"></td>
				</tr>
				<?php endif; ?>
              <tr> 
                <td colspan="2" class="listtopic"> 
                  <?=htmlspecialchars($ifname);?>
                  interface</td>
              </tr>
              <tr> 
                <td width="22%" class="vncellt">Status</td>
                <td width="78%" class="listr"> 
                  <?=htmlspecialchars($ifinfo['status']);?>
                </td>
              </tr><?php if ($ifinfo['dhcplink']): ?>
			  <tr> 
				<td width="22%" class="vncellt">DHCP</td>
				<td width="78%" class="listr"> 
				  <?=htmlspecialchars($ifinfo['dhcplink']);?>&nbsp;&nbsp;
				  <?php if ($ifinfo['dhcplink'] == "up"): ?>
				  <input type="submit" name="submit" value="Release" class="formbtns">
				  <?php else: ?>
				  <input type="submit" name="submit" value="Renew" class="formbtns">
				  <?php endif; ?>
				</td>
			  </tr><?php endif; if ($ifinfo['pppoelink']): ?>
              <tr> 
                <td width="22%" class="vncellt">PPPoE</td>
                <td width="78%" class="listr"> 
                  <?=htmlspecialchars($ifinfo['pppoelink']);?>&nbsp;&nbsp;
				  <?php if ($ifinfo['pppoelink'] == "up"): ?>
				  <input type="submit" name="submit" value="Disconnect" class="formbtns">
				  <?php else: ?>
				  <input type="submit" name="submit" value="Connect" class="formbtns">
				  <?php endif; ?>
                </td>
              </tr><?php  endif; if ($ifinfo['pptplink']): ?>
              <tr> 
                <td width="22%" class="vncellt">PPTP</td>
                <td width="78%" class="listr"> 
                  <?=htmlspecialchars($ifinfo['pptplink']);?>&nbsp;&nbsp;
				  <?php if ($ifinfo['pptplink'] == "up"): ?>
				  <input type="submit" name="submit" value="Disconnect" class="formbtns">
				  <?php else: ?>
				  <input type="submit" name="submit" value="Connect" class="formbtns">
				  <?php endif; ?>
                </td>
              </tr><?php  endif; if ($ifinfo['modemlink']): ?>
              <tr> 
                <td width="22%" class="vncellt">Modem</td>
                <td width="78%" class="listr"> 
                  <?=htmlspecialchars($ifinfo['modemlink']);?>&nbsp;&nbsp;
				  <?php if ($ifinfo['modemlink'] == "up"): ?>
				  <input type="submit" name="submit" value="Disconnect" class="formbtns">
				  <?php else: ?>
				  <input type="submit" name="submit" value="Connect" class="formbtns">
				  <?php endif; ?>
                </td>
              </tr><?php  endif; ?>
              <tr> 
                <td width="22%" class="vncellt">MAC address</td>
                <td width="78%" class="listr"> 
                  <?=htmlspecialchars($ifinfo['macaddr']);?>
                </td>
              </tr><?php if ($ifinfo['status'] != "down"): ?>
			  <?php if ($ifinfo['dhcplink'] != "down" && $ifinfo['pppoelink'] != "down" && $ifinfo['pptplink'] != "down" && $ifinfo['modemlink'] != "down"): ?>
			
			  <?php if (!empty($ifaddr4s)): ?>
              	<tr> 
                	<td width="22%" class="vncellt">IPv4 address</td>
                	<td width="78%" class="listr"> 
			 		<?php foreach ($ifaddr4s as $if4info):
		 			echo "$if4info<br>";
		    		endforeach; ?></td>
              </tr>
              <?php endif; ?>
			  <?php if ($ifinfo['gateway']): ?>
              <tr> 
                <td width="22%" class="vncellt">IPv4 gateway</td>
                <td width="78%" class="listr"> 
                  <?=htmlspecialchars($ifinfo['gateway']);?>
                  &nbsp; </td>
              </tr><?php endif; ?>
			  <?php if (!empty($ifaddr6s)): ?>
			  <tr> 
		        <td width="22%" class="vncellt">IPv6 address</td>
		        <td width="78%" class="listr">
				  <?php foreach ($ifaddr6s as $if6info):
				    echo "$if6info<br>";
				  endforeach; ?></td>
		      </tr>
              <?php endif; ?>
			  <?php if ($ifinfo['gateway6']): ?>
              <tr> 
                <td width="22%" class="vncellt">IPv6 gateway</td>
                <td width="78%" class="listr"> 
                  <?=htmlspecialchars($ifinfo['gateway6']);?>
                  &nbsp; </td>
              </tr><?php endif; ?>
	          <?php if ($config['interfaces']['wan']['ipaddr6'] == "aiccu" && $ifdescr == "wan"): ?>
              <tr> 
                <td width="22%" class="vncellt">AICCU address</td>
                <td width="78%" class="listr"> 
                  <?php foreach ($ifaddr6a as $if6infoa):
				    echo "$if6infoa<br>";
				  endforeach; ?></td>
              </tr>
              <?php endif; ?>
			  <?php if ($ifdescr == "wan" && !empty($resolvers)): ?>
              <tr>
                <td width="22%" class="vncellt">ISP DNS servers</td>
                <td width="78%" class="listr">
				<?php foreach ($resolvers as $resolver):
				 		echo "$resolver<br>";
				    	endforeach; ?></td>
              </tr>
			  <?php endif; endif; if ($ifinfo['media']): ?>
              <tr> 
                <td width="22%" class="vncellt">Media</td>
                <td width="78%" class="listr"> 
                  <?=htmlspecialchars($ifinfo['media']);?>
                </td>
              </tr><?php endif; ?><?php if ($ifinfo['channel']): ?>
              <tr> 
                <td width="22%" class="vncellt">Channel</td>
                <td width="78%" class="listr"> 
                  <?=htmlspecialchars($ifinfo['channel']);?>
                </td>
              </tr><?php endif; ?><?php if ($ifinfo['ssid']): ?>
              <tr> 
                <td width="22%" class="vncellt">SSID</td>
                <td width="78%" class="listr"> 
                  <?=htmlspecialchars($ifinfo['ssid']);?>
                </td>
              </tr><?php endif; ?>
              <tr> 
                <td width="22%" class="vncellt">In/out packets</td>
                <td width="78%" class="listr"> 
                  <?=htmlspecialchars($ifinfo['inpkts'] . "/" . $ifinfo['outpkts'] . " (" . 
				  		format_bytes($ifinfo['inbytes']) . "/" . format_bytes($ifinfo['outbytes']) . ")");?>
                </td>
              </tr><?php if (isset($ifinfo['inerrs'])): ?>
              <tr> 
                <td width="22%" class="vncellt">In/out errors</td>
                <td width="78%" class="listr"> 
                  <?=htmlspecialchars($ifinfo['inerrs'] . "/" . $ifinfo['outerrs']);?>
                </td>
              </tr><?php endif; ?><?php if (isset($ifinfo['collisions'])): ?>
              <tr> 
                <td width="22%" class="vncellt">Collisions</td>
                <td width="78%" class="listr"> 
                  <?=htmlspecialchars($ifinfo['collisions']);?>
                </td>
              </tr><?php endif; ?>
	      <?php endif; ?>
              <?php $i++; endforeach; ?>
            </table>
</form>
<?php include("fend.inc"); ?>
