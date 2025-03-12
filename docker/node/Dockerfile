FROM node:18

WORKDIR /var/www/html

# Install dependencies
RUN apt-get update && apt-get install -y \
    bash \
    git \
    && rm -rf /var/lib/apt/lists/*

# Add node_modules/.bin to PATH
ENV PATH /var/www/html/node_modules/.bin:$PATH

# Use tini as init
RUN apt-get update && apt-get install -y tini && rm -rf /var/lib/apt/lists/*
ENTRYPOINT ["/usr/bin/tini", "--"]

# Install dependencies first, then run watch
CMD ["sh", "-c", "npm install && npm run watch || tail -f /dev/null"]