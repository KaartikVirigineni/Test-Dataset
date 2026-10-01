const Koa = require('koa');
const Router = require('koa-router');
const bodyParser = require('koa-bodyparser');
const json = require('koa-json');
const logger = require('koa-logger');
const cors = require('@koa/cors');
const serve = require('koa-static');
const fs = require('fs');
const path = require('path');
const yaml = require('js-yaml');

const db = require('./db');
const authRoutes = require('./routes/auth');
const consentRoutes = require('./routes/consent');
const cookieRoutes = require('./routes/cookie');
const userRoutes = require('./routes/user');

const app = new Koa();
const router = new Router();

// Middleware
app.use(logger());
app.use(cors());
app.use(bodyParser());
app.use(json());

// Serve Swagger UI static files
app.use(serve(path.join(__dirname, '../public')));

// Health check
router.get('/health', (ctx) => {
  ctx.body = { status: 'ok', timestamp: new Date().toISOString() };
});

// Swagger spec endpoint
router.get('/swagger', (ctx) => {
  const swaggerPath = path.join(__dirname, '../openapi.yaml');
  const swaggerContent = fs.readFileSync(swaggerPath, 'utf8');
  ctx.type = 'application/yaml';
  ctx.body = swaggerContent;
});

// Swagger UI redirect
router.get('/docs', (ctx) => {
  ctx.redirect('/swagger-ui.html');
});

// Mount routes
router.use('/api/auth', authRoutes.routes(), authRoutes.allowedMethods());
router.use('/api/consents', consentRoutes.routes(), consentRoutes.allowedMethods());
router.use('/api/cookies', cookieRoutes.routes(), cookieRoutes.allowedMethods());
router.use('/api/users', userRoutes.routes(), userRoutes.allowedMethods());

app.use(router.routes());
app.use(router.allowedMethods());

// Error handling
app.on('error', (err, ctx) => {
  console.error('Server error:', err);
});

const PORT = process.env.PORT || 3000;

app.listen(PORT, () => {
  console.log(`CMP API Server running on http://localhost:${PORT}`);
  console.log(`Swagger documentation: http://localhost:${PORT}/docs`);
  console.log(`API Spec: http://localhost:${PORT}/swagger`);
});