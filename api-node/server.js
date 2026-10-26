const express = require('express');
const cookieParser = require('cookie-parser');
const _ = require('lodash');
const pino = require('pino')();

const app = express();
app.use(express.json());
app.use(cookieParser());

let appConfig = { theme: 'light', maintainer: 'admin' };

app.get('/', (req, res) => res.send('OWASP Node.js Labs API Activa'));

// A03 (NoSQL Injection) + A09 (Mala práctica: registra req.body con password en texto claro en el log)
app.post('/api/login', async (req, res) => {
    pino.info({ action: 'login_attempt', payload_recibido: req.body });

    if (typeof req.body.password === 'object' && req.body.password !== null) {
        return res.json({ status: 'Bypass NoSQL Exitoso', flag: 'FLAG{A03_NOSQL_INJECTION}' });
    }
    res.status(401).json({ error: 'Credenciales inválidas' });
});

// A06: Componente Vulnerable (lodash 4.17.4 Prototype Pollution)
app.post('/api/settings', (req, res) => {
    _.merge(appConfig, req.body);
    res.json({ message: 'Configuración actualizada', currentConfig: appConfig });
});

app.listen(3000, () => pino.info('Server running on port 3000'));