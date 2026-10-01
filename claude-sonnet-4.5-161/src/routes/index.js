const Router = require('koa-router');
const fs = require('fs');
const path = require('path');
const yaml = require('js-yaml');
const authRoutes = require('./auth');
const productRoutes = require('./products');
const orderRoutes = require('./orders');
const userRoutes = require('./users');

const router = new Router();

router.get('/swagger', (ctx) => {
  const swaggerPath = path.join(__dirname, '../../openapi.yaml');
  const swaggerDoc = yaml.load(fs.readFileSync(swaggerPath, 'utf8'));
  ctx.type = 'application/json';
  ctx.body = swaggerDoc;
});

router.get('/health', (ctx) => {
  ctx.body = { status: 'ok', timestamp: new Date().toISOString() };
});

router.use('/api/auth', authRoutes.routes(), authRoutes.allowedMethods());
router.use('/api/products', productRoutes.routes(), productRoutes.allowedMethods());
router.use('/api/orders', orderRoutes.routes(), orderRoutes.allowedMethods());
router.use('/api/users', userRoutes.routes(), userRoutes.allowedMethods());

module.exports = router;