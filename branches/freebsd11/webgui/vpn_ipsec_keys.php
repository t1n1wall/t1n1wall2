#!/usr/local/bin/php
<?php
/*
	$Id: vpn_ipsec_keys.php 522 2012-10-22 15:47:41Z mkasper $
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

$pgtitle = array("VPN", "IPsec", "Pre-shared keys");
require("guiconfig.inc");

if (!is_array($config['ipsec']['mobilekey'])) {
	$config['ipsec']['mobilekey'] = array();
}
ipsec_mobilekey_sort();
$a_mobilekey = &$config['ipsec']['mobilekey'];

if ($_POST) {
	foreach ($_POST as $pn => $pv) {
		if (preg_match("/^del_(\d+)_x$/", $pn, $matches)) {
			$id = $matches[1];
			if ($a_mobilekey[$id]) {
				unset($a_mobilekey[$id]);
				write_config();
				touch($d_ipsecconfdirty_path);
				header("Location: vpn_ipsec_keys.php");
				exit;
			}
		}
	}
}

?>
<?php include("fbegin.inc"); ?>
<form action="vpn_ipsec.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_ipsecconfdirty_path)): ?><p>
<?php print_info_box_np("The IPsec tunnel configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
<input name="apply" type="submit" class="formbtn" id="apply" value="Apply changes"></p>
<?php endif; ?>
</form>
<form action="vpn_ipsec_keys.php" method="post">
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tab pane">
  <tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php 
   	$tabs = array('Tunnels' => 'vpn_ipsec.php',
           		  'Mobile clients' => 'vpn_ipsec_mobile.php',
           		  'Pre-shared keys' => 'vpn_ipsec_keys.php',
           		  'CAs/CRLs' => 'vpn_ipsec_ca.php');
	dynamic_tab_menu($tabs);
?>       
  </ul>
  </td></tr>
  <tr> 
    <td class="tabcont">
              <table width="80%" border="0" cellpadding="0" cellspacing="0" summary="content pane">
                <tr> 
                  <td class="listhdrr">Identifier</td>
                  <td class="listhdr">Pre-shared key</td>
                  <td class="list"></td>
				</tr>
			  <?php $i = 0; foreach ($a_mobilekey as $mobilekeyent): ?>
                <tr> 
                  <td class="listlr">
                    <?=htmlspecialchars($mobilekeyent['ident']);?>
                  </td>
                  <td class="listr">
                    <?=htmlspecialchars($mobilekeyent['pre-shared-key']);?>
                  </td>
                  <td class="list" nowrap> <a href="vpn_ipsec_keys_edit.php?id=<?=$i;?>"><img src="e.png" title="edit key" width="17" height="17" border="0" alt="edit key"></a>
                     &nbsp;<input name="del_<?=$i;?>" type="image" src="x.png" width="17" height="17" title="delete key" alt="delete key" onclick="return confirm('Do you really want to delete this key?')"></td>
				</tr>
			  <?php $i++; endforeach; ?>
                <tr> 
                  <td class="list" colspan="2"></td>
                  <td class="list"> <a href="vpn_ipsec_keys_edit.php"><img src="plus.png" title="add key" width="17" height="17" border="0" alt="add key"></a></td>
				</tr>
              </table>
			 </td>
			</tr>
		</table>
</form>
<?php include("fend.inc"); ?>
