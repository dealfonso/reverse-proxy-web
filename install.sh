SERVER_DNS="$1"
INSTALL_FOLDER=test

if [ -z "$SERVER_DNS" ]; then
  echo "Please provide the server DNS as the first argument"
  exit 1
fi

if ! host "$SERVER_DNS" > /dev/null; then
  echo "The server DNS provided is not valid"
  exit 1
fi

if [ ! -f etc/nginx/nginx.conf ]; then
  echo "Please run this script from the root of the project"
  exit 1
fi

# Instalamos las dependencias: nginx con el mod-stream y php (pero instalamos tambien php-curl, porque funciona en versiones nuevas)
apt install nginx libnginx-mod-stream php php-curl

# Activamos el modulo de php
php enmod curl

# Activamos y arrancamos nginx
systemctl enable nginx
systemctl start nginx

# Creamos la estructura de directorios
mkdir -p "$INSTALL_FOLDER"
mkdir -p "$INSTALL_FOLDER/etc/nginx"
mkdir -p "$INSTALL_FOLDER/etc/systemd/system"

# Copiamos los archivos de configuración de ngnix (y los modificamos para que funcionen en el servidor)
cp -r etc/nginx/* "$INSTALL_FOLDER/etc/nginx"
cat etc/nginx/nginx.conf | sed "s/<PROXY_DNS>/$SERVER_DNS/g" > "$INSTALL_FOLDER/etc/nginx/nginx.conf"

# Copiamos el archivo del servicio
cp etc/systemd/system/* "$INSTALL_FOLDER/etc/systemd/system"

# Copiamos el binario que monitoriza los ficheros para recargar nginx cuando sea necesario
mkdir -p "$INSTALL_FOLDER/usr/sbin"
cp usr/sbin/* "$INSTALL_FOLDER/usr/sbin"

# Copiamos los archivos de la aplicación web
mkdir -p "$INSTALL_FOLDER/var/www/"
cp -r var/www/* "$INSTALL_FOLDER/var/www/"

# Finalmente enlazamos la configuración de la web con la de nginx
ln -s /var/www/proxy-manager/configuracion_nginx.443.conf /etc/nginx/proxy-manager/
ln -s /var/www/proxy-manager/configuracion_nginx.80.conf /etc/nginx/proxy-manager/
chown www-data:root /var/www/proxy-manager/configuracion_nginx.443.conf
chown www-data:root /var/www/proxy-manager/configuracion_nginx.80.conf

# Recargamos nginx para que coja la nueva configuración
systemctl reload nginx
