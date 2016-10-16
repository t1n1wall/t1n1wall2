#!/usr/local/bin/php
<?php 
/*
	$Id: system_routes_edit.php 411 2010-11-12 12:58:55Z mkasper $
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

if ($ipv6routes = ($_GET['type'] == 'ipv6')) {
	$configname = 'route6';
	$typelink = '&type=ipv6';
	$maxnetmask = 128;
} else {
	$configname = 'route';
	$typelink = '';
	$maxnetmask = 32;
}
$pgtitle = array("System", "Static routes", "Edit");	/* make group manager happy */
$pgtitle = array("System", ipv6enabled() ? ($ipv6routes ? 'IPv6 Static routes' : 'IPv4 Static routes') : 'Static routes', "Edit");

if (!is_array($config['staticroutes'][$configname]))
	$config['staticroutes'][$configname] = array();

staticroutes_sort();
$a_routes = &$config['staticroutes'][$configname];

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_routes[$id]) {
	$pconfig['interface'] = $a_routes[$id]['interface'];
	list($pconfig['network'],$pconfig['network_subnet']) = 
		explode('/', $a_routes[$id]['network']);
	$pconfig['gateway'] = $a_routes[$id]['gateway'];
	$pconfig['descr'] = $a_routes[$id]['descr'];
}

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "interface network network_subnet gateway");
	$reqdfieldsn = explode(",", "Interface,Destination network,Destination network bit count,Gateway");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
	if (($_POST['network'] && !($ipv6routes ? is_ipaddr6($_POST['network']) : is_ipaddr($_POST['network'])))) {
		$input_errors[] = "A valid destination network must be specified.";
	}
	if (($_POST['network_subnet'] && !is_numeric($_POST['network_subnet']))) {
		$input_errors[] = "A valid destination network bit count must be specified.";
	}
	if (($_POST['gateway'] && !($ipv6routes ? is_ipaddr6($_POST['gateway']) : is_ipaddr($_POST['gateway'])))) {
		$input_errors[] = "A valid gateway IP address must be specified.";
	}

	/* check for overlaps */
	if ($ipv6routes)
	    $osn = gen_subnet6($_POST['network'], $_POST['network_subnet']) . "/" . $_POST['network_subnet'];
	else
	    $osn = gen_subnet($_POST['network'], $_POST['network_subnet']) . "/" . $_POST['network_subnet'];
	
	foreach ($a_routes as $route) {
		if (isset($id) && ($a_routes[$id]) && ($a_routes[$id] === $route))
			continue;

		if ($route['network'] == $osn) {
			$input_errors[] = "A route to this destination network already exists.";
			break;
		}
	}

	if (!$input_errors) {
		$route = array();
		$route['interface'] = $_POST['interface'];
		$route['network'] = $osn;
		$route['gateway'] = $_POST['gateway'];
		$route['descr'] = $_POST['descr'];

		if (isset($id) && $a_routes[$id])
			$a_routes[$id] = $route;
		else
			$a_routes[] = $route;
		
		touch($d_staticroutesdirty_path);
		
		write_config();
		
		header("Location: system_routes.php?{$typelink}");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>
            <form action="system_routes_edit.php?<?=$typelink?>" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">Interface</td>
                  <td width="78%" class="vtable">
					<select name="interface" class="formfld">
                      <?php $interfaces = array('lan' => 'LAN', 'wan' => 'WAN');
					  if (!$ipv6routes)
					      $interfaces['pptp'] = "PPTP";
					  for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
					  	$interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
					  }
					  foreach ($interfaces as $iface => $ifacename): ?>
                      <option value="<?=$iface;?>" <?php if ($iface == $pconfig['interface']) echo "selected"; ?>> 
                      <?=htmlspecialchars($ifacename);?>
                      </option>
                      <?php endforeach; ?>
                    </select> <br>
                    <span class="vexpl">Choose which interface this route applies to.</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Destination network</td>
                  <td width="78%" class="vtable"> 
                    <?=$mandfldhtml;?><input name="network" type="text" class="formfld" id="network" size="20" value="<?=htmlspecialchars($pconfig['network']);?>"> 
				  / 
                    <select name="network_subnet" class="formfld" id="network_subnet">
                      <?php for ($i = $maxnetmask; $i >= 1; $i--): ?>
                      <option value="<?=$i;?>" <?php if ($i == $pconfig['network_subnet']) echo "selected"; ?>>
                      <?=$i;?>
                      </option>
                      <?php endfor; ?>
                    </select>
                    <br> <span class="vexpl">Destination network for this static route</span></td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncellreq">Gateway</td>
                  <td width="78%" class="vtable"> 
                    <?=$mandfldhtml;?><input name="gateway" type="text" class="formfld" id="gateway" size="40" value="<?=htmlspecialchars($pconfig['gateway']);?>">
                    <br> <span class="vexpl">Gateway to be used to reach the destination network</span></td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncell">Description</td>
                  <td width="78%" class="vtable"> 
                    <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>">
                    <br> <span class="vexpl">You may enter a description here
                    for your reference (not parsed).</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="Save">
                    <?php if (isset($id) && $a_routes[$id]): ?>
                    <input name="id" type="hidden" value="<?=htmlspecialchars($id);?>">
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
</form>
<?php include("fend.inc"); ?>
