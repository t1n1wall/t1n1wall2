#!/usr/local/bin/bash

set -e

if [ -z "$MW_BUILDPATH" -o ! -d "$MW_BUILDPATH" ]; then
	echo "\$MW_BUILDPATH is not set"
	exit 1
fi

VERSION=`cat $MW_BUILDPATH/t1n1fs/etc/version`
if [ $MW_ARCH = "amd64" ]; then
	VERSION=$VERSION.$MW_ARCH	
fi

/usr/bin/strings $MW_BUILDPATH/t1n1fs/lib/libcrypto.so.* | grep "^OpenSSL " | grep -i freebsd > $MW_BUILDPATH/t1n1fs/etc/libcrypto.version
/usr/bin/strings $MW_BUILDPATH/t1n1fs/usr/lib/libssl.so.* | grep "^OpenSSL " > $MW_BUILDPATH/t1n1fs/etc/libssl.version

makemfsroot() {
	PLATFORM=$1
	SPARESPACE=$2
	
	echo -n "Making mfsroot for $PLATFORM..."
	
	echo $PLATFORM > $MW_BUILDPATH/t1n1fs/etc/platform
	cd $MW_BUILDPATH/tmp
	dd if=/dev/zero of=mfsroot-$PLATFORM bs=1k count=`du -d0 $MW_BUILDPATH/t1n1fs | cut -b1-5 | tr " " "+" | xargs -I {} echo "($SPARESPACE)+{}" | bc` > /dev/null 2>&1
	mdconfig -a -t vnode -f mfsroot-$PLATFORM -u 20
	disklabel -rw /dev/md20 auto
	newfs -b 8192 -f 1024 -o space -m 0 /dev/md20 > /dev/null
	mount /dev/md20 /mnt
	cd /mnt
	tar -cf - -C $MW_BUILDPATH/t1n1fs ./ | tar -xpf -
	cd $MW_BUILDPATH/tmp
	umount /mnt
	mdconfig -d -u 20
	gzip -9f mfsroot-$PLATFORM
	
	echo " done"
}

makeimage() {
	PLATFORM=$1
	SPARESPACE=$2
	FIRMWAREIMG=$3
	
	if [ $FIRMWAREIMG ]; then
		echo -n "Making image for $PLATFORM with $FIRMWAREIMG firmware..."
	else 
		echo -n "Making image for $PLATFORM..."
	fi

	# Make staging area to help calc space
	mkdir $MW_BUILDPATH/tmp/firmwaretmp
	
	cp $MW_BUILDPATH/tmp/kernel.gz $MW_BUILDPATH/tmp/firmwaretmp
	cp $MW_BUILDPATH/tmp/mfsroot-$PLATFORM.gz $MW_BUILDPATH/tmp/firmwaretmp/mfsroot.gz
	cp /boot/{loader,loader.rc} $MW_BUILDPATH/tmp/firmwaretmp
	cp $MW_BUILDPATH/t1n1fs/conf.default/config.xml $MW_BUILDPATH/tmp/firmwaretmp

	if [ $FIRMWAREIMG ]; then
		cp $MW_BUILDPATH/images/$FIRMWAREIMG-$VERSION.img $MW_BUILDPATH/tmp/firmwaretmp
	fi

	cd $MW_BUILDPATH/tmp
	dd if=/dev/zero of=image.bin bs=1k count=`du -d0 $MW_BUILDPATH/tmp/firmwaretmp  | cut -b1-5 | tr " " "+" | xargs -I {} echo "($SPARESPACE)+{}" | bc` > /dev/null 2>&1
	rm -rf $MW_BUILDPATH/tmp/firmwaretmp
	
	mdconfig -a -t vnode -f $MW_BUILDPATH/tmp/image.bin -u 30
	disklabel  -wn  /dev/md30 auto 2>/dev/null |  awk '/unused/{if (M==""){sub("unused","4.2BSD");M=1}}{print}' > md.label
    bsdlabel -m  i386 -R -B -b /boot/boot /dev/md30 md.label
    newfs -b 8192 -f 1024 -O 1 -U -o space -m 0 /dev/md30a > /dev/null
	mount /dev/md30a /mnt
	
	cp $MW_BUILDPATH/tmp/kernel.gz /mnt/
	cp $MW_BUILDPATH/tmp/mfsroot-$PLATFORM.gz /mnt/mfsroot.gz
	mkdir -p /mnt/boot/kernel
	cp /boot/loader /mnt/boot
	cp $MW_BUILDPATH/freebsd10/build/boot/$PLATFORM/loader.rc /mnt/boot
	if [ -r $MW_BUILDPATH/freebsd10/build/boot/$PLATFORM/boot.config ]; then
		cp $MW_BUILDPATH/freebsd10/build/boot/$PLATFORM/boot.config /mnt
	fi

	
	if [ $FIRMWAREIMG ]; then
		cp $MW_BUILDPATH/images/$FIRMWAREIMG-$VERSION.img /mnt/firmware.img
	fi
	
	mkdir /mnt/conf
	cp $MW_BUILDPATH/t1n1fs/conf.default/config.xml /mnt/conf
	cd $MW_BUILDPATH/tmp
	umount /mnt
	mdconfig -d -u 30
	gzip -9f image.bin
	if [ $FIRMWAREIMG ]; then
		mv image.bin.gz $MW_BUILDPATH/images/$PLATFORM-installer-$VERSION.img
	else
		mv image.bin.gz $MW_BUILDPATH/images/$PLATFORM-$VERSION.img
	fi
	echo " done"
}

# Creating mfsroots with 4MB spare space
	makemfsroot generic-pc-cdrom 4096
	makemfsroot generic-pc 4096
	makemfsroot generic-pc-serial 4096
	
# Make firmware img with 2MB space 	
	makeimage generic-pc 2048
	makeimage generic-pc-serial 2048
	makeimage generic-pc 2048 generic-pc
	makeimage generic-pc-serial 2048 generic-pc-serial
	
# Make ISO
	echo -n "Making ISO..."
	cd $MW_BUILDPATH/tmp
	mkdir -p $MW_BUILDPATH/tmp/cdroot/boot/kernel

	cp /boot/{cdboot,loader} $MW_BUILDPATH/tmp/cdroot/boot
	cp $MW_BUILDPATH/freebsd10/build/boot/generic-pc/loader.rc $MW_BUILDPATH/tmp/cdroot/boot
	cp kernel.gz $MW_BUILDPATH/tmp/cdroot/
	cp mfsroot-generic-pc-cdrom.gz $MW_BUILDPATH/tmp/cdroot/mfsroot.gz
	cp $MW_BUILDPATH/images/generic-pc-$VERSION.img $MW_BUILDPATH/tmp/cdroot/firmware.img
	mkisofs -b "boot/cdboot" -no-emul-boot -A "t1n1wall $VERSION CD-ROM image" \
        -c "boot/boot.catalog" -d -r -publisher "t1n1wall.com" \
        -p "t1n1wall.com" -V "t1n1wall_cd" -o "t1n1wall.iso" \
        -quiet $MW_BUILDPATH/tmp/cdroot
	mv t1n1wall.iso $MW_BUILDPATH/images/generic-pc-$VERSION.iso
	echo " done"

# Make installer images (serial and generic)
	#Make firmware image that contains a firmware image


echo "Finished Stage 6"
