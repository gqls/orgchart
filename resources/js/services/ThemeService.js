// Create a new service: ThemeService.js
module.exports = {
    /**
     * Apply organization-specific theming to the application
     * @param {Object} organization - The organization object containing theme properties
     */
    setTheme(organization) {
        if (!organization) return;

        const root = document.documentElement;

        // Set primary color if available
        if (organization.primary_color) {
            // Set CSS variables based on organization colors
            root.style.setProperty('--primary-color', organization.primary_color || '#4caf50');
            root.style.setProperty('--secondary-color', organization.secondary_color || '#2196f3');

            // Derive complementary colors
            root.style.setProperty('--primary-light', this.lightenColor(organization.primary_color, 40) || '#a5d6a7');
            root.style.setProperty('--primary-dark', this.darkenColor(organization.primary_color, 20) || '#2e7d32');
        }

        // Set secondary color if available
        if (organization.secondary_color) {
            root.style.setProperty('--secondary-color', organization.secondary_color);

            const darkShade = this.darkenColor(organization.secondary_color, 20);
            const lightShade = this.lightenColor(organization.secondary_color, 40);

            root.style.setProperty('--secondary-dark', darkShade);
            root.style.setProperty('--secondary-light', lightShade);
        }


        // Persist theme in localStorage for quicker loading next time
        localStorage.setItem('org_theme', JSON.stringify({
            primary: organization.primary_color,
            secondary: organization.secondary_color
        }));
    },

    /**
     * Create a lighter version of a hex color
     * @param {string} color - Hex color code
     * @param {number} percent - Amount to lighten (0-100)
     * @returns {string} - Lightened hex color
     */
    lightenColor(color, percent) {
        if (!color || !color.startsWith('#')) return '#ffffff';

        let r = parseInt(color.slice(1, 3), 16);
        let g = parseInt(color.slice(3, 5), 16);
        let b = parseInt(color.slice(5, 7), 16);

        r = Math.min(255, Math.floor(r + (255 - r) * percent / 100));
        g = Math.min(255, Math.floor(g + (255 - g) * percent / 100));
        b = Math.min(255, Math.floor(b + (255 - b) * percent / 100));

        return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
    },

    /**
     * Create a darker version of a hex color
     * @param {string} color - Hex color code
     * @param {number} percent - Amount to darken (0-100)
     * @returns {string} - Darkened hex color
     */
    darkenColor(color, percent) {
        if (!color || !color.startsWith('#')) return '#000000';

        let r = parseInt(color.slice(1, 3), 16);
        let g = parseInt(color.slice(3, 5), 16);
        let b = parseInt(color.slice(5, 7), 16);

        r = Math.max(0, Math.floor(r * (100 - percent) / 100));
        g = Math.max(0, Math.floor(g * (100 - percent) / 100));
        b = Math.max(0, Math.floor(b * (100 - percent) / 100));

        return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
    }
}