FROM dimaip/docker-neos-alpine:latest
ENV PHP_TIMEZONE=Europe/Moscow
ENV REPOSITORY_URL=https://github.com/psmb/KateheoDistr
ENV AWS_ENDPOINT=https://hb.bizmrg.com
ENV AWS_BACKUP_ARN=s3://psmb-neos-resources/db/kateheo/
ENV DONT_PUBLISH_PERSISTENT=1
COPY --chown=80:80 composer.json /data/www-provisioned/composer.json
COPY --chown=80:80 DistributionPackages/Sfi.Kateheo/composer.json /data/www-provisioned/DistributionPackages/Sfi.Kateheo/composer.json
RUN cd /data/www-provisioned && \
    composer install && \
    rm -rf /composer/cache && \
    mkdir -p /data/www-provisioned/Configuration && \
    cp /Settings.yaml /data/www-provisioned/Configuration/
COPY --chown=80:80 ./ /data/www-provisioned/
