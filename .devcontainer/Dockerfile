FROM alpine:3.22

ARG USERNAME=vscode
ARG USER_UID=1000
ARG USER_GID=1000

ENV HOME=/home/${USERNAME}

RUN apk update \
  && apk add --no-cache \
    bash \
    bat \
    ca-certificates \
    curl \
    delta \
    direnv \
    eza \
    fd \
    gcompat \
    git \
    github-cli \
    jq \
    just \
    libgcc \
    libstdc++ \
    nodejs \
    npm \
    openssh-client \
    php84 \
    php84-cli \
    php84-ctype \
    php84-curl \
    php84-dom \
    php84-fileinfo \
    php84-iconv \
    php84-mbstring \
    php84-odbc \
    php84-opcache \
    php84-openssl \
    php84-pcntl \
    php84-pdo \
    php84-pdo_pgsql \
    php84-pdo_sqlite \
    php84-pgsql \
    php84-phar \
    php84-posix \
    php84-session \
    php84-simplexml \
    php84-sodium \
    php84-sqlite3 \
    php84-tokenizer \
    php84-xml \
    php84-xmlreader \
    php84-xmlwriter \
    php84-zip \
    python3 \
    ripgrep \
    sd \
    tar \
    tzdata \
    unzip \
    xz \
    yq \
  && ln -sf /usr/bin/php84 /usr/bin/php \
  && rm -rf /var/cache/apk/*

RUN addgroup -g ${USER_GID} ${USERNAME} \
  && adduser -D -h ${HOME} -s /bin/bash -G ${USERNAME} -u ${USER_UID} ${USERNAME} \
  && mkdir -p ${HOME} \
  && chown -R ${USERNAME}:${USERNAME} ${HOME}

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

USER ${USERNAME}
WORKDIR ${HOME}

ENV PATH="${HOME}/.npm-global/bin:${HOME}/.config/composer/vendor/bin:${PATH}"

RUN mkdir -p "${HOME}/.npm-global" \
  && npm config set prefix "${HOME}/.npm-global"

RUN npm install -g @anthropic-ai/claude-code @openai/codex 

CMD ["/bin/bash"]
