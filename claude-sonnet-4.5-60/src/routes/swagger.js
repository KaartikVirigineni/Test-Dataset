const Router = require('koa-router');
const fs = require('fs');
const path = require('path');

const router = new Router();

router.get('/swagger', async (ctx) => {
  const swaggerPath = path.join(__dirname, '../../openapi.yaml');
  const swaggerContent = fs.readFileSync(swaggerPath, 'utf8');
  
  ctx.type = 'application/yaml';
  ctx.body = swaggerContent;
});

module.exports = router;