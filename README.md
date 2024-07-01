# Proxy Web

Este es un proyecto sencillo que permite instalar un proxy web a otras páginas, utilizando el nombre DNS del equipo. 

El caso se uso básico es: accedo a `https://web1.i3m.upv.es` y se sirve la petición una máquina con la IP privada `172.16.12.1`, en el puerto 10443.

Esta plataforma permite
- Definir el prefijo de las URLs que se van a servir (en el caso del ejemplo, `web1`)
- Definir la IP de la máquina que va a ejecutar la petición (en el caso del ejemplo, `172.16.12.1`)
- Definir el puerto de la máquina que va a ejecutar la petición (en el caso del ejemplo, `10443`)

## Arquitectura

El sistema tiene 3 partes:

1. Servidor `nginx` configurado como proxy inverso. Este servidor recibe las peticiones y las redirige a la máquina que se ha configurado.
2. Aplicación `php` que permite hacer la configuración del proxy inverso, indicando los parámetros
3. Servicio `bash` que se encarga de revisar la configuración y actualizar el fichero de configuración de `nginx` en caso de que haya cambios.

## Instalación

Los pasos son los siguientes:

1. Instalar el servidor nginx e incluir la configuración básica (en el fichero `etc/nginx.conf`).
2. Instalar el servidor php y los módulos necesarios.
3. Instalar la aplicación php (en el directorio `/var/www/proxy-manager`).
4. Instalar el servicio bash (los ficheros `etc/systemd/system/proxy-watch.service`, `etc/proxy-watch.conf` y `usr/sbin/proxy-watch.sh`) y ponerlo en funcionamiento.

Para una puesta en marcha sencilla, lo mejor es utilizar el instalador `install.sh`.

> Es muy **IMPORTANTE** prestar atención a la configuración del servicio `proxy-watch` porque es el pegamento entre la aplicación web y el servidor `nginx`.

## Configuración 

### Servidor nginx

La configuración de `nginx` es compleja: utiliza proxies y variables, así que es mejor utilizar el fichero `etc/nginx.conf` que se proporciona. 

En el, se debe establecer el valor `<PROXY_DNS>` al nombre DNS principal que va a servir la aplicación php de gestión del proxy.

El mapeo de las URLs se hace con unos ficheros de configuración nginx que genera la aplicación PHP en los ficheros. Estos ficheros deben estar disponibles en el directorio `/etc/nginx/proxy-manager` (*) ver más abajo.

### Aplicación PHP

La aplicación php se instala en el directorio `/var/www/proxy-manager`.

Los ficheros de configuración de configuración de mapeos de nginx los genera la aplicación de php y están en `/var/www/proxy-manager`. Deben tener los permisos adecuados para que los pueda crear la aplicación php y los pueda leer el servicio bash (`chown www-data:root /var/www/proxy-manager/*.conf` y `chmod 640 /var/www/proxy-manager/*.conf`).

Estos ficheros deben estar disponibles para nginx en `/etc/nging/proxy-manager/` y lo mejor es enlazados con los ficheros de configuración de nginx en ese directorio (por ejemplo, `ln -s /etc/nginx/proxy-manager/configuracion_nginx.80.conf /etc/nginx/proxy-manager/configuracion_nginx.80.conf` y `ln -s /etc/nginx/proxy-manager/configuracion_nginx.443.conf /etc/nginx/proxy-manager/configuracion_nginx.443.conf`).

Además, tenemos los siguientes ficheros:
- `mapeos.json` que contiene la configuración de los mapeos y que se irán actualizando por la aplicación `php`.
- `usuarios.json` que contiene los usuarios y contraseñas que se pueden utilizar para acceder a la aplicación `php`.

El fichero `mapeos.json` tiene que poder ser escrito por la aplicación php y el fichero `usuarios.json` debe poder ser leído por la aplicación php. 

### Servicio bash

El servicio bash es el pegamento entre la aplicación PHP y el servidor nginx, así que es muy importante que esté bien configurado.

* El fichero `/etc/proxy-watch.conf` también debe apuntar a estos ficheros, en la variable `NGINX_CONFIG_FILES`.
* es necesario configurar los valores de `EMAIL_FROM`, `EMAIL_TO` y `EMAIL_SERVER` del fichero `etc/proxy-watch.conf` para que el servicio pueda enviar correos electrónicos para informar de eventuales errores.

## Usuarios

Los usuarios se gestionan en el fichero `usuarios.json` que se encuentra en el directorio `/var/www/proxy-manager`. Este es un diccionario `json` que tiene como clave el nombre de usuario y como valor el hash de la contraseña.

La contraseña se puede generar usando el comando `htpasswd -nm <nombre de usuario>` y pegándola en el fichero `usuarios.json`.

En caso de que el usuario venga autenticado por una fuente externa, se puede poner el nombre de usuario como clave y el valor `""` como contraseña (por ejemplo, con la autenticación de la UPV o que esté autenticado por google).

# Documentación

- https://gist.github.com/kekru/c09dbab5e78bf76402966b13fa72b9d2
- https://stackoverflow.com/questions/38371840/ssl-pass-through-in-nginx-reverse-proxy
