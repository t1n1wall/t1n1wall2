#!/usr/local/bin/php
<?php
/*
	$Id: license.php 560 2014-01-14 16:11:20Z mkasper $
	part of t1n1wall (http://t1n1wall.com)
	
	Copyright (C) 2003-2008 Manuel Kasper <mk@neon1.net>.
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

$pgtitle = array("License");
require("guiconfig.inc"); 
?>
<?php include("fbegin.inc"); ?>
            <p><strong>t1n1wall&reg; is Copyright &copy; 2015 by Andrew White 
              (<a href="mailto:mk@neon1.net">andywhite@t1n1wall.com</a>).<br>
              All rights reserved.</strong></p>
            <p><strong>t1n1wall&reg; is a fork of m0n0wall&reg 
              </strong></p>
            <p><strong>m0n0wall&reg; is Copyright &copy; 2002-2015 by Manuel Kasper 
              (<a href="mailto:mk@neon1.net">mk@neon1.net</a>).<br>
              All rights reserved.</strong></p>
            <p> Redistribution and use in source and binary forms, with or without<br>
              modification, are permitted provided that the following conditions 
              are met:<br>
              <br>
              1. Redistributions of source code must retain the above copyright 
              notice,<br>
              this list of conditions and the following disclaimer.<br>
              <br>
              2. Redistributions in binary form must reproduce the above copyright<br>
              notice, this list of conditions and the following disclaimer in 
              the<br>
              documentation and/or other materials provided with the distribution.<br>
              <br>
              <strong>THIS SOFTWARE IS PROVIDED &quot;AS IS'' AND ANY EXPRESS 
              OR IMPLIED WARRANTIES,<br>
              INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY<br>
              AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT 
              SHALL THE<br>
              AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, 
              EXEMPLARY,<br>
              OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT 
              OF<br>
              SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR 
              BUSINESS<br>
              INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER 
              IN<br>
              CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)<br>
              ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED 
              OF THE<br>
              POSSIBILITY OF SUCH DAMAGE</strong>.</p>
            <p>m0n0wall is a registered trademark of Manuel Kasper.</p>
            <hr size="1">
            <p>The following persons have contributed code to m0n0wall, and therefore t1n1wall:</p>
            <p>Manuel Kasper (<a href="mailto:mk@neon1.net">mk@neon1.net</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666"> m0n0wall itself !
              And thats too big a deal to list everything here.</font></em><br>
              <br>
            <p>Bob Zoller (<a href="mailto:bob@kludgebox.com">bob@kludgebox.com</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">Diagnostics: Ping 
              function; WLAN channel auto-select; DNS forwarder</font></em><br>
              <br>
              Michael Mee (<a href="mailto:mikemee2002@pobox.com">mikemee2002@pobox.com</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">Timezone and NTP 
              client support</font></em><br>
              <br>
              Magne Andreassen (<a href="mailto:magne.andreassen@bluezone.no">magne.andreassen@bluezone.no</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">Remote syslog'ing; 
              some code bits for DHCP server on optional interfaces</font></em><br>
              <br>
              Rob Whyte (<a href="mailto:rob@g-labs.com">rob@g-labs.com</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">Idea/code bits 
              for encrypted webGUI passwords; minimalized SNMP agent</font></em><br>
              <br>
              Petr Verner (<a href="mailto:verner@ipps.cz">verner@ipps.cz</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">Advanced outbound 
              NAT: destination selection</font></em><br>
              <br>
              Bruce A. Mah (<a href="mailto:bmah@acm.org">bmah@acm.org</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">Filtering bridge 
              patches </font></em><br>
              <br>
              Jim McBeath (<a href="mailto:monowall@j.jimmc.org">monowall@j.jimmc.org</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">Filter rule patches 
              (ordering, block/pass, disabled); better status page;</font></em><br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">webGUI assign network ports page</font></em><br>
              <br>
              Chris Olive (<a href="mailto:chris@technologEase.com">chris@technologEase.com</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">enhanced &quot;execute 
              command&quot; page</font></em><br>
              <br>
              Pauline Middelink (<a href="mailto:middelink@polyware.nl">middelink@polyware.nl</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">DHCP client: send hostname patch</font></em><br>
              <br>
              Bj�rn P�lsson (<a href="mailto:bjorn@networksab.com">bjorn@networksab.com</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">DHCP lease list page</font></em><br>
              <br>
              Peter Allgeyer (<a href="mailto:allgeyer@web.de">allgeyer@web.de</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">&quot;reject&quot; type filter rules; dial-on-demand; WAN connect/disconnect; auto-add proxy ARP </font></em><br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">firewall log filtering; DynDNS server/port; Diag: ARP improvements</font></em><br>
              <br>
              Thierry Lechat (<a href="mailto:dev@lechat.org">dev@lechat.org</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">SVG-based traffic grapher</font></em><br>
              <br>
              Steven Honson (<a href="mailto:steven@honson.org">steven@honson.org</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">per-user IP address assignments for PPTP VPN</font></em><br>
              <br>
              Kurt Inge Sm�dal (<a href="mailto:kurt@emsp.no">kurt@emsp.no</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">NAT on optional interfaces</font></em><br>
              <br>
              Dinesh Nair (<a href="mailto:dinesh@alphaque.com">dinesh@alphaque.com</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">captive portal: pass-through MAC/IP addresses, RADIUS authentication &amp; accounting;</font></em><br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">HTTP server concurrency limit</font></em><br>
              <br>
              Justin Ellison (<a href="mailto:justin@techadvise.com">justin@techadvise.com</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">traffic shaper TOS matching; magic shaper; DHCP deny unknown clients;<br>
			  &nbsp;&nbsp;&nbsp;&nbsp;IPsec user FQDNs; DHCP relay</font></em><br>
			  <br>
              Fred Wright (<a href="mailto:fw@well.com">fw@well.com</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">ipfilter window scaling fix; ipnat ICMP checksum adjustment fix; IPsec dead SA fixes;</font></em><br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">netgraph PPP PFC fixes; kernel build improvements;</font></em><br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">updated DP83815 short cable bug workaround</font></em><br>
			  <br>
              Michael Hanselmann (<a href="mailto:m0n0@hansmi.ch">m0n0@hansmi.ch</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">IDE hard disk standby; exec.php arrow keys</font></em><br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">IPv6 support</font></em><br>
			  <br>
              Audun Larsen (<a href="mailto:larsen@xqus.com">larsen@xqus.com</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">CPU/memory usage display</font></em><br>
			  <br>
              Pavel A. Grodek (<a href="mailto:pg@abletools.com">pg@abletools.com</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">Traffic shaper packet loss rate/queue size</font></em><br>
			  <br>
              Pascal Suter (<a href="mailto:d-monodev@psuter.ch">d-monodev@psuter.ch</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">Captive portal local user database</font></em><br>
			  <br>
              Matt Juszczak (<a href="mailto:matt@atopia.net">matt@atopia.net</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">Captive portal logging</font></em><br>
			  <br>
              Enrique Maldonado (<a href="mailto:enrique@directemar.cl">enrique@directemar.cl</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">IPsec certificate support</font></em><br>
			  <br>
              Ken Wiesner (<a href="mailto:ken.wiesner@clearshout.com">ken.wiesner@clearshout.com</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">Ping source interface selection</font></em><br>
			  <br>
              Joe Suhre (<a href="mailto:jsuhre@nullconcepts.com">jsuhre@nullconcepts.com</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">DNS forwarder domain overriding</font></em><br>
			  <br>
              Paul Taylor (<a href="mailto:paultaylor@winn-dixie.com">paultaylor@winn-dixie.com</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">ARP table, Traceroute and Filter state pages</font></em><br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">captive portal: disable concurrent logins, file manager</font></em><br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">Webgui users/groups</font></em><br>
			  <br>
              Jonathan De Graeve (<a href="mailto:m0n0wall@esstec.be">m0n0wall@esstec.be</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">Complete captive portal RADIUS overhaul, cleanup</font></em><br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">captive portal: file manager, volume stats, FW rulepool (virtual port pool), MAC formatting, per-user bandwidth limitation</font></em><br>
			  <br>
              Marcel Wiget (<a href="mailto:mwiget@mac.com">mwiget@mac.com</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">captive portal: Voucher authentication</font></em><br>
			  <br>
              Michael Iedema (<a href="mailto:michael@askozia.com">michael@askozia.com</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">CD-ROM to HD installation feature</font></em><br>
			  <br>
              Andrew White (<a href="mailto:andywhite@gmail.com">andywhite@gmail.com</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">IPv6 improvements</font></em><br>
			  <br>
              St&eacute;phane Billiart (<a href="mailto:stephane.billiart@gmail.com">stephane.billiart@gmail.com</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">Captive portal improvements (logout, status page, password change)</font></em><br>
			  <br>
              Lennart Grahl (<a href="mailto:lennart.grahl@gmail.com">lennart.grahl@gmail.com</a>)<br>
              &nbsp;&nbsp;&nbsp;&nbsp;<em><font color="#666666">Scheduler</font></em><br>
            <hr size="1">
            <p>m0n0wall is based upon/includes various free software packages, 
              listed below.<br>
              The author of m0n0wall would like to thank the authors of these 
              software packages for their efforts.</p>
            <p>FreeBSD (<a href="http://www.freebsd.org" target="_blank">http://www.freebsd.org</a>)<br>
              Copyright &copy; 1994-2005 FreeBSD, Inc. All rights reserved.<br>
              <br>
              This product includes PHP, freely available from <a href="http://www.php.net/" target="_blank">http://www.php.net</a>.<br>
              Copyright &copy; 1999 - 2005 The PHP Group. All rights reserved.<br>
              <br>
              mini_httpd (<a href="http://www.acme.com/software/mini_httpd" target="_blank">http://www.acme.com/software/mini_httpd)</a><br>
              Copyright &copy; 1999, 2000 by Jef Poskanzer &lt;jef@acme.com&gt;. 
              All rights reserved.<br>
              <br>
              ISC DHCP server (<a href="http://www.isc.org/products/DHCP/" target="_blank">http://www.isc.org/products/DHCP</a>)<br>
              Copyright &copy; 1996-2003 Internet Software Consortium. All rights 
              reserved.<br>
              <br>
              ipfilter (<a href="http://coombs.anu.edu.au/ipfilter" target="_blank">http://coombs.anu.edu.au/ipfilter</a>)<br>
              Copyright &copy; 1993-2002 by Darren Reed.<br>
              <br>
              MPD - Multi-link PPP daemon for FreeBSD (<a href="http://www.dellroad.org/mpd" target="_blank">http://www.dellroad.org/mpd</a>)<br>
              Copyright &copy; 2003-2004, Archie L. Cobbs, Michael Bretterklieber, Alexander Motin<br>
All rights reserved.<br>
              <br>
              ez-ipupdate (<a href="http://www.gusnet.cx/proj/ez-ipupdate/" target="_blank">http://www.gusnet.cx/proj/ez-ipupdate</a>)<br>
              Copyright &copy; 1998-2001 Angus Mackay. All rights reserved.<br>
              <br>
              Circular log support for FreeBSD syslogd (<a href="http://software.wwwi.com/syslogd/" target="_blank">http://software.wwwi.com/syslogd</a>)<br>
              Copyright &copy; 2001 Jeff Wheelhouse (jdw@wwwi.com)<br>
              <br>
              Dnsmasq - a DNS forwarder for NAT firewalls (<a href="http://www.thekelleys.org.uk" target="_blank">http://www.thekelleys.org.uk</a>)<br>
              Copyright &copy; 2000-2003 Simon Kelley.<br>
              <br>
              Racoon (<a href="http://www.kame.net/racoon" target="_blank">http://www.kame.net/racoon</a>)<br>
              Copyright &copy; 1995-2002 WIDE Project. All rights reserved.<br>
              <br>
              UCD-SNMP (<a href="http://www.ece.ucdavis.edu/ucd-snmp" target="_blank">http://www.ece.ucdavis.edu/ucd-snmp</a>)<br>
              Copyright &copy; 1989, 1991, 1992 by Carnegie Mellon University.<br>
              Copyright &copy; 1996, 1998-2000 The Regents of the University of 
              California. All rights reserved.<br>
              Copyright &copy; 2001-2002, Network Associates Technology, Inc. 
              All rights reserved.<br>
              Portions of this code are copyright &copy; 2001-2002, Cambridge 
              Broadband Ltd. All rights reserved.<br>
              <br>
              choparp (<a href="http://choparp.sourceforge.net/" target="_blank">http://choparp.sourceforge.net</a>)<br>
              Copyright &copy; 1997 Takamichi Tateoka (tree@mma.club.uec.ac.jp)<br>
			  Copyright
&copy; 2002 Thomas Quinot (thomas@cuivre.fr.eu.org)<br>
              <br>
			  wol (<a href="http://ahh.sourceforge.net/wol" target="_blank">http://ahh.sourceforge.net/wol</a>)<br>
			  Copyright &copy; 2000,2001,2002,2003,2004 Thomas Krennwallner &lt;krennwallner@aon.at&gt;<br>
              <br>
              PHP RADIUS PECL package<br>
              Copyright (c) 2003, Michael Bretterklieber &lt;michael@bretterklieber.com&gt;. All rights reserved.<br>
              <br>
              ATAidle (<a href="http://www.cran.org.uk/bruce/software/ataidle/" target="_blank">http://www.cran.org.uk/bruce/software/ataidle</a>)<br>
              Copyright 2004-2007 Bruce Cran &lt;bruce@cran.org.uk&gt;. All rights reserved.<br>
              <br>
              AICCU (<a href="http://www.sixxs.net/tools/aiccu/" target="_blank">http://www.sixxs.net/tools/aiccu/</a>)<br>
              Copyright (C) SixXS Staff &lt;info@sixxs.net&gt;. All rights reserved.
<?php include("fend.inc"); ?>
