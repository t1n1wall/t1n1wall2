#!/usr/local/bin/php
<?php 
/*
	$Id: services_captiveportal_vouchers_edit.php 411 2010-11-12 12:58:55Z mkasper $
	Copyright (C) 2007 Marcel Wiget <mwiget@mac.com>.
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

$pgtitle = array("Services", "Captive portal", "Edit Voucher Rolls");
require("guiconfig.inc");

if (!is_array($config['voucher'])) {
    $config['voucher'] = array();
}

if (!is_array($config['voucher']['roll'])) {
	$config['voucher']['roll'] = array();
}
// captiveportal_users_sort();
$a_roll = &$config['voucher']['roll'];

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_roll[$id]) {
	$pconfig['number'] = $a_roll[$id]['number'];
	$pconfig['count'] = $a_roll[$id]['count'];
	$pconfig['minutes'] = $a_roll[$id]['minutes'];
	$pconfig['comment'] = $a_roll[$id]['comment'];
}

$maxnumber = (1<<$config['voucher']['rollbits']) -1;    // Highest Roll#
$maxcount = (1<<$config['voucher']['ticketbits']) -1;   // Highest Ticket#

if ($_POST) {
	
	unset($input_errors);
	$pconfig = $_POST;

    /* input validation */
    $reqdfields = explode(" ", "number count minutes");
    $reqdfieldsn = explode(",", "Number,Count,minutes");

    do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

    if (!is_numeric($_POST['number']) || $_POST['number'] >= $maxnumber) 
        $input_errors[] = "Roll number must be numeric and less than $maxnumber";

    if (!is_numeric($_POST['count']) || $_POST['count'] < 1 || $_POST['count'] > $maxcount)
        $input_errors[] = "A roll has at least one voucher and less than $maxcount.";

    if (!is_numeric($_POST['minutes']) || $_POST['minutes'] < 1)
        $input_errors[] = "Each voucher must be good for at least 1 minute.";

    if (!$input_errors) {

        if (isset($id) && $a_roll[$id])
            $rollent = $a_roll[$id];

        $rollent['number']  = $_POST['number'];
        $rollent['minutes'] = $_POST['minutes'];
        $rollent['comment'] = $_POST['comment'];

        /* New Roll or modified voucher count: create bitmask */
        voucher_lock();
        if ($_POST['count'] != $rollent['count']) {
            $rollent['count'] = $_POST['count'];
            $len = ($rollent['count']>>3) + 1;   // count / 8 +1
            $rollent['used'] = base64_encode(str_repeat("\000",$len)); // 4 bitmask
            $rollent['active'] = array();
            voucher_write_used_db($rollent['number'], $rollent['used']);
            voucher_write_active_db($rollent['number'], array());   // create empty DB
            voucher_log(LOG_INFO, "All {$rollent['count']} vouchers from Roll {$rollent['number']} marked unused");
        } else {
            // existing roll has been modified but without changing the count
            // read active and used DB from ramdisk and store it in XML config
            $rollent['used'] = base64_encode(voucher_read_used_db($rollent['number']));
            $activent = array();
            $db = array();
            $active_vouchers = voucher_read_active_db($rollent['number'], $rollent['minutes']);
            foreach($active_vouchers as $voucher => $line) {
                list($timestamp, $minutes) = explode(",", $line);
                $activent['voucher'] = $voucher;
                $activent['timestamp'] = $timestamp;
                $activent['minutes'] = $minutes;
                $db[] = $activent;
            }
            $rollent['active'] = $db;
        }
        voucher_unlock();
        if (isset($id) && $a_roll[$id])
            $a_roll[$id] = $rollent;
        else
            $a_roll[] = $rollent;

        write_config();

        header("Location: services_captiveportal_vouchers.php");
        exit;
    }
}

?>
<?php include("fbegin.inc"); ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<form action="services_captiveportal_vouchers_edit.php" method="post" name="iform" id="iform">
  <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
	<tr> 
	  <td width="22%" valign="top" class="vncellreq">Roll#</td>
	  <td width="78%" class="vtable"> 
		<?=$mandfldhtml;?><input name="number" type="text" class="formfld" id="number" size="10" value="<?=htmlspecialchars($pconfig['number']);?>"> 
        <br>
        <span class="vexpl">Enter the Roll# (0..<?=htmlspecialchars($maxnumber);?>) found on top of the generated/printed vouchers.</span>
		</td>
	</tr>
	<tr> 
	  <td width="22%" valign="top" class="vncellreq">Minutes per Ticket</td>
	  <td width="78%" class="vtable"> 
		<?=$mandfldhtml;?><input name="minutes" type="text" class="formfld" id="minutes" size="10" value="<?=htmlspecialchars($pconfig['minutes']);?>"> 
        <br>
        <span class="vexpl">Defines the time in minutes that a user is allowed access. The clock starts ticking the first time a voucher is used for authentication.</span>
	   </td>
	</tr>
	<tr> 
	  <td width="22%" valign="top" class="vncellreq">Count</td>
	  <td width="78%" class="vtable"> 
		<?=$mandfldhtml;?><input name="count" type="text" class="formfld" id="count" size="10" value="<?=htmlspecialchars($pconfig['count']);?>"> 
        <br>
        <span class="vexpl">Enter the number of vouchers (1..<?=htmlspecialchars($maxcount);?>) found on top of the generated/printed vouchers. WARNING: Changing this number for an existing Roll will mark all vouchers as unused again.</span>
		</td>
	</tr>
	<tr> 
	  <td width="22%" valign="top" class="vncell">Comment</td>
	  <td width="78%" class="vtable"> 
		<?=$mandfldhtml;?><input name="comment" type="text" class="formfld" id="comment" size="60" value="<?=htmlspecialchars($pconfig['comment']);?>"> 
        <br>
        <span class="vexpl">Can be used to further identify this roll. Ignored by the system.</span>
		</td>
	</tr>
	<tr> 
	  <td width="22%" valign="top">&nbsp;</td>
	  <td width="78%"> 
		<input name="Submit" type="submit" class="formbtn" value="Save"> 
		<?php if (isset($id) && $a_roll[$id]): ?>
		<input name="id" type="hidden" value="<?=htmlspecialchars($id);?>">
		<?php endif; ?>
	  </td>
	</tr>
  </table>
 </form>
<?php include("fend.inc"); ?>
