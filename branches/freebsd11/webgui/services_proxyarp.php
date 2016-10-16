#!/usr/local/bin/php
<?php
/*
	$Id: services_proxyarp.php 522 2012-10-22 15:47:41Z mkasper $
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

$pgtitle = array("Services", "Proxy ARP");
require("guiconfig.inc");

if (!is_array($config['proxyarp']['proxyarpnet'])) {
	$config['proxyarp']['proxyarpnet'] = array();
}
proxyarp_sort();
$a_proxyarp = &$config['proxyarp']['proxyarpnet'];

if ($_POST) {

	foreach ($_POST as $pn => $pv) {
		if (preg_match("/^del_(\d+)_x$/", $pn, $matches)) {
			$id = $matches[1];
			if ($a_proxyarp[$id]) {
				unset($a_proxyarp[$id]);
				write_config();
				touch($d_proxyarpdirty_path);
				header("Location: services_proxyarp.php");
				exit;
			}
		}
	}
	
	$pconfig = $_POST;
	
	$retval = 0;
	if (!file_exists($d_sysrebootreqd_path)) {
		config_lock();
		$retval = services_proxyarp_configure();
		config_unlock();
	}
	$savemsg = get_std_save_message($retval);

	if ($retval == 0) {
		if (file_exists($d_proxyarpdirty_path))
			unlink($d_proxyarpdirty_path);
	}
}

?>
<?php include("fbegin.inc"); ?>
<form action="services_proxyarp.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_proxyarpdirty_path)): ?><p>
<?php print_info_box_np("The proxy ARP configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
<input name="apply" type="submit" class="formbtn" id="apply" value="Apply changes"></p>
<?php endif; ?>
              <table width="100%" border="0" cellpadding="0" cellspacing="0" summary="content pane">
                <tr>
                  <td width="20%" class="listhdrr">Interface</td>
                  <td width="30%" class="listhdrr">Network</td>
                  <td width="40%" class="listhdr">Description</td>
                  <td width="10%" class="list"></td>
				</tr>
			  <?php $i = 0; foreach ($a_proxyarp as $arpent): ?>
                <tr>
				  <td class="listlr">
                  <?php
				  	if ($arpent['interface']) {
					  $iflabels = array('lan' => 'LAN', 'wan' => 'WAN');
					  for ($j = 1; isset($config['interfaces']['opt' . $j]); $j++)
						$iflabels['opt' . $j] = $config['interfaces']['opt' . $j]['descr'];
					  echo htmlspecialchars($iflabels[$arpent['interface']]);
					} else {
						echo "WAN";
					}
	    		  ?>
                  </td>
                  <td class="listr">
				  <?php if (isset($arpent['network'])) {
				  			list($sa,$sn) = explode("/", $arpent['network']);
							if ($sn == 32)
								echo $sa;
							else
					  			echo $arpent['network'];
						} else if (isset($arpent['range']))
							echo $arpent['range']['from'] . "-" . $arpent['range']['to'];
                    ?>&nbsp;
                  </td>
                  <td class="listbg">
                    <?=htmlspecialchars($arpent['descr']);?>&nbsp;
                  </td>
                  <td valign="middle" nowrap class="list"> <a href="services_proxyarp_edit.php?id=<?=$i;?>"><img src="e.png" title="edit network" width="17" height="17" border="0" alt="edit network"></a>
                     &nbsp;<input name="del_<?=$i;?>" type="image" src="x.png" width="17" height="17" title="delete network" alt="delete network" onclick="return confirm('Do you really want to delete this network?')"></td>
				</tr>
			  <?php $i++; endforeach; ?>
                <tr> 
                  <td class="list" colspan="3"></td>
                  <td class="list"> <a href="services_proxyarp_edit.php"><img src="plus.png" title="add network" width="17" height="17" border="0" alt="add network"></a></td>
				</tr>
              </table>
            </form>
            <p class="vexpl"><span class="red"><strong>Note:<br>
                      </strong></span>Proxy ARP can be used if you need t1n1wall to send ARP
					  replies on an interface for other IP addresses than its own (e.g. for 1:1, advanced outbound or server NAT). It is not
					  necessary on the WAN interface if you have a subnet routed to you or if you use PPPoE/PPTP, and it only works on the WAN interface if it's configured with a static IP address or DHCP.</p>
            <?php include("fend.inc"); ?>
