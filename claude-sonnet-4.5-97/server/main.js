import { Meteor } from 'meteor/meteor';
import express from 'express';
import bodyParser from 'body-parser';
import cors from 'cors';
import { initDatabase } from './db';
import { authRouter } from './routes/auth';
import { alertsRouter } from './routes/alerts';
import { monitoringRouter } from './routes/monitoring';
import { swaggerRouter } from './routes/swagger';

const app = express();

app.use(cors());
app.use(bodyParser.json());

initDatabase();

app.use('/api/auth', authRouter);
app.use('/api/alerts', alertsRouter);
app.use('/api/monitoring', monitoringRouter);
app.use('/', swaggerRouter);

app.get('/health', (req, res) => {
  res.json({ status: 'ok' });
});

Meteor.startup(() => {
  const PORT = process.env.PORT || 3000;
  
  if (!process.env.METEOR_PARENT_PID) {
    app.listen(PORT, () => {
      console.log(`AlertHub REST API running on port ${PORT}`);
    });
  }
  
  WebApp.connectHandlers.use(app);
});