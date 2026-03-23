FROM swaggerapi/swagger-ui:v5.17.14

ENV SWAGGER_JSON_URL=/specs/index.yaml

COPY api-docs /usr/share/nginx/html/specs
COPY api-docs/default.conf /etc/nginx/conf.d/default.conf
COPY api-docs/swagger-initializer.js /usr/share/nginx/html/swagger-initializer.js
RUN mkdir -p /usr/share/nginx/html/docs \
    && find /usr/share/nginx/html -mindepth 1 -maxdepth 1 ! -name docs -exec cp -R {} /usr/share/nginx/html/docs/ \;
