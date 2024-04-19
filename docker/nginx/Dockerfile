FROM nginx:1.18-alpine

COPY nginx.conf /etc/nginx/nginx.conf
COPY sites/default.conf /etc/nginx/sites-available/default.conf
COPY conf.d/default.conf /etc/nginx/conf.d/default.conf

COPY crm-api.local.crt /etc/nginx/ssl/crm-api.local.crt
COPY crm-api.local.key /etc/nginx/ssl/crm-api.local.key

EXPOSE 80 443

CMD ["nginx"]