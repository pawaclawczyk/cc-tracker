FROM php

RUN apt-get update && \
    apt-get install htop

WORKDIR /app

CMD ["/bin/bash"]