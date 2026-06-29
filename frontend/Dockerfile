FROM node:20

WORKDIR /var/www/frontend
COPY . .

RUN npm install

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
