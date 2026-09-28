import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';
import apiRouter from './routes/api.js';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = process.env.PORT || 5008;

// Middlewares
app.use(cors());
app.use(express.json());

// API Endpoints: /api/... and alias /api/v2/...
app.use('/api', apiRouter);
app.use('/api/v2', apiRouter);

// Serve static React SPA bundle
const distPath = path.resolve(__dirname, '../dist');
app.use(express.static(distPath));

// Catch-all SPA route
app.use((req, res) => {
  res.sendFile(path.join(distPath, 'index.html'));
});

// Start listening
app.listen(PORT, () => {
  console.log(`=========================================`);
  console.log(`🚀 ASPARTECH V2 React & Node API Server`);
  console.log(`📡 URL: http://127.0.0.1:${PORT}`);
  console.log(`📁 Environment: ${process.env.NODE_ENV || 'production'}`);
  console.log(`=========================================`);
});

export default app;
