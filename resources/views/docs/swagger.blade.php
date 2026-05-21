<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API Docs</title>
    @vite(['resources/css/app.css', 'resources/js/swagger.js'])
</head>
<body>
    <div style="padding: 12px; border-bottom: 1px solid #e5e7eb; display: flex; gap: 8px; align-items: center;">
        <input id="swagger-token" type="text" placeholder="Bearer token" style="flex: 1; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;">
        <button id="swagger-token-save" type="button" style="padding: 8px 12px; border: 1px solid #111827; border-radius: 6px; background: #111827; color: white;">Use token</button>
        <button id="swagger-token-clear" type="button" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white;">Clear</button>
    </div>
    <div id="swagger-ui"></div>
</body>
</html>
