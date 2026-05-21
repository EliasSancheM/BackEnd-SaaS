import SwaggerUIBundle from 'swagger-ui-dist/swagger-ui-bundle.js';
import 'swagger-ui-dist/swagger-ui.css';

const tokenInput = document.getElementById('swagger-token');
const saveButton = document.getElementById('swagger-token-save');
const clearButton = document.getElementById('swagger-token-clear');
const storageKey = 'swagger-bearer-token';

SwaggerUIBundle({
    url: `/docs/openapi.yaml?v=${Date.now()}`,
    dom_id: '#swagger-ui',
    persistAuthorization: true,
    requestInterceptor: (request) => {
        request.headers.Accept = 'application/json';

        const token = localStorage.getItem(storageKey);

        if (token) {
            request.headers.Authorization = token;
        }

        return request;
    },
}).then(async (ui) => {
    const applyToken = (token) => {
        if (!token) {
            return;
        }

        const normalizedToken = token.startsWith('Bearer ') ? token : `Bearer ${token}`;
        ui.preauthorizeApiKey('bearerAuth', normalizedToken);
        localStorage.setItem(storageKey, normalizedToken);
        tokenInput.value = normalizedToken;
    };

    const savedToken = localStorage.getItem(storageKey);

    if (savedToken) {
        applyToken(savedToken);
    }

    saveButton?.addEventListener('click', () => {
        applyToken(tokenInput.value.trim());
    });

    clearButton?.addEventListener('click', () => {
        localStorage.removeItem(storageKey);
        tokenInput.value = '';
    });

    const response = await fetch('/docs/token');

    if (response.ok) {
        const data = await response.json();
        applyToken(data.token);
    }
});
