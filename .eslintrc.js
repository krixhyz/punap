export default [
    {
        ignores: [
            "node_modules/**",
            "vendor/**",
            "public/**",
            "storage/**",
            "bootstrap/**",
            "**/dist/**",
        ],
    },
    {
        files: ["apps/**/*.ts", "apps/**/*.tsx", "packages/**/*.ts", "packages/**/*.tsx"],
        rules: {
            "no-console": "warn",
            "no-unused-vars": "warn",
        },
    },
];
