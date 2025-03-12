// cypress/integration/auth.spec.js
describe('Authentication', () => {
    beforeEach(() => {
        cy.visit('/login');
    });

    it('shows login form', () => {
        cy.contains('h1', 'Login');
        cy.get('input[name="email"]').should('be.visible');
        cy.get('input[name="password"]').should('be.visible');
        cy.get('button[type="submit"]').should('be.visible');
    });

    it('allows a user to login', () => {
        // Intercept the API request
        cy.intercept('POST', '/api/login', {
            statusCode: 200,
            body: {
                user: {
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
                },
                {
                    id: 2,
                    name: 'Future Scenario',
                    is_current: false
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
                    color: '#4caf50'
                },
                {
                    id: 2,
                    name: 'Finance',
                    color: '#2196f3'
                }
            ]
        }).as('departmentsRequest');

        cy.intercept('GET', '/api/organizations/1/scenarios/1/metrics', {
            statusCode: 200,
            body: [
                {
                    id: 1,
                    name: 'Headcount',
                    code: 'headcount',
                    format: 'number',
                    pivot: { value: 25, goal: 20 }
                },
                {
                    id: 2,
                    name: 'Total Fully Loaded Cost',
                    code: 'total_fully_loaded_cost',
                    format: 'currency',
                    pivot: { value: 2500000, goal: 2000000 }
                }
            ]
        }).as('metricsRequest');

        cy.visit('/organizations/1/dashboard');
    });

        it('displays the To Be Modeling tab', () => {
            cy.wait('@organizationRequest');
            cy.wait('@scenariosRequest');

            cy.get('.tab').contains('To Be Modeling').click();
            cy.get('.to-be-modeling').should('be.visible');
        });

        it('shows scenario selector with available scenarios', () => {
            cy.wait('@organizationRequest');
            cy.wait('@scenariosRequest');

            cy.get('.tab').contains('To Be Modeling').click();

            cy.get('#scenario-select option').should('have.length', 2);
            cy.get('#scenario-select option').first().should('have.text', 'Current Scenario');
            cy.get('#scenario-select option').last().should('have.text', 'Future Scenario');
        });

        it('allows creating a new scenario', () => {
            cy.wait('@organizationRequest');
            cy.wait('@scenariosRequest');

            cy.get('.tab').contains('To Be Modeling').click();

            // Intercept the create scenario request
            cy.intercept('POST', '/api/organizations/1/scenarios', {
                statusCode: 201,
                body: {
                    id: 3,
                    name: 'New Test Scenario',
                    description: 'Test description',
                    is_current: false,
                    is_base: false
                }
            }).as('createScenarioRequest');

            cy.intercept('GET', '/api/organizations/1/scenarios/3/*', {
                statusCode: 200,
                body: {
                    id: 3,
                    name: 'New Test Scenario',
                    description: 'Test description',
                    is_current: false,
                    is_base: false
                }
            });

            // Click create button
            cy.get('.btn-create').click();

            // Fill form
            cy.get('#scenario-name').type('New Test Scenario');
            cy.get('#scenario-description').type('Test description');
            cy.get('#base-scenario').select('Current Scenario');

            // Submit form
            cy.get('.modal-footer .btn-primary').click();

            // Verify request was made
            cy.wait('@createScenarioRequest').its('request.body').should('deep.equal', {
                name: 'New Test Scenario',
                description: 'Test description',
                base_scenario_id: 1
            });
        });

        it('shows department structure in department tab', () => {
            cy.wait('@organizationRequest');
            cy.wait('@scenariosRequest');
            cy.wait('@departmentsRequest');

            cy.get('.tab').contains('To Be Modeling').click();

            // Department tab should be active by default
            cy.get('.department-modeling').should('be.visible');
            cy.get('.department-card').should('have.length', 2);
            cy.get('.department-card').first().contains('Executive');
            cy.get('.department-card').last().contains('Finance');
        });

        it('allows editing departments', () => {
            cy.wait('@organizationRequest');
            cy.wait('@scenariosRequest');
            cy.wait('@departmentsRequest');

            cy.get('.tab').contains('To Be Modeling').click();

            // Intercept the update department request
            cy.intercept('PUT', '/api/organizations/1/departments/1', {
                statusCode: 200,
                body: {
                    id: 1,
                    name: 'Executive Leadership',
                    color: '#ff5722'
                }
            }).as('updateDepartmentRequest');

            // Click edit button on first department
            cy.get('.department-card').first().find('.card-actions i.fa-edit').click();

            // Edit form should be visible
            cy.get('.department-edit-form').should('be.visible');

            // Update fields
            cy.get('#name').clear().type('Executive Leadership');
            cy.get('#color').invoke('val', '#ff5722').trigger('change');

            // Save changes
            cy.get('.btn-save').click();

            // Verify request was made
            cy.wait('@updateDepartmentRequest');
        });

        it('shows cost modeling tab with metrics', () => {
            cy.wait('@organizationRequest');
            cy.wait('@scenariosRequest');
            cy.wait('@metricsRequest');

            cy.get('.tab').contains('To Be Modeling').click();

            // Switch to cost tab
            cy.get('.modeling-tabs .tab').contains('Cost Modeling').click();

            // Cost view should be visible with summary
            cy.get('.cost-modeling').should('be.visible');
            cy.get('.summary-card').contains('Total Fully Loaded Cost');
            cy.get('.summary-value').contains('$2,500,000');
        });

        it('allows applying cost changes across the board', () => {
            cy.wait('@organizationRequest');
            cy.wait('@scenariosRequest');
            cy.wait('@metricsRequest');

            cy.get('.tab').contains('To Be Modeling').click();

            // Switch to cost tab
            cy.get('.modeling-tabs .tab').contains('Cost Modeling').click();

            // Intercept position updates
            cy.intercept('PUT', '/api/organizations/1/scenarios/1/positions/*', {
                statusCode: 200,
                body: {}
            }).as('updatePositionsRequest');

            // Intercept metrics recalculation
            cy.intercept('POST', '/api/organizations/1/scenarios/1/calculate-metrics', {
                statusCode: 200,
                body: {}
            }).as('calculateMetricsRequest');

            // Click apply cost change button
            cy.get('.cost-actions .btn-action').click();

            // Cost modeling form should be visible
            cy.get('.cost-modeling-form').should('be.visible');

            // Fill form
            cy.get('#change-percentage').clear().type('-10');
            cy.get('#apply-to').select('All Positions');

            // Preview should be updated
            cy.get('.preview-diff').contains('-10%');
            cy.get('.preview-diff').contains('-$250,000');

            // Apply changes
            cy.get('.btn-apply').click();

            // Verify requests were made
            cy.wait('@updatePositionsRequest');
            cy.wait('@calculateMetricsRequest');
        });

        it('shows headcount planning tab with metrics', () => {
            cy.wait('@organizationRequest');
            cy.wait('@scenariosRequest');
            cy.wait('@metricsRequest');

            cy.get('.tab').contains('To Be Modeling').click();

            // Switch to headcount tab
            cy.get('.modeling-tabs .tab').contains('Headcount Planning').click();

            // Headcount view should be visible with summary
            cy.get('.headcount-modeling').should('be.visible');
            cy.get('.summary-card').contains('Total Headcount');
            cy.get('.summary-value').contains('25');
        });

        it('allows modeling headcount changes', () => {
            cy.wait('@organizationRequest');
            cy.wait('@scenariosRequest');
            cy.wait('@metricsRequest');

            cy.get('.tab').contains('To Be Modeling').click();

            // Switch to headcount tab
            cy.get('.modeling-tabs .tab').contains('Headcount Planning').click();

            // Intercept position creation
            cy.intercept('POST', '/api/organizations/1/scenarios/1/positions', {
                statusCode: 201,
                body: {
                    id: 3,
                    title: 'New Position',
                    status: 'new'
                }
            }).as('createPositionsRequest');

            // Intercept metrics recalculation
            cy.intercept('POST', '/api/organizations/1/scenarios/1/calculate-metrics', {
                statusCode: 200,
                body: {}
            }).as('calculateMetricsRequest');

            // Click model headcount change button
            cy.get('.headcount-actions .btn-action').click();

            // Headcount modeling form should be visible
            cy.get('.headcount-modeling-form').should('be.visible');

            // Fill form
            cy.get('#headcount-change').clear().type('5');
            cy.get('#headcount-change-type').select('Increase (Add Positions)');
            cy.get('#headcount-target').select('All Departments');
            cy.get('#avg-cost').clear().type('100000');

            // Preview should be updated
            cy.get('.preview-diff').contains('+5 positions');
            cy.get('.impact-value').contains('$500,000');

            // Apply changes
            cy.get('.btn-apply').click();

            // Verify requests were made
            cy.wait('@createPositionsRequest');
            cy.wait('@calculateMetricsRequest');
        });
    });
