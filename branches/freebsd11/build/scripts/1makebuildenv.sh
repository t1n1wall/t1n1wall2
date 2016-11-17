#!/usr/local/bin/bash

set -e

if [ -z "$MW_BUILDPATH" -o ! -d "$MW_BUILDPATH" ]; then
	echo "\$MW_BUILDPATH is not set"
	exit 1
fi

# get build env ready
	if [ ! -x /usr/local/bin/mkisofs ]; then
		pkg install -y  cdrtools
	fi
	if [ ! -x /usr/local/bin/autoconf-2.13 ]; then
		pkg install -y  autoconf213
	fi
	if [ ! -x /usr/local/bin/autoconf-2.69 ]; then
		pkg install -y  autoconf
	fi
	if [ ! -x /usr/local/bin/gcc46 ]; then
		pkg install -y gcc46
		ln -s /usr/local/bin/gcc46 /usr/local/bin/gcc
	fi
	if [ ! -x /usr/local/bin/makedepend ]; then
                pkg install -y makedepend 
        fi

	cd $MW_BUILDPATH

# ensure system time is correct
	pgrep ntpd > /dev/null || ntpdate pool.ntp.org

# make filesystem structure for image
	mkdir  t1n1fs images
	cd t1n1fs
	mkdir -p etc/rc.d/ bin cf conf.default dev etc/mpd-modem ftmp mnt proc root sbin tmp libexec lib/casper /var/etc/dnsmasq usr/bin usr/lib usr/libexec usr/local usr/sbin usr/share usr/local/bin usr/local/captiveportal usr/local/lib usr/local/sbin/.libs usr/local/www usr/share/misc boot/kernel
 
# insert svn files to filesystem
	cp -v -r $MW_BUILDPATH/freebsd11/phpconf/rc.* etc/
	cp -v -r $MW_BUILDPATH/freebsd11/phpconf/inc etc/
	cp -v -r $MW_BUILDPATH/freebsd11/etc/* etc
	cp -v -r $MW_BUILDPATH/freebsd11/webgui/ usr/local/www/
	cp -v -r $MW_BUILDPATH/freebsd11/captiveportal usr/local/
 
# set permissions
	chmod -R 0755 usr/local/www/* usr/local/captiveportal/* etc/rc*
	chmod -R 0755 etc/dhcp6c-exit-hooks
# create links
	ln -s /cf/conf conf
	ln -s /var/run/htpasswd usr/local/www/.htpasswd
 
# configure build information
	date > etc/version.buildtime
	date +%s > etc/version.buildtime.unix
	VERSION=`cat $MW_BUILDPATH/freebsd11/version`

	if [ -r $MW_BUILDPATH/freebsd11/svnrevision ]; then
		# replace character '%' in version with repository revision
		SVNREV=`cat $MW_BUILDPATH/freebsd11/svnrevision`
		VERSION=${VERSION/\%/$SVNREV}
	fi
	
	echo $VERSION > etc/version
 
# get and set current default configuration
	cp -v $MW_BUILDPATH/freebsd11/phpconf/config.xml conf.default/config.xml
 
# insert termcap and zoneinfo files
	cp -v /usr/share/misc/termcap usr/share/misc
 
# do zoneinfo.tgz and dev fs
	cd tmp 
	cp -v $MW_BUILDPATH/freebsd11/build/files/zoneinfo.tgz $MW_BUILDPATH/t1n1fs/usr/share
# create php.ini	
	cp -v $MW_BUILDPATH/freebsd11/build/files/php.ini $MW_BUILDPATH/t1n1fs/usr/local/lib/php.ini
# create login.conf
	cp -v $MW_BUILDPATH/freebsd11/build/files/login.conf $MW_BUILDPATH/t1n1fs/etc/
# create missing etc files
	tar -xzf $MW_BUILDPATH/freebsd11/build/files/etcadditional.tgz -C $MW_BUILDPATH/t1n1fs/
	cp -v $MW_BUILDPATH/freebsd11/build/files/mpd-modem.script $MW_BUILDPATH/t1n1fs/etc/mpd-modem/mpd.script
# setup pwd.db spwd.db and install passwd from master.passwd
	/usr/sbin/pwd_mkdb -d $MW_BUILDPATH/t1n1fs/etc -p $MW_BUILDPATH/t1n1fs/etc/master.passwd # install /etc/passwd from the master.passwd file
	/usr/sbin/pwd_mkdb -d $MW_BUILDPATH/t1n1fs/etc    $MW_BUILDPATH/t1n1fs/etc/master.passwd # install /etc/pwd.db and /etc/spwd.db

echo "Finished Stage 1"
