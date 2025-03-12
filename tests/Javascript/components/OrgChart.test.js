// tests/javascript/components/OrgChart.test.js
import { mount, shallowMount } from '@vue/test-utils';
import OrgChart from '@/components/OrgChart.vue';
import axios from 'axios';

// Mock axios
jest.mock('axios');

describe('OrgChart.vue', () => {
    let wrapper;

    const mockOrganization = {
        id: 1,
        name: 'Test Organization'
    };

    const mockScenario = {
        id: 1,
        name: 'Current Scenario',
        is_current: true
    };

    const mockScenarios = [
        mockScenario,
        {
            id: 2,
            name: 'Alternative Scenario',
            is_current: false
        }
    ];

    const mockDepartments = [
        {
            id: 1,
            name: 'Finance',
            color: '#4caf50'
        },
        {
            id: 2,
            name: 'HR',
            color: '#2196f3'
        }
    ];

    const mockPositions = [
        {
            id: 1,
            title: 'CEO',
            name: 'John Doe',
            employee_id: '211001',
            department_id: 1,
            function: 'Leadership',
            grade: '1',
            fully_loaded_cost: 250000,
            x: 100,
            y: 100,
            pivot: {
                status: 'unchanged'
            }
        },
        {
            id: 2,
            title: 'CFO',
            name: 'Jane Smith',
            employee_id: '211002',
            department_id: 1,
            function: 'Finance',
            grade: '2',
            fully_loaded_cost: 200000,
            x: 300,
            y: 200,
            pivot: {
                status: 'unchanged'
            }
        }
    ];

    const mockRelationships = [
        {
            id: 1,
            manager_position_id: 1,
            direct_report_position_id: 2
        }
    ];

    beforeEach(() => {
        // Mock axios responses
        axios.get.mockImplementation((url) => {
            if (url.includes('/scenarios')) {
                return Promise.resolve({ data: mockScenarios });
            } else if (url.includes('/positions')) {
                return Promise.resolve({ data: mockPositions });
            } else if (url.includes('/relationships')) {
                return Promise.resolve({ data: mockRelationships });
            } else if (url.includes('/departments')) {
                return Promise.resolve({ data: mockDepartments });
            }
            return Promise.reject(new Error('Not found'));
        });

        // Mock refs for canvas context
        HTMLCanvasElement.prototype.getContext = jest.fn(() => ({
            beginPath: jest.fn(),
            stroke: jest.fn(),
            moveTo: jest.fn(),
            lineTo: jest.fn(),
            bezierCurveTo: jest.fn()
        }));

        // Create wrapper
        wrapper = shallowMount(OrgChart, {
            propsData: {
                organization: mockOrganization,
                scenario: mockScenario
            }
        });
    });

    afterEach(() => {
        jest.clearAllMocks();
        wrapper.unmount();
    });

    it('loads scenario data when mounted', () => {
        expect(axios.get).toHaveBeenCalledWith('/api/organizations/1/scenarios');
        expect(axios.get).toHaveBeenCalledWith('/api/organizations/1/scenarios/1/positions');
        expect(axios.get).toHaveBeenCalledWith('/api/organizations/1/scenarios/1/relationships');
    });

    it('renders nodes for each position', async () => {
        wrapper.setData({ nodes: mockPositions });
        await wrapper.vm.$nextTick();

        const nodes = wrapper.findAll('.org-node');
        expect(nodes.length).toBe(2);
    });

    it('sets correct style for nodes based on position data', async () => {
        wrapper.setData({ nodes: mockPositions });
        await wrapper.vm.$nextTick();

        const firstNode = wrapper.findAll('.org-node')[0];
        expect(firstNode.attributes('style')).toContain('left: 100px');
        expect(firstNode.attributes('style')).toContain('top: 100px');
    });

    it('toggles edit mode when edit button is clicked', async () => {
        const initialState = wrapper.vm.editMode;
        await wrapper.find('.btn-action').trigger('click');
        expect(wrapper.vm.editMode).toBe(!initialState);
    });

    it('shows node detail panel when a node is selected', async () => {
        wrapper.setData({ nodes: mockPositions });
        await wrapper.vm.$nextTick();

        const firstNode = wrapper.findAll('.org-node')[0];
        await firstNode.trigger('click');

        expect(wrapper.vm.selectedNode).toBeTruthy();
        expect(wrapper.vm.selectedNode.id).toBe(1);
        expect(wrapper.find('.node-detail-panel').exists()).toBe(true);
    });

    it('can filter nodes by function', async () => {
        wrapper.setData({
            nodes: mockPositions,
            functions: ['Leadership', 'Finance'],
            functionFilter: 'Leadership'
        });
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.filteredNodes.length).toBe(1);
        expect(wrapper.vm.filteredNodes[0].title).toBe('CEO');
    });

    it('calculates position for new nodes based on hierarchy', () => {
        wrapper.setData({
            nodes: mockPositions,
            relationships: mockRelationships
        });

        const newNode = {
            id: 3,
            title: 'Financial Analyst'
        };

        // Create a relationship where node 2 (CFO) is manager of node 3
        const newRelationship = {
            manager_position_id: 2,
            direct_report_position_id: 3
        };

        wrapper.vm.relationships.push(newRelationship);
        wrapper.vm.calculateNodePosition(newNode);

        // Node should be positioned based on its level in the hierarchy (2) and position among siblings
        expect(newNode.x).toBeTruthy();
        expect(newNode.y).toBeTruthy();
    });

    it('updates node position when dragging in edit mode', async () => {
        wrapper.setData({
            nodes: mockPositions,
            editMode: true
        });
        await wrapper.vm.$nextTick();

        // Simulate mousedown on node
        const firstNode = wrapper.findAll('.org-node')[0];
        await firstNode.trigger('mousedown', {
            clientX: 100,
            clientY: 100
        });

        expect(wrapper.vm.draggingNode).toBeTruthy();
        expect(wrapper.vm.draggingNode.id).toBe(1);

        // Simulate mousemove
        await wrapper.find('.org-chart').trigger('mousemove', {
            clientX: 200,
            clientY: 200
        });

        // Check if position was updated
        const updatedNode = wrapper.vm.nodes.find(n => n.id === 1);
        expect(updatedNode.x).toBeGreaterThan(100);
        expect(updatedNode.y).toBeGreaterThan(100);
    });

    it('adds a new node when create button is clicked in edit mode', async () => {
        wrapper.setData({ editMode: true });
        await wrapper.vm.$nextTick();

        const createButton = wrapper.findAll('.btn-action')[1]; // Second button should be create
        await createButton.trigger('click');

        expect(wrapper.vm.nodes.length).toBe(1); // One new node added
        expect(wrapper.vm.nodes[0].title).toBe('New Position');
        expect(wrapper.vm.nodes[0].isNew).toBe(true);
    });
});
