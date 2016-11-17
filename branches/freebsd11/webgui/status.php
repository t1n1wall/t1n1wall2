#!/usr/local/bin/php
<?php
/*
	$Id: status.php 495 2012-02-11 20:43:57Z mkasper $
	part of m0n0wall (http://m0n0.ch/wall)
	
	Copyright (C) 2003-2006 Jim McBeath <jimmc@macrovision.com> and Manuel Kasper <mk@neon1.net>.
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

/* Execute a command, with a title, and generate an HTML table
 * showing the results.
 */

function doCmdT($title, $command, $isstr) {
    echo "<p>\n";
    echo "<!-- TODO: Block elements like table are not allowed inside of an anchor --><a name=\"" . $title . "\">\n";
    echo "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
    echo "<tr><td class=\"listtopic\">" . $title . "</td></tr>\n";
    echo "<tr><td class=\"listlr\"><pre>";		/* no newline after pre */
	
	if ($isstr) {
		echo htmlspecialchars($command);
	} else {
		if ($command == "dumpconfigxml") {
			$fd = @fopen("/conf/config.xml", "r");
			if ($fd) {
				while (!feof($fd)) {
					$line = fgets($fd);
					/* remove password tag contents */
					$line = preg_replace("/<password>.*?<\\/password>/", "<password>xxxxx</password>", $line);
					$line = preg_replace("/<pre-shared-key>.*?<\\/pre-shared-key>/", "<pre-shared-key>xxxxx</pre-shared-key>", $line);
					$line = str_replace("\t", "    ", $line);
					echo htmlspecialchars($line,ENT_NOQUOTES);
				}
			}
			fclose($fd);
		} else {
			exec ($command . " 2>&1", $execOutput, $execStatus);
			for ($i = 0; isset($execOutput[$i]); $i++) {
				if ($i > 0) {
					echo "\n";
				}
				echo htmlspecialchars($execOutput[$i],ENT_NOQUOTES);
			}
		}
	}
    echo "</pre></tr>\n";
    echo "</table>\n";
}

/* Execute a command, giving it a title which is the same as the command. */
function doCmd($command) {
    doCmdT($command,$command);
}

/* Define a command, with a title, to be executed later. */
function defCmdT($title, $command) {
    global $commands;
    $title = htmlspecialchars($title,ENT_NOQUOTES);
    $commands[] = array($title, $command, false);
}

/* Define a command, with a title which is the same as the command,
 * to be executed later.
 */
function defCmd($command) {
    defCmdT($command,$command);
}

/* Define a string, with a title, to be shown later. */
function defStrT($title, $str) {
    global $commands;
    $title = htmlspecialchars($title,ENT_NOQUOTES);
    $commands[] = array($title, $str, true);
}

/* List all of the commands as an index. */
function listCmds() {
    global $commands;
    echo "<p>This status page includes the following information:\n";
    echo "<ul>\n";
    for ($i = 0; isset($commands[$i]); $i++ ) {
        echo "<li><strong><a href=\"#" . $commands[$i][0] . "\">" . $commands[$i][0] . "</a></strong>\n";
    }
    echo "</ul>\n";
}

/* Execute all of the commands which were defined by a call to defCmd. */
function execCmds() {
    global $commands;
    for ($i = 0; isset($commands[$i]); $i++ ) {
        doCmdT($commands[$i][0], $commands[$i][1], $commands[$i][2]);
    }
}

/* Set up all of the commands we want to execute. */
defCmdT("System uptime","uptime");
defCmdT("Interfaces","/sbin/ifconfig -a");

defCmdT("Routing tables","/usr/bin/netstat -nr");

defCmdT("Network buffers", "/usr/bin/netstat -m");
defCmdT("Network protocol statistics", "/usr/bin/netstat -s");

defCmdT("Kernel parameters", "/sbin/sysctl -a");
defCmdT("Kernel modules loaded", "/sbin/kldstat");

defCmdT("ipfw show", "/sbin/ipfw show");
defCmdT("pfctl -s nat", "/sbin/pfctl -s nat");
defCmdT("pfctl -v -s nat", "/sbin/pfctl -v -s nat");
defCmdT("pfctl -s info", "/sbin/pfctl -s info");
defCmdT("pfctl -s Tables", "/sbin/pfctl -s Tables");
defCmdT("pfctl -s rules", "/sbin/pfctl -s rules");

defStrT("unparsed nat rules", filter_nat_rules_generate());
defStrT("unparsed table definitions", filter_pools_generate());
defStrT("unparsed pf rules", filter_rules_generate());
defStrT("unparsed ipfw rules", shaper_rules_generate());

defCmdT("resolv.conf","cat /etc/resolv.conf");

defCmdT("Processes","ps xauww");
defCmdT("dnsmasq/dhcpd.conf","cat /var/etc/dnsmasq/dhcpd.conf");
defCmdT("ez-ipupdate.cache","cat /conf/ez-ipupdate.cache");
if (ipv6enabled())
	defCmdT("rtadvd.conf","cat /var/etc/rtadvd.conf");

defCmdT("df","/bin/df");

defCmdT("racoon.conf","cat /var/etc/racoon.conf");
defCmdT("SPD","/usr/local/sbin/setkey -DP");
defCmdT("SAD","/usr/local/sbin/setkey -D");

defCmdT("last 200 system log entries","/usr/sbin/clog /var/log/system.log 2>&1 | tail -n 200");
defCmdT("last 50 filter log entries","/usr/sbin/clog /var/log/filter.log 2>&1 | tail -n 50");

defCmd("ls /conf");
defCmd("ls /var/run");
defCmdT("config.xml","dumpconfigxml");

$pageTitle = "t1n1wall: status";

exec("/bin/date", $dateOutput, $dateStatus);
$currentDate = $dateOutput[0];

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?=$pageTitle;?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="gui.css" rel="stylesheet" type="text/css">
<style type="text/css">
<!--
pre {
   margin: 0px;
   font-family: courier new, courier;
   font-weight: normal;
   font-size: 9pt;
}
-->
</style>
</head>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<p><span class="pgtitle"><?=$pageTitle;?></span><br>
<strong><?=$currentDate;?></strong>
<p><span class="red"><strong>Note: make sure to remove any sensitive information 
(passwords, maybe also IP addresses) before posting 
information from this page in public places (like mailing lists)!</strong></span><br>
Passwords in config.xml have been automatically removed.

<?php listCmds(); ?>

<?php execCmds(); ?>

</body>
</html>
