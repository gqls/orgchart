// tests/javascript/components/AsIsAnalysis.test.js
import { mount, shallowMount } from '@vue/test-utils';
import AsIsAnalysis from '@/components/AsIsAnalysis.vue';
import axios from 'axios';
import Chart from 'chart.js/auto';

// Mock axios and Chart.js
jest.mock('axios');
jest.mock('chart.js/auto');

describe('AsIsAnalysis.vue', () => {
    let wrapper;

    const mockOrganization = {
        id: 1,
        name: 'Test Organization'
    };

    const mockScenario = {
        id: 1,
        name: 'Current Scenario'
    };

    const mockPositions = [
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
            department_id: 1,
            function: 'Finance',
            grade: '2',
            fully_loaded_cost: 200000,
            pivot: { status: 'unchanged' }
        },
        {
            id: 3,
            title: 'HR Director',
            name: 'Bob Johnson',
            department_id: 2,
            function: 'HR',
            grade: '2',
            fully_loaded_cost: 180000,
            pivot: { status: 'new' }
        }
    ];

    const mockDepartments = [
        { id: 1, name: 'Executive', code: 'EXEC' },
        { id: 2, name: 'HR', code: 'HR' }
    ];

    const mockMetrics = [
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
    ];

    beforeEach(() => {
        // Mock axios responses
        axios.get.mockImplementation((url) => {
            if (url.includes('/positions')) {
                return Promise.resolve({ data: mockPositions });
            } else if (url.includes('/departments')) {
                return Promise.resolve({ data: mockDepartments });
            } else if (url.includes('/metrics')) {
                return Promise.resolve({ data: mockMetrics });
            }
            return Promise.reject(new Error('Not found'));
        });

        // Mock Chart constructors
        Chart.mockImplementation(() => ({
            destroy: jest.fn()
        }));

        // Create wrapper
        wrapper = shallowMount(AsIsAnalysis, {
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

    it('loads data when mounted', () => {
        expect(axios.get).toHaveBeenCalledWith('/api/organizations/1/scenarios/1/positions');
        expect(axios.get).toHaveBeenCalledWith('/api/organizations/1/departments');
        expect(axios.get).toHaveBeenCalledWith('/api/organizations/1/scenarios/1/metrics');
    });

    it('displays the correct tabs', () => {
        const tabs = wrapper.findAll('.tab');
        expect(tabs.length).toBe(4);
        expect(tabs[0].text()).toBe('Dashboard');
        expect(tabs[1].text()).toBe('Position Tracking');
        expect(tabs[2].text()).toBe('Spans & Layers');
        expect(tabs[3].text()).toBe('Cost Analysis');
    });

    it('shows key metrics in dashboard view', async () => {
        wrapper.setData({ metrics: mockMetrics, activeTab: 'dashboard' });
        await wrapper.vm.$nextTick();

        const metricCards = wrapper.findAll('.metric-card');
        expect(metricCards.length).toBe(4); // Only key metrics should be shown
    });

    it('correctly counts positions by status', async () => {
        wrapper.setData({ positions: mockPositions });
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.getStatusCount('unchanged')).toBe(2);
        expect(wrapper.vm.getStatusCount('new')).toBe(1);
        expect(wrapper.vm.getStatusCount('changed')).toBe(0);
        expect(wrapper.vm.getStatusCount('removed')).toBe(0);
    });

    it('filters positions based on selected filters', async () => {
        wrapper.setData({
            positions: mockPositions,
            filters: {
                department: 1,
                function: '',
                status: ''
            }
        });
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.filteredPositions.length).toBe(2);

        wrapper.setData({
            filters: {
                department: '',
                function: 'Finance',
                status: ''
            }
        });
