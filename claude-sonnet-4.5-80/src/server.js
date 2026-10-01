const Koa = require('koa');
const Router = require('@koa/router');
const bodyParser = require('koa-bodyparser');
const cors = require('@koa/cors');
const serve = require('koa-static');
const path = require('path');
const db = require('./db');
const authRoutes = require('./routes/auth');
const customerRoutes = require('./routes/customers');
const { errorHandler } = require('./middleware/errorHandler');

const app = new Koa();
const router = new Router();

db.init();

app.use(errorHandler);
app.use(cors());
app.use(bodyParser());

router.get('/health', (ctx) => {
  ctx.body = { status: 'ok' };
});

router.get('/swagger', async (ctx) => {
  ctx.type = 'application/yaml';
  ctx.body = require('fs').readFileSync(path.join(__dirname, '../openapi.yaml'), 'utf8');
});

app.use(router.routes());
app.use(authRoutes.routes());
app.use(customerRoutes.routes());
app.use(serve(path.join(__dirname, '../public')));

const PORT = process.env.PORT || 3000;

app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});