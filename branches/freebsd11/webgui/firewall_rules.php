#!/usr/local/bin/php
<?php 
/*
	$Id: firewall_rules.php 576 2015-02-18 00:44:44Z awhite $
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

require("guiconfig.inc");

if ($ipv6rules = ($_GET['type'] == 'ipv6')) {
	$configname = 'rule6';
	$typelink = '&type=ipv6';
} else {
	$configname = 'rule';
	$typelink = '';
}
$pgtitle = array("Firewall", "Rules");	/* make group manager happy */
$pgtitle = array("Firewall", ipv6enabled() ? ($ipv6rules ? 'IPv6 Rules' : 'IPv4 Rules') : 'Rules');

if (!is_array($config['filter'][$configname])) {
	$config['filter'][$configname] = array();
}
filter_rules_sort();
$a_filter = &$config['filter'][$configname];

$if = $_GET['if'];
if ($_POST['if'])
	$if = $_POST['if'];
	
$iflist = array("lan" => "LAN", "wan" => "WAN");

if ((($config['pptpd']['mode'] == "server" )) && !$ipv6rules)
	$iflist['pptp'] = "PPTP VPN";

if ((($config['l2tp']['mode'] == "server" )) && !$ipv6rules)
	$iflist['l2tp'] = "L2TP VPN";

if (isset($config['ipsec']['enable']))
	$iflist['ipsec'] = "IPsec VPN";

for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
	$iflist['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
}

if (!$if || !isset($iflist[$if]))
	$if = "wan";

if ($_POST) {

	$pconfig = $_POST;

	if ($_POST['apply']) {
		$retval = 0;
		
		if (!file_exists($d_sysrebootreqd_path)) {
			config_lock();
			if ($configname == 'rule6') {
				$retval = filter_configure6();
			} else {
				$retval = filter_configure(true);
			}
			config_unlock();
		}
		$savemsg = get_std_save_message($retval);
		if ($retval == 0) {
			if (file_exists($d_natconfdirty_path))
				unlink($d_natconfdirty_path);
			if (file_exists($d_filterconfdirty_path))
				unlink($d_filterconfdirty_path);
		}
	}
}

if (isset($_POST['del_x'])) {
	/* delete selected rules */
	if (is_array($_POST['rule']) && count($_POST['rule'])) {
		foreach ($_POST['rule'] as $rulei) {
			unset($a_filter[$rulei]);
		}
		write_config();
		touch($d_filterconfdirty_path);
		header("Location: firewall_rules.php?if={$if}{$typelink}");
		exit;
	}
} else {
	/* yuck - IE won't send value attributes for image buttons, while Mozilla does - 
	   so we use .x/.y to find move and toggle button clicks instead... */
	unset($movebtn);
	unset($togglebtn);
	foreach ($_POST as $pn => $pd) {
		if (preg_match("/move_(\d+)_x/", $pn, $matches)) {
			$movebtn = $matches[1];
			break;
		} else if (preg_match("/toggle_(\d+)_x/", $pn, $matches)) {
			$togglebtn = $matches[1];
			break;
		}
	}
	/* move selected rules before this rule */
	if (isset($movebtn) && is_array($_POST['rule']) && count($_POST['rule'])) {
		$a_filter_new = array();
		
		/* copy all rules < $movebtn and not selected */
		for ($i = 0; $i < $movebtn; $i++) {
			if (!in_array($i, $_POST['rule']))
				$a_filter_new[] = $a_filter[$i];
		}
		
		/* copy all selected rules */
		for ($i = 0; $i < count($a_filter); $i++) {
			if ($i == $movebtn)
				continue;
			if (in_array($i, $_POST['rule']))
				$a_filter_new[] = $a_filter[$i];
		}
		
		/* copy $movebtn rule */
		if ($movebtn < count($a_filter))
			$a_filter_new[] = $a_filter[$movebtn];
		
		/* copy all rules > $movebtn and not selected */
		for ($i = $movebtn+1; $i < count($a_filter); $i++) {
			if (!in_array($i, $_POST['rule']))
				$a_filter_new[] = $a_filter[$i];
		}
		
		$a_filter = $a_filter_new;
		write_config();
		touch($d_filterconfdirty_path);
		header("Location: firewall_rules.php?if={$if}{$typelink}");
		exit;
	}
	
	if (isset($togglebtn)) {
		if ($a_filter[$togglebtn]) {
			$a_filter[$togglebtn]['disabled'] = !isset($a_filter[$togglebtn]['disabled']);
			write_config();
			touch($d_filterconfdirty_path);
			header("Location: firewall_rules.php?if={$if}{$typelink}");
			exit;
		}
	}
}

