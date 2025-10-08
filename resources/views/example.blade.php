<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PromptPay QR Generator - Example</title>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
        }

        .button-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 30px;
        }

        button {
            padding: 14px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f7fafc;
            color: #333;
            border: 2px solid #e1e8ed;
        }

        .btn-secondary:hover {
            background: #edf2f7;
        }

        .btn-download {
            background: #48bb78;
            color: white;
            width: 100%;
        }

        .btn-download:hover {
            background: #38a169;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(72, 187, 120, 0.4);
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .qr-result {
            text-align: center;
            display: none;
        }

        .qr-result.show {
            display: block;
        }

        .qr-result img {
            max-width: 100%;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .qr-info {
            background: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .qr-info p {
            color: #4a5568;
            margin: 5px 0;
            font-size: 14px;
        }

        .qr-info strong {
            color: #2d3748;
        }

        .error {
            background: #fed7d7;
            color: #c53030;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .error.show {
            display: block;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading.show {
            display: block;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🇹🇭 PromptPay QR Generator</h1>
        <p class="subtitle">Generate Thai PromptPay QR codes instantly</p>

        <div class="error" id="error"></div>

        <form id="promptpay-form">
            <div class="form-group">
                <label for="identifier">Phone Number or National ID</label>
                <input
                    type="text"
                    id="identifier"
                    name="identifier"
                    placeholder="0812345678 or 1234567890123"
                    required
                >
            </div>

            <div class="form-group">
                <label for="amount">Amount (THB) - Optional</label>
                <input
                    type="number"
                    id="amount"
                    name="amount"
                    placeholder="100.00"
                    step="0.01"
                    min="0"
                >
            </div>

            <div class="button-group">
                <button type="submit" class="btn-primary">Generate QR</button>
                <button type="button" class="btn-secondary" onclick="resetForm()">Reset</button>
            </div>
        </form>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p style="margin-top: 15px; color: #666;">Generating QR code...</p>
        </div>

        <div class="qr-result" id="qr-result">
            <div class="qr-info">
                <p><strong>Identifier:</strong> <span id="result-identifier"></span></p>
                <p><strong>Amount:</strong> <span id="result-amount"></span></p>
            </div>
            <img id="qr-image" src="" alt="PromptPay QR Code">
            <button class="btn-download" onclick="downloadQR()">Download QR Code</button>
        </div>
    </div>

    <script>
        // Setup Axios with CSRF token
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        const token = document.head.querySelector('meta[name="csrf-token"]');
        if (token) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
        }

        // Form submission
        document.getElementById('promptpay-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const identifier = document.getElementById('identifier').value;
            const amount = document.getElementById('amount').value;

            await generatePromptPay(identifier, amount);
        });

        // Generate PromptPay QR
        async function generatePromptPay(identifier, amount) {
            hideError();
            hideResult();
            showLoading();

            try {
                const response = await axios.post('/promptpay/generate', {
                    identifier: identifier,
                    amount: amount || null,
                    size: 300
                });

                if (response.data.success) {
                    displayResult(response.data);
                }
            } catch (error) {
                if (error.response && error.response.data) {
                    showError(error.response.data.message || 'Failed to generate QR code');
                } else {
                    showError('Network error. Please try again.');
                }
            } finally {
                hideLoading();
            }
        }

        // Download QR code
        async function downloadQR() {
            const identifier = document.getElementById('identifier').value;
            const amount = document.getElementById('amount').value;

            try {
                const response = await axios.post('/promptpay/download', {
                    identifier: identifier,
                    amount: amount || null,
                    size: 500
                }, {
                    responseType: 'blob'
                });

                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `promptpay-${Date.now()}.png`);
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
            } catch (error) {
                showError('Failed to download QR code');
            }
        }

        // Display result
        function displayResult(data) {
            document.getElementById('result-identifier').textContent = data.identifier;
            document.getElementById('result-amount').textContent = data.amount ? `฿${parseFloat(data.amount).toFixed(2)}` : 'Open Amount';
            document.getElementById('qr-image').src = data.qr_code;
            document.getElementById('qr-result').classList.add('show');
        }

        // Show/hide functions
        function showError(message) {
            const errorDiv = document.getElementById('error');
            errorDiv.textContent = message;
            errorDiv.classList.add('show');
        }

        function hideError() {
            document.getElementById('error').classList.remove('show');
        }

        function showLoading() {
            document.getElementById('loading').classList.add('show');
        }

        function hideLoading() {
            document.getElementById('loading').classList.remove('show');
        }

        function hideResult() {
            document.getElementById('qr-result').classList.remove('show');
        }

        function resetForm() {
            document.getElementById('promptpay-form').reset();
            hideResult();
            hideError();
        }
    </script>
</body>
</html>
