const Koa = require('koa');
const bodyParser = require('koa-bodyparser');
const serve = require('koa-static');
const mount = require('koa-mount');
const path = require('path');
const router = require('./routes');
const { initDatabase } = require('./database');
const { errorHandler } = require('./middleware/errorHandler');

const app = new Koa();
const PORT = process.env.PORT || 3000;

initDatabase();

app.use(errorHandler);
app.use(bodyParser());
app.use(router.routes());
app.use(router.allowedMethods());

app.use(mount('/swagger-ui', serve(path.join(__dirname, '../public/swagger-ui'))));

app.listen(PORT, () => {
  console.log(`EcommerceHub API running on port ${PORT}`);
  console.log(`Swagger UI: http://localhost:${PORT}/swagger-ui`);
  console.log(`API Spec: http://localhost:${PORT}/swagger`);
});