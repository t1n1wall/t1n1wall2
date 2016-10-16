#!/usr/local/bin/php
<?php 
/*
	$Id: diag_backup.php 238 2008-01-21 18:33:33Z mkasper $
	part of t1n1wall (http://m0n0.ch/wall)
	
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

$pgtitle = array("Diagnostics", "Backup/restore");

/* omit no-cache headers because it confuses IE with file downloads */
$omit_nocacheheaders = true;
require("guiconfig.inc"); 

if ($_POST) {

	unset($input_errors);
	
	if (stristr($_POST['Submit'], "Restore"))
		$mode = "restore";
	else if (stristr($_POST['Submit'], "Download"))
		$mode = "download";
		
	if ($mode) {
		if ($mode == "download") {
			config_lock();
			
			$fn = "config-" . $config['system']['hostname'] . "." . 
				$config['system']['domain'] . "-" . date("YmdHis") . ".xml";
			
			$fs = filesize($g['conf_path'] . "/config.xml");
			header("Content-Type: application/octet-stream"); 
			header("Content-Disposition: attachment; filename=$fn");
			header("Content-Length: $fs");
			readfile($g['conf_path'] . "/config.xml");
			config_unlock();
			exit;
		} else if ($mode == "restore") {
			if (is_uploaded_file($_FILES['conffile']['tmp_name'])) {
				if (config_install($_FILES['conffile']['tmp_name']) == 0) {
					system_reboot();
					$savemsg = "The configuration has been restored. The firewall is now rebooting.";
				} else {
					$errstr = "The configuration could not be restored.";
					if ($xmlerr)
						$errstr .= " (XML error: $xmlerr)";
					$input_errors[] = $errstr;
				}
			} else {
				$input_errors[] = "The configuration could not be restored (file upload error).";
			}
		}
	}
}
?>
<?php include("fbegin.inc"); ?>
            <form action="diag_backup.php" method="post" enctype="multipart/form-data">
            <?php if ($input_errors) print_input_errors($input_errors); ?>
            <?php if ($savemsg) print_info_box($savemsg); ?>
              <table width="100%" border="0" cellspacing="0" cellpadding="6" summary="inner content pane">
                <tr> 
                  <td colspan="2" class="listtopic">Backup configuration</td>
                </tr>
                <tr> 
                  <td width="22%" valign="baseline" class="vncell">&nbsp;</td>
                  <td width="78%" class="vtable"> 
                    Click this button to download the system configuration 
                      in XML format.<br>
                      <br>
                      <input name="Submit" type="submit" class="formbtn" id="download" value="Download configuration"></td>
                </tr>
                <tr> 
                  <td colspan="2" class="list" height="12"></td>
                </tr>
                <tr> 
                  <td colspan="2" class="listtopic">Restore configuration</td>
                </tr>
                <tr> 
                  <td width="22%" valign="baseline" class="vncell">&nbsp;</td>
                  <td width="78%" class="vtable"> 
                    Open a t1n1wall configuration XML file and click the button 
                      below to restore the configuration.<br>
                      <br>
                      <strong><span class="red">Note:</span></strong><br>
                      The firewall will reboot after restoring the configuration.<br>
                      <br>
                      <input name="conffile" type="file" class="formfld" id="conffile" size="40">
                      <br>
                      <br>
                      <input name="Submit" type="submit" class="formbtn" id="restore" value="Restore configuration">
                  </td>
                </tr>
              </table>
            </form>
<?php include("fend.inc"); ?>
