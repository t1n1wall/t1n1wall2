#!/usr/local/bin/php
<?php 
/*
	$Id: firewall_nat_out.php 557 2014-01-11 20:05:02Z awhite $
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

$pgtitle = array("Firewall", "NAT", "Outbound");
require("guiconfig.inc");

if (!is_array($config['nat']['advancedoutbound']['rule']))
    $config['nat']['advancedoutbound']['rule'] = array();
    
$a_out = &$config['nat']['advancedoutbound']['rule'];
nat_out_rules_sort();

if ($_POST) {

    $pconfig = $_POST;

    $config['nat']['advancedoutbound']['enable'] = ($_POST['enable']) ? true : false;

	if (isset($_POST['del_x']) && is_array($_POST['entries'])) {
		foreach ($_POST['entries'] as $entry) {
			if ($a_out[$entry])
				unset($a_out[$entry]);
		}
	}

    write_config();
    
    $retval = 0;
    
    if (!file_exists($d_sysrebootreqd_path)) {
		config_lock();
        $retval |= filter_configure();
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

?>
<?php include("fbegin.inc"); ?>
<form action="firewall_nat_out.php" method="post">
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
              <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="info pane">
                <tr> 
                  <td class="vtable">
                      <input name="enable" type="checkbox" id="enable" value="yes" <?php if (isset($config['nat']['advancedoutbound']['enable'])) echo "checked";?>>
                      <strong>Enable advanced outbound NAT</strong></td>
                </tr>
                <tr> 
                  <td> <input name="submit" type="submit" class="formbtn" value="Save"> 
                  </td>
                </tr>
                <tr>
                  <td><p><span class="vexpl"><span class="red"><strong>Note:<br>
                      </strong></span>If advanced outbound NAT is enabled, no outbound NAT
                      rules will be automatically generated anymore. Instead, only the mappings
                      you specify below will be used. With advanced outbound NAT disabled,
                      a mapping is automatically created for each interface's subnet
                      (except WAN) and any mappings specified below will be ignored.</span>
                      If you use target addresses other than the WAN interface's IP address,
                      then depending on<span class="vexpl"> the way your WAN connection is setup,
                      you may also need <a href="services_proxyarp.php">proxy ARP</a>.</span><br>
                      <br>
                      You may enter your own mappings below.</p>
                    </td>
                </tr>
              </table>
              <table width="100%" border="0" cellpadding="0" cellspacing="0" summary="content pane">
                <tr> 
    			  <td width="5%" class="list">&nbsp;</td>
                  <td width="10%" class="listhdrr">Interface</td>
                  <td width="18%" class="listhdrr">Source</td>
                  <td width="19%" class="listhdrr">Destination</td>
                  <td width="18%" class="listhdrr">Target</td>
                  <td width="20%" class="listhdr">Description</td>
                  <td width="10%" class="list"></td>
                </tr>
              <?php $i = 0; foreach ($a_out as $natent): ?>
                <tr valign="top"> 
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
                    <?=$natent['source']['network'];?>
                  </td>
                  <td class="listr"> 
                    <?php
                      if (isset($natent['destination']['any']))
                          echo "*";
                      else {
                          if (isset($natent['destination']['not']))
                              echo "!&nbsp;";
                          echo $natent['destination']['network'];
                      }
                    ?>
                  </td>
                  <td class="listr"> 
                    <?php
                      if (!$natent['target'])
                          echo "*";
                      else
                          echo $natent['target'];
                         
                      if (isset($natent['noportmap']))
                          echo "<br>(no portmap)";
                    ?>
                  </td>
                  <td class="listbg"> 
                    <?=htmlspecialchars($natent['descr']);?>&nbsp;
                  </td>
                  <td class="list" nowrap> <a href="firewall_nat_out_edit.php?id=<?=$i;?>"><img src="e.png" title="edit mapping" width="17" height="17" border="0" alt="edit mapping"></a></td>
                </tr>
              <?php $i++; endforeach; ?>
                <tr> 
                  <td class="list" colspan="6"></td>
                  <td class="list">
					<input name="del" type="image" src="x.png" width="17" height="17" title="delete selected mappings" alt="delete selected mappings" onclick="return confirm('Do you really want to delete the selected mappings?')">
					<a href="firewall_nat_out_edit.php"><img src="plus.png" title="add mapping" width="17" height="17" border="0" alt="add mapping"></a></td>
                </tr>
              </table>
</td>
  </tr>
</table>
            </form>
<?php include("fend.inc"); ?>
