#!/usr/local/bin/php
<?php 
/*
	$Id: system_groupmanager.php 
	part of m0n0wall (http://m0n0.ch/wall)

	Copyright (C) 2005 Paul Taylor <paultaylor@winn-dixie.com>.
	All rights reserved. 

	Copyright (C) 2003-2005 Manuel Kasper <mk@neon1.net>.
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

$pgtitle = array("System", "Group manager");

// Returns an array of pages with their descriptions
function getAdminPageList() {
	global $g;
	
    $tmp = Array();

    if ($dir = opendir($g['www_path'])) {
		while($file = readdir($dir)) {
	    	// Make sure the file exists
	    	if($file != "." && $file != ".." && $file[0] != '.') {
	    		// Is this a .php file?
	    		if (fnmatch('*.php',$file)) {
	    			// Read the description out of the file
		    		$contents = file_get_contents($file);
		    		// Looking for a line like:
		    		// $pgtitle = array("System", "Group manager");
		    		$offset = strpos($contents,'$pgtitle');
		    		$titlepos = strpos($contents,'(',$offset);
		    		$titleendpos = strpos($contents,')',$titlepos);
		    		if (($offset > 0) && ($titlepos > 0) && ($titleendpos > 0)) {
		    			// Title found, extract it
		    			$title = str_replace(',',':',str_replace(array('"'),'',substr($contents,++$titlepos,($titleendpos - $titlepos))));
		    			$tmp[$file] = trim($title);
		    		}
		    		else {
		    			$tmp[$file] = '';
		    		}
	    		
	    		}
	        }
		}

        closedir($dir);
        
        // Sets Interfaces:Optional page that didn't read in properly with the above method,
        // and pages that don't have descriptions.
        $tmp['interfaces_opt.php'] = "Interfaces: Optional";
        $tmp['graph.php'] = "Diagnostics: Interface Traffic";
        $tmp['graph_cpu.php'] = "Diagnostics: CPU Utilization";
        $tmp['exec.php'] = "Hidden: Exec";
        $tmp['exec_raw.php'] = "Hidden: Exec Raw";
        $tmp['status.php'] = "Hidden: Detailed Status";
        $tmp['uploadconfig.php'] = "Hidden: Upload Configuration";
        $tmp['index.php'] = "*Landing Page after Login";
        $tmp['system_usermanager.php'] = "*User Password";
        $tmp['diag_logs_settings.php'] = "Diagnostics: Logs: Settings";
        $tmp['diag_logs_vpn.php'] = "Diagnostics: Logs: PPTP VPN";
        $tmp['diag_logs_filter.php'] = "Diagnostics: Logs: Firewall";
        $tmp['diag_logs_portal.php'] = "Diagnostics: Logs: Captive Portal";
        $tmp['diag_logs_dhcp.php'] = "Diagnostics: Logs: DHCP";
        $tmp['diag_logs.php'] = "Diagnostics: Logs: System";
		$tmp['interfaces_secondaries.php'] = "Interfaces: Add Secondary IP addresses";
        
        // Add appropriate descriptions for extensions, if they exist
        if(file_exists("extensions.inc")){
	   	   include("extensions.inc");
		}

        asort($tmp);
        return $tmp;
    }
}

// Get a list of all admin pages & Descriptions
$pages = getAdminPageList();

if (!is_array($config['system']['group'])) {
	$config['system']['group'] = array();
}
admin_groups_sort();
$a_group = &$config['system']['group'];

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];
	
if ($_POST) {

	unset($input_errors);
	
	foreach ($_POST as $pn => $pv) {
		if (preg_match("/^del_(\d+)_x$/", $pn, $matches)) {
			$id = $matches[1];
			if ($a_group[$id]) {
			    $ok_to_delete = true;
			    if (isset($config['system']['user'])) {
		    	    foreach ($config['system']['user'] as $userent) {
		    	    	if ($userent['groupname'] == $a_group[$id]['name']) {
		    				$ok_to_delete = false;
		    				$input_errors[] = "users still exist who are members of this group!";
		    				break;	    
		    	    	}
		    	    }
			    }
		        if ($ok_to_delete) {
		    		unset($a_group[$id]);
			       	write_config();
				    header("Location: system_groupmanager.php");
				    exit;
			    }
			}
		}
	}

	if (!$input_errors) {
		$pconfig = $_POST;

		/* input validation */
		$reqdfields = explode(" ", "groupname");
		$reqdfieldsn = explode(",", "Group Name");
	
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
		if (preg_match("/[^a-zA-Z0-9\.\-_ ]/", $_POST['groupname']))
			$input_errors[] = "The group name contains invalid characters.";
		
		if (!$input_errors && !(isset($id) && $a_group[$id])) {
			/* make sure there are no dupes */
			foreach ($a_group as $group) {
				if ($group['name'] == $_POST['groupname']) {
					$input_errors[] = "Another entry with the same group name already exists.";
					break;
				}
			}
		}
	
		if (!$input_errors) {
	
			if (isset($id) && $a_group[$id])
				$group = $a_group[$id];
		
			$group['name'] = $_POST['groupname'];
			$group['description'] = $_POST['description'];
			unset($group['pages']);
			foreach ($pages as $fname => $title) {
				$identifier = str_replace('.php','',$fname);
				if ($_POST[$identifier] == 'yes') {
					$group['pages'][] = $fname;
				}			
			}		
		
			if (isset($id) && $a_group[$id])
				$a_group[$id] = $group;
			else
				$a_group[] = $group;
		
			write_config();
		
			header("Location: system_groupmanager.php");
			exit;
		}
	}
}

