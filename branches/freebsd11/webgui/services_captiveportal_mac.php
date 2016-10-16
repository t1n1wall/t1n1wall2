#!/usr/local/bin/php
<?php
/*
	$Id: services_captiveportal_mac.php 522 2012-10-22 15:47:41Z mkasper $
	part of m0n0wall (http://m0n0.ch/wall)
	
	Copyright (C) 2004 Dinesh Nair <dinesh@alphaque.com>
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

$pgtitle = array("Services", "Captive portal", "Pass-through MAC");
require("guiconfig.inc");

if (!is_array($config['captiveportal']['passthrumac']))
	$config['captiveportal']['passthrumac'] = array();

passthrumacs_sort();
$a_passthrumacs = &$config['captiveportal']['passthrumac'] ;

if ($_POST) {

	$pconfig = $_POST;

	if ($_POST['apply']) {
		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			$retval = captiveportal_passthrumac_configure();
		}
		$savemsg = get_std_save_message($retval);
		if ($retval == 0) {
			if (file_exists($d_passthrumacsdirty_path)) {
				config_lock();
				unlink($d_passthrumacsdirty_path);
				config_unlock();
			}
		}
	}
	
	foreach ($_POST as $pn => $pv) {
		if (preg_match("/^del_(\d+)_x$/", $pn, $matches)) {
			$id = $matches[1];
			if ($a_passthrumacs[$id]) {
				unset($a_passthrumacs[$id]);
				write_config();
				touch($d_passthrumacsdirty_path);
				header("Location: services_captiveportal_mac.php");
				exit;
			}
		}
	}
}

?>
<?php include("fbegin.inc"); ?>
<form action="services_captiveportal_mac.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_passthrumacsdirty_path)): ?><p>
<?php print_info_box_np("The captive portal MAC address configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
<input name="apply" type="submit" class="formbtn" id="apply" value="Apply changes"></p>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tab pane">
  <tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php 
   	$tabs = array('Captive Portal' => 'services_captiveportal.php',
           		  'Pass-through MAC' => 'services_captiveportal_mac.php',
           		  'Allowed IP addresses' => 'services_captiveportal_ip.php',
           		  'Users' => 'services_captiveportal_users.php',
           		  'Vouchers' => 'services_captiveportal_vouchers.php',
           		  'File Manager' => 'services_captiveportal_filemanager.php');
	dynamic_tab_menu($tabs);
?> 
  </ul>
  </td></tr>
  <tr>
  <td class="tabcont">
  <table width="100%" border="0" cellpadding="0" cellspacing="0" summary="content pane">
	<tr>
	  <td width="30%" class="listhdrr">MAC address</td>
	  <td width="60%" class="listhdr">Description</td>
	  <td width="10%" class="list"></td>
	</tr>
  <?php $i = 0; foreach ($a_passthrumacs as $mac): ?>
	<tr>
	  <td class="listlr">
		<?=strtolower($mac['mac']);?>
	  </td>
	  <td class="listbg">
		<?=htmlspecialchars($mac['descr']);?>&nbsp;
	  </td>
	  <td valign="middle" nowrap class="list"> <a href="services_captiveportal_mac_edit.php?id=<?=$i;?>"><img src="e.png" title="edit host" width="17" height="17" border="0" alt="edit host"></a>
		 &nbsp;<input name="del_<?=$i;?>" type="image" src="x.png" width="17" height="17" title="delete host" alt="delete host" onclick="return confirm('Do you really want to delete this host?')"></td>
	</tr>
  <?php $i++; endforeach; ?>
	<tr> 
	  <td class="list" colspan="2">&nbsp;</td>
	  <td class="list"> <a href="services_captiveportal_mac_edit.php"><img src="plus.png" title="add host" width="17" height="17" border="0" alt="add host"></a></td>
	</tr>
	<tr>
	<td colspan="2" class="list"><span class="vexpl"><span class="red"><strong>
	Note:<br>
	</strong></span>
	Adding MAC addresses as pass-through MACs  allows them access through the captive portal automatically without being taken to the portal page. The pass-through MACs can change their IP addresses on the fly and upon the next access, the pass-through tables are changed accordingly. Pass-through MACs will however still be disconnected after the captive portal timeout period.</span></td>
	<td class="list">&nbsp;</td>
	</tr>
  </table>
  </td>
  </tr>
  </table>
</form>
<?php include("fend.inc"); ?>
