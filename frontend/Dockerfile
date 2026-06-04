FROM node:22-alpine

WORKDIR /app

# Copiamos solo el package.json
COPY package.json ./

# Usamos npm install ya que no tenemos lockfile
RUN npm install

# Copiamos el resto del código
COPY . .

EXPOSE 5173

# CMD mantiene tu configuración para que sea accesible desde fuera del contenedor
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0", "--port", "5173"]