### Find the most recent tag ###
echo "***********************"
echo "Find the most recent tag"
echo "***********************"
VER=`git describe --tags`
echo "Latest Tag = "$VER
### Zip the folder with the latest tag ###
echo "***********************"
echo "Zip the folder with the latest tag"
  ###  If $DIR does NOT exists clone the repo ###
echo "***********************"
zip -r altapay_magento2-community_$VER.zip ./* -x '*.git*' '*package.sh*'
