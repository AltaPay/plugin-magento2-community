CURRENT_PATH=`pwd`
DIR=$CURRENT_PATH"/plugin-magento2-community"
if [ -d "$DIR" ]; then
  # Take action if $DIR exists. #
  echo "***********************"
  echo "Retriving latest package"
  echo "***********************"
  cd $DIR
  git pull
else
  ###  If $DIR does NOT exists clone the repo ###
  echo "***********************"
  echo "Cloning Repo"
  echo "***********************"
  git clone https://github.com/AltaPay/plugin-magento2-community.git
  cd $DIR
fi
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
zip -r altapay_magento2-community_$VER.zip ./*
### Clone validation script ###
echo "***********************"
echo "Clone validation script"
echo "***********************"
git clone https://gist.github.com/f9c1853715090dbdbc69.git
mv $DIR/f9c1853715090dbdbc69/validate_m2_package.php $DIR
rm -rf $DIR/f9c1853715090dbdbc69/
### Validate the zip folder with the recommended file ###
echo "***********************"
echo "Validate the zip folder"
echo "***********************"
php validate_m2_package.php -d altapay_magento2-community_$VER.zip
