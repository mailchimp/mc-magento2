# IF WE RUN ONLY ONE VERSION OF PHP WE NO NEED PASS THE VERSION
while getopts "P:p:" flag
    do
        case "${flag}" in
            p) PHP_VERSION=${OPTARG};;
        esac
    done
currentPath=${PWD}
# Create symlink for php
cd ../../../../
captain=`grep captainhook composer.json`
if [ -z $captain ];
  then
    echo "No captain installed, install captainhook first"
    exit 1;
fi

if [ ! -d "../vendor/vendor" ];
  then
    echo 'phpcs not installed'
    exit 1;
fi

rm php
if [ $? -ne 0 ]; then
	echo "Warning can't remove the php file"
fi
if [ -z $PHP_VERSION ]
  then
    ln -s `which php` php
  else
    which php$PHP_VERSION
    if [ $? -ne 0 ]
      then
        echo 'Error invalid php version';
        exit 1;
    fi
    ln -s `which php$PHP_VERSION` php
fi
# End create symlink for php

echo '*** Finding magento version ***'
lineversion=`grep \"version composer.json`
lines=( $lineversion )
version=${lines[1]}
version=`echo $version | awk '{gsub(/^["\t]+|[",\t]+$/,""); print $0, "" }'`
versionMiddle="$(cut -d'.' -f2 <<<"$version")"
versionMajor="$(cut -d'.' -f3 <<<"$version")"
echo $version
cd $currentPath

if [ $versionMiddle -eq '4' ]
  then
    echo '******* starting setting up captainhook in Mailchimp.... ******* '
    mkdir vendor
    cp ../../../../vendor/autoload.php vendor
    ../../../../vendor/bin/captainhook install -n
    rm -rf vendor
    echo '******* finished setting up captainhook in Mailchimp.... ******* '
fi
