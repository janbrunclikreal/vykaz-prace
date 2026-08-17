const API = {
    csrfToken: '',

    setCsrfToken(token) {
        this.csrfToken = token;
    },

    async request(url, options = {}) {
        options.headers = options.headers || {};
        options.headers['Content-Type'] = 'application/json';
        options.headers['Accept'] = 'application/json';

        if (this.csrfToken && ['POST', 'PUT', 'DELETE'].includes(options.method?.toUpperCase())) {
            options.headers['X-CSRF-Token'] = this.csrfToken;
        }

        try {
            const response = await fetch(url, options);
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(data.error || `Chyba serveru (${response.status})`);
            }

            // Pokud server vrátí nový CSRF token, uložíme ho
            if (data.csrf_token) {
                this.setCsrfToken(data.csrf_token);
            }

            return data;
        } catch (error) {
            console.error(`API Chyba: ${url}`, error);
            throw error;
        }
    },

    async get(url) {
        return this.request(url, { method: 'GET' });
    },

    async post(url, body) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(body)
        });
    },

    async put(url, body) {
        return this.request(url, {
            method: 'PUT',
            body: JSON.stringify(body)
        });
    },

    async delete(url) {
        return this.request(url, { method: 'DELETE' });
    }
};
