#!/usr/local/bin/php
<?php 
/*
	$Id: diag_ipsec_spd.php 521 2012-10-19 15:07:13Z mkasper $
	part of m0n0wall (http://m0n0.ch/wall)
	
	Copyright (C) 2003-2012 Manuel Kasper <mk@neon1.net>.
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

$pgtitle = array("Diagnostics", "IPsec");

require("guiconfig.inc");
?>
<?php include("fbegin.inc"); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php 
    	$tabs = array('SAD' => 'diag_ipsec_sad.php',
            		  'SPD' => 'diag_ipsec_spd.php');
		dynamic_tab_menu($tabs);
?>
  </ul>
  </td></tr>
  <tr> 
    <td class="tabcont">
<?php

/* delete any SPs? */
if (isset($_POST['del_x']) && is_array($_POST['entries'])) {
	$fd = @popen("/usr/local/sbin/setkey -c > /dev/null 2>&1", "w");
	if ($fd) {
		foreach ($_POST['entries'] as $entry) {
			list ($src,$dst,$dir) = split(";", $entry);
			fwrite($fd, "spddelete $src $dst any -P $dir ;\n");
		}
		pclose($fd);
		sleep(1);
	}
}

/* query SAD */
$fd = @popen("/usr/local/sbin/setkey -DP", "r");
$spd = array();
if ($fd) {
	while (!feof($fd)) {
		$line = chop(fgets($fd));
		if (!$line)
			continue;
		if ($line == "No SPD entries.")
			break;
		if ($line[0] != "\t") {
			if (is_array($cursp)) {
				$spi=$cursp['spi'];
				$spd[$spi] = $cursp;
			}
			$cursp = array();
			$linea = explode(" ", $line);
			$cursp['src'] = $linea[0];
			$cursp['dst'] = $linea[1];
			$i = 0;
		} else if (is_array($cursp)) {
			$linea = explode(" ", trim($line));
			if ($i == 1) {
				if ($linea[1] == "none")	/* don't show default anti-lockout rule */
					unset($cursp);
				else
					$cursp['dir'] = $linea[0];
			} else if ($i == 2) {
				$upperspec = explode("/", $linea[0]);
				$cursp['proto'] = $upperspec[0];
				list($cursp['ep_src'], $cursp['ep_dst']) = explode("-", $upperspec[2]);
			} else if ($i == 5) {
				$upperspec = explode("=", $linea[0]);
				$cursp['spi'] = $upperspec[1];
			}
		}
		$i++;
	}
	if (is_array($cursp) && count($cursp))
		$spi=$cursp['spi'];
		$spd[$spi] = $cursp;
	pclose($fd);
}
if (array_filter($spd)):
?>
<form action="diag_ipsec_spd.php" method="post">
            <table width="100%" border="0" cellpadding="0" cellspacing="0" summary="inner content pane">
  <tr>
    			<td class="list">&nbsp;</td>
                <td nowrap class="listhdrr">Source</td>
                <td nowrap class="listhdrr">Destination</td>
                <td nowrap class="listhdrr">Direction</td>
                <td nowrap class="listhdrr">Protocol</td>
                <td nowrap class="listhdrr">Tunnel endpoints</td>
                <td nowrap class="list"></td>
	</tr>
<?php
ksort($spd);
foreach ($spd as $sp): ?>
	<tr>
		<?php
			$args = htmlspecialchars($sp['src'] . ";" . $sp['dst'] . ";" . $sp['dir']);
		?>
		<td class="listt"><input type="checkbox" name="entries[]" value="<?=$args;?>" style="margin: 0 5px 0 0; padding: 0; width: 15px; height: 15px;"></td>
		<td class="listlr" valign="top"><?=htmlspecialchars($sp['src']);?></td>
		<td class="listr" valign="top"><?=htmlspecialchars($sp['dst']);?></td>
		<td class="listr" valign="top"><img src="<?=$sp['dir'];?>.png" width="11" height="11" style="margin-top: 2px"></td>
		<td class="listr" valign="top"><?=htmlspecialchars(strtoupper($sp['proto']));?></td>
		<td class="listr" valign="top"><?=htmlspecialchars($sp['ep_src']);?> - <br>
			<?=htmlspecialchars($sp['ep_dst']);?></td>
		<td class="list" nowrap>
		</td>
				
	</tr>
<?php endforeach; ?>
	 <tr> 
	   <td></td>
	 </tr> 
	 <tr> 
	   <td class="list" colspan="6"></td>
	   <td class="list"><input name="del" type="image" src="x.png" width="17" height="17" title="delete selected SPs" alt="delete selected SPs" onclick="return confirm('Do you really want to delete the selected security policies?')"></td>
	 </tr>
</table>
<br>
<table border="0" cellspacing="0" cellpadding="0" summary="inout content pane">
  <tr> 
	<td width="16"><img src="in.png" width="11" height="11" alt=""></td>
	<td>incoming (as seen by firewall)</td>
  </tr>
  <tr> 
	<td colspan="5" height="4"></td>
  </tr>
  <tr> 
	<td><img src="out.png" width="11" height="11" alt=""></td>
	<td>outgoing (as seen by firewall)</td>
  </tr>
</table>
</form>
<?php else: ?>
<p><strong>No IPsec security policies.</strong></p>
<?php endif; ?>
</td></tr></table>
<?php include("fend.inc"); ?>
