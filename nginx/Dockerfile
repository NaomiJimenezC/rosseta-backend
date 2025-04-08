FROM nginx:stable-alpine

# Copiar la configuración de Nginx
COPY default.conf /etc/nginx/conf.d/

# Copiar los certificados SSL (si los tienes)
# COPY ssl/ /etc/nginx/ssl/

# Exponer los puertos HTTP y HTTPS
EXPOSE 80 443

# Comando por defecto para iniciar Nginx
CMD ["nginx", "-g", "daemon off;"]
