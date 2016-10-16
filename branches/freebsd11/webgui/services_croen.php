#!/usr/local/bin/php
<?php
/*
	$Id: services_croen.php 534 2012-11-24 17:37:21Z lgrahl $
	part of m0n0wall (http://m0n0.ch/wall)
	
	Copyright (C) 2012 Lennart Grahl <lennart.grahl@gmail.com>.
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

	// Title
	$pgtitle = array("Services", "Scheduler");

	// m0n0wall & shared functions
	require_once("guiconfig.inc");
	
	// Default config
	croen_set_default_config();

	// Save
	if (isset($_POST['save']) || isset($_POST['apply'])) {
		unset($input_errors);

		// Validate
		if (!isset($_POST['apply'])) {
			// 1: Input validation (required fields exist)
			$reqdfields = Array('interval');
			$reqdfieldsn = Array('Loop interval');
			do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
			
			// 2: Input validation (field values)
			if (!$input_errors) {
				if (!ctype_digit($_POST['interval']) || (int)$_POST['interval'] < 1 || (int)$_POST['interval'] > 60) {
					$input_errors[] = "The loop interval has to be between 1 and 60";
				}
			}
			
			// 3: Set config variables & write config
			if (!$input_errors) {
				// Config variables
				$config['croen']['interval'] = (int)$_POST['interval'];
				$config['croen']['enable'] = (isset($_POST['enable']) ? TRUE : FALSE);
				
				// Write config
				write_config();
			}
		}
		
		// Restart service & retrieve save message
		if (!$input_errors) {
			$retval = 0;
			if (!file_exists($d_sysrebootreqd_path)) {
				config_lock();
				$retval = services_croen_configure();
				config_unlock();
			}
			$savemsg = get_std_save_message($retval);
			if ($retval == 0) {
				if (file_exists($d_croendirty_path)) {
					unlink($d_croendirty_path);
				}
			}
		}
	}
	
	// Shared vars
	$data = croen_vars(Array('descr', 'repeat', 'date_once', 'date_weekly'));
	// Croen form vars
	$pconfig['enable'] = (isset($config['croen']['enable']) ? TRUE : FALSE);
	$pconfig['interval'] = (isset($config['croen']['interval']) ? $config['croen']['interval'] : 10);
	$pconfig['jobset'] = &$config['croen']['jobset'];

	// Actions
	$config_changed = FALSE;
	foreach ($_POST as $pn => $pv) {
		// Delete job
		if (preg_match("/^del_(\d+)_x$/", $pn, $matches)) {
			unset($pconfig['jobset'][$matches[1]]);
			$config_changed = TRUE;
			
		// Toggle job
		} elseif (preg_match("/^toggle_(\d+)_x$/", $pn, $matches)) {
			if (isset($pconfig['jobset'][$matches[1]])) {
				$pconfig['jobset'][$matches[1]]['disabled'] = !isset($pconfig['jobset'][$matches[1]]['disabled']);
			}
			$config_changed = TRUE;
		}
	}
	
	// Changed someting: Write config, set dirty & reroute
	if ($config_changed) {
		if ($pconfig['enable']) {
			touch($d_croendirty_path);
		}
		write_config();
		header("Location: services_croen.php");
		exit;
	}
	
	// Include webinterface
	include("fbegin.inc");

	// JavaScript to modify forms
	echo '
		<script type="text/javascript">
			<!--
			function enable_change(enable_change) {
				var endis;
				endis = !(document.iform.enable.checked || enable_change);
				document.iform.interval.disabled = endis;
			}
			//-->
		</script>';

	// Show errors (if any)
	if ($input_errors) {
		print_input_errors($input_errors);
	}
	// Show savemsg (if any)
	if ($savemsg) {
		print_info_box($savemsg);
	}

	// Show form
	echo '
		<form action="services_croen.php" method="post" name="iform" id="iform">';

	// Show dirty message (if dirty)
	if (file_exists($d_croendirty_path)) {
		echo '
			<p>';
		print_info_box_np("The scheduler configuration has been changed.<br>You must apply the changes in order for them to take effect.");
		echo '<input name="apply" type="submit" class="formbtn" id="apply" value="Apply changes"></p>';
	}

	echo '
			<table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
				<tr>
					<td width="22%" valign="top" class="vtable">&nbsp;</td>
					<td width="78%" class="vtable">
						<input name="enable" type="checkbox" id="enable" value="yes" onClick="enable_change(false)"'.($pconfig['enable'] ? ' checked' : '').'>
						<strong>Enable scheduler</strong>
					</td>
				</tr>
				<tr>
					<td width="22%" valign="top" class="vncell">Loop interval</td>
					<td width="78%" class="vtable">
						<input name="interval" type="text" class="formfld" id="interval" size="2" value="'.htmlspecialchars($pconfig['interval']).'"> minutes<br>
						The loop interval is used to compensate for sudden changes in time and date. An example would be the switch to daylight saving time/standard time or time correction from the NTP client.<br>
						Default is 10 minutes.
					</td>
				</tr>
				<tr>
					<td width="22%" valign="top">&nbsp;</td>
					<td width="78%">
						<input name="save" type="submit" class="formbtn" value="Save" onClick="enable_change(true)"> 
					</td>
				</tr>
			</table><br>
		</form>
		<form action="services_croen.php" method="post" name="iform2" id="iform2">
			<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tab pane">
				<tr><td class="tabcont">
					<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="content pane">
						<tr>
							<td width="7%" class="list">&nbsp;</td>
							<td width="21%" class="listhdrr">Repeat</td>
							<td width="50%" class="listhdrr">Job(s)</td>
							<td width="17%" class="listhdr">Description</td>
							<td width="5%" class="list">&nbsp;</td>
						</tr>';
		
	// Jobsets
	$arrow = ' <img src="in.png" width="8" height="8" border="0"> ';
	foreach ($pconfig['jobset'] AS $job_id => $jobset) {
		echo '
						<tr>
							<td><input name="toggle_'.$job_id.'" type="image" src="enable'.(isset($jobset['disabled']) ? '_d' : '').'.png" width="11" height="11" title="click to toggle enabled/disabled status">&nbsp;&nbsp;'.(isset($jobset['syslog']) ? '<img src="log'.(isset($jobset['disabled']) ? '_d' : '').'.gif" width="11" height="11" border="0">' : '&nbsp;').'</td>
							<td class="listlr">'.
								($jobset['repeat'] == 'x_minute' ? str_replace("x minute", ($jobset['minute'] > 1 ? $jobset['minute']." minutes" : "minute"), $data['repeat'][$jobset['repeat']]) : $data['repeat'][$jobset['repeat']].',<br>').
								($jobset['repeat'] == 'once' ? date($data['date_once'], strtotime(htmlspecialchars($jobset['date']).' '.htmlspecialchars($jobset['time']))) : 
								($jobset['repeat'] == 'daily' ? htmlspecialchars($jobset['time']) : 
								($jobset['repeat'] == 'weekly' ? $data['date_weekly'][htmlspecialchars($jobset['weekday'])].', '.htmlspecialchars($jobset['time']) : 
								($jobset['repeat'] == 'monthly' ? htmlspecialchars($jobset['day']).
									((int)$jobset['day'] == 1 || (int)$jobset['day'] == 21 ? 'st' : ((int)$jobset['day'] == 2 || (int)$jobset['day'] == 22 ? 'nd' : ((int)$jobset['day'] == 3 || (int)$jobset['day'] == 23 ? 'rd' : 'th')))
								.', '.htmlspecialchars($jobset['time']) : ''))))
							.'</td>
							<td class="listr" style="padding:0;">';

		// Jobs
		$first = TRUE;
		foreach ($jobset['job'] AS $job) {
			echo '
								<div style="padding: 4px 6px;'.(!$first ? ' border-top: 1px solid #999999;' : '').'">';
			$first = FALSE;

			$j = croen_job_exists($job['name'], Array('descr'), (isset($job['target']) ? Array('target' => $job['target']) : Array()));
			if ($j) {
				$jfirst = TRUE;
				foreach ($j['descr']['job'] AS $v) {
					echo (!$jfirst ? $arrow : '').$v;
					$jfirst = FALSE;
				}
				echo '<span class="gray">'.(isset($j['descr']['target']) ? $arrow.$j['descr']['target'] : '').(isset($job['value']) ? $arrow.$job['value'] : '').(isset($j['descr']['input']) ? ' '.$j['descr']['input'] : '').'</span>';
			}

			echo '</div>';
		}

		echo '
							</td>
							<td class="listbg">'.(isset($jobset['descr']) && !empty($jobset['descr']) ? $jobset['descr'] : '&nbsp;').'</td>
							<td nowrap class="list">
								<a href="services_croen_edit.php?id='.$job_id.'"><img src="e.png" title="edit job" width="17" height="17" border="0" alt="edit job"></a>
								<input name="del_'.$job_id.'" type="image" src="x.png" width="17" height="17" title="delete job" alt="delete job" onclick="return confirm(\'Do you really want to delete this job?\')">
							</td>
						</tr>';
	}

	echo '
						<tr> 
							<td class="list" colspan="4"></td>
							<td class="list">
								<a href="services_croen_edit.php"><img src="plus.png" title="add job" width="17" height="17" border="0" alt="add job"></a>
							</td>
						</tr>
					</table>
					<table border="0" cellspacing="0" cellpadding="0" summary="info pane">
						<tr> 
							<td width="16"><img src="enable.png" width="11" height="11" alt=""></td>
							<td>enabled</td>
							<td width="14"></td>
							<td width="16"><img src="log.gif" width="11" height="11" alt=""></td>
							<td>log</td>
						</tr>
						<tr>
							<td colspan="5" height="4"></td>
						</tr>
						<tr>
							<td><img src="enable_d.png" width="11" height="11" alt=""></td>
							<td>disabled</td>
							<td></td>
							<td><img src="log_d.gif" width="11" height="11" alt=""></td>
							<td>log (disabled)</td>
						</tr>
					</table>
				</td></tr>
			</table>
		</form>

		<script type="text/javascript">
			<!--
			enable_change(false);
			//-->
		</script>';

	// m0n0wall
	include("fend.inc");

?>
