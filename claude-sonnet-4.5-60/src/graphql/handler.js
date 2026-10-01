const { createHandler } = require('graphql-http/lib/use/http');
const schema = require('./schema');
const resolvers = require('./resolvers');

const handler = createHandler({
  schema,
  rootValue: resolvers,
  context: (req) => ({
    headers: req.headers
  })
});

async function graphqlHandler(ctx) {
  const [body, init] = await handler({
    url: ctx.url,
    method: ctx.method,
    headers: ctx.headers,
    body: () => 
      new Promise((resolve) => {
        let data = '';
        ctx.req.on('data', (chunk) => {
          data += chunk;
        });
        ctx.req.on('end', () => {
          resolve(data);
        });
      }),
    raw: ctx.req
  });

  ctx.status = init.status;
  
  for (const [key, value] of Object.entries(init.headers)) {
    ctx.set(key, value);
  }

  if (body) {
    const reader = body.getReader();
    const chunks = [];
    
    while (true) {
      const { done, value } = await reader.read();
      if (done) break;
      chunks.push(value);
    }
    
    const allChunks = new Uint8Array(chunks.reduce((acc, chunk) => acc + chunk.length, 0));
    let position = 0;
    for (const chunk of chunks) {
      allChunks.set(chunk, position);
      position += chunk.length;
    }
    
    ctx.body = Buffer.from(allChunks);
  }
}

module.exports = { graphqlHandler };