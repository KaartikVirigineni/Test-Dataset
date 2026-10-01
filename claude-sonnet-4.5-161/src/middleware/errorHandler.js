const errorHandler = async (ctx, next) => {
  try {
    await next();
  } catch (err) {
    console.error('Error:', err);
    ctx.status = err.status || 500;
    ctx.body = {
      error: err.message || 'Internal server error'
    };
  }
};

module.exports = { errorHandler };