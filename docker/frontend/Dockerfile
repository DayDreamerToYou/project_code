FROM nginx:1.25-alpine

LABEL maintainer="Fishery System"
LABEL description="Frontend container for Fishery Data Management System"

RUN apk add --no-cache \
    curl \
    tzdata

ENV TZ=Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN mkdir -p /usr/share/nginx/html \
    && mkdir -p /var/log/nginx

COPY public/ /usr/share/nginx/html/

COPY nginx.conf /etc/nginx/conf.d/default.conf

RUN chown -R nginx:nginx /usr/share/nginx/html \
    && chmod -R 755 /usr/share/nginx/html

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
