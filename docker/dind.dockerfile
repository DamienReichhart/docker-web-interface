# Use the latest Ubuntu image
FROM ubuntu:latest


ARG UID=1025
ARG GID=1025

ENV DOCKERUSER=dockeruser


# Install dependencies and Docker
RUN apt-get update && apt-get install -y \
    curl \
    lsb-release \
    gnupg \
    sudo \
    openssh-server \
    docker.io

# Add Docker's official GPG key
RUN curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -

# Add Docker repository
RUN echo "deb [arch=amd64] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list

RUN apt-get update && apt-get install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin -y

# Set up a user to log in through SSH
RUN useradd -m -d /home/$DOCKERUSER -s /bin/bash $DOCKERUSER && echo "$DOCKERUSER:dockerpassword" | chpasswd && \
    usermod -aG sudo $DOCKERUSER && \
    echo '$USER ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers

# Add user to docker group with correct GID
RUN usermod -aG docker $DOCKERUSER && \
    # Fix permissions for the Docker socket
    mkdir -p /var/run/docker && \
    chown root:docker /var/run/docker && \
    chmod 775 /var/run/docker

# Set up SSH
RUN mkdir /var/run/sshd

# Define entrypoint script
RUN echo "#!/bin/sh \n \
    # Start Docker daemon in background \n \
    service docker start || true \n \
    # Ensure docker socket has the right permissions \n \
    chmod 666 /var/run/docker.sock || true \n \
    # Start SSH daemon \n \
    /usr/sbin/sshd -D" > /home/$DOCKERUSER/start.sh && \
    chmod +x /home/$DOCKERUSER/start.sh

CMD ["/home/dockeruser/start.sh"]