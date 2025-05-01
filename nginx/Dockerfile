FROM nginx:stable-alpine

# Copiar la configuración de Nginx
COPY default.conf /etc/nginx/conf.d/

# Exponer los puertos HTTP y HTTPS
EXPOSE 80 443

# Comando por defecto para iniciar Nginx
CMD ["nginx", "-g", "daemon off;"]
