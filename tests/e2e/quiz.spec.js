/**
 * Playwright Tests for Quiz REST Endpoints
 *
 * @package interactive-lesson
 * @since 1.0.0
 */

const { test, expect } = require('@playwright/test');

// Base URL for the WordPress REST API
const BASE_URL = 'http://localhost/wp-json/quiz/v1';

// Helper function to log in and get nonce
async function getNonceAndCookies(request) {
    const loginResponse = await request.post('http://localhost/wp-json/jwt-auth/v1/token', {
        data: {
            username: 'admin',
            password: 'admin',
        },
    });
    const loginData = await loginResponse.json();
    const nonce = await request.get('http://localhost/wp-json/').then(res => res.headers()['x-wp-nonce']);
    return { token: loginData.token, nonce, cookies: await loginResponse.headers()['set-cookie'] };
}

test.describe('Quiz Endpoint Tests', () => {
    let authData;

    test.beforeAll(async ({ request }) => {
        // Create a test user via WordPress API or setup script
        await request.post('http://localhost/wp-json/wp/v2/users', {
            data: {
                username: 'testuser',
                email: 'testuser@example.com',
                password: 'testpassword',
                roles: ['subscriber'],
            },
            headers: {
                Authorization: `Basic ${Buffer.from('admin:admin').toString('base64')}`, // Admin credentials
                'X-WP-Nonce': await request.get('http://localhost/wp-json/').then(res => res.headers()['x-wp-nonce']),
            },
        });

        authData = await getNonceAndCookies(request);
    });

    test.afterAll(async ({ request }) => {
        // Clean up test user
        await request.delete('http://localhost/wp-json/wp/v2/users/testuser', {
            headers: {
                Authorization: `Basic ${Buffer.from('admin:admin').toString('base64')}`,
                'X-WP-Nonce': authData.nonce,
            },
        });
    });

    test('should reject submission for unauthenticated user', async ({ request }) => {
        const response = await request.post(`${BASE_URL}/submit`, {
            data: {
                question: 'What is 2+2?',
                answer: '4',
                correct_answer: '4',
            },
        });

        expect(response.status()).toBe(401);
        const data = await response.json();
        expect(data.code).toBe('rest_forbidden');
        expect(data.message).toBe('You must be logged in to access this resource.');
    });

    test('should reject submission with invalid nonce', async ({ request }) => {
        const response = await request.post(`${BASE_URL}/submit`, {
            data: {
                question: 'What is 2+2?',
                answer: '4',
                correct_answer: '4',
            },
            headers: {
                Authorization: `Bearer ${authData.token}`,
                'X-WP-Nonce': 'invalid_nonce',
            },
        });

        expect(response.status()).toBe(403);
        const data = await response.json();
        expect(data.code).toBe('invalid_nonce');
        expect(data.message).toBe('Invalid nonce.');
    });

    test('should accept valid quiz submission with correct answer', async ({ request }) => {
        const response = await request.post(`${BASE_URL}/submit`, {
            data: {
                question: 'What is 2+2?',
                answer: '4',
                correct_answer: '4',
            },
            headers: {
                Authorization: `Bearer ${authData.token}`,
                'X-WP-Nonce': authData.nonce,
            },
        });

        expect(response.status()).toBe(200);
        const data = await response.json();
        expect(data.success).toBe(true);
        expect(data.answer).toBe('4');
        expect(data.message).toBe('Correct!');
    });

    test('should accept valid quiz submission with incorrect answer', async ({ request }) => {
        const response = await request.post(`${BASE_URL}/submit`, {
            data: {
                question: 'What is 2+2?',
                answer: '5',
                correct_answer: '4',
            },
            headers: {
                Authorization: `Bearer ${authData.token}`,
                'X-WP-Nonce': authData.nonce,
            },
        });

        expect(response.status()).toBe(200);
        const data = await response.json();
        expect(data.success).toBe(true);
        expect(data.answer).toBe('5');
        expect(data.message).toBe('Incorrect. The correct answer is 4.');
    });

    test('should retrieve quiz results', async ({ request }) => {
        // Submit a quiz answer first
        await request.post(`${BASE_URL}/submit`, {
            data: {
                question: 'What is 2+2?',
                answer: '4',
                correct_answer: '4',
            },
            headers: {
                Authorization: `Bearer ${authData.token}`,
                'X-WP-Nonce': authData.nonce,
            },
        });

        const response = await request.get(`${BASE_URL}/results`, {
            headers: {
                Authorization: `Bearer ${authData.token}`,
                'X-WP-Nonce': authData.nonce,
            },
        });

        expect(response.status()).toBe(200);
        const data = await response.json();
        expect(data.success).toBe(true);
        expect(data.results).toHaveLength(1);
        expect(data.results[0].answer).toBe('4');
        expect(data.results[0].is_correct).toBe(true);
        expect(data.total_score).toBe(1);
        expect(data.timestamp).toBeTruthy();
    });

    test('should return empty results for no submissions', async ({ request }) => {
        const response = await request.get(`${BASE_URL}/results`, {
            headers: {
                Authorization: `Bearer ${authData.token}`,
                'X-WP-Nonce': authData.nonce,
            },
        });

        expect(response.status()).toBe(200);
        const data = await response.json();
        expect(data.success).toBe(true);
        expect(data.results).toHaveLength(0);
        expect(data.total_score).toBe(0);
        expect(data.timestamp).toBe('');
    });
});