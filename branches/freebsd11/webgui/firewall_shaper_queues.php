#!/usr/local/bin/php
<?php 
/*
	$Id: firewall_shaper_queues.php 521 2012-10-19 15:07:13Z mkasper $
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

$pgtitle = array("Firewall", "Traffic shaper", "Queues");
require("guiconfig.inc");

if (!is_array($config['shaper']['pipe'])) {
	$config['shaper']['pipe'] = array();
}
if (!is_array($config['shaper']['queue'])) {
	$config['shaper']['queue'] = array();
}
$a_queues = &$config['shaper']['queue'];
$a_pipe = &$config['shaper']['pipe'];

if (isset($_POST['del_x']) && is_array($_POST['entries'])) {
	foreach ($_POST['entries'] as $entry) {
		if ($a_queues[$entry]) {
			/* check that no rule references this queue */
			if (is_array($config['shaper']['rule'])) {
				foreach ($config['shaper']['rule'] as $rule) {
					if (isset($rule['targetqueue']) && ($rule['targetqueue'] == $entry)) {
						$input_errors[] = "This queue cannot be deleted because it is still referenced by a rule.";
						break;
					}
				}
			}
		}
	}
	
	if (!$input_errors) {
		foreach ($_POST['entries'] as $entry) {
			// Scheduler: delete matching jobs
			croen_update_job('shaper-set_queue_weight', $a_queues[$entry]['descr']);
			unset($a_queues[$entry]);
	
			/* renumber all rules */
			if (is_array($config['shaper']['rule'])) {
				for ($i = 0; isset($config['shaper']['rule'][$i]); $i++) {
					$currule = &$config['shaper']['rule'][$i];
					if (isset($currule['targetqueue']) && ($currule['targetqueue'] > $entry))
						$currule['targetqueue']--;
				}
			}
		}
	
		write_config();
		touch($d_shaperconfdirty_path);
		header("Location: firewall_shaper_queues.php");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<form action="firewall_shaper.php" method="post">
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_shaperconfdirty_path)): ?><p>
<?php print_info_box_np("The traffic shaper configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
<input name="apply" type="submit" class="formbtn" id="apply" value="Apply changes"></p>
<?php endif; ?>
</form>
<form action="firewall_shaper_queues.php" method="post">
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tab pane">
  <tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php 
   	$tabs = array('Rules' => 'firewall_shaper.php',
           		  'Pipes' => 'firewall_shaper_pipes.php',
           		  'Queues' => 'firewall_shaper_queues.php',
           		  'Magic shaper wizard' => 'firewall_shaper_magic.php');
	dynamic_tab_menu($tabs);
?>       
  </ul>
  </td></tr>
  <tr> 
    <td class="tabcont">
              <table width="100%" border="0" cellpadding="0" cellspacing="0" summary="content pane">
                      <tr> 
    			  		<td width="5%" class="list">&nbsp;</td>
                        <td width="10%" class="listhdrr">No.</td>
                        <td width="20%" class="listhdrr">Pipe</td>
                        <td width="5%" class="listhdrr">Weight</td>
                        <td width="20%" class="listhdrr">Mask</td>
                        <td width="30%" class="listhdr">Description</td>
                        <td width="10%" class="list"></td>
                      </tr>
                      <?php $i = 0; foreach ($a_queues as $queue): ?>
                      <tr valign="top"> 
				  		<td class="listt"><input type="checkbox" name="entries[]" value="<?=$i;?>" style="margin: 0 5px 0 0; padding: 0; width: 15px; height: 15px;"></td>
                        <td class="listlr"> 
                          <?=($i+1);?></td>
                        <td class="listr"> 
							<?php
							if ($a_pipe[$queue['targetpipe']]['descr'])
								$desc = htmlspecialchars($a_pipe[$queue['targetpipe']]['descr']);
							else 
								$desc = "Pipe " . ($queue['targetpipe']+1);
							?>	
                          <a href="firewall_shaper_pipes_edit.php?id=<?=$queue['targetpipe'];?>"><?=$desc;?></a></td>
                        <td class="listr"> 
                          <?=$queue['weight'];?></td>
                        <td class="listr"> 
                          <?php if ($queue['mask']): ?>
                          <?=$queue['mask'];?>
                          <?php endif; ?>
                          &nbsp; </td>
                        <td class="listbg"> 
                          <?=htmlspecialchars($queue['descr']);?>
                          &nbsp; </td>
                        <td valign="middle" nowrap class="list"> <a href="firewall_shaper_queues_edit.php?id=<?=$i;?>"><img src="e.png" title="edit queue" width="17" height="17" border="0" alt="edit queue"></a></td>
                      </tr>
                      <?php $i++; endforeach; ?>
                      <tr> 
                        <td class="list" colspan="6"></td>
                        <td class="list">
							<input name="del" type="image" src="x.png" width="17" height="17" title="delete selected queues" alt="delete selected queues" onclick="return confirm('Do you really want to delete the selected queues?')">
							<a href="firewall_shaper_queues_edit.php"><img src="plus.png" title="add queue" width="17" height="17" border="0" alt="add queue"></a></td>
                      </tr>
                    </table><br>
                    <strong><span class="red">Note:</span></strong> a queue can 
                    only be deleted if it is not referenced by any rules.</td>
	</tr>
</table>
            </form>
<?php include("fend.inc"); ?>
