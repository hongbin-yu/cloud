# latest tag
FROM npulidom/alpine-nginx-php

# working directory
WORKDIR /var/www

# extra ops ...

COPY . .

# start supervisor
CMD ["--nginx-env"]