#!/usr/local/bin/php
<?php 
/*
    $Id: status_captiveportal.php 529 2012-11-01 13:57:57Z mkasper $
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

$pgtitle = array("Status", "Captive portal");
require("guiconfig.inc");
?>
<?php include("fbegin.inc"); ?>
<?php

if ($_POST) {
	foreach ($_POST as $pn => $pv) {
		if (preg_match("/^del_(.+)_x$/", $pn, $matches)) {
			$id = $matches[1];
	    	captiveportal_disconnect_client($id, 6);
		}
	}
}

flush();

function clientcmp($a, $b) {
    global $order;
    return strcmp($a[$order], $b[$order]);
}

$cpdb = array();
captiveportal_lock();
$fp = @fopen("{$g['vardb_path']}/captiveportal.db","r");

if ($fp) {
    while (!feof($fp)) {
        $line = trim(fgets($fp));
        if ($line) {
            $cpent = explode(",", $line);
            $volume = getVolume($cpent[1]);
            $cpent[7] = $volume['output_bytes'];
            $cpent[8] = $volume['input_bytes'];
            if ($_GET['showact'])
                $cpent[9] = captiveportal_get_last_activity($cpent[1]);
            $cpdb[] = $cpent;
        }
    }

    fclose($fp);

    if ($_GET['order']) {
        if ($_GET['order'] == "ip")
            $order = 2;
        else if ($_GET['order'] == "mac")
            $order = 3;
        else if ($_GET['order'] == "user")
            $order = 4;
        else if ($_GET['order'] == "download")
            $order = 7;
        else if ($_GET['order'] == "upload")
            $order = 8;
        else if ($_GET['order'] == "lastact")
            $order = 9;
        else
            $order = 0;
        usort($cpdb, "clientcmp");
    }
}
captiveportal_unlock();
?>

<form action="status_captiveportal.php" method="post" enctype="multipart/form-data" name="iform" id="iform">

<?php if (isset($config['voucher']['enable'])): ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tab pane">
<tr><td class="tabnavtbl">
<ul id="tabnav">
<?php 
$tabs = array('Users' => 'status_captiveportal.php',
        'Active Vouchers' => 'status_captiveportal_vouchers.php',
        'Voucher Rolls' => 'status_captiveportal_voucher_rolls.php',
        'Test Vouchers' => 'status_captiveportal_test.php');
    dynamic_tab_menu($tabs);
?> 
</ul>
</td></tr>
<tr>
<td class="tabcont">
<?php endif; ?>

<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="content pane">
  <tr>
    <td class="listhdrr"><a href="?order=ip&amp;showact=<?=htmlspecialchars($_GET['showact']);?>">IP address</a></td>
    <td class="listhdrr"><a href="?order=mac&amp;showact=<?=htmlspecialchars($_GET['showact']);?>">MAC address</a></td>
    <td class="listhdrr"><a href="?order=start&amp;showact=<?=htmlspecialchars($_GET['showact']);?>">Session start</a></td>
    <td class="listhdrr"><a href="?order=download&amp;showact=<?=htmlspecialchars($_GET['showact']);?>">Download</a></td>
    <td class="listhdrr"><a href="?order=upload&amp;showact=<?=htmlspecialchars($_GET['showact']);?>">Upload</a></td>
    <?php if ($_GET['showact']): ?>
    <td class="listhdrr"><a href="?order=lastact&amp;showact=<?=htmlspecialchars($_GET['showact']);?>">Last activity</a></td>
    <?php endif; ?>
    <td class="listhdr"><a href="?order=user&amp;showact=<?=htmlspecialchars($_GET['showact']);?>">Username</a></td>
    <td class="list"></td>
  </tr>
<?php foreach ($cpdb as $cpent): ?>
  <tr>
    <td class="listlr"><?=$cpent[2];?></td>
    <td class="listr"><?=$cpent[3];?>&nbsp;</td>
    <td class="listr"><?=htmlspecialchars(date("m/d/Y H:i:s", $cpent[0]));?></td>
    <td class="listr"><?=format_bytes($cpent[7]);?></td>
    <td class="listr"><?=format_bytes($cpent[8]);?></td>
    <?php if ($_GET['showact']): ?>
    <td class="listr"><?php if ($cpent[9]) echo htmlspecialchars(date("m/d/Y H:i:s", $cpent[9]));?></td>
    <?php endif; ?>
    <td class="listr"><?=$cpent[4];?>&nbsp;</td>
    <td valign="middle" class="list" nowrap>
	<input name="del_<?=$cpent[1];?>" type="image" src="x.png" width="17" height="17" title="disconnect client" alt="disconnect client" onclick="return confirm('Do you really want to disconnect this client?')">
  </tr>
<?php endforeach; ?>
</table>

<?php if (isset($config['voucher']['enable'])): ?>
</td>
</tr>
</table>
<?php endif; ?>
</form>

<p><!-- TODO: paragraph is not valid here -->
<form action="status_captiveportal.php" method="GET">
<input type="hidden" name="order" value="<?=htmlspecialchars($_GET['order']);?>">
<?php if ($_GET['showact']): ?>
<input type="hidden" name="showact" value="0">
<input type="submit" class="formbtn" value="Don't show last activity">
<?php else: ?>
<input type="hidden" name="showact" value="1">
<input type="submit" class="formbtn" value="Show last activity">
<?php endif; ?>
</form>
</p>
<?php include("fend.inc"); ?>
