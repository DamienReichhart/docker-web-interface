# Base image with Alpine (minimal, lightweight)
FROM httpd:alpine


ARG UID=1010
ARG GID=1010

RUN apk add nano

# Install the necessary packages
RUN apk add --no-cache shadow

# Security: Avoid running as root; create a non-root user and group for Apache to run under
RUN addgroup -g 1010 apache && adduser -u 1010 -G apache -S apache

# Create the socket right group and assign the user
RUN addgroup --gid 1020 socket
RUN usermod -a -G socket apache

# Copy Apache virtual host configuration to the container
COPY apache/config/apache.vhost.conf /usr/local/apache2/conf/extra/apache.vhost.conf

# Enable Apache modules to ensure proper functionality
RUN sed -i \
    # Uncomment the configuration for mod_deflate to enable compression
    -e '/#LoadModule deflate_module/s/^#//g' \
    # Uncomment the configuration for mod_proxy to enable proxying capabilities
    -e '/#LoadModule proxy_module/s/^#//g' \
    # Uncomment the configuration for mod_proxy_fcgi to enable FastCGI proxy module
    -e '/#LoadModule proxy_fcgi_module/s/^#//g' \
    # Uncomment the configuration for mod_proxy_unix to enable UNIX domain sockets
    -e '/#LoadModule proxy_unix_module/s/^#//g' \
    # Uncomment the configuration for mod_rewrite to enable URL rewriting
    -e '/#LoadModule rewrite_module/s/^#//g' \
    /usr/local/apache2/conf/httpd.conf

# Enable mod_ssl and other required modules
RUN sed -i '/#LoadModule ssl_module/s/^#//g' /usr/local/apache2/conf/httpd.conf \
    && sed -i '/#LoadModule socache_shmcb_module/s/^#//g' /usr/local/apache2/conf/httpd.conf

# Security: Enable security-related headers and protection mechanisms
RUN echo "LoadModule headers_module modules/mod_headers.so" >> /usr/local/apache2/conf/httpd.conf \
    && echo "Header always set X-Content-Type-Options \"nosniff\"" >> /usr/local/apache2/conf/httpd.conf \
    && echo "Header always set X-XSS-Protection \"1; mode=block\"" >> /usr/local/apache2/conf/httpd.conf \
    && echo "Header always set Strict-Transport-Security \"max-age=31536000; includeSubDomains\"" >> /usr/local/apache2/conf/httpd.conf \
    && echo "Header always set X-Frame-Options \"SAMEORIGIN\"" >> /usr/local/apache2/conf/httpd.conf

# Copy SSL certificates and keys
COPY ./apache/certs/server.crt /usr/local/apache2/conf/server.crt
COPY ./apache/certs/server.key /usr/local/apache2/conf/server.key

# Include the virtual host configuration in Apache's main config
RUN echo "Include /usr/local/apache2/conf/extra/apache.vhost.conf" >> /usr/local/apache2/conf/httpd.conf

# Set correct permissions for the certificates and key
RUN chmod 600 /usr/local/apache2/conf/server.key /usr/local/apache2/conf/server.crt

# Change the Apache user and group in httpd.conf
RUN sed -i 's/User daemon/User apache/g' /usr/local/apache2/conf/httpd.conf && \
    sed -i 's/Group daemon/Group apache/g' /usr/local/apache2/conf/httpd.conf

# Security: Change ownership of necessary files to the non-root apache user
RUN mkdir /var/www
RUN chown -R apache:apache /var/www
RUN chown -R apache:apache /usr/local/apache2
RUN chmod -R 777 /var/run


# Security : install crowdsec agent and bouncer

RUN cd /tmp && \
    wget https://github.com/crowdsecurity/crowdsec/releases/download/v1.6.3/crowdsec-release.tgz && \
    tar xzf crowdsec-release* && \
    rm *.tgz && \
    apk add bash newt envsubst && \
    cd /tmp/crowdsec-v* && \
    # Docker mode skips configuring systemd
    ./wizard.sh --docker-mode && \
    cscli hub update && \
    # A collection is just a bunch of parsers and scenarios bundled together for convienence
    apk add nftables && \
    echo "http://dl-cdn.alpinelinux.org/alpine/edge/testing" >> /etc/apk/repositories && \
    apk update && \
    apk add cs-firewall-bouncer&& \
    cscli collections install crowdsecurity/linux && \
    cscli parsers install crowdsecurity/whitelists && \
    cscli collections install crowdsecurity/apache2 && \
    cscli collections install crowdsecurity/apiscp && \
    cscli collections install crowdsecurity/appsec-crs && \
    cscli collections install crowdsecurity/appsec-generic-rules && \
    cscli collections install crowdsecurity/appsec-virtual-patching && \
    cscli appsec-configs install crowdsecurity/appsec-default && \
    cscli collections install crowdsecurity/linux && \
    cscli collections install crowdsecurity/linux-lpe


# Set working directory
WORKDIR /var/www/html

# Expose port 80 (HTTP) and 443 (HTTPS)
EXPOSE 80 443

# Run Apache as the non-root user
USER apache
