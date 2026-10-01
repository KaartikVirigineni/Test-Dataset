const Koa = require('koa');
const Router = require('koa-router');
const bodyParser = require('koa-bodyparser');
const cors = require('koa-cors');
const mount = require('koa-mount');
const serve = require('koa-static');
const path = require('path');
const db = require('./db');
const authRoutes = require('./routes/auth');
const taskRoutes = require('./routes/tasks');
const swaggerRoutes = require('./routes/swagger');
const { graphqlHandler } = require('./graphql/handler');

const app = new Koa();
const router = new Router();

// Initialize database
db.init();

// Middleware
app.use(cors());
app.use(bodyParser());

// Error handling
app.use(async (ctx, next) => {
  try {
    await next();
  } catch (err) {
    ctx.status = err.status || 500;
    ctx.body = {
      error: err.message || 'Internal server error'
    };
    console.error('Error:', err);
  }
});

// Health check
router.get('/health', (ctx) => {
  ctx.body = { status: 'ok' };
});

// Mount routes
app.use(authRoutes.routes()).use(authRoutes.allowedMethods());
app.use(taskRoutes.routes()).use(taskRoutes.allowedMethods());
app.use(swaggerRoutes.routes()).use(swaggerRoutes.allowedMethods());

// GraphQL endpoint
router.all('/graphql', graphqlHandler);

app.use(router.routes()).use(router.allowedMethods());

// Swagger UI static files
app.use(mount('/swagger-ui', serve(path.join(__dirname, 'swagger-ui'))));

const PORT = process.env.PORT || 3000;

app.listen(PORT, () => {
  console.log(`Server running on http://localhost:${PORT}`);
  console.log(`REST API: http://localhost:${PORT}/api`);
  console.log(`GraphQL: http://localhost:${PORT}/graphql`);
  console.log(`Swagger: http://localhost:${PORT}/swagger`);
  console.log(`Swagger UI: http://localhost:${PORT}/swagger-ui`);
});