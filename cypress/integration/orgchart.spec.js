// cypress/integration/orgchart.spec.js
describe('Organization Chart', () => {
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

        cy.intercept('GET', '/api/organizations/1', {
            statusCode: 200,
            body: {
                id: 1,
                name: 'Test Organization',
                slug: 'test-organization',
                description: 'Test description'
            }
        }).as('organizationRequest');

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

        cy.intercept('GET', '/api/organizations/1/scenarios/1', {
            statusCode: 200,
            body: {
                id: 1,
                name: 'Current Scenario',
                is_current: true
            }
        }).as('scenarioRequest');

        cy.intercept('GET', '/api/organizations/1/scenarios/1/positions', {
            statusCode: 200,
            body: [
                {
                    id: 1,
                    title: 'CEO',
                    name: 'John Doe',
                    department_id: 1,
                    function: 'Leadership',
                    grade: '1',
                    fully_loaded_cost: 250000,
                    x: 100,
                    y: 100,
                    pivot: { status: 'unchanged' }
                },
                {
                    id: 2,
                    title: 'CFO',
                    name: 'Jane Smith',
                    department_id: 1,
                    function: 'Finance',
                    grade: '2',
                    fully_loaded_cost: 200000,
                    x: 300,
                    y: 200,
                    pivot: { status: 'unchanged' }
                }
            ]
        }).as('positionsRequest');

        cy.intercept('GET', '/api/organizations/1/scenarios/1/relationships', {
            statusCode: 200,
            body: [
                {
                    id: 1,
                    manager_position_id: 1,
                    direct_report_position_id: 2
                }
            ]
        }).as('relationshipsRequest');

        cy.visit('/organizations/1/dashboard');
    });

    it('displays the organization chart tab', () => {
        cy.wait('@organizationRequest');
        cy.wait('@scenariosRequest');
        cy.wait('@scenarioRequest');
        cy.wait('@positionsRequest');
        cy.wait('@relationshipsRequest');

        cy.get('.tab').contains('Org Chart').click();
        cy.get('.org-chart-container').should('be.visible');
    });

    it('renders nodes for positions', () => {
        cy.wait('@organizationRequest');
        cy.wait('@scenariosRequest');
        cy.wait('@scenarioRequest');
        cy.wait('@positionsRequest');
        cy.wait('@relationshipsRequest');

        cy.get('.tab').contains('Org Chart').click();
        cy.get('.org-node').should('have.length', 2);
        cy.get('.org-node').contains('CEO');
        cy.get('.org-node').contains('CFO');
    });

    it('shows node details when clicked', () => {
        cy.wait('@organizationRequest');
        cy.wait('@scenariosRequest');
        cy.wait('@scenarioRequest');
        cy.wait('@positionsRequest');
        cy.wait('@relationshipsRequest');

        cy.get('.tab').contains('Org Chart').click();
        cy.get('.org-node').contains('CEO').click();

        cy.get('.node-detail-panel').should('be.visible');
        cy.get('.node-detail-panel').contains('CEO');
        cy.get('.node-detail-panel').contains('John Doe');
    });

    it('allows editing in edit mode', () => {
        cy.wait('@organizationRequest');
        cy.wait('@scenariosRequest');
        cy.wait('@scenarioRequest');
        cy.wait('@positionsRequest');
        cy.wait('@relationshipsRequest');

        cy.get('.tab').contains('Org Chart').click();

        // Enter edit mode
        cy.get('.org-chart-actions .btn-action').first().click();

        // Click on a node
        cy.get('.org-node').contains('CEO').click();

        // Edit form should be visible
        cy.get('.edit-form').should('be.visible');

        // Intercept the update request
        cy.intercept('PUT', '/api/organizations/1/positions/*', {
            statusCode: 200,
            body: {
                id: 1,
                title: 'Chief Executive Officer',
                name: 'John Doe Updated'
            }
        }).as('updatePositionRequest');

        // Update the title
        cy.get('.edit-form input#title').clear().type('Chief Executive Officer');
        cy.get('.edit-form input#name').clear().type('John Doe Updated');

        // Save changes
        cy.get('.edit-form .btn-primary').click();

        // Verify update request was made
        cy.wait('@updatePositionRequest');
    });
});