const express = require('express');
const cookieParser = require('cookie-parser');
const pino = require('pino')();

const app = express();
app.use(express.json());
app.use(cookieParser());

app.get('/', (req, res) => res.send('OWASP Node.js Labs API Activa'));

app.post('/api/login', async (req, res) => {
    pino.info({ action: 'login_attempt', body: req.body });
    if (typeof req.body.password === 'object' && req.body.password !== null) {
        return res.json({ status: 'Bypass NoSQL Exitoso', flag: 'FLAG{A03_NOSQL_INJECTION}' });
    }
    res.status(401).json({ error: 'Credenciales inválidas' });
});

app.listen(3000, () => pino.info('Server running on port 3000'));
