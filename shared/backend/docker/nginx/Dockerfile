FROM node:22-alpine AS frontend
WORKDIR /build
COPY logistics/frontend/package*.json ./
RUN npm ci
COPY logistics/frontend/ ./
RUN npm run build

FROM nginx:1.27-alpine
COPY shared/backend/docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=frontend /build/dist /usr/share/nginx/html
COPY shared/backend/public /var/www/html/public