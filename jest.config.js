// jest.config.js
module.exports = {
    testEnvironment: 'jsdom',
    roots: ['<rootDir>/tests/javascript'],
    moduleFileExtensions: ['js', 'vue'],
    moduleNameMapper: {
        '^@/(.*)$': '<rootDir>/resources/js/$1',
        '\\.(css|less|scss|sass)$': '<rootDir>/tests/javascript/styleMock.js'
    },
    transform: {
        '^.+\\.js$': 'babel-jest',
        '^.+\\.vue$': '@vue/vue3-jest'
    },
    testMatch: [
        '**/tests/javascript/**/*.test.js'
    ],
    setupFilesAfterEnv: [
        '<rootDir>/tests/javascript/setup.js'
    ],
    collectCoverage: true,
    collectCoverageFrom: [
        'resources/js/components/**/*.{js,vue}',
        '!**/node_modules/**'
    ],
    coverageReporters: ['text', 'html'],
    transformIgnorePatterns: [
        '/node_modules/(?!(vue|chart.js|lodash-es))'
    ]
};

// babel.config.js
module.exports = {
    presets: [
        '@babel/preset-env'
    ],
    plugins: [
        '@babel/plugin-transform-runtime'
    ]
};

// tests/javascript/styleMock.js
module.exports = {};