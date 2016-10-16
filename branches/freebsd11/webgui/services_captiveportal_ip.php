#!/usr/local/bin/php
<?php
/*
	$Id: services_captiveportal_ip.php 522 2012-10-22 15:47:41Z mkasper $
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

$pgtitle = array("Services", "Captive portal", "Allowed IP Addresses");
require("guiconfig.inc");

if (!is_array($config['captiveportal']['allowedip']))
	$config['captiveportal']['allowedip'] = array();

allowedips_sort();
$a_allowedips = &$config['captiveportal']['allowedip'] ;

if ($_POST) {

	$pconfig = $_POST;

	if ($_POST['apply']) {
		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			$retval = captiveportal_allowedip_configure();
		}
		$savemsg = get_std_save_message($retval);
		if ($retval == 0) {
			if (file_exists($d_allowedipsdirty_path)) {
				config_lock();
				unlink($d_allowedipsdirty_path);
				config_unlock();
			}
		}
	}
	
	foreach ($_POST as $pn => $pv) {
		if (preg_match("/^del_(\d+)_x$/", $pn, $matches)) {
			$id = $matches[1];
			if ($a_allowedips[$id]) {
				unset($a_allowedips[$id]);
				write_config();
				touch($d_allowedipsdirty_path);
				header("Location: services_captiveportal_ip.php");
				exit;
			}
		}
	}
}

?>
<?php include("fbegin.inc"); ?>
<form action="services_captiveportal_ip.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_allowedipsdirty_path)): ?><p>
<?php print_info_box_np("The captive portal IP address configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
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
	  <td width="30%" class="listhdrr">IP address</td>
	  <td width="60%" class="listhdr">Description</td>
	  <td width="10%" class="list"></td>
	</tr>
  <?php $i = 0; foreach ($a_allowedips as $ip): ?>
	<tr>
	  <td class="listlr">
		<?php if($ip['dir'] == "to") 
			echo "any <img src=\"in.png\" width=\"11\" height=\"11\" align=\"middle\" alt=\"\">";
		?>	
		<?=strtolower($ip['ip']);?>
		<?php if($ip['dir'] == "from") 
			echo "<img src=\"in.png\" width=\"11\" height=\"11\" align=\"absmiddle\" alt=\"\"> any";
		?>	
	  </td>
	  <td class="listbg">
		<?=htmlspecialchars($ip['descr']);?>&nbsp;
	  </td>
	  <td valign="middle" nowrap class="list"> <a href="services_captiveportal_ip_edit.php?id=<?=$i;?>"><img src="e.png" title="edit address" width="17" height="17" border="0" alt="edit address"></a>
		 &nbsp;<input name="del_<?=$i;?>" type="image" src="x.png" width="17" height="17" title="delete address" alt="delete address" onclick="return confirm('Do you really want to delete this address?')"></td>
	</tr>
  <?php $i++; endforeach; ?>
	<tr> 
	  <td class="list" colspan="2">&nbsp;</td>
	  <td class="list"> <a href="services_captiveportal_ip_edit.php"><img src="plus.png" title="add address" width="17" height="17" border="0" alt="add address"></a></td>
	</tr>
	<tr>
	<td colspan="2" class="list"><p class="vexpl"><span class="red"><strong>
	  Note:<br>
	  </strong></span>
	  Adding allowed IP addresses will allow IP access to/from these addresses through the captive portal without being taken to the portal page. This can be used for a web server serving images for the portal page or a DNS server on another network, for example. By specifying <em>from</em> addresses, it may be used to always allow pass-through access from a client behind the captive portal.</p>
	  <table border="0" cellspacing="0" cellpadding="0">
		<tr>
		  <td><span class="vexpl">any <img src="in.png" width="11" height="11" align="middle" alt=""> x.x.x.x </span></td>
		  <td><span class="vexpl">All connections <strong>to</strong> the IP address are allowed</span></td>
		</tr>
		<tr>
		  <td colspan="5" height="4"></td>
		</tr>
		<tr>
		<td>x.x.x.x <span class="vexpl"><img src="in.png" width="11" height="11" align="middle" alt=""></span> any&nbsp;&nbsp;&nbsp; </td>
		  <td><span class="vexpl">All connections <strong>from</strong> the IP address are allowed </span></td>
		</tr>
	  </table></td>
	<td class="list">&nbsp;</td>
	</tr>
  </table>
  </td>
  </tr>
  </table>
</form>
<?php include("fend.inc"); ?>