?>
<?php include("fbegin.inc"); ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<script language="JavaScript">
function toggle(source) {
  checkboxes = document.getElementsByTagName('input')
  for(var i=0, n=checkboxes.length;i<n;i++) {
  	if(checkboxes[i].type=='checkbox') {
     checkboxes[i].checked = source.checked;
    }
  }
}
</script>
<form action="system_groupmanager.php" method="post" name="iform" id="iform">
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tab pane">
  <tr><td class="tabnavtbl">
  <ul id="tabnav">
	<?php 
    	$tabs = array('Users' => 'system_usermanager.php',
            		  'Groups' => 'system_groupmanager.php');
		dynamic_tab_menu($tabs);
    ?>     
  </ul>
  </td></tr>    
<tr>
  <td class="tabcont">
<?php
if($_GET['act']=="new" || $_GET['act']=="edit"){
	if($_GET['act']=="edit"){
		if (isset($id) && $a_group[$id]) {
	       $pconfig['name'] = $a_group[$id]['name'];
	       $pconfig['description'] = $a_group[$id]['description'];
	       $pconfig['pages'] = $a_group[$id]['pages'];
        }
	}
?>
          <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="group management pane">
            <tr> 
              <td width="22%" valign="top" class="vncellreq">Group name</td>
              <td width="78%" class="vtable"> 
                <input name="groupname" type="text" class="formfld" id="groupname" size="20" value="<?=htmlspecialchars($pconfig['name']);?>"> 
                </td>
            </tr>
            <tr> 
              <td width="22%" valign="top" class="vncell">Description</td>
              <td width="78%" class="vtable"> 
                <input name="description" type="text" class="formfld" id="description" size="20" value="<?=htmlspecialchars($pconfig['description']);?>">
                <br>
                Group description, for your own information only</td>
            </tr>
            <tr>
			  	<td colspan="4"><br>&nbsp;Select that pages that this group may access.  Members of this group will be able to perform all actions that<br>&nbsp; are possible from each individual web page.  Ensure you set access levels appropriately.<br><br>
			  	<span class="vexpl"><span class="red"><strong>&nbsp;Note: </strong></span>Pages 
          marked with an * are strongly recommended for every group.</span>
			  	</td>
				</tr>
            <tr>
              <td colspan="2">
              <table width="100%" border="0" cellpadding="0" cellspacing="0" summary="page permission pane">
              <tr>
                <td class="listhdrr"><input type="checkbox" onClick="toggle(this)"></td>
                <td class="listhdrr">Page Description</td>
                <td class="listhdr">Filename</td>
              </tr>
              <?php 
              foreach ($pages as $fname => $title) {
              	$identifier = str_replace('.php','',$fname);
              	?>
              	<tr><td class="listlr">
              	<input name="<?=$identifier?>" type="checkbox" id="<?=$identifier?>" value="yes" <?php if (in_array($fname,$pconfig['pages'])) echo "checked"; ?>></td>
              	<td class="listr"><?=$title?></td>
              	<td class="listr"><?=$fname?></td>
              	</tr>
              	<?
              } ?>
              </table>
              </td>
            </tr>
            <tr> 
              <td width="22%" valign="top">&nbsp;</td>
              <td width="78%"> 
                <input name="save" type="submit" class="formbtn" value="Save"> 
		        <?php if (isset($id) && $a_group[$id]): ?>
		        <input name="id" type="hidden" value="<?=htmlspecialchars($id);?>">
		        <?php endif; ?>                
              </td>
            </tr>
          </table>
<?php
} else {
?>
 <table width="100%" border="0" cellpadding="0" cellspacing="0" summary="page permission pane">
    <tr>
       <td width="35%" class="listhdrr">Group name</td>
       <td width="20%" class="listhdrr">Description</td>
       <td width="20%" class="listhdrr">Pages Accessible</td>                  
       <td width="10%" class="list"></td>
	</tr>
	<?php $i = 0; foreach($a_group as $group): ?>
		<tr>
                  <td class="listlr">
                    <?=htmlspecialchars($group['name']); ?>&nbsp;
                  </td>
                  <td class="listr">
                    <?=htmlspecialchars($group['description']);?>&nbsp;
                  </td>
                  <td class="listbg">
                    <?=count($group['pages']);?>&nbsp;
                  </td>
                  <td valign="middle" nowrap class="list"> <a href="system_groupmanager.php?act=edit&amp;id=<?=$i; ?>"><img src="e.png" title="edit group" width="17" height="17" border="0" alt="edit group"></a>
                     &nbsp;<input name="del_<?=$i;?>" type="image" src="x.png" width="17" height="17" title="delete group" alt="delete group" onclick="return confirm('Do you really want to delete this group?')"></td>
		</tr>
	<?php $i++; endforeach; ?>
	    <tr> 
			<td class="list" colspan="3"></td>
			<td class="list"> <a href="system_groupmanager.php?act=new"><img src="plus.png" title="add group" width="17" height="17" border="0" alt="add group"></a></td>
		</tr>
		<tr>
			<td colspan="3">
		      Additional webGUI admin groups can be added here.  Each group can be restricted to specific portions of the webGUI.  Individually select the desired web pages each group may access.  For example, a troubleshooting group could be created which has access only to selected Status and Diagnostics pages.
			</td>
		</tr>
 </table>
<?php } ?>
     
  </td>
  </tr>
  </table>
 </form>
<?php include("fend.inc"); ?>
