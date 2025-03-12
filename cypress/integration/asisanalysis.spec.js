// cypress/integration/asisanalysis.spec.js
describe('As Is Analysis', () => {
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
                email: 'test@example.com'
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

        cy.intercept('GET', '/api/organizations/1/departments', {
            statusCode: 200,
            body: [
                {
                    id: 1,
                    name: 'Executive',
                    code: 'EXEC'
                },
                {
                    id: 2,
                    name: 'Finance',
                    code: 'FIN'
                },
                {
                    id: 3,
                    name: 'HR',
                    code: 'HR'
                }
            ]
        }).as('departmentsRequest');

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
                    pivot: { status: 'unchanged' }
                },
                {
                    id: 2,
                    title: 'CFO',
                    name: 'Jane Smith',
                    department_id: 2,
                    function: 'Finance',
                    grade: '2',
                    fully_loaded_cost: 200000,
                    pivot: { status: 'unchanged' }
                },
                {
                    id: 3,
                    title: 'HR Director',
                    name: 'Bob Johnson',
                    department_id: 3,
                    function: 'HR',
                    grade: '2',
                    fully_loaded_cost: 180000,
                    pivot: { status: 'new' }
                }
            ]
        }).as('positionsRequest');

        cy.intercept('GET', '/api/organizations/1/scenarios/1/metrics', {
            statusCode: 200,
            body: [
                {
                    id: 1,
                    name: 'Headcount',
                    code: 'headcount',
                    format: 'number',
                    pivot: { value: 3, goal: 3 }
                },
                {
                    id: 2,
                    name: 'Total Fully Loaded Cost',
                    code: 'total_fully_loaded_cost',
                    format: 'currency',
                    pivot: { value: 630000, goal: 600000 }
                },
                {
                    id: 3,
                    name: 'Average Span',
                    code: 'avg_span',
                    format: 'number',
                    pivot: { value: 2, goal: 3 }
                },
                {
                    id: 4,
                    name: 'Total Managers',
                    code: 'total_managers',
                    format: 'number',
                    pivot: { value: 1, goal: 1 }
                },
                {
                    id: 5,
                    name: 'Total Layers',
                    code: 'total_layers',
                    format: 'number',
                    pivot: { value: 2, goal: 2 }
                }
            ]
        }).as('metricsRequest');

        cy.visit('/organizations/1/dashboard');
    });

    it('displays the As Is Analysis tab', () => {
        cy.wait('@organizationRequest');
        cy.wait('@scenariosRequest');

        cy.get('.tab').contains('As Is Analysis').click();
        cy.get('.as-is-analysis').should('be.visible');
    });

    it('shows dashboard overview with key metrics', () => {
        cy.wait('@organizationRequest');
        cy.wait('@scenariosRequest');
        cy.wait('@metricsRequest');

        cy.get('.tab').contains('As Is Analysis').click();

        // Dashboard tab should be active by default
        cy.get('.dashboard-view').should('be.visible');

        // Should show key metrics
        cy.get('.key-metrics').should('be.visible');
        cy.get('.metric-card').should('have.length.at.least', 2);

        // Verify headcount metric
        cy.get('.metric-card').contains('Headcount')
            .parent()
            .find('.metric-value')
            .should('contain', '3');

        // Verify cost metric
        cy.get('.metric-card').contains('Total Fully Loaded Cost')
            .parent()
            .find('.metric-value')
            .should('contain', '$630,000');
    });

    it('shows position tracking view with data', () => {
        cy.wait('@organizationRequest');
        cy.wait('@scenariosRequest');
        cy.wait('@positionsRequest');

        cy.get('.tab').contains('As Is Analysis').click();

        // Switch to position tab
        cy.get('.metrics-tabs .tab').contains('Position Tracking').click();

        // Position view should be visible
        cy.get('.position-view').should('be.visible');

        // Should show tracking summary
        cy.get('.tracking-summary').should('be.visible');
        cy.get('.summary-item').contains('Unchanged Positions')
            .find('.summary-value')
            .should('contain', '2');
        cy.get('.summary-item').contains('New Positions')
            .find('.summary-value')
            .should('contain', '1');

        // Should show positions table
        cy.get('.positions-table table').should('be.visible');
        cy.get('.positions-table tbody tr').should('have.length', 3);

        // Verify position data in table
        cy.get('.positions-table tbody tr').contains('CEO');
        cy.get('.positions-table tbody tr').contains('CFO');
        cy.get('.positions-table tbody tr').contains('HR Director');
    });

    it('allows filtering positions by department', () => {
        cy.wait('@organizationRequest');
        cy.wait('@scenariosRequest');
        cy.wait('@positionsRequest');
        cy.wait('@departmentsRequest');

        cy.get('.tab').contains('As Is Analysis').click();

        // Switch to position tab
        cy.get('.metrics-tabs .tab').contains('Position Tracking').click();

        // Use department filter
        cy.get('#position-department-filter').select('Finance');

        // Table should only show CFO
        cy.get('.positions-table tbody tr').should('have.length', 1);
        cy.get('.positions-table tbody tr').contains('CFO');
    });

    it('shows spans & layers analysis with metrics', () => {
        cy.wait('@organizationRequest');
        cy.wait('@scenariosRequest');
        cy.wait('@metricsRequest');

        cy.get('.tab').contains('As Is Analysis').click();

        // Switch to spans tab
        cy.get('.metrics-tabs .tab').contains('Spans & Layers').click();

        // Spans view should be visible
        cy.get('.spans-view').should('be.visible');

        // Should show spans summary
        cy.get('.spans-summary').should('be.visible');
        cy.get('.summary-card').contains('Average Span of Control')
            .find('.summary-main')
            .should('contain', '2.00');

        // Should show organizational layers
        cy.get('.summary-card').contains('Organizational Layers')
            .find('.summary-main')
            .should('contain', '2');
    });

    it('shows cost analysis with metrics', () => {
        cy.wait('@organizationRequest');
        cy.wait('@scenariosRequest');
        cy.wait('@metricsRequest');

        cy.get('.tab').contains('As Is Analysis').click();

        // Switch to cost tab
        cy.get('.metrics-tabs .tab').contains('Cost Analysis').click();

        // Cost view should be visible
        cy.get('.cost-view').should('be.visible');

        // Should show cost summary
        cy.get('.cost-summary').should('be.visible');
        cy.get('.summary-card').contains('Total Fully Loaded Cost')
            .find('.summary-value')
            .should('contain', '$630,000');

        // Should show goal comparison
        cy.get('.summary-card').contains('Total Fully Loaded Cost')
            .find('.summary-comparison')
            .should('contain', '$600,000');
    });
});
