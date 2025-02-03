rm -fR /usr/local/cpanel/base/frontend/jupiter/redis_plugin
mkdir /usr/local/cpanel/base/frontend/jupiter/redis_plugin
cd /usr/local/cpanel/base/frontend/jupiter/redis_plugin

echo "Downloading Redis cPanel Plugin..."
wget -q https://github.com/softplexity/redis-cpanel-plugin/archive/main.zip -O Redis_Plugin_Package.zip

# Extract Archive ZIP
echo "Extracting Plugin..."
unzip Redis_Plugin_Package.zip

# Moving To Plugin Residence
mv redis-cpanel-plugin-main/plugin/* ./

# Register Plugin with cPanel
/usr/local/cpanel/scripts/install_plugin /usr/local/cpanel/base/frontend/jupiter/redis_plugin --theme jupiter
 

#Cleanup By Removing Packages
echo "Cleaning Up..."
rm -vf Redis_Plugin_Package.zip
rm -rvf redis-cpanel-plugin-main
cd -
cd ../
rm -rvf redis-cpanel-plugin

# Fix Permissions
echo "Finalizing Permissions..."
chmod -R 755 /usr/local/cpanel/base/frontend/jupiter/redis_plugin