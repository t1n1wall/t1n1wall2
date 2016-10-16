#!/usr/local/bin/php
<?php 
/*
	$Id: firewall_nat_1to1.php 521 2012-10-19 15:07:13Z mkasper $
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

$pgtitle = array("Firewall", "NAT", "1:1");
require("guiconfig.inc");

if (!is_array($config['nat']['onetoone'])) {
	$config['nat']['onetoone'] = array();
}
$a_1to1 = &$config['nat']['onetoone'];
nat_1to1_rules_sort();

if ($_POST) {

	$pconfig = $_POST;

	if ($_POST['apply']) {
		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			config_lock();
			$retval |= filter_configure(true);
			$retval |= services_proxyarp_configure();
			config_unlock();
		}
		$savemsg = get_std_save_message($retval);
		
		if ($retval == 0) {
			if (file_exists($d_natconfdirty_path))
				unlink($d_natconfdirty_path);
			if (file_exists($d_filterconfdirty_path))
				unlink($d_filterconfdirty_path);
			if (file_exists($d_proxyarpdirty_path))
				unlink($d_proxyarpdirty_path);
		}
	}
}

if (isset($_POST['del_x']) && is_array($_POST['entries'])) {
	foreach ($_POST['entries'] as $entry) {
		if ($a_1to1[$entry])
			unset($a_1to1[$entry]);
	}	
	
	write_config();
	touch($d_natconfdirty_path);
	header("Location: firewall_nat_1to1.php");
	exit;
}

?>
<?php include("fbegin.inc"); ?>
<form action="firewall_nat_1to1.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_natconfdirty_path)): ?><p>
<?php print_info_box_np("The NAT configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
<input name="apply" type="submit" class="formbtn" id="apply" value="Apply changes"></p>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tab pane">
<tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php
   	$tabs = array('Inbound' => 'firewall_nat.php',
           		  'Server NAT' => 'firewall_nat_server.php',
           		  '1:1' => 'firewall_nat_1to1.php',
           		  'Outbound' => 'firewall_nat_out.php');
	dynamic_tab_menu($tabs);
?>    
  </ul>
  </td></tr>
  <tr> 
    <td class="tabcont">
              <table width="100%" border="0" cellpadding="0" cellspacing="0" summary="content pane">
                <tr> 
    			  <td width="5%" class="list">&nbsp;</td>
				  <td width="10%" class="listhdrr">Interface</td>
                  <td width="20%" class="listhdrr">External IP</td>
                  <td width="20%" class="listhdrr">Internal IP</td>
                  <td width="35%" class="listhdr">Description</td>
                  <td width="10%" class="list"></td>
				</tr>
			  <?php $i = 0; foreach ($a_1to1 as $natent): ?>
                <tr> 
				  <td class="listt"><input type="checkbox" name="entries[]" value="<?=$i;?>" style="margin: 0 5px 0 0; padding: 0; width: 15px; height: 15px;"></td>
				  <td class="listlr">
                  <?php
					if (!$natent['interface'] || ($natent['interface'] == "wan"))
						echo "WAN";
					else
						echo htmlspecialchars($config['interfaces'][$natent['interface']]['descr']);
				  ?>
                  </td>
                  <td class="listr"> 
                    <?php echo $natent['external'];
					if ($natent['subnet']) echo "/" . $natent['subnet']; ?>
                  </td>
                  <td class="listr"> 
                    <?php echo $natent['internal'];
					if ($natent['subnet']) echo "/" . $natent['subnet']; ?>
                  </td>
                  <td class="listbg"> 
                    <?=htmlspecialchars($natent['descr']);?>&nbsp;
                  </td>
                  <td class="list" nowrap> <a href="firewall_nat_1to1_edit.php?id=<?=$i;?>"><img src="e.png" title="edit mapping" width="17" height="17" border="0" alt="edit mapping"></a></td>
				</tr>
			  <?php $i++; endforeach; ?>
                <tr> 
                  <td class="list" colspan="5"></td>
                  <td class="list">
					<input name="del" type="image" src="x.png" width="17" height="17" title="delete selected mappings" alt="delete selected mappings" onclick="return confirm('Do you really want to delete the selected mappings?')">
					<a href="firewall_nat_1to1_edit.php"><img src="plus.png" title="add mapping" width="17" height="17" border="0" alt="add mapping"></a></td>
				</tr>
              </table><br>
			  	<span class="vexpl"><span class="red"><strong>Note:<br>
                </strong></span>Depending on the way your WAN connection is setup, you may also need <a href="services_proxyarp.php">proxy ARP</a>.</span>
</td>
</tr>
</table>
</form>
<?php include("fend.inc"); ?>
