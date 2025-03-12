// tests/javascript/components/Dashboard.test.js
import { mount, shallowMount } from '@vue/test-utils';
import Dashboard from '@/components/Dashboard.vue';
import Sidebar from '@/components/Sidebar.vue';
import AsIsAnalysis from '@/components/AsIsAnalysis.vue';
import ToBeModeling from '@/components/ToBeModeling.vue';
import OrgChart from '@/components/OrgChart.vue';
import axios from 'axios';

// Mock axios
jest.mock('axios');

describe('Dashboard.vue', () => {
    let wrapper;

    const mockOrganization = {
        id: 1,
        name: 'Test Organization',
        slug: 'test-organization'
    };

    const mockScenarios = [
        {
            id: 1,
            name: 'Current Scenario',
            is_current: true,
            is_base: false
        },
        {
            id: 2,
            name: 'Base Scenario',
            is_current: false,
            is_base: true
        }
    ];

    const mockMetrics = [
        {
            id: 1,
            name: 'Headcount',
            code: 'headcount',
            format: 'number',
            pivot: {
                value: 100,
                goal: 80
            }
        },
        {
            id: 2,
            name: 'Total Fully Loaded Cost',
            code: 'total_fully_loaded_cost',
            format: 'currency',
            pivot: {
                value: 10000000,
                goal: 9000000
            }
        }
    ];

    beforeEach(() => {
        // Mock axios responses
        axios.get.mockImplementation((url) => {
            if (url.includes('/organizations/1')) {
                return Promise.resolve({ data: mockOrganization });
            } else if (url.includes('/scenarios')) {
                return Promise.resolve({ data: mockScenarios });
            } else if (url.includes('/metrics')) {
                return Promise.resolve({ data: mockMetrics });
            }
            return Promise.reject(new Error('Not found'));
        });

        // Create wrapper
        wrapper = shallowMount(Dashboard, {
            propsData: {
                organizationId: 1
            },
            global: {
                stubs: {
                    Sidebar: true,
                    AsIsAnalysis: true,
                    ToBeModeling: true,
                    OrgChart: true,
                    ActivityAnalysis: true,
                    HrData: true
                }
            }
        });
    });

    afterEach(() => {
        jest.clearAllMocks();
        wrapper.unmount();
    });

    it('fetches organization data on creation', () => {
        expect(axios.get).toHaveBeenCalledWith('/api/organizations/1');
    });

    it('fetches scenarios on creation', () => {
        expect(axios.get).toHaveBeenCalledWith('/api/organizations/1/scenarios');
    });

    it('sets current scenario when scenarios are loaded', async () => {
        await wrapper.vm.$nextTick();
        expect(wrapper.vm.currentScenario).toBeTruthy();
        expect(wrapper.vm.currentScenario.id).toBe(1);
    });

    it('renders organization name in header', async () => {
        await wrapper.vm.$nextTick();
        expect(wrapper.find('.dashboard-header h1').text()).toBe('Test Organization');
    });

    it('toggles sidebar when toggle button is clicked', async () => {
        const initialState = wrapper.vm.sidebarOpen;
        await wrapper.find('.btn-toggle').trigger('click');
        expect(wrapper.vm.sidebarOpen).toBe(!initialState);
    });

    it('changes tabs when clicking on tab headers', async () => {
        await wrapper.vm.$nextTick();
        const tabs = wrapper.findAll('.tab');
        await tabs[1].trigger('click'); // Click on second tab
        expect(wrapper.vm.activeTab).toBe(wrapper.vm.tabs[1].id);
    });

    it('displays different components based on active tab', async () => {
        await wrapper.vm.$nextTick();

        // Set active tab to as-is-analysis
        wrapper.setData({ activeTab: 'as-is-analysis' });
        await wrapper.vm.$nextTick();
        expect(wrapper.findComponent(AsIsAnalysis).exists()).toBe(true);

        // Change to to-be-modeling
        wrapper.setData({ activeTab: 'to-be-modeling' });
        await wrapper.vm.$nextTick();
        expect(wrapper.findComponent(ToBeModeling).exists()).toBe(true);

        // Change to org-chart
        wrapper.setData({ activeTab: 'org-chart' });
        await wrapper.vm.$nextTick();
        expect(wrapper.findComponent(OrgChart).exists()).toBe(true);
    });

    it('displays metric cards with correct values', async () => {
        // Mock fetchDashboardMetrics method response
        wrapper.setData({ dashboardMetrics: mockMetrics });
        await wrapper.vm.$nextTick();

        const metricCards = wrapper.findAll('.metric-card');
        expect(metricCards.length).toBe(2);

        const headcountCard = metricCards[0];
        expect(headcountCard.find('.metric-value').text()).toContain('100');

        const costCard = metricCards[1];
        expect(costCard.find('.metric-value').text()).toContain('$10,000,000');
    });

    it('correctly formats metric values based on type', () => {
        const currencyMetric = { format: 'currency', pivot: { value: 1000000 } };
        const percentageMetric = { format: 'percentage', pivot: { value: 75.5 } };
        const numberMetric = { format: 'number', pivot: { value: 1234 } };

        expect(wrapper.vm.formatMetricValue(currencyMetric)).toContain('$1,000,000');
        expect(wrapper.vm.formatMetricValue(percentageMetric)).toBe('75.5%');
        expect(wrapper.vm.formatMetricValue(numberMetric)).toBe('1,234');
    });
});
