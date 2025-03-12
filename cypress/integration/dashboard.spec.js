// cypress/integration/dashboard.spec.js
describe('Dashboard', () => {
    beforeEach(() => {
        // Stub localStorage to simulate login
        cy.window().then((win) => {
            win.localStorage.setItem('token', 'fake-token');
            win.localStorage.setItem('user', JSON.stringify({
                id: 1,
                name: 'Test User',
                email: 'test@example.com'
            }));
        });

        // Setup intercepts
        cy.intercept('GET', '/api/user', {
            statusCode: 200,
            body: {
                id: 1,
                name: 'Test User',
                email: 'test@example.com',
                role: {
                    id: 1,
                    name: 'Orgcharts Admin',
                    slug: 'orgcharts-admin'
                }
            }
        }).as('userRequest');

        cy.intercept('GET', '/api/organizations', {
            statusCode: 200,
            body: [
                {
                    id: 1,
                    name: 'Test Organization',
                    slug: 'test-organization',
                    description: 'Test description'
                }
            ]
        }).as('organizationsRequest');

        cy.visit('/dashboard');
    });

    it('loads the dashboard', () => {
        cy.wait('@userRequest');
        cy.wait('@organizationsRequest');

        cy.contains('Welcome, Test User');
        cy.contains('Test Organization');
    });

    it('allows navigation to organization dashboard', () => {
        cy.wait('@userRequest');
        cy.wait('@organizationsRequest');

        // Intercept scenarios request
        cy.intercept('GET', '/api/organizations/1/scenarios', {
            statusCode: 200,
            body: [
                {
                    id: 1,
                    name: 'Current Scenario',
                    is_current: true
                }
            ]
        }).as('scenariosRequest');

        // Intercept organization request
        cy.intercept('GET', '/api/organizations/1', {
            statusCode: 200,
            body: {
                id: 1,
                name: 'Test Organization',
                slug: 'test-organization',
                description: 'Test description'
            }
        }).as('organizationRequest');

        cy.contains('Test Organization').click();

        cy.wait('@organizationRequest');
        cy.wait('@scenariosRequest');

        cy.url().should('include', '/organizations/1/dashboard');
        cy.contains('Test Organization');
    });
});