?>
<?php include("fbegin.inc"); ?>
<script type="text/javascript">
<!--
function fr_toggle(id) {
	var checkbox = document.getElementById('frc' + id);
	checkbox.checked = !checkbox.checked;
	fr_bgcolor(id);
}
function fr_bgcolor(id) {
	var row = document.getElementById('fr' + id);
	var checkbox = document.getElementById('frc' + id);
	var cells = row.getElementsByTagName("td");
	
	for (i = 2; i <= 6; i++) {
		cells[i].style.backgroundColor = checkbox.checked ? "#FFFFBB" : "#FFFFFF";
	}
	cells[7].style.backgroundColor = checkbox.checked ? "#FFFFBB" : "#D9DEE8";
}
function fr_insline(id, on) {
	var row = document.getElementById('fr' + id);
	var prevrow;
	if (id != 0) {
		prevrow = document.getElementById('fr' + (id-1));
	} else {
		if (<?php if (($if == "wan") && isset($config['interfaces']['wan']['blockpriv'])) echo "true"; else echo "false"; ?>) {
			prevrow = document.getElementById('frrfc1918');
		} else {
			prevrow = document.getElementById('frheader');
		}
	}
	
	var cells = row.getElementsByTagName("td");
	var prevcells = prevrow.getElementsByTagName("td");
	
	for (i = 2; i <= 7; i++) {
		if (on) {
			prevcells[i].style.borderBottom = "3px solid #999999";
			prevcells[i].style.paddingBottom = (id != 0) ? 2 : 3;
		} else {
			prevcells[i].style.borderBottomWidth = "1px";
			prevcells[i].style.paddingBottom = (id != 0) ? 4 : 5;
		}
	}
	
	for (i = 2; i <= 7; i++) {
		if (on) {
			cells[i].style.borderTop = "2px solid #999999";
			cells[i].style.paddingTop = 2;
		} else {
			cells[i].style.borderTopWidth = 0;
			cells[i].style.paddingTop = 4;
		}
	}
}
// -->
</script>
<form action="firewall_rules.php<?=($typelink ? '?' . $typelink : '')?>" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_filterconfdirty_path)): ?><p>
<?php print_info_box_np("The firewall rule configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
<input name="apply" type="submit" class="formbtn" id="apply" value="Apply changes"></p>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tab pane">
  <tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php $i = 0; foreach ($iflist as $ifent => $ifname):
	if ($ifent == $if): ?>
    <li class="tabact"><?=htmlspecialchars($ifname);?></li>
<?php else: ?>
    <li class="<?php if ($i == 0) echo "tabinact1"; else echo "tabinact";?>"><a href="firewall_rules.php?if=<?=$ifent;?><?=$typelink;?>"><?=htmlspecialchars($ifname);?></a></li>
<?php endif; ?>
<?php $i++; endforeach; ?>
  </ul>
  </td></tr>
  <tr> 
    <td class="tabcont">
              <table width="100%" border="0" cellpadding="0" cellspacing="0" summary="content pane">
                <tr id="frheader">
                  <td width="3%" class="list">&nbsp;</td>
                  <td width="5%" class="list">&nbsp;</td>
                  <td width="10%" class="listhdrr">Proto</td>
                  <td width="15%" class="listhdrr">Source</td>
                  <td width="10%" class="listhdrr">Port</td>
                  <td width="15%" class="listhdrr">Destination</td>
                  <td width="10%" class="listhdrr">Port</td>
                  <td width="22%" class="listhdr">Description</td>
                  <td width="10%" class="list"></td>
				</tr>
<?php if (($if == "wan") && isset($config['interfaces']['wan']['blockpriv'])): ?>
                <tr valign="top" id="frrfc1918">
                  <td class="listt"></td>
                  <td class="listt" align="center"><img src="block.gif" width="11" height="11" border="0" alt=""></td>
                  <td class="listlr" style="background-color: #e0e0e0">*</td>
                  <td class="listr" style="background-color: #e0e0e0">
						<?php if ($ipv6rules): ?>
						Reserved IPv6 networks
						<?php else: ?>
						RFC 1918 networks
						<?php endif; ?></td>
                  <td class="listr" style="background-color: #e0e0e0">*</td>
                  <td class="listr" style="background-color: #e0e0e0">*</td>
                  <td class="listr" style="background-color: #e0e0e0">*</td>
                  <td class="listbg" style="background-color: #e0e0e0">
						<?php if ($ipv6rules): ?>
						Block reserved networks
						<?php else: ?>
						Block private networks
						<?php endif; ?></td>
                  <td valign="middle" nowrap class="list">
				    <table border="0" cellspacing="0" cellpadding="1" summary="rule table">
					<tr>
					  <td><img src="in_d.png" width="17" height="17" title="move selected rules before this rule" alt="move selected rules before this rule"></td>
					  <td><a href="interfaces_wan.php#rfc1918"><img src="e.png" title="edit rule" width="17" height="17" border="0" alt="edit rule"></a></td>
					</tr>
					<tr>
					  <td align="center" valign="middle"></td>
					  <td><img src="plus_d.png" title="add a new rule based on this one" width="17" height="17" border="0" alt="add a new rule based on this one"></td>
					</tr>
					</table>
				  </td>
				</tr>
<?php endif; ?>
				<?php $nrules = 0; for ($i = 0; isset($a_filter[$i]); $i++):
					$filterent = $a_filter[$i];
					if ($filterent['interface'] != $if)
						continue;
				?>
                <tr valign="top" id="fr<?=$nrules;?>">
                  <td class="listt"><input type="checkbox" id="frc<?=$nrules;?>" name="rule[]" value="<?=$i;?>" onClick="fr_bgcolor('<?=$nrules;?>')" style="margin: 0; padding: 0; width: 15px; height: 15px;"></td>
                  <td class="listt" align="center">
				  <?php if ($filterent['type'] == "block")
				  			$iconfn = "block";
						else if ($filterent['type'] == "reject") {
							if ($filterent['protocol'] == "tcp" || $filterent['protocol'] == "udp")
								$iconfn = "reject";
							else
								$iconfn = "block";
						} else
							$iconfn = "pass";
						if (isset($filterent['disabled'])) {
							$textss = "<span class=\"gray\">";
							$textse = "</span>";
							$iconfn .= "_d";
						} else {
							$textss = $textse = "";
						}
				  ?>
				  <input name="toggle_<?=$i;?>" type="image" src="<?=$iconfn;?>.gif" width="11" height="11" title="click to toggle enabled/disabled status">
				  <?php if (isset($filterent['log'])):
							$iconfn = "log_s";
						if (isset($filterent['disabled']))
							$iconfn .= "_d";
				  	?>
				  <br><img src="<?=$iconfn;?>.gif" width="11" height="15" border="0">
				  <?php endif; ?>
				  </td>
                  <td class="listlr" onClick="fr_toggle(<?=$nrules;?>)"> 
                    <?=$textss;?><?php if (isset($filterent['protocol'])) echo strtoupper($filterent['protocol']); else echo "*"; ?><?=$textse;?>
                  </td>
                  <td class="listr" onClick="fr_toggle(<?=$nrules;?>)">
				    <?=$textss;?><?php echo htmlspecialchars(pprint_address($filterent['source'])); ?><?=$textse;?>
                  </td>
                  <td class="listr" onClick="fr_toggle(<?=$nrules;?>)">
                    <?=$textss;?><?php echo htmlspecialchars(pprint_port($filterent['source']['port'])); ?><?=$textse;?>
                  </td>
                  <td class="listr" onClick="fr_toggle(<?=$nrules;?>)"> 
				    <?=$textss;?><?php echo htmlspecialchars(pprint_address($filterent['destination'])); ?><?=$textse;?>
                  </td>
                  <td class="listr" onClick="fr_toggle(<?=$nrules;?>)"> 
                    <?=$textss;?><?php echo htmlspecialchars(pprint_port($filterent['destination']['port'])); ?><?=$textse;?>
                  </td>
                  <td class="listbg" onClick="fr_toggle(<?=$nrules;?>)"> 
                    <?=$textss;?><?=htmlspecialchars($filterent['descr']);?>&nbsp;<?=$textse;?>
                  </td>
                  <td valign="middle" nowrap class="list">
				    <table border="0" cellspacing="0" cellpadding="1" summary="button pane">
					<tr>
					  <td><input name="move_<?=$i;?>" type="image" src="in.png" width="17" height="17" title="move selected rules before this rule" onMouseOver="fr_insline(<?=$nrules;?>, true)" onMouseOut="fr_insline(<?=$nrules;?>, false)"></td>
					  <td><a href="firewall_rules_edit.php?id=<?=$i;?><?=$typelink;?>"><img src="e.png" title="edit rule" width="17" height="17" border="0" alt="edit rule"></a></td>
					</tr>
					<tr>
					  <td align="center" valign="middle"></td>
					  <td><a href="firewall_rules_edit.php?dup=<?=$i;?><?=$typelink;?>"><img src="plus.png" title="add a new rule based on this one" width="17" height="17" border="0" alt="add a new rule based on this one"></a></td>
					</tr>
					</table>
				  </td>
				</tr>
			  <?php $nrules++; endfor; ?>
			  <?php if ($nrules == 0): ?>
              <td class="listt"></td>
			  <td class="listt"></td>
			  <td class="listlr" colspan="6" align="center" valign="middle">
			  <span class="gray">
			  No rules are currently defined for this interface.<br>
			  All incoming connections on this interface will be blocked until you add pass rules.<br><br>
			  Click the <a href="firewall_rules_edit.php?if=<?=htmlspecialchars($if);?><?=$typelink;?>"><img src="plus.png" title="add new rule" border="0" width="17" height="17" align="middle" alt="add new rule"></a> button to add a new rule.</span>
			  </td>
			  <?php endif; ?>
                <tr id="fr<?=$nrules;?>"> 
                  <td class="list"></td>
                  <td class="list"></td>
                  <td class="list">&nbsp;</td>
                  <td class="list">&nbsp;</td>
                  <td class="list">&nbsp;</td>
                  <td class="list">&nbsp;</td>
                  <td class="list">&nbsp;</td>
                  <td class="list">&nbsp;</td>
                  <td class="list">
				    <table border="0" cellspacing="0" cellpadding="1" summary="button pane">
					<tr>
				      <td>
					  <?php if ($nrules == 0): ?><img src="in_d.png" width="17" height="17" title="move selected rules to end" border="0" alt="move selected rules to end"><?php else: ?><input name="move_<?=$i;?>" type="image" src="in.png" width="17" height="17" title="move selected rules to end" onMouseOver="fr_insline(<?=$nrules;?>, true)" onMouseOut="fr_insline(<?=$nrules;?>, false)"><?php endif; ?></td>
					  <td></td>
				    </tr>
					<tr>
					  <td><?php if ($nrules == 0): ?><img src="x_d.png" width="17" height="17" title="delete selected rules" border="0" alt="delete selected rules"><?php else: ?><input name="del" type="image" src="x.png" width="17" height="17" title="delete selected rules" alt="delete selected rules" onclick="return confirm('Do you really want to delete the selected rules?')"><?php endif; ?></td>
					  <td><a href="firewall_rules_edit.php?if=<?=htmlspecialchars($if);?><?=$typelink;?>"><img src="plus.png" title="add new rule" width="17" height="17" border="0" alt="add new rule"></a></td>
					</tr>
				    </table>
				  </td>
				</tr>
              </table>
			  <table border="0" cellspacing="0" cellpadding="0" summary="info pane">
                <tr> 
                  <td width="16"><img src="pass.gif" width="11" height="11" alt=""></td>
                  <td>pass</td>
                  <td width="14"></td>
                  <td width="16"><img src="block.gif" width="11" height="11" alt=""></td>
                  <td>block</td>
                  <td width="14"></td>
                  <td width="16"><img src="reject.gif" width="11" height="11" alt=""></td>
                  <td>reject</td>
                  <td width="14"></td>
                  <td width="16"><img src="log.gif" width="11" height="11" alt=""></td>
                  <td>log</td>
                </tr>
                <tr>
                  <td colspan="5" height="4"></td>
                </tr>
                <tr> 
                  <td><img src="pass_d.gif" width="11" height="11" alt=""></td>
                  <td>pass (disabled)</td>
                  <td></td>
                  <td><img src="block_d.gif" width="11" height="11" alt=""></td>
                  <td>block (disabled)</td>
                  <td></td>
                  <td><img src="reject_d.gif" width="11" height="11" alt=""></td>
                  <td>reject (disabled)</td>
                  <td></td>
                  <td width="16"><img src="log_d.gif" width="11" height="11" alt=""></td>
                  <td>log (disabled)</td>
                </tr>
              </table>
    </td>
  </tr>
</table><br>
  <strong><span class="red">Hint:<br>
  </span></strong>Rules are evaluated on a first-match basis (i.e. 
  the action of the first rule to match a packet will be executed). 
  This means that if you use block rules, you'll have to pay attention 
  to the rule order. Everything that isn't explicitly passed is blocked 
  by default.
  <input type="hidden" name="if" value="<?=htmlspecialchars($if);?>">
</form>
<?php include("fend.inc"); ?>
