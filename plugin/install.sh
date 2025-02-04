rm -fR /usr/local/cpanel/base/frontend/jupiter/redis_plugin
mkdir /usr/local/cpanel/base/frontend/jupiter/redis_plugin
cd /usr/local/cpanel/base/frontend/jupiter/redis_plugin || exit

echo "Downloading Redis cPanel Plugin..."
wget -q https://github.com/softplexity/redis-plugin-cpanel/archive/refs/heads/main.zip -O Redis_Plugin_Package.zip

# Extract Archive ZIP
echo "Extracting Plugin..."
unzip Redis_Plugin_Package.zip

# Moving To Plugin Residence
mv redis-plugin-cpanel-main/plugin/* ./

# Register Plugin with cPanel
/usr/local/cpanel/scripts/install_plugin /usr/local/cpanel/base/frontend/jupiter/redis_plugin --theme jupiter
 

#Cleanup By Removing Packages
echo "Cleaning Up..."
rm -vf Redis_Plugin_Package.zip
rm -rvf redis-plugin-cpanel-main
cd - || exit
cd ../
rm -rvf redis-plugin-cpanel

# Fix Permissions
echo "Finalizing Permissions..."
chmod -R 755 /usr/local/cpanel/base/frontend/jupiter/redis_plugin