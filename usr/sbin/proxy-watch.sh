#!/bin/bash

# Creamos esta aplicacion para que se haga un "reload" de nginx cuando cambien los ficheros de configuracion.
#  si no hiciera falta, podriamos haber acoplado un poco mas la aplicacion a nginx (que generase directamente)
#  la configuracion
if [ -e /etc/proxy-watch.conf ]; then
        . /etc/proxy-watch.conf
fi

function verify_config() {
    echo "mapeos de proxy actualizados"
    # Comprobamos que la configuracion es correcta y si es asi, la cargamos
    nginx -t
    if [ $? -eq 0 ]; then
        systemctl reload nginx
    else
        echo "la configuracion de nginx no es valida" >&2
        if [ "$EMAIL_SERVER" == "" ] && [ "$EMAIL_FROM" != "" ] && [ "$EMAIL_TO" != "" ]; then
            echo "ha ocurrido un error al generar los ficheros de mapeos: la configuracion de nginx no es valida" | sendemail -f "$EMAIL_FROM" -t "$EMAIL_TO" -u "$EMAIL_HEADER" -s "$EMAIL_SERVER"
            if [ $? -ne 0 ]; then
                echo "ha ocurrido un error al enviar el email" >&2
            fi
        fi
    fi
}

verify_config
while inotifywait -q -e close_write $NGINX_CONFIG_FILES; do
        verify_config
done